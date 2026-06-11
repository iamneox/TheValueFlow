package configstore

import (
	"context"
	"encoding/json"
	"log/slog"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/thevalueflow/tracker/internal/models"
)

type Store struct {
	rdb      *redis.Client
	interval time.Duration

	mu       sync.RWMutex
	offers   map[string]models.OfferConfig
	partners map[int64]models.PartnerConfig
	domains  map[string]models.DomainConfig
}

func New(rdb *redis.Client, interval time.Duration) *Store {
	return &Store{
		rdb:      rdb,
		interval: interval,
		offers:   make(map[string]models.OfferConfig),
		partners: make(map[int64]models.PartnerConfig),
		domains:  make(map[string]models.DomainConfig),
	}
}

func (s *Store) Start(ctx context.Context) {
	if err := s.sync(ctx); err != nil {
		slog.Warn("initial config sync failed", "error", err)
	}

	ticker := time.NewTicker(s.interval)
	go func() {
		defer ticker.Stop()
		for {
			select {
			case <-ctx.Done():
				return
			case <-ticker.C:
				if err := s.sync(ctx); err != nil {
					slog.Warn("config sync failed", "error", err)
				}
			}
		}
	}()
}

func (s *Store) sync(ctx context.Context) error {
	offers, err := s.scanOffers(ctx)
	if err != nil {
		return err
	}
	partners, err := s.scanPartners(ctx)
	if err != nil {
		return err
	}
	domains, err := s.scanDomains(ctx)
	if err != nil {
		return err
	}

	s.mu.Lock()
	s.offers = offers
	s.partners = partners
	s.domains = domains
	s.mu.Unlock()

	slog.Debug("config synced",
		"offers", len(offers),
		"partners", len(partners),
		"domains", len(domains),
	)
	return nil
}

func (s *Store) scanOffers(ctx context.Context) (map[string]models.OfferConfig, error) {
	bySlug, err := scanJSON[models.OfferConfig](ctx, s.rdb, "offer:*", func(o models.OfferConfig) string {
		if o.Slug != "" {
			return strings.ToLower(o.Slug)
		}
		if o.ID != 0 {
			return strconv.FormatInt(o.ID, 10)
		}
		return ""
	})
	if err != nil {
		return nil, err
	}
	// Also index by numeric ID for ?o=123 links
	for _, o := range bySlug {
		if o.ID != 0 {
			bySlug[strconv.FormatInt(o.ID, 10)] = o
		}
	}
	return bySlug, nil
}

func (s *Store) scanPartners(ctx context.Context) (map[int64]models.PartnerConfig, error) {
	out := make(map[int64]models.PartnerConfig)
	iter := s.rdb.Scan(ctx, 0, "partner:*", 100).Iterator()
	for iter.Next(ctx) {
		raw, err := s.rdb.Get(ctx, iter.Val()).Bytes()
		if err != nil {
			continue
		}
		var p models.PartnerConfig
		if err := json.Unmarshal(raw, &p); err != nil || p.ID == 0 {
			continue
		}
		out[p.ID] = p
	}
	return out, iter.Err()
}

func (s *Store) scanDomains(ctx context.Context) (map[string]models.DomainConfig, error) {
	return scanJSON[models.DomainConfig](ctx, s.rdb, "domain:*", func(d models.DomainConfig) string {
		key := strings.ToLower(d.Domain)
		if key == "" {
			return ""
		}
		return key
	})
}

func scanJSON[T any](ctx context.Context, rdb *redis.Client, pattern string, keyFn func(T) string) (map[string]T, error) {
	out := make(map[string]T)
	iter := rdb.Scan(ctx, 0, pattern, 100).Iterator()
	for iter.Next(ctx) {
		raw, err := rdb.Get(ctx, iter.Val()).Bytes()
		if err != nil {
			continue
		}
		var v T
		if err := json.Unmarshal(raw, &v); err != nil {
			continue
		}
		key := keyFn(v)
		if key == "" {
			continue
		}
		out[key] = v
	}
	return out, iter.Err()
}

func (s *Store) GetOffer(slug string) (models.OfferConfig, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	o, ok := s.offers[strings.ToLower(slug)]
	return o, ok
}

func (s *Store) GetPartner(id int64) (models.PartnerConfig, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	p, ok := s.partners[id]
	return p, ok
}

func (s *Store) IsDomainAllowed(host string) bool {
	host = strings.ToLower(strings.TrimSpace(host))
	if host == "" {
		return false
	}
	s.mu.RLock()
	defer s.mu.RUnlock()
	d, ok := s.domains[host]
	if !ok {
		return false
	}
	return strings.EqualFold(d.Status, "active")
}

func (s *Store) OfferActive(slug string) bool {
	o, ok := s.GetOffer(slug)
	return ok && strings.EqualFold(o.Status, "active")
}

func (s *Store) PartnerActive(id int64) bool {
	p, ok := s.GetPartner(id)
	return ok && strings.EqualFold(p.Status, "active")
}

func (s *Store) GetOfferByID(id int64) (models.OfferConfig, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	for _, o := range s.offers {
		if o.ID == id {
			return o, true
		}
	}
	return models.OfferConfig{}, false
}
