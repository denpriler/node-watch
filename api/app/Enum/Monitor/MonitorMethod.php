<?php

namespace App\Enum\Monitor;

enum MonitorMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case HEAD = 'HEAD';
}
