<?php

namespace App\Http\Resources\Auth;

use App\DTO\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @var \App\Models\User */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return User::fromModel($this->resource)->toArray();
    }
}
