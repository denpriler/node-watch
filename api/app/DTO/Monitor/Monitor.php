<?php

declare(strict_types=1);

namespace App\DTO\Monitor;

use App\Enum\Monitor\MonitorMethod;
use App\Enum\Monitor\MonitorRegion;
use App\Enum\Monitor\MonitorStatus;
use App\Models\Monitor as MonitorModel;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Monitor extends Data
{
    /**
     * @param  MonitorRegion[]  $regions
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
        public readonly MonitorMethod $method,
        public readonly int $check_interval,
        public readonly int $timeout,
        public readonly int $expected_status,
        public readonly array $regions,
        public readonly bool $is_active,
        public readonly ?string $next_check_at,
        public readonly MonitorStatus $last_status,
    ) {}

    public static function fromModel(MonitorModel $monitor): self
    {
        return new self(
            id: $monitor->id,
            name: $monitor->name,
            url: $monitor->url,
            method: $monitor->method,
            check_interval: $monitor->check_interval,
            timeout: $monitor->timeout,
            expected_status: $monitor->expected_status,
            regions: array_map(MonitorRegion::from(...), $monitor->regions),
            is_active: $monitor->is_active,
            next_check_at: $monitor->next_check_at?->format('Y-m-d H:i:s'),
            last_status: $monitor->last_status,
        );
    }
}
