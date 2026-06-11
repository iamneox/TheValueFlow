package main

import (
	"context"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/thevalueflow/tracker/internal/buffer"
	"github.com/thevalueflow/tracker/internal/config"
	"github.com/thevalueflow/tracker/internal/configstore"
	"github.com/thevalueflow/tracker/internal/db"
	"github.com/thevalueflow/tracker/internal/geo"
	"github.com/thevalueflow/tracker/internal/handlers"
	"github.com/thevalueflow/tracker/internal/worker"
)

func main() {
	slog.SetDefault(slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{Level: slog.LevelInfo})))

	cfg, err := config.Load()
	if err != nil {
		slog.Error("load config", "error", err)
		os.Exit(1)
	}

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	rdb := redis.NewClient(&redis.Options{Addr: cfg.RedisAddr})
	if err := rdb.Ping(ctx).Err(); err != nil {
		slog.Error("redis ping", "error", err)
		os.Exit(1)
	}
	defer rdb.Close()

	mysql, err := db.NewMySQL(cfg.MySQLDSN)
	if err != nil {
		slog.Error("mysql connect", "error", err)
		os.Exit(1)
	}
	defer mysql.Close()

	buf := buffer.New(rdb)
	if err := buf.EnsureStreams(ctx); err != nil {
		slog.Error("ensure streams", "error", err)
		os.Exit(1)
	}

	store := configstore.New(rdb, cfg.ConfigSyncInterval)
	store.Start(ctx)

	geoLookup := geo.New(cfg.MaxMindDBPath)

	tracking := handlers.NewTracking(handlers.HandlerConfig{
		ClickCacheTTL: cfg.ClickCacheTTL,
	}, store, buf, geoLookup)

	wrk := worker.New(buf, mysql, cfg.WorkerBatchSize, cfg.WorkerFlushInterval)
	internal := handlers.NewInternal(store, buf, wrk, cfg.WorkerFlushInterval)

	mainSrv := &http.Server{
		Addr:         cfg.ListenAddr,
		Handler:      tracking.Router(),
		ReadTimeout:  5 * time.Second,
		WriteTimeout: 10 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	internalSrv := &http.Server{
		Addr:         cfg.InternalListenAddr,
		Handler:      internal.Router(),
		ReadTimeout:  5 * time.Second,
		WriteTimeout: 5 * time.Second,
		IdleTimeout:  30 * time.Second,
	}

	go func() {
		slog.Info("tracking server listening", "addr", cfg.ListenAddr)
		if err := mainSrv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			slog.Error("tracking server", "error", err)
			stop()
		}
	}()

	go func() {
		slog.Info("internal server listening", "addr", cfg.InternalListenAddr)
		if err := internalSrv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			slog.Error("internal server", "error", err)
			stop()
		}
	}()

	go wrk.Run(ctx)

	<-ctx.Done()
	slog.Info("shutting down")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	_ = mainSrv.Shutdown(shutdownCtx)
	_ = internalSrv.Shutdown(shutdownCtx)
}
