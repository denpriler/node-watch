<?php

declare(strict_types=1);

namespace App\DTO\Monitor;

use App\Enum\Monitor\MonitorRegion;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

final class MonitorLogBucket extends Data
{
    public function __construct(
        #[MapName('bucket')]
        public readonly CarbonImmutable $bucket,
        public readonly MonitorRegion $region,
        #[MapName('avg_response_time_ms')]
        public readonly float $avgResponseTimeMs,
        #[MapName('min_response_time_ms')]
        public readonly int $minResponseTimeMs,
        #[MapName('max_response_time_ms')]
        public readonly int $maxResponseTimeMs,
        #[MapName('avg_ttfb_ms')]
        public readonly float $avgTtfbMs,
        #[MapName('down_count')]
        public readonly int $downCount,
        #[MapName('sample_count')]
        public readonly int $sampleCount,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            bucket: CarbonImmutable::parse($row['bucket']),
            region: MonitorRegion::from($row['region']),
            avgResponseTimeMs: (float) $row['avg_response_time_ms'],
            minResponseTimeMs: (int) $row['min_response_time_ms'],
            maxResponseTimeMs: (int) $row['max_response_time_ms'],
            avgTtfbMs: (float) $row['avg_ttfb_ms'],
            downCount: (int) $row['down_count'],
            sampleCount: (int) $row['sample_count'],
        );
    }
}
