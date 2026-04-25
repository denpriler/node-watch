<?php

namespace App\DTO\Auth;

use App\Models\User as UserModel;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class User extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
    ) {}

    public static function fromModel(UserModel $user): self
    {
        return self::from($user->toArray());
    }
}
