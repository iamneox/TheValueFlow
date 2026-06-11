package handlers

import (
	"context"
	"log/slog"
	"math/rand"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"github.com/oklog/ulid/v2"
	"github.com/thevalueflow/tracker/internal/buffer"
	"github.com/thevalueflow/tracker/internal/configstore"
	"github.com/thevalueflow/tracker/internal/geo"
	"github.com/thevalueflow/tracker/internal/models"
	"github.com/thevalueflow/tracker/internal/ua"
)

var pixelGIF = []byte{
	0x47, 0x49, 0x46, 0x38, 0x39, 0x61, 0x01, 0x00, 0x01, 0x00, 0x80, 0x00, 0x00,
	0xff, 0xff, 0xff, 0x00, 0x00, 0x00, 0x21, 0xf9, 0x04, 0x01, 0x00, 0x00, 0x00,
	0x00, 0x2c, 0x00, 0x00, 0x00, 0x00, 0x01, 0x00, 0x01, 0x00, 0x00, 0x02, 0x02,
	0x44, 0x01, 0x00, 0x3b,
}

type Tracking struct {
	cfg     HandlerConfig
	store   *configstore.Store
	buf     *buffer.Buffer
	geo     geo.Lookup
	entropy *ulid.MonotonicEntropy
}

type HandlerConfig struct {
	ClickCacheTTL time.Duration
}

func NewTracking(cfg HandlerConfig, store *configstore.Store, buf *buffer.Buffer, geoLookup geo.Lookup) *Tracking {
	return &Tracking{
		cfg:     cfg,
		store:   store,
		buf:     buf,
		geo:     geoLookup,
		entropy: ulid.Monotonic(rand.New(rand.NewSource(time.Now().UnixNano())), 0),
	}
}

func (h *Tracking) Router() chi.Router {
	r := chi.NewRouter()
	r.Use(middleware.RealIP)
	r.Use(middleware.Recoverer)

	r.Get("/c", h.handleClick)
	r.Get("/i", h.handleEmailImpression)
	r.Get("/li", h.handleLandingImpression)
	r.Get("/p", h.handlePostback)

	return r
}

func (h *Tracking) handleClick(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	offerParam := q.Get("o")
	partnerID, _ := strconv.ParseInt(q.Get("p"), 10, 64)
	sourceID := q.Get("s")

	offer, offerOK := h.resolveOffer(offerParam)
	if !offerOK || !strings.EqualFold(offer.Status, "active") {
		http.Error(w, "offer not found", http.StatusNotFound)
		return
	}
	if partnerID == 0 || !h.store.PartnerActive(partnerID) {
		http.Error(w, "partner not found", http.StatusNotFound)
		return
	}

	clickID := ulid.MustNew(ulid.Timestamp(time.Now()), h.entropy).String()
	ip := geo.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"))
	country, city := h.geo.Lookup(ip)
	parsedUA := ua.Parse(r.UserAgent())
	trackingDomain := r.Host

	isUnique := h.buf.MarkDuplicate(r.Context(), offer.ID, partnerID, ip, 24*time.Hour)
	isDuplicate := !isUnique

	ev := models.ClickEvent{
		ClickID:        clickID,
		OfferID:        offer.ID,
		PartnerID:      partnerID,
		SourceID:       sourceID,
		TrackingDomain: trackingDomain,
		ClickedAt:      time.Now().UTC(),
		IP:             ip,
		Country:        country,
		City:           city,
		Device:         parsedUA.Device,
		OS:             parsedUA.OS,
		Browser:        parsedUA.Browser,
		UserAgent:      r.UserAgent(),
		Referer:        r.Referer(),
		AffSub1:        q.Get("sub1"),
		AffSub2:        q.Get("sub2"),
		AffSub3:        q.Get("sub3"),
		AffSub4:        q.Get("sub4"),
		AffSub5:        q.Get("sub5"),
		IsUnique:       isUnique,
		IsDuplicate:    isDuplicate,
	}

	if offer.LandingURL == "" {
		ev.IsInvalid = true
		ev.InvalidReason = "missing_landing_url"
		h.enqueueClick(r.Context(), ev)
		http.Error(w, "landing not configured", http.StatusBadRequest)
		return
	}

	h.enqueueClick(r.Context(), ev)
	_ = h.buf.CacheClick(r.Context(), buffer.ClickMeta{
		ClickID:   clickID,
		OfferID:   offer.ID,
		PartnerID: partnerID,
		SourceID:  sourceID,
		Country:   country,
		Device:    parsedUA.Device,
		OS:        parsedUA.OS,
		Browser:   parsedUA.Browser,
		City:      city,
	}, h.cfg.ClickCacheTTL)

	redirectURL, err := appendClickID(offer.LandingURL, clickID)
	if err != nil {
		http.Error(w, "invalid landing url", http.StatusInternalServerError)
		return
	}
	http.Redirect(w, r, redirectURL, http.StatusFound)
}

