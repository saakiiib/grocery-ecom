<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects every request to the one preferred production host from APP_URL
 * (for example www.shop.co.uk → shop.co.uk), so HTTP/HTTPS and www/non-www
 * variants never split rankings. Disabled outside production and when
 * SEO_FORCE_CANONICAL_HOST is off.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || ! config('seo.force_canonical_host')) {
            return $next($request);
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        // Gateways and machine endpoints keep their own host handling.
        $segment = explode('/', trim($request->path(), '/'))[0] ?? '';
        if (in_array($segment, ['api', 'webhooks', 'up'], true)) {
            return $next($request);
        }

        $appUrl = parse_url((string) config('app.url'));
        $host = strtolower((string) ($appUrl['host'] ?? ''));
        $scheme = strtolower((string) ($appUrl['scheme'] ?? 'https'));

        if ($host === '') {
            return $next($request);
        }

        if (strtolower($request->getHost()) === $host && strtolower($request->getScheme()) === $scheme) {
            return $next($request);
        }

        return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
    }
}
