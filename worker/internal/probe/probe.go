package probe

import (
	"context"
	"crypto/tls"
	"fmt"
	"net/http"
	"net/http/httptrace"
	"time"
)

type Request struct {
	MonitorID      int
	URL            string
	Method         string
	TimeoutSeconds int
	ExpectedStatus int
}

type Result struct {
	MonitorID      int
	StatusCode     int
	ResponseTimeMs int64
	TTFBMs         int64
	IsUp           bool
	Error          *string
	CheckedAt      time.Time
}

var httpClient = &http.Client{
	Transport: &http.Transport{
		TLSClientConfig: &tls.Config{MinVersion: tls.VersionTLS12},
	},
	// Record the final response status without following redirects,
	// so we can compare against expected_status accurately.
	CheckRedirect: func(_ *http.Request, _ []*http.Request) error {
		return http.ErrUseLastResponse
	},
}

func Execute(ctx context.Context, req Request) Result {
	result := Result{
		MonitorID: req.MonitorID,
		CheckedAt: time.Now().UTC(),
	}

	timeout := time.Duration(req.TimeoutSeconds) * time.Second
	if timeout <= 0 {
		timeout = 30 * time.Second
	}

	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	httpReq, err := http.NewRequestWithContext(ctx, req.Method, req.URL, nil)
	if err != nil {
		errStr := fmt.Sprintf("build request: %s", err)
		result.Error = &errStr
		return result
	}
	httpReq.Header.Set("User-Agent", "NodeWatch-Probe/1.0")

	var ttfb time.Duration
	start := time.Now()

	trace := &httptrace.ClientTrace{
		GotFirstResponseByte: func() { ttfb = time.Since(start) },
	}
	httpReq = httpReq.WithContext(httptrace.WithClientTrace(httpReq.Context(), trace))

	resp, err := httpClient.Do(httpReq)
	elapsed := time.Since(start)

	if err != nil {
		errStr := err.Error()
		result.Error = &errStr
		return result
	}
	defer resp.Body.Close()

	result.StatusCode = resp.StatusCode
	result.ResponseTimeMs = elapsed.Milliseconds()
	result.TTFBMs = ttfb.Milliseconds()
	result.IsUp = resp.StatusCode == req.ExpectedStatus

	return result
}
