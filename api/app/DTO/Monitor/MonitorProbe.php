<?php

declare(strict_types=1);

namespace App\DTO\Monitor;

use App\Enum\Monitor\MonitorMethod;
use App\Models\Monitor;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class MonitorProbe extends Data
{
    public function __construct(
        public readonly int $monitorId,
        public readonly string $url,
        public readonly MonitorMethod $method,
        public readonly int $timeout,
        #[MapName('expected_status')]
        public readonly int $expectedStatus,
    ) {}

    public static function fromMonitor(Monitor $monitor): self
    {
        return new self(
            monitorId: $monitor->id,
            url: $monitor->url,
            method: $monitor->method,
            timeout: $monitor->timeout,
            expectedStatus: $monitor->expected_status,
        );
    }
}