func (h *Tracking) handleEmailImpression(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	offerParam := q.Get("o")
	partnerID, _ := strconv.ParseInt(q.Get("p"), 10, 64)
	sourceID := q.Get("s")

	offer, offerOK := h.resolveOffer(offerParam)
	if !offerOK || partnerID == 0 {
		h.servePixel(w)
		return
	}

	ip := geo.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"))
	country, city := h.geo.Lookup(ip)
	parsedUA := ua.Parse(r.UserAgent())

	ev := models.ImpressionEvent{
		OfferID:     offer.ID,
		PartnerID:   partnerID,
		SourceID:    sourceID,
		Type:        "email_open",
		ImpressedAt: time.Now().UTC(),
		IP:          ip,
		Country:     country,
		Device:      parsedUA.Device,
		OS:          parsedUA.OS,
		Browser:     parsedUA.Browser,
		City:        city,
	}

	if err := h.buf.PushImpression(r.Context(), ev); err != nil {
		slog.Error("push email impression", "error", err)
	}
	h.servePixel(w)
}

func (h *Tracking) handleLandingImpression(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	offerParam := q.Get("o")
	partnerID, _ := strconv.ParseInt(q.Get("p"), 10, 64)
	clickID := q.Get("click_id")

	offer, offerOK := h.resolveOffer(offerParam)
	if !offerOK || partnerID == 0 {
		h.servePixel(w)
		return
	}

	ip := geo.ClientIP(r.RemoteAddr, r.Header.Get("X-Forwarded-For"))
	country, city := h.geo.Lookup(ip)
	parsedUA := ua.Parse(r.UserAgent())

	ev := models.ImpressionEvent{
		OfferID:     offer.ID,
		PartnerID:   partnerID,
		Type:        "landing",
		ClickID:     clickID,
		ImpressedAt: time.Now().UTC(),
		IP:          ip,
		Country:     country,
		Device:      parsedUA.Device,
		OS:          parsedUA.OS,
		Browser:     parsedUA.Browser,
		City:        city,
	}

	if err := h.buf.PushImpression(r.Context(), ev); err != nil {
		slog.Error("push landing impression", "error", err)
	}
	h.servePixel(w)
}

func (h *Tracking) handlePostback(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	clickID := strings.TrimSpace(q.Get("click_id"))
	if clickID == "" {
		http.Error(w, "click_id required", http.StatusBadRequest)
		return
	}

	payout, _ := strconv.ParseFloat(q.Get("payout"), 64)
	status := normalizeStatus(q.Get("status"))
	txid := q.Get("txid")

	meta, ok := h.buf.GetClick(r.Context(), clickID)
	if !ok {
		http.Error(w, "click not found", http.StatusNotFound)
		return
	}

	offer, offerOK := h.store.GetOfferByID(meta.OfferID)
	revenue := float64(0)
	if offerOK {
		defaultPayout, defaultRevenue := offer.ConversionRates()
		revenue = defaultRevenue
		if payout == 0 {
			payout = defaultPayout
		}
	}

	ev := models.ConversionEvent{
		ClickID:       clickID,
		OfferID:       meta.OfferID,
		PartnerID:     meta.PartnerID,
		TransactionID: txid,
		Status:        status,
		Payout:        payout,
		Revenue:       revenue,
		Method:        "postback",
		ConvertedAt:   time.Now().UTC(),
		Country:       meta.Country,
		Device:        meta.Device,
		OS:            meta.OS,
		Browser:       meta.Browser,
		City:          meta.City,
		SourceID:      meta.SourceID,
	}

	if err := h.buf.PushConversion(r.Context(), ev); err != nil {
		slog.Error("push conversion", "error", err)
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "text/plain")
	w.WriteHeader(http.StatusOK)
	_, _ = w.Write([]byte("OK"))
}

func (h *Tracking) enqueueClick(ctx context.Context, ev models.ClickEvent) {
	if err := h.buf.PushClick(ctx, ev); err != nil {
		slog.Error("push click", "error", err, "click_id", ev.ClickID)
	}
}

func (h *Tracking) servePixel(w http.ResponseWriter) {
	w.Header().Set("Content-Type", "image/gif")
	w.Header().Set("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
	w.WriteHeader(http.StatusOK)
	_, _ = w.Write(pixelGIF)
}

func (h *Tracking) resolveOffer(param string) (models.OfferConfig, bool) {
	if param == "" {
		return models.OfferConfig{}, false
	}
	if o, ok := h.store.GetOffer(param); ok {
		return o, true
	}
	if id, err := strconv.ParseInt(param, 10, 64); err == nil {
		return h.store.GetOfferByID(id)
	}
	return models.OfferConfig{}, false
}

func appendClickID(landingURL, clickID string) (string, error) {
	u, err := url.Parse(landingURL)
	if err != nil {
		return "", err
	}
	q := u.Query()
	q.Set("click_id", clickID)
	u.RawQuery = q.Encode()
	return u.String(), nil
}

func normalizeStatus(status string) string {
	switch strings.ToLower(strings.TrimSpace(status)) {
	case "pending":
		return "pending"
	case "rejected", "declined":
		return "rejected"
	default:
		return "approved"
	}
}
