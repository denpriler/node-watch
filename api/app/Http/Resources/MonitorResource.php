<?php

namespace App\Http\Resources;

use App\DTO\Monitor\Monitor;
use App\Models\Monitor as MonitorModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitorResource extends JsonResource
{
    /** @var MonitorModel */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return Monitor::fromModel($this->resource)->toArray();
    }
}
