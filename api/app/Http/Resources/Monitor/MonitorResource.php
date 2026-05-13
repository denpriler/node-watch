<?php

declare(strict_types=1);

namespace App\Http\Resources\Monitor;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class MonitorResource extends JsonApiResource
{
    /** @var Monitor */
    public $resource;

    /** @var array<string, mixed> */
    public array $relationships = [];

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'url' => $this->resource->url,
            'method' => $this->resource->method->value,
            'check_interval' => $this->resource->check_interval,
            'timeout' => $this->resource->timeout,
            'expected_status' => $this->resource->expected_status,
            'regions' => $this->resource->regions,
            'is_active' => $this->resource->is_active,
            'last_status' => $this->resource->last_status->value,
            'last_checked_at' => $this->resource->next_check_at
                ?->subSeconds($this->resource->check_interval)
                ?->toIso8601String(),
        ];
    }
}
