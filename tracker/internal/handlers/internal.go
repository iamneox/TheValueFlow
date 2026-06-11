package handlers

import (
	"encoding/json"
	"net/http"
	"strings"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/thevalueflow/tracker/internal/buffer"
	"github.com/thevalueflow/tracker/internal/configstore"
	"github.com/thevalueflow/tracker/internal/worker"
)

type Internal struct {
	store         *configstore.Store
	buf           *buffer.Buffer
	wrk           *worker.Worker
	flushInterval time.Duration
}

func NewInternal(store *configstore.Store, buf *buffer.Buffer, wrk *worker.Worker, flushInterval time.Duration) *Internal {
	return &Internal{
		store:         store,
		buf:           buf,
		wrk:           wrk,
		flushInterval: flushInterval,
	}
}

func (h *Internal) Router() chi.Router {
	r := chi.NewRouter()
	r.Get("/internal/allow-host", h.allowHost)
	r.Get("/internal/buffer-status", h.bufferStatus)
	r.Post("/internal/flush-stats", h.flushStats)
	return r
}

func (h *Internal) allowHost(w http.ResponseWriter, r *http.Request) {
	domain := strings.ToLower(strings.TrimSpace(r.URL.Query().Get("domain")))
	if domain == "" {
		http.Error(w, "domain required", http.StatusBadRequest)
		return
	}

	allowed := h.store.IsDomainAllowed(domain)
	w.Header().Set("Content-Type", "application/json")
	if allowed {
		w.WriteHeader(http.StatusOK)
		_ = json.NewEncoder(w).Encode(map[string]bool{"allow": true})
		return
	}
	w.WriteHeader(http.StatusForbidden)
	_ = json.NewEncoder(w).Encode(map[string]bool{"allow": false})
}

func (h *Internal) bufferStatus(w http.ResponseWriter, r *http.Request) {
	pending, err := h.buf.PendingCounts(r.Context())
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(map[string]interface{}{
		"pending": map[string]int64{
			"clicks":       pending["clicks"],
			"impressions":  pending["impressions"],
			"conversions":  pending["conversions"],
		},
		"flush_interval_seconds": h.flushInterval.Seconds(),
	})
}

func (h *Internal) flushStats(w http.ResponseWriter, r *http.Request) {
	count, err := h.wrk.FlushNow(r.Context())
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(map[string]int{
		"processed": count,
	})
}
