package buffer

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/thevalueflow/tracker/internal/models"
)

type Buffer struct {
	rdb *redis.Client
}

func New(rdb *redis.Client) *Buffer {
	return &Buffer{rdb: rdb}
}

func (b *Buffer) EnsureStreams(ctx context.Context) error {
	streams := []string{models.StreamClicks, models.StreamImpressions, models.StreamConversions}
	for _, stream := range streams {
		if err := b.rdb.XGroupCreateMkStream(ctx, stream, models.ConsumerGroup, "0").Err(); err != nil {
			if err.Error() != "BUSYGROUP Consumer Group name already exists" {
				return fmt.Errorf("create group for %s: %w", stream, err)
			}
		}
	}
	return nil
}

func (b *Buffer) PushClick(ctx context.Context, ev models.ClickEvent) error {
	return b.push(ctx, models.StreamClicks, ev)
}

func (b *Buffer) PushImpression(ctx context.Context, ev models.ImpressionEvent) error {
	return b.push(ctx, models.StreamImpressions, ev)
}

func (b *Buffer) PushConversion(ctx context.Context, ev models.ConversionEvent) error {
	return b.push(ctx, models.StreamConversions, ev)
}

func (b *Buffer) push(ctx context.Context, stream string, payload any) error {
	data, err := json.Marshal(payload)
	if err != nil {
		return err
	}
	return b.rdb.XAdd(ctx, &redis.XAddArgs{
		Stream: stream,
		Values: map[string]interface{}{"payload": string(data)},
	}).Err()
}

type ClickMeta struct {
	ClickID   string `json:"click_id"`
	OfferID   int64  `json:"offer_id"`
	PartnerID int64  `json:"partner_id"`
	SourceID  string `json:"source_id"`
	Country   string `json:"country"`
	Device    string `json:"device"`
	OS        string `json:"os"`
	Browser   string `json:"browser"`
	City      string `json:"city"`
}

func (b *Buffer) CacheClick(ctx context.Context, meta ClickMeta, ttl time.Duration) error {
	key := clickKey(meta.ClickID)
	data, err := json.Marshal(meta)
	if err != nil {
		return err
	}
	return b.rdb.Set(ctx, key, data, ttl).Err()
}

func (b *Buffer) GetClick(ctx context.Context, clickID string) (ClickMeta, bool) {
	raw, err := b.rdb.Get(ctx, clickKey(clickID)).Bytes()
	if err != nil {
		return ClickMeta{}, false
	}
	var meta ClickMeta
	if err := json.Unmarshal(raw, &meta); err != nil {
		return ClickMeta{}, false
	}
	return meta, true
}

func (b *Buffer) MarkDuplicate(ctx context.Context, offerID, partnerID int64, ip string, ttl time.Duration) bool {
	key := fmt.Sprintf("tvf:uniq:%d:%d:%s", offerID, partnerID, ip)
	ok, err := b.rdb.SetNX(ctx, key, "1", ttl).Result()
	if err != nil {
		return true
	}
	return ok
}

func clickKey(clickID string) string {
	return "tvf:click:" + clickID
}

func (b *Buffer) ReadGroup(ctx context.Context, stream string, count int64, block time.Duration) ([]redis.XMessage, error) {
	res, err := b.rdb.XReadGroup(ctx, &redis.XReadGroupArgs{
		Group:    models.ConsumerGroup,
		Consumer: models.ConsumerName,
		Streams:  []string{stream, ">"},
		Count:    count,
		Block:    block,
	}).Result()
	if err == redis.Nil {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	if len(res) == 0 {
		return nil, nil
	}
	return res[0].Messages, nil
}

func (b *Buffer) Ack(ctx context.Context, stream string, ids ...string) error {
	if len(ids) == 0 {
		return nil
	}
	return b.rdb.XAck(ctx, stream, models.ConsumerGroup, ids...).Err()
}

// PendingCounts returns unacknowledged events per stream for the tracker consumer group.
func (b *Buffer) PendingCounts(ctx context.Context) (map[string]int64, error) {
	streams := map[string]string{
		"clicks":       models.StreamClicks,
		"impressions":  models.StreamImpressions,
		"conversions":  models.StreamConversions,
	}

	out := make(map[string]int64, len(streams))
	for name, stream := range streams {
		pending, err := b.rdb.XPending(ctx, stream, models.ConsumerGroup).Result()
		if err != nil {
			return nil, fmt.Errorf("xpending %s: %w", stream, err)
		}
		out[name] = pending.Count
	}

	return out, nil
}
