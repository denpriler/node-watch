<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'NodeWatch API',
    description: 'Self-hosted uptime monitoring service',
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
)]
#[OA\Schema(
    schema: 'UserResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '1'),
        new OA\Property(property: 'type', type: 'string', example: 'users'),
        new OA\Property(
            property: 'attributes',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            ],
        ),
        new OA\Property(property: 'relationships', type: 'object'),
    ],
)]
#[OA\Schema(
    schema: 'MonitorResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '1'),
        new OA\Property(property: 'type', type: 'string', example: 'monitors'),
        new OA\Property(
            property: 'attributes',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'My Website'),
                new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com'),
                new OA\Property(property: 'method', type: 'string', enum: ['GET', 'POST', 'HEAD'], example: 'HEAD'),
                new OA\Property(property: 'check_interval', type: 'integer', example: 60),
                new OA\Property(property: 'timeout', type: 'integer', example: 30),
                new OA\Property(property: 'expected_status', type: 'integer', example: 200),
                new OA\Property(property: 'regions', type: 'array', items: new OA\Items(type: 'string', example: 'eu-west')),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(property: 'last_status', type: 'integer', enum: [0, 1, 2], example: 0),
            ],
        ),
        new OA\Property(property: 'relationships', type: 'object'),
    ],
)]
#[OA\Schema(
    schema: 'MonitorLogEntry',
    properties: [
        new OA\Property(property: 'monitor_id', type: 'integer', example: 1),
        new OA\Property(property: 'checked_at', type: 'string', format: 'date-time', example: '2026-05-01 10:00:00'),
        new OA\Property(property: 'region', type: 'string', enum: ['eu-west', 'us-east', 'ap-south'], example: 'eu-west'),
        new OA\Property(property: 'status_code', type: 'integer', example: 200),
        new OA\Property(property: 'response_time_ms', type: 'integer', example: 142),
        new OA\Property(property: 'ttfb_ms', type: 'integer', example: 87),
        new OA\Property(property: 'is_up', type: 'boolean', example: true),
        new OA\Property(property: 'error', type: 'string', nullable: true, example: null),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'MonitorLogBucket',
    properties: [
        new OA\Property(property: 'bucket', type: 'string', format: 'date-time', example: '2026-05-01T10:00:00Z'),
        new OA\Property(property: 'region', type: 'string', enum: ['eu-west', 'us-east', 'ap-south'], example: 'eu-west'),
        new OA\Property(property: 'avg_response_time_ms', type: 'number', format: 'float', example: 142.5),
        new OA\Property(property: 'min_response_time_ms', type: 'integer', example: 98),
        new OA\Property(property: 'max_response_time_ms', type: 'integer', example: 210),
        new OA\Property(property: 'avg_ttfb_ms', type: 'number', format: 'float', example: 87.3),
        new OA\Property(property: 'down_count', type: 'integer', example: 0),
        new OA\Property(property: 'sample_count', type: 'integer', example: 12),
    ],
    type: 'object',
)]
class OpenApiSpec {}
