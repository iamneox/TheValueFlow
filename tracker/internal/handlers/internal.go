package handlers

import (
	"encoding/json"
	"net/http"
	"strings"

	"github.com/go-chi/chi/v5"
	"github.com/thevalueflow/tracker/internal/configstore"
)

type Internal struct {
	store *configstore.Store
}

func NewInternal(store *configstore.Store) *Internal {
	return &Internal{store: store}
}

func (h *Internal) Router() chi.Router {
	r := chi.NewRouter()
	r.Get("/internal/allow-host", h.allowHost)
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
