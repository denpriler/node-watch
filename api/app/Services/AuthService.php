<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(RegisterRequest $request): User
    {
        return User::create($request->validated());
    }

    public function login(LoginRequest $request): ?string
    {
        if (! Auth::attempt($request->validated())) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        return $user->createToken('api')->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
