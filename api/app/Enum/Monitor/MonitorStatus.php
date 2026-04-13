<?php

namespace App\Enum\Monitor;

enum MonitorStatus: int
{
    case PENDING = 0;
    case UP = 1;
    case DOWN = 2;

    public function toStringValue(): string
    {
        return match ($this) {
            MonitorStatus::PENDING => 'PENDING',
            MonitorStatus::UP => 'UP',
            MonitorStatus::DOWN => 'DOWN',
        };
    }
}
