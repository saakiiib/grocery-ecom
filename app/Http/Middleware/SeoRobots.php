<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenGraph;
use SEOMeta;
use Symfony\Component\HttpFoundation\Response;
use Twitter;

/**
 * Sets the safe SEO defaults Google needs on every public HTML page: a
 * self-referencing canonical (query strings stripped) and an explicit robots
 * directive. Controllers can override either — the shop sets noindex on
 * search results, and private pages always get noindex, nofollow.
 */
class SeoRobots
{
    /** Route-name prefixes that must never be indexed. */
    private const PRIVATE_ROUTE_PREFIXES = [
        'admin.', 'account', 'lists.', 'order.', 'webhooks.', 'password.',
        'checkout', 'bag', 'track', 'favourites', 'dashboard',
        'login', 'logout', 'register',
    ];

    /** First path segments that must never be indexed (fallback when unnamed). */
    private const PRIVATE_PATH_SEGMENTS = [
        'admin', 'account', 'orders', 'order', 'checkout', 'bag', 'login',
        'register', 'track', 'favourites', 'dashboard', 'api', 'webhooks',
        'search-catalog', 'sitemap.xml', 'robots.txt',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($this->isPrivate($request)) {
            SEOMeta::setRobots('noindex, nofollow');

            return $next($request);
        }

        // `url()->current()` excludes the query string, so tracking, sort and
        // filter parameters never leak into the canonical.
        $canonical = url()->current();
        SEOMeta::setRobots('index, follow');
        SEOMeta::setCanonical($canonical);
        OpenGraph::setUrl($canonical);
        Twitter::setUrl($canonical);

        return $next($request);
    }

    private function isPrivate(Request $request): bool
    {
        $routeName = (string) optional($request->route())->getName();
        foreach (self::PRIVATE_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        $segment = explode('/', trim($request->path(), '/'))[0] ?? '';

        return in_array($segment, self::PRIVATE_PATH_SEGMENTS, true);
    }
}
