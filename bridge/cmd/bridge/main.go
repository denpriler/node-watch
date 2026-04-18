package main

import (
	"context"
	"log/slog"
	"os"
	"os/signal"
	"sync"
	"syscall"

	"github.com/priler/node-watch/bridge/internal/bridge"
	"github.com/priler/node-watch/bridge/internal/config"
)

func main() {
	slog.SetDefault(slog.New(slog.NewJSONHandler(os.Stdout, nil)))

	cfg, err := config.Load()
	if err != nil {
		slog.Error("load config", "error", err)
		os.Exit(1)
	}

	ctx, cancel := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer cancel()

	var wg sync.WaitGroup
	workers := make([]*bridge.Worker, 0, len(cfg.Regions))

	for _, region := range cfg.Regions {
		w, err := bridge.NewWorker(cfg, region)
		if err != nil {
			slog.Error("create worker", "topic", region.KafkaTopic, "error", err)
			os.Exit(1)
		}
		workers = append(workers, w)

		wg.Add(1)
		go func() {
			defer wg.Done()
			w.Run(ctx)
		}()
	}

	<-ctx.Done()
	slog.Info("shutting down")

	for _, w := range workers {
		if err := w.Close(); err != nil {
			slog.Error("close worker", "error", err)
		}
	}

	wg.Wait()
	slog.Info("shutdown complete")
}
