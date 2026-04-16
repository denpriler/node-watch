package result

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"nodewatch/worker/internal/probe"
)

type Sender struct {
	apiURL string
	token  string
	client *http.Client
}

type payload struct {
	MonitorID      int     `json:"monitor_id"`
	Region         string  `json:"region"`
	StatusCode     int     `json:"status_code"`
	ResponseTimeMs int64   `json:"response_time_ms"`
	TTFBMs         int64   `json:"ttfb_ms"`
	IsUp           bool    `json:"is_up"`
	Error          *string `json:"error"`
	CheckedAt      string  `json:"checked_at"`
}

func NewSender(apiURL, token string) *Sender {
	return &Sender{
		apiURL: apiURL,
		token:  token,
		client: &http.Client{Timeout: 10 * time.Second},
	}
}

func (s *Sender) Send(ctx context.Context, region string, r probe.Result) error {
	p := payload{
		MonitorID:      r.MonitorID,
		Region:         region,
		StatusCode:     r.StatusCode,
		ResponseTimeMs: r.ResponseTimeMs,
		TTFBMs:         r.TTFBMs,
		IsUp:           r.IsUp,
		Error:          r.Error,
		CheckedAt:      r.CheckedAt.Format(time.RFC3339),
	}

	body, err := json.Marshal(p)
	if err != nil {
		return fmt.Errorf("marshal payload: %w", err)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, s.apiURL, bytes.NewReader(body))
	if err != nil {
		return fmt.Errorf("build request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Internal-Token", s.token)

	resp, err := s.client.Do(req)
	if err != nil {
		return fmt.Errorf("post result: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected API status %d", resp.StatusCode)
	}

	return nil
}
