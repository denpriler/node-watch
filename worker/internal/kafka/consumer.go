package kafka

import (
	"context"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"log/slog"

	"github.com/segmentio/kafka-go"
	"github.com/segmentio/kafka-go/sasl/scram"

	"nodewatch/worker/internal/config"
	"nodewatch/worker/internal/probe"
	"nodewatch/worker/internal/result"
)

// monitorProbeMessage matches the JSON produced by Laravel's MonitorProbe DTO.
type monitorProbeMessage struct {
	MonitorID      int    `json:"monitorId"`
	URL            string `json:"url"`
	Method         string `json:"method"`
	Timeout        int    `json:"timeout"`
	ExpectedStatus int    `json:"expected_status"`
}

type Consumer struct {
	reader *kafka.Reader
	sender *result.Sender
	region string
}

func NewConsumer(cfg *config.Config, sender *result.Sender) (*Consumer, error) {
	dialer := &kafka.Dialer{}

	if cfg.KafkaUsername != "" {
		mechanism, err := scram.Mechanism(scram.SHA256, cfg.KafkaUsername, cfg.KafkaPassword)
		if err != nil {
			return nil, fmt.Errorf("init SCRAM-SHA-256: %w", err)
		}
		dialer.SASLMechanism = mechanism
		dialer.TLS = &tls.Config{MinVersion: tls.VersionTLS12}
	}

	reader := kafka.NewReader(kafka.ReaderConfig{
		Brokers:  []string{cfg.KafkaBroker},
		Topic:    cfg.KafkaTopic,
		GroupID:  cfg.KafkaGroupID,
		MinBytes: 1,
		MaxBytes: 1 << 20, // 1 MB
		Dialer:   dialer,
	})

	return &Consumer{
		reader: reader,
		sender: sender,
		region: cfg.Region,
	}, nil
}

func (c *Consumer) Run(ctx context.Context) error {
	slog.Info("worker started",
		"region", c.region,
		"topic", c.reader.Config().Topic,
		"group", c.reader.Config().GroupID,
	)

	for {
		msg, err := c.reader.FetchMessage(ctx)
		if err != nil {
			if ctx.Err() != nil {
				return nil // graceful shutdown
			}
			return fmt.Errorf("fetch message: %w", err)
		}

		var probe_msg monitorProbeMessage
		if err := json.Unmarshal(msg.Value, &probe_msg); err != nil {
			slog.Error("malformed message — skipping", "key", string(msg.Key), "error", err)
			_ = c.reader.CommitMessages(ctx, msg)
			continue
		}

		slog.Info("probing", "monitor_id", probe_msg.MonitorID, "url", probe_msg.URL)

		probeResult := probe.Execute(ctx, probe.Request{
			MonitorID:      probe_msg.MonitorID,
			URL:            probe_msg.URL,
			Method:         probe_msg.Method,
			TimeoutSeconds: probe_msg.Timeout,
			ExpectedStatus: probe_msg.ExpectedStatus,
		})

		if err := c.sender.Send(ctx, c.region, probeResult); err != nil {
			slog.Error("failed to deliver result",
				"monitor_id", probe_msg.MonitorID,
				"error", err,
			)
		} else {
			slog.Info("result delivered",
				"monitor_id", probe_msg.MonitorID,
				"is_up", probeResult.IsUp,
				"status_code", probeResult.StatusCode,
				"response_time_ms", probeResult.ResponseTimeMs,
			)
		}

		if err := c.reader.CommitMessages(ctx, msg); err != nil {
			slog.Error("commit failed", "error", err)
		}
	}
}

func (c *Consumer) Close() error {
	return c.reader.Close()
}
