<?php

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /** @var User */
    public $resource;

    /** @var array<string, mixed> */
    public array $relationships = [];

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'email' => $this->resource->email,
        ];
    }
}
