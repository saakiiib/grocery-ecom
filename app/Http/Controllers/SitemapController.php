<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Dynamic XML sitemap and robots.txt so both always reflect live data and the
 * configured production domain — nothing to regenerate by hand on deploy.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('seo_sitemap_xml', now()->addHour(), fn (): string => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Build the full sitemap from live categories, products and pages. */
    protected function build(): string
    {
        $urls = [];

        $add = function (string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.8') use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $add(route('home'), null, 'daily', '1.0');

        foreach ([
            'shop' => '0.9',
            'shop.offers' => '0.7',
            'about' => '0.6',
            'gallery' => '0.5',
            'faq' => '0.6',
            'contact' => '0.6',
            'delivery' => '0.8',
            'loyalty' => '0.5',
            'recipes.index' => '0.5',
            'privacy' => '0.3',
            'terms' => '0.3',
            'refund' => '0.3',
        ] as $name => $priority) {
            if (app('router')->has($name)) {
                $add(route($name), null, 'monthly', $priority);
            }
        }

        Category::where('status', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(fn ($category) => $add(
                route('shop.category', $category->slug),
                $category->updated_at?->toAtomString(),
                'weekly',
                '0.7'
            ));

        Product::where('status', true)
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(fn ($product) => $add(
                route('product.show', $product->slug),
                $product->updated_at?->toAtomString(),
                'weekly',
                '0.6'
            ));

        if (app('router')->has('recipes.show')) {
            Recipe::where('status', true)->orderBy('id')->get(['slug', 'updated_at'])
                ->each(fn ($recipe) => $add(
                    route('recipes.show', $recipe->slug),
                    $recipe->updated_at?->toAtomString(),
                    'monthly',
                    '0.4'
                ));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.$this->escape($url['loc']).'</loc>'."\n";
            if ($url['lastmod'] !== null) {
                $xml .= '    <lastmod>'.$this->escape($url['lastmod']).'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return $xml;
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Only block non-HTML endpoints here. Customer/transactional pages',
            '# (bag, checkout, account, login, register, track, favourites, order)',
            '# send "noindex, nofollow" from SeoRobots — blocking them in robots.txt',
            '# would stop Google from ever reading that directive.',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /search-catalog',
            'Disallow: /api/',
            'Disallow: /webhooks/',
            'Disallow: /newsletter',
            'Disallow: /notify',
            '',
            '# Search and filter permutations — crawl the clean category URL instead.',
            'Disallow: /*?q=',
            'Disallow: /*?sort=',
            'Disallow: /*?min_price=',
            'Disallow: /*?max_price=',
            'Disallow: /*?diet=',
            'Disallow: /*?free_from=',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
