package bridge

import (
	"context"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"log/slog"
	"time"

	"github.com/priler/node-watch/bridge/internal/cfqueue"
	"github.com/priler/node-watch/bridge/internal/config"
	"github.com/segmentio/kafka-go"
	"github.com/segmentio/kafka-go/sasl/scram"
)

type Worker struct {
	region config.RegionConfig
	reader *kafka.Reader
	cf     *cfqueue.Client
}

func NewWorker(cfg *config.Config, region config.RegionConfig) (*Worker, error) {
	mechanism, err := scram.Mechanism(scram.SHA256, cfg.KafkaSASLUser, cfg.KafkaSASLPass)
	if err != nil {
		return nil, fmt.Errorf("create SASL mechanism: %w", err)
	}

	dialer := &kafka.Dialer{
		Timeout:       10 * time.Second,
		DualStack:     true,
		TLS:           &tls.Config{MinVersion: tls.VersionTLS12},
		SASLMechanism: mechanism,
	}

	reader := kafka.NewReader(kafka.ReaderConfig{
		Brokers:        cfg.KafkaBrokers,
		Topic:          region.KafkaTopic,
		GroupID:        "cf-bridge",
		MinBytes:       1,
		MaxBytes:       1 << 20, // 1 MB
		MaxWait:        500 * time.Millisecond,
		CommitInterval: time.Second,
		Dialer:         dialer,
	})

	return &Worker{
		region: region,
		reader: reader,
		cf:     cfqueue.NewClient(cfg.CFAccountID, cfg.CFAPIToken),
	}, nil
}

func (w *Worker) Run(ctx context.Context) {
	log := slog.With("topic", w.region.KafkaTopic, "queue", w.region.CFQueueID)
	log.Info("worker started")

	for {
		msg, err := w.reader.FetchMessage(ctx)
		if err != nil {
			if ctx.Err() != nil {
				log.Info("worker stopped")
				return
			}
			log.Error("fetch message", "error", err)
			time.Sleep(time.Second)
			continue
		}

		if err := w.forward(ctx, msg.Value); err != nil {
			log.Error("forward to CF queue", "error", err, "offset", msg.Offset)
			// не коммитим — сообщение будет доставлено повторно при следующем запуске
			continue
		}

		if err := w.reader.CommitMessages(ctx, msg); err != nil {
			log.Error("commit offset", "error", err, "offset", msg.Offset)
		}
	}
}

func (w *Worker) forward(ctx context.Context, payload []byte) error {
	// проверяем что payload — валидный JSON перед отправкой
	if !json.Valid(payload) {
		return fmt.Errorf("invalid JSON payload: %s", payload)
	}

	const maxRetries = 3
	var err error
	for attempt := range maxRetries {
		err = w.cf.Send(ctx, w.region.CFQueueID, json.RawMessage(payload))
		if err == nil {
			return nil
		}
		if ctx.Err() != nil {
			return ctx.Err()
		}
		backoff := time.Duration(1<<attempt) * time.Second
		slog.Warn("CF queue send failed, retrying", "attempt", attempt+1, "backoff", backoff, "error", err)
		time.Sleep(backoff)
	}
	return fmt.Errorf("after %d attempts: %w", maxRetries, err)
}

func (w *Worker) Close() error {
	return w.reader.Close()
}
