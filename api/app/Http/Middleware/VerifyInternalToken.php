<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class VerifyInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('auth.internal_token');

        if (! $expected || $request->header('X-Internal-Token') !== $expected) {
            throw new UnauthorizedHttpException('Internal token is invalid.');
        }

        return $next($request);
    }
}
