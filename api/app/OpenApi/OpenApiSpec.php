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
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'telegram_chat_id', type: 'string', nullable: true, example: null),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'MonitorResource',
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
        new OA\Property(property: 'next_check_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'last_status', type: 'string', enum: ['PENDING', 'UP', 'DOWN'], example: 'PENDING'),
    ],
    type: 'object',
)]
class OpenApiSpec {}
