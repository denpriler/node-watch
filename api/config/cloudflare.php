<?php

declare(strict_types=1);

use App\Enum\Monitor\MonitorRegion;

return [
    'queue' => [
        'account_id' => env('CF_QUEUE_ACCOUNT_ID'),
        'api_token' => env('CF_QUEUE_API_TOKEN'),
        'id' => [
            MonitorRegion::EU_WEST->value => env('CF_QUEUE_EU_WEST_ID'),
            MonitorRegion::US_EAST->value => env('CF_QUEUE_US_EAST_ID'),
            MonitorRegion::AP_SOUTH->value => env('CF_QUEUE_AP_SOUTH_ID'),
        ],
    ],
];
