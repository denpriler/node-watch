<?php

return [
    'host' => env('CLICKHOUSE_HOST', 'http://127.0.0.1:8123'),
    'user' => env('CLICKHOUSE_USER', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
];
