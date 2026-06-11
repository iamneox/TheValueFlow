package worker

import (
	"context"
	"encoding/json"
	"log/slog"
	"time"

	"github.com/thevalueflow/tracker/internal/buffer"
	"github.com/thevalueflow/tracker/internal/db"
	"github.com/thevalueflow/tracker/internal/models"
)

type Worker struct {
	buf      *buffer.Buffer
	mysql    *db.MySQL
	batch    int
	interval time.Duration
}

func New(buf *buffer.Buffer, mysql *db.MySQL, batch int, interval time.Duration) *Worker {
	return &Worker{
		buf:      buf,
		mysql:    mysql,
		batch:    batch,
		interval: interval,
	}
}

func (w *Worker) Run(ctx context.Context) {
	ticker := time.NewTicker(w.interval)
	defer ticker.Stop()

	pending := newBatch()

	for {
		select {
		case <-ctx.Done():
			w.flush(ctx, pending)
			return
		case <-ticker.C:
			w.collect(ctx, pending)
			w.flush(ctx, pending)
		}
	}
}

func (w *Worker) collect(ctx context.Context, pending *batch) {
	w.readClicks(ctx, pending)
	w.readImpressions(ctx, pending)
	w.readConversions(ctx, pending)
}

func (w *Worker) FlushNow(ctx context.Context) (int, error) {
	pending := newBatch()
	w.drainAll(ctx, pending)

	count := len(pending.clicks) + len(pending.impressions) + len(pending.conversions)
	w.flush(ctx, pending)

	return count, nil
}

func (w *Worker) drainAll(ctx context.Context, pending *batch) {
	w.readClicks(ctx, pending, 0)
	w.readImpressions(ctx, pending, 0)
	w.readConversions(ctx, pending, 0)
}

func (w *Worker) readClicks(ctx context.Context, pending *batch) {
	w.readClicks(ctx, pending, 100*time.Millisecond)
}

func (w *Worker) readClicks(ctx context.Context, pending *batch, block time.Duration) {
	w.readStream(ctx, models.StreamClicks, block, func(raw []byte) error {
		var ev models.ClickEvent
		if err := json.Unmarshal(raw, &ev); err != nil {
			return err
		}
		pending.addClick(ev)
		return nil
	})
}

func (w *Worker) readImpressions(ctx context.Context, pending *batch) {
	w.readImpressions(ctx, pending, 100*time.Millisecond)
}

func (w *Worker) readImpressions(ctx context.Context, pending *batch, block time.Duration) {
	w.readStream(ctx, models.StreamImpressions, block, func(raw []byte) error {
		var ev models.ImpressionEvent
		if err := json.Unmarshal(raw, &ev); err != nil {
			return err
		}
		pending.addImpression(ev)
		return nil
	})
}

func (w *Worker) readConversions(ctx context.Context, pending *batch) {
	w.readConversions(ctx, pending, 100*time.Millisecond)
}

func (w *Worker) readConversions(ctx context.Context, pending *batch, block time.Duration) {
	w.readStream(ctx, models.StreamConversions, block, func(raw []byte) error {
		var ev models.ConversionEvent
		if err := json.Unmarshal(raw, &ev); err != nil {
			return err
		}
		pending.addConversion(ev)
		return nil
	})
}

func (w *Worker) readStream(ctx context.Context, stream string, block time.Duration, handle func([]byte) error) {
	for {
		msgs, err := w.buf.ReadGroup(ctx, stream, int64(w.batch), block)
		if err != nil {
			slog.Error("read stream", "stream", stream, "error", err)
			return
		}
		if len(msgs) == 0 {
			return
		}

		ids := make([]string, 0, len(msgs))
		for _, msg := range msgs {
			raw, ok := msg.Values["payload"].(string)
			if !ok {
				ids = append(ids, msg.ID)
				continue
			}
			if err := handle([]byte(raw)); err != nil {
				slog.Warn("decode event", "stream", stream, "error", err)
			}
			ids = append(ids, msg.ID)
		}

		if err := w.buf.Ack(ctx, stream, ids...); err != nil {
			slog.Error("ack stream", "stream", stream, "error", err)
		}

		if len(msgs) < w.batch {
			return
		}
	}
}

func (w *Worker) flush(ctx context.Context, pending *batch) {
	if pending.empty() {
		return
	}

	flushCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	if err := w.mysql.InsertClicks(flushCtx, pending.clicks); err != nil {
		slog.Error("insert clicks", "error", err, "count", len(pending.clicks))
	} else {
		pending.clicks = nil
	}

	if err := w.mysql.InsertImpressions(flushCtx, pending.impressions); err != nil {
		slog.Error("insert impressions", "error", err, "count", len(pending.impressions))
	} else {
		pending.impressions = nil
	}

	if err := w.mysql.InsertConversions(flushCtx, pending.conversions); err != nil {
		slog.Error("insert conversions", "error", err, "count", len(pending.conversions))
	} else {
		pending.conversions = nil
	}

	if err := w.mysql.UpsertStatsHourly(flushCtx, pending.stats); err != nil {
		slog.Error("upsert stats", "error", err, "keys", len(pending.stats))
	} else {
		pending.stats = make(map[models.StatsKey]models.StatsDelta)
	}
}

type batch struct {
	clicks      []models.ClickEvent
	impressions []models.ImpressionEvent
	conversions []models.ConversionEvent
	stats       map[models.StatsKey]models.StatsDelta
}

func newBatch() *batch {
	return &batch{stats: make(map[models.StatsKey]models.StatsDelta)}
}

func (b *batch) empty() bool {
	return len(b.clicks) == 0 && len(b.impressions) == 0 && len(b.conversions) == 0
}

func (b *batch) addClick(c models.ClickEvent) {
	b.clicks = append(b.clicks, c)
	key := models.StatsKey{
		Hour:      db.TruncateHour(c.ClickedAt),
		OfferID:   c.OfferID,
		PartnerID: c.PartnerID,
		SourceID:  c.SourceID,
		Country:   c.Country,
		Device:    c.Device,
		OS:        c.OS,
		Browser:   c.Browser,
		City:      c.City,
	}
	delta := b.stats[key]
	delta.GrossClicks++
	if c.IsUnique {
		delta.UniqueClicks++
	}
	if c.IsDuplicate {
		delta.DuplicateClicks++
	}
	if c.IsInvalid {
		delta.InvalidClicks++
	}
	b.stats[key] = delta
}

func (b *batch) addImpression(i models.ImpressionEvent) {
	b.impressions = append(b.impressions, i)
	key := models.StatsKey{
		Hour:      db.TruncateHour(i.ImpressedAt),
		OfferID:   i.OfferID,
		PartnerID: i.PartnerID,
		SourceID:  i.SourceID,
		Country:   i.Country,
		Device:    i.Device,
		OS:        i.OS,
		Browser:   i.Browser,
		City:      i.City,
	}
	delta := b.stats[key]
	delta.Impressions++
	b.stats[key] = delta
}

func (b *batch) addConversion(c models.ConversionEvent) {
	b.conversions = append(b.conversions, c)
	if c.Status != "approved" {
		return
	}
	key := models.StatsKey{
		Hour:      db.TruncateHour(c.ConvertedAt),
		OfferID:   c.OfferID,
		PartnerID: c.PartnerID,
		SourceID:  c.SourceID,
		Country:   c.Country,
		Device:    c.Device,
		OS:        c.OS,
		Browser:   c.Browser,
		City:      c.City,
	}
	delta := b.stats[key]
	delta.Conversions++
	delta.Revenue += c.Revenue
	delta.Payout += c.Payout
	b.stats[key] = delta
}
