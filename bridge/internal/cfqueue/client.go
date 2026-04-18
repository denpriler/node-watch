package cfqueue

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"
)

const baseURL = "https://api.cloudflare.com/client/v4"

type Client struct {
	accountID string
	apiToken  string
	http      *http.Client
}

func NewClient(accountID, apiToken string) *Client {
	return &Client{
		accountID: accountID,
		apiToken:  apiToken,
		http:      &http.Client{Timeout: 15 * time.Second},
	}
}

type sendRequest struct {
	Messages []message `json:"messages"`
}

type message struct {
	Body        json.RawMessage `json:"body"`
	ContentType string          `json:"content_type"`
}

type sendResponse struct {
	Success bool `json:"success"`
	Result  struct {
		Queued int `json:"queued"`
	} `json:"result"`
	Errors []struct {
		Message string `json:"message"`
	} `json:"errors"`
}

func (c *Client) Send(ctx context.Context, queueID string, payload json.RawMessage) error {
	body, err := json.Marshal(sendRequest{
		Messages: []message{{Body: payload, ContentType: "json"}},
	})
	if err != nil {
		return fmt.Errorf("marshal request: %w", err)
	}

	url := fmt.Sprintf("%s/accounts/%s/queues/%s/messages/batch", baseURL, c.accountID, queueID)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(body))
	if err != nil {
		return fmt.Errorf("create request: %w", err)
	}
	req.Header.Set("Authorization", "Bearer "+c.apiToken)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.http.Do(req)
	if err != nil {
		return fmt.Errorf("send request: %w", err)
	}
	defer resp.Body.Close()

	raw, _ := io.ReadAll(resp.Body)

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("CF API status %d: %s", resp.StatusCode, raw)
	}

	var result sendResponse
	if err := json.Unmarshal(raw, &result); err != nil {
		return fmt.Errorf("decode response: %w", err)
	}
	if !result.Success {
		msg := "unknown error"
		if len(result.Errors) > 0 {
			msg = result.Errors[0].Message
		}
		return fmt.Errorf("CF API error: %s", msg)
	}

	return nil
}
