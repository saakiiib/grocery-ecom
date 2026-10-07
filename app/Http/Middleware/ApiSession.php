<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives the mobile app the same server-side session the website uses
 * (bag, last_order_id…), keyed by the device's X-Session-Id header.
 */
class ApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) $request->header('X-Session-Id');
        if (! preg_match('/^[A-Za-z0-9]{40}$/', $id)) {
            $id = Str::random(40);
        }

        $session = app('session.store');
        $session->setId($id);
        $session->start();
        $request->setLaravelSession($session);

        $response = $next($request);

        $session->save();
        $response->headers->set('X-Session-Id', $id);

        return $response;
    }
}
