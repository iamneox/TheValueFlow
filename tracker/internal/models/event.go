package models

import (
	"crypto/sha1"
	"encoding/hex"
	"fmt"
	"time"
)

const (
	StreamClicks       = "tvf:stream:clicks"
	StreamImpressions  = "tvf:stream:impressions"
	StreamConversions  = "tvf:stream:conversions"
	ConsumerGroup      = "tvf-worker"
	ConsumerName       = "worker-1"
)

type OfferConfig struct {
	ID         int64   `json:"id"`
	Slug       string  `json:"slug"`
	Status     string  `json:"status"`
	LandingURL string  `json:"landing_url"`
	Payout     float64 `json:"payout"`
	Revenue    float64 `json:"revenue"`
}

type PartnerConfig struct {
	ID     int64  `json:"id"`
	Status string `json:"status"`
}

type DomainConfig struct {
	Domain    string `json:"domain"`
	PartnerID int64  `json:"partner_id"`
	Status    string `json:"status"`
}

type ClickEvent struct {
	ClickID        string    `json:"click_id"`
	OfferID        int64     `json:"offer_id"`
	PartnerID      int64     `json:"partner_id"`
	SourceID       string    `json:"source_id"`
	TrackingDomain string    `json:"tracking_domain"`
	ClickedAt      time.Time `json:"clicked_at"`
	IP             string    `json:"ip"`
	Country        string    `json:"country"`
	City           string    `json:"city"`
	Device         string    `json:"device"`
	OS             string    `json:"os"`
	Browser        string    `json:"browser"`
	UserAgent      string    `json:"user_agent"`
	Referer        string    `json:"referer"`
	AffSub1        string    `json:"aff_sub1"`
	AffSub2        string    `json:"aff_sub2"`
	AffSub3        string    `json:"aff_sub3"`
	AffSub4        string    `json:"aff_sub4"`
	AffSub5        string    `json:"aff_sub5"`
	IsUnique       bool      `json:"is_unique"`
	IsDuplicate    bool      `json:"is_duplicate"`
	IsInvalid      bool      `json:"is_invalid"`
	InvalidReason  string    `json:"invalid_reason"`
}

type ImpressionEvent struct {
	OfferID     int64     `json:"offer_id"`
	PartnerID   int64     `json:"partner_id"`
	SourceID    string    `json:"source_id"`
	Type        string    `json:"type"`
	ClickID     string    `json:"click_id"`
	ImpressedAt time.Time `json:"impressed_at"`
	IP          string    `json:"ip"`
	Country     string    `json:"country"`
	Device      string    `json:"device"`
	OS          string    `json:"os"`
	Browser     string    `json:"browser"`
	City        string    `json:"city"`
}

type ConversionEvent struct {
	ClickID       string    `json:"click_id"`
	OfferID       int64     `json:"offer_id"`
	PartnerID     int64     `json:"partner_id"`
	TransactionID string    `json:"transaction_id"`
	Status        string    `json:"status"`
	Payout        float64   `json:"payout"`
	Revenue       float64   `json:"revenue"`
	Method        string    `json:"method"`
	ConvertedAt   time.Time `json:"converted_at"`
	Country       string    `json:"country"`
	Device        string    `json:"device"`
	OS            string    `json:"os"`
	Browser       string    `json:"browser"`
	City          string    `json:"city"`
	SourceID      string    `json:"source_id"`
}

type StatsKey struct {
	Hour      time.Time
	OfferID   int64
	PartnerID int64
	SourceID  string
	Country   string
	Device    string
	OS        string
	Browser   string
	City      string
}

func (k StatsKey) DimensionsHash() string {
	sum := sha1.Sum([]byte(fmt.Sprintf("%s|%s|%s|%s|%s", k.Country, k.Device, k.OS, k.Browser, k.City)))
	return hex.EncodeToString(sum[:])
}

type StatsDelta struct {
	Impressions      int64
	GrossClicks      int64
	UniqueClicks     int64
	DuplicateClicks  int64
	InvalidClicks    int64
	Conversions      int64
	Revenue          float64
	Payout           float64
}
