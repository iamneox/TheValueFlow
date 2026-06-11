package geo

import (
	"net"
	"strings"
	"sync"
)

type Lookup interface {
	Lookup(ip string) (country, city string)
}

type Stub struct{}

func (Stub) Lookup(ip string) (string, string) {
	return "", ""
}

type MaxMind struct {
	path string
	mu   sync.RWMutex
	// Placeholder for future MaxMind integration; falls back to empty when unavailable.
}

func NewMaxMind(path string) *MaxMind {
	return &MaxMind{path: path}
}

func (m *MaxMind) Lookup(ip string) (country, city string) {
	if m.path == "" {
		return "", ""
	}
	// MaxMind GeoIP2 reader can be wired here when the database file is deployed.
	return "", ""
}

func New(path string) Lookup {
	if strings.TrimSpace(path) == "" {
		return Stub{}
	}
	return NewMaxMind(path)
}

func ClientIP(remoteAddr string, forwardedFor string) string {
	if forwardedFor != "" {
		parts := strings.Split(forwardedFor, ",")
		if ip := strings.TrimSpace(parts[0]); ip != "" {
			return ip
		}
	}
	host, _, err := net.SplitHostPort(remoteAddr)
	if err != nil {
		return remoteAddr
	}
	return host
}
