<?php

declare(strict_types=1);

namespace App\Enum\Monitor;

enum MonitorRegion: string
{
    case EU_WEST = 'eu-west';
    case US_EAST = 'us-east';
    case AP_SOUTH = 'ap-south';

    public function toStringValue(): string
    {
        return match ($this) {
            MonitorRegion::EU_WEST => 'eu-west',
            MonitorRegion::US_EAST => 'us-east',
            MonitorRegion::AP_SOUTH => 'ap-south',
        };
    }
}
