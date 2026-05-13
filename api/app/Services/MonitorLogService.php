<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Monitor\MonitorLogBucket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

final readonly class MonitorLogService
{
    public function __construct(
        protected ClickHouseService $clickHouseService,
    ) {}

    /**
     * @return array<string, array<MonitorLogBucket>>
     */
    public function logsById(int $id, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $start = $from ?? CarbonImmutable::now()->subDay();
        $end = $to ?? CarbonImmutable::now();

        if ($end->isBefore($start)) {
            [$start, $end] = [$end, $start];
        }

        $bucketSeconds = $this->bucketSeconds((int) $start->diffInSeconds($end));

        try {
            return collect(
                $this->clickHouseService->select(
                    sprintf(
                        "SELECT
                            toStartOfInterval(checked_at, INTERVAL %d SECOND) AS bucket,
                            region,
                            avg(response_time_ms) AS avg_response_time_ms,
                            min(response_time_ms) AS min_response_time_ms,
                            max(response_time_ms) AS max_response_time_ms,
                            avg(ttfb_ms) AS avg_ttfb_ms,
                            sum(is_up = 0) AS down_count,
                            count() AS sample_count
                        FROM monitor_logs
                        WHERE monitor_id = %d AND checked_at >= '%s' AND checked_at <= '%s'
                        GROUP BY bucket, region
                        ORDER BY bucket ASC",
                        $bucketSeconds,
                        $id,
                        $start->format('Y-m-d H:i:s'),
                        $end->format('Y-m-d H:i:s')
                    )
                )
            )
                ->map(fn (array $row) => MonitorLogBucket::fromRow($row))
                ->groupBy(fn (MonitorLogBucket $b) => $b->region->value)
                ->toArray();
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());

            return [];
        }
    }

    private function bucketSeconds(int $rangeSeconds): int
    {
        return match (true) {
            $rangeSeconds <= 86400 => 300,   // ≤ 1d  → 5 min
            $rangeSeconds <= 604800 => 3600,  // ≤ 7d  → 1 h
            $rangeSeconds <= 2592000 => 14400, // ≤ 30d → 4 h
            default => 43200, // > 30d → 12 h
        };
    }
}
