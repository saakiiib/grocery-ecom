<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /** api_auth = token required, api_auth:optional = resolve the shopper when a token is sent. */
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $user = null;
        $plain = $request->bearerToken();

        if ($plain) {
            $token = ApiToken::with('user')->where('token', hash('sha256', $plain))->first();
            $user = $token?->user;
            if ($user && ($user->status != 1 || $user->user_type != 0)) {
                $user = null;
            }
            if ($token && $user && (! $token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5)))) {
                $token->forceFill(['last_used_at' => now()])->save();
            }
        }

        if ($user) {
            Auth::guard('web')->setUser($user);
            $request->setUserResolver(fn () => $user);
        } elseif ($mode === 'required') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
