<?php

namespace App\Http\Resources;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /** @var Monitor */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
            'next_check_at' => $this->whenNotNull($this->resource->next_check_at?->format('Y-m-d H:i:s')),
            'last_status' => $this->resource->last_status->toStringValue(),
        ];
    }
}
