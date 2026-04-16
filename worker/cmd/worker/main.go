package main

import (
	"context"
	"log/slog"
	"os"
	"os/signal"
	"syscall"

	workerkafka "nodewatch/worker/internal/kafka"
	"nodewatch/worker/internal/config"
	"nodewatch/worker/internal/result"
)

func main() {
	cfg, err := config.Load()
	if err != nil {
		slog.Error("config error", "error", err)
		os.Exit(1)
	}

	sender := result.NewSender(cfg.ResultAPIURL, cfg.InternalToken)

	consumer, err := workerkafka.NewConsumer(cfg, sender)
	if err != nil {
		slog.Error("failed to create consumer", "error", err)
		os.Exit(1)
	}
	defer consumer.Close()

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	if err := consumer.Run(ctx); err != nil {
		slog.Error("worker error", "error", err)
		os.Exit(1)
	}

	slog.Info("worker stopped gracefully")
}
