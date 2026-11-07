<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('auth.token.sliding')) {
            $token = $request->user()?->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $lifetime = config('auth.token.lifetime');

                if (! $token->expires_at || $token->expires_at->lt(now()->addMinutes($lifetime - 1))) {
                    $token->forceFill(['expires_at' => now()->addMinutes($lifetime)])->save();
                }
            }
        }

        return $next($request);
    }
}
