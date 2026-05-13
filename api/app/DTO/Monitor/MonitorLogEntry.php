<?php

declare(strict_types=1);

namespace App\DTO\Monitor;

use App\Enum\Monitor\MonitorRegion;
use App\Http\Requests\ProbeResultRequest;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class MonitorLogEntry extends Data
{
    public function __construct(
        #[MapName('monitor_id')]
        public readonly int $monitorId,
        #[MapName('checked_at')]
        public readonly CarbonImmutable $checkedAt,
        public readonly MonitorRegion $region,
        #[MapName('status_code')]
        public readonly int $statusCode,
        #[MapName('response_time_ms')]
        public readonly int $responseTimeMs,
        #[MapName('ttfb_ms')]
        public readonly int $ttfbMs,
        #[MapName('is_up')]
        public readonly bool $isUp,
        public readonly ?string $error,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromMonitorLog(array $row): self
    {
        return new self(
            monitorId: (int) $row['monitor_id'],
            checkedAt: CarbonImmutable::parse($row['checked_at']),
            region: MonitorRegion::from($row['region']),
            statusCode: (int) $row['status_code'],
            responseTimeMs: (int) $row['response_time_ms'],
            ttfbMs: (int) $row['ttfb_ms'],
            isUp: (bool) $row['is_up'],
            error: $row['error'] ?? null,
        );
    }

    public static function fromProbeResultRequest(ProbeResultRequest $request): self
    {
        return new self(
            monitorId: $request->monitor_id,
            checkedAt: CarbonImmutable::parse($request->checked_at),
            region: MonitorRegion::from($request->region),
            statusCode: $request->status_code,
            responseTimeMs: $request->response_time_ms,
            ttfbMs: $request->ttfb_ms,
            isUp: $request->is_up,
            error: $request->error,
        );
    }

    public function toArray(): array
    {
        return [
            'monitor_id' => $this->monitorId,
            'is_up' => $this->isUp,
            'datetime' => $this->checkedAt->getTimestamp(),
            'region' => $this->region->value,
            'status' => $this->statusCode,
            'response_time_ms' => $this->responseTimeMs,
            'ttfb_ms' => $this->ttfbMs,
        ];
    }
}
