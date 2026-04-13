CREATE TABLE IF NOT EXISTS monitor_logs
(
    monitor_id       UInt64,
    checked_at       DateTime,
    region           LowCardinality(String),
    status_code      UInt16,
    response_time_ms UInt32,
    is_up            UInt8,
    error            Nullable(String),
    ttfb_ms          UInt32,
    cert_expires_at  Nullable(DateTime)
)
ENGINE = MergeTree
PARTITION BY toYYYYMM(checked_at)
ORDER BY (monitor_id, checked_at)
TTL checked_at + INTERVAL 90 DAY;
