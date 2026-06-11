package config

import (
	"fmt"
	"os"
	"strconv"
	"time"
)

type Config struct {
	RedisAddr           string
	MySQLDSN            string
	ListenAddr          string
	InternalListenAddr  string
	MaxMindDBPath       string
	WorkerBatchSize     int
	WorkerFlushInterval time.Duration
	ConfigSyncInterval  time.Duration
	ClickCacheTTL       time.Duration
}

func Load() (Config, error) {
	cfg := Config{
		RedisAddr:           envOr("REDIS_ADDR", "127.0.0.1:6379"),
		ListenAddr:          envOr("LISTEN_ADDR", ":8080"),
		InternalListenAddr:  envOr("INTERNAL_LISTEN_ADDR", ":8081"),
		MaxMindDBPath:       os.Getenv("MAXMIND_DB_PATH"),
		WorkerBatchSize:     envIntOr("WORKER_BATCH_SIZE", 500),
		WorkerFlushInterval: envDurationOr("WORKER_FLUSH_INTERVAL", 2*time.Second),
		ConfigSyncInterval:  envDurationOr("CONFIG_SYNC_INTERVAL", 30*time.Second),
		ClickCacheTTL:       envDurationOr("CLICK_CACHE_TTL", 90*24*time.Hour),
	}

	dsn := os.Getenv("MYSQL_DSN")
	if dsn == "" {
		host := envOr("MYSQL_HOST", "10.5.0.3")
		port := envOr("MYSQL_PORT", "3306")
		user := envOr("MYSQL_USER", "tvf")
		pass := os.Getenv("MYSQL_PASSWORD")
		db := envOr("MYSQL_DATABASE", "tvf")
		if pass == "" {
			return cfg, fmt.Errorf("MYSQL_PASSWORD or MYSQL_DSN is required")
		}
		dsn = fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true&charset=utf8mb4&loc=UTC", user, pass, host, port, db)
	}
	cfg.MySQLDSN = dsn

	return cfg, nil
}

func envOr(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func envIntOr(key string, fallback int) int {
	v := os.Getenv(key)
	if v == "" {
		return fallback
	}
	n, err := strconv.Atoi(v)
	if err != nil {
		return fallback
	}
	return n
}

func envDurationOr(key string, fallback time.Duration) time.Duration {
	v := os.Getenv(key)
	if v == "" {
		return fallback
	}
	d, err := time.ParseDuration(v)
	if err != nil {
		return fallback
	}
	return d
}
