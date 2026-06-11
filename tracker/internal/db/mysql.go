package db

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/thevalueflow/tracker/internal/models"
)

type MySQL struct {
	db *sql.DB
}

func NewMySQL(dsn string) (*MySQL, error) {
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, err
	}
	db.SetMaxOpenConns(20)
	db.SetMaxIdleConns(10)
	db.SetConnMaxLifetime(5 * time.Minute)

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		_ = db.Close()
		return nil, fmt.Errorf("mysql ping: %w", err)
	}
	return &MySQL{db: db}, nil
}

func (m *MySQL) Close() error {
	return m.db.Close()
}

func (m *MySQL) InsertClicks(ctx context.Context, clicks []models.ClickEvent) error {
	if len(clicks) == 0 {
		return nil
	}

	const cols = 23
	query := `INSERT INTO raw_clicks (
		click_id, offer_id, partner_id, source_id, tracking_domain, clicked_at,
		ip, country, city, device, os, browser, user_agent, referer,
		aff_sub1, aff_sub2, aff_sub3, aff_sub4, aff_sub5,
		is_unique, is_duplicate, is_invalid, invalid_reason
	) VALUES `
	placeholders := make([]string, 0, len(clicks))
	args := make([]any, 0, len(clicks)*cols)

	for _, c := range clicks {
		placeholders = append(placeholders, "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
		args = append(args,
			c.ClickID, nullInt64(c.OfferID), nullInt64(c.PartnerID), nullString(c.SourceID),
			nullString(c.TrackingDomain), c.ClickedAt,
			nullString(c.IP), nullString(c.Country), nullString(c.City),
			nullString(c.Device), nullString(c.OS), nullString(c.Browser),
			nullString(c.UserAgent), nullString(c.Referer),
			nullString(c.AffSub1), nullString(c.AffSub2), nullString(c.AffSub3),
			nullString(c.AffSub4), nullString(c.AffSub5),
			c.IsUnique, c.IsDuplicate, c.IsInvalid, nullString(c.InvalidReason),
		)
	}

	query += strings.Join(placeholders, ",")
	_, err := m.db.ExecContext(ctx, query, args...)
	return err
}

func (m *MySQL) InsertImpressions(ctx context.Context, impressions []models.ImpressionEvent) error {
	if len(impressions) == 0 {
		return nil
	}

	const cols = 9
	query := `INSERT INTO raw_impressions (
		offer_id, partner_id, source_id, type, click_id, impressed_at, ip, country
	) VALUES `
	placeholders := make([]string, 0, len(impressions))
	args := make([]any, 0, len(impressions)*cols)

	for _, i := range impressions {
		placeholders = append(placeholders, "(?,?,?,?,?,?,?,?)")
		args = append(args,
			nullInt64(i.OfferID), nullInt64(i.PartnerID), nullString(i.SourceID),
			i.Type, nullString(i.ClickID), i.ImpressedAt,
			nullString(i.IP), nullString(i.Country),
		)
	}

	query += strings.Join(placeholders, ",")
	_, err := m.db.ExecContext(ctx, query, args...)
	return err
}

func (m *MySQL) InsertConversions(ctx context.Context, conversions []models.ConversionEvent) error {
	if len(conversions) == 0 {
		return nil
	}

	const cols = 9
	query := `INSERT INTO conversions (
		click_id, offer_id, partner_id, transaction_id, status, payout, revenue, method, converted_at
	) VALUES `
	placeholders := make([]string, 0, len(conversions))
	args := make([]any, 0, len(conversions)*cols)

	for _, c := range conversions {
		placeholders = append(placeholders, "(?,?,?,?,?,?,?,?,?)")
		args = append(args,
			nullString(c.ClickID), nullInt64(c.OfferID), nullInt64(c.PartnerID),
			nullString(c.TransactionID), c.Status, c.Payout, c.Revenue, c.Method, c.ConvertedAt,
		)
	}

	query += strings.Join(placeholders, ",")
	_, err := m.db.ExecContext(ctx, query, args...)
	return err
}

func (m *MySQL) UpsertStatsHourly(ctx context.Context, deltas map[models.StatsKey]models.StatsDelta) error {
	if len(deltas) == 0 {
		return nil
	}

	query := `INSERT INTO stats_hourly (
		hour, offer_id, partner_id, source_id, country, device, os, browser, city, dimensions_hash,
		impressions, gross_clicks, unique_clicks, duplicate_clicks, invalid_clicks,
		conversions, revenue, payout
	) VALUES `
	placeholders := make([]string, 0, len(deltas))
	args := make([]any, 0, len(deltas)*18)

	for key, delta := range deltas {
		placeholders = append(placeholders, "(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
		args = append(args,
			key.Hour, nullInt64(key.OfferID), nullInt64(key.PartnerID), nullString(key.SourceID),
			nullString(key.Country), nullString(key.Device), nullString(key.OS),
			nullString(key.Browser), nullString(key.City), key.DimensionsHash(),
			delta.Impressions, delta.GrossClicks, delta.UniqueClicks, delta.DuplicateClicks,
			delta.InvalidClicks, delta.Conversions, delta.Revenue, delta.Payout,
		)
	}

	query += strings.Join(placeholders, ",")
	query += ` ON DUPLICATE KEY UPDATE
		impressions = impressions + VALUES(impressions),
		gross_clicks = gross_clicks + VALUES(gross_clicks),
		unique_clicks = unique_clicks + VALUES(unique_clicks),
		duplicate_clicks = duplicate_clicks + VALUES(duplicate_clicks),
		invalid_clicks = invalid_clicks + VALUES(invalid_clicks),
		conversions = conversions + VALUES(conversions),
		revenue = revenue + VALUES(revenue),
		payout = payout + VALUES(payout)`

	_, err := m.db.ExecContext(ctx, query, args...)
	return err
}

func nullString(v string) any {
	if v == "" {
		return nil
	}
	return v
}

func nullInt64(v int64) any {
	if v == 0 {
		return nil
	}
	return v
}

func TruncateHour(t time.Time) time.Time {
	return t.UTC().Truncate(time.Hour)
}
