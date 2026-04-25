<?php

namespace App\Enum\Monitor;

enum MonitorStatus: int
{
    case PENDING = 0;
    case UP = 1;
    case DOWN = 2;
}
