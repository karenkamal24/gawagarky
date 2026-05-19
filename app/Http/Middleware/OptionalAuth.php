<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;


class OptionalAuth
{
    public function handle(Request $request, Closure $next)
    {
        $request->setUserResolver(function () use ($request) {

            $token = $request->bearerToken();

            if (! $token) {
                return null; // Guest
            }

            $accessToken = PersonalAccessToken::findToken($token);

            return $accessToken?->tokenable;
        });

        return $next($request);
    }
}
