<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Central SEO helpers: meta-text fallbacks and JSON-LD nodes.
 *
 * Structured data is built from the same database rows the storefront shows,
 * so prices, stock and business details stay in sync automatically. Empty
 * values are omitted rather than emitted as blank properties.
 */
class Seo
{
    /** Business/organisation @id reused by every other schema node. */
    public static function organizationId(): string
    {
        return url('/').'#organization';
    }

    /** Storefront brand name with a safe final fallback. */
    public static function brand(): string
    {
        $name = trim((string) (CompanyDetails::cached()->company_name ?? ''));

        return ($name !== '' && strcasecmp($name, 'Evergreen') !== 0) ? $name : 'Alam Mini Market';
    }

    /** Absolute URL for a stored relative path (or pass an absolute URL through). */
    public static function imageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return url($path);
    }

    /** Strip rich copy down to a single safe, trimmed plain-text line. */
    public static function plain(?string $html, int $limit = 160): string
    {
        $text = trim((string) $html);
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $text) ?? $text;
        $text = preg_replace('/<(?:br\s*\/?|\/(?:p|div|li|h[1-6]|blockquote|tr))\b[^>]*>/i', ' ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $limit > 0 ? Str::limit($text, $limit, '') : $text;
    }

    /** First non-empty candidate, cleaned to plain text and trimmed to $limit. */
    public static function firstText(array $candidates, int $limit = 160): string
    {
        foreach ($candidates as $candidate) {
            $text = self::plain($candidate, $limit);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * Meta description for a category: saved description, then category copy.
     */
    public static function categoryDescription(Category $category): string
    {
        return self::firstText([
            $category->meta_description,
            $category->description,
            $category->parent?->description,
        ]);
    }

    /* ---------------------------------------------------------------------
     | JSON-LD nodes
     | ------------------------------------------------------------------- */

    /** GroceryStore / Organization node — the single business entity. */
    public static function organization(): array
    {
        $company = CompanyDetails::cached();
        $config = config('seo.business', []);

        $node = [
            '@type' => ['GroceryStore', 'Organization'],
            '@id' => self::organizationId(),
            'name' => self::brand(),
            'url' => url('/'),
        ];

        $logo = self::imageUrl($company->company_logo ? 'uploads/company/'.$company->company_logo : null);
        if ($logo !== null) {
            $node['logo'] = $logo;
            $node['image'] = $logo;
        }

        $phone = self::firstPhone([$company->phone1, $company->phone2, $company->phone3, $company->phone4]);
        if ($phone !== null) {
            $node['telephone'] = $phone;
        }

        $email = trim((string) $company->email1);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $node['email'] = $email;
        }

        $address = self::postalAddress($company, $config);
        if ($address !== []) {
            $node['address'] = $address;
        }

        $sameAs = self::socialProfiles($company);
        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        $hours = self::openingHours($company->opening_time);
        if ($hours !== []) {
            $node['openingHoursSpecification'] = $hours;
        }

        $area = self::areaServed($config);
        if ($area !== []) {
            $node['areaServed'] = $area;
        }

        $priceRange = trim((string) ($config['price_range'] ?? ''));
        if ($priceRange !== '') {
            $node['priceRange'] = $priceRange;
        }

        return $node;
    }

    /** WebSite node with a sitelinks search box action. */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => self::brand(),
            'inLanguage' => 'en-GB',
            'publisher' => ['@id' => self::organizationId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('shop').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Product node for the details page.
     *
     * @param  array{priceNum?: float|int|null, inStock?: bool, ratingAvg?: float|null, ratingCount?: int, images?: array<int, string>}  $detail
     */
    public static function product(Product $product, array $detail = []): array
    {
        $url = route('product.show', $product->slug);
        $default = $product->defaultVariant();
        $price = $detail['priceNum'] ?? $default?->sellingPrice();
        $inStock = array_key_exists('inStock', $detail) ? (bool) $detail['inStock'] : (bool) $default?->in_stock;

        $images = collect($detail['images'] ?? [])
            ->map(fn ($image) => self::imageUrl($image))
            ->filter()
            ->unique()
            ->values();

        $hero = self::imageUrl($product->seoArray()['image']);
        if ($hero !== null && ! $images->contains($hero)) {
            $images->prepend($hero);
        }
        // Keep the gallery small — Google does not need every shot.
        $images = $images->take(5)->values();

        $node = [
            '@type' => 'Product',
            '@id' => $url.'#product',
            'name' => $product->name,
            'url' => $url,
        ];

        $description = self::firstText([
            $product->meta_description,
            $product->tagline,
            $product->description,
            $product->category?->description,
        ], 400);
        if ($description !== '') {
            $node['description'] = $description;
        }

        if ($images->isNotEmpty()) {
            $node['image'] = $images->all();
        }

        $sku = trim((string) ($default?->sku ?? ''));
        if ($sku !== '') {
            $node['sku'] = $sku;
        }

        if ($product->category) {
            $node['category'] = $product->category->name;
        }

        if ($price !== null) {
            $node['offers'] = [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'GBP',
                'price' => number_format((float) $price, 2, '.', ''),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => self::organizationId()],
            ];
        }

        $ratingCount = (int) ($detail['ratingCount'] ?? 0);
        $ratingAvg = $detail['ratingAvg'] ?? null;
        if ($ratingCount > 0 && $ratingAvg !== null) {
            $node['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) round((float) $ratingAvg, 1),
                'reviewCount' => $ratingCount,
            ];
        }

        return $node;
    }

    /**
     * BreadcrumbList node.
     *
     * @param  array<int, array{name: string, url: string}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach (array_values($crumbs) as $i => $crumb) {
            $name = trim((string) ($crumb['name'] ?? ''));
            $itemUrl = trim((string) ($crumb['url'] ?? ''));
            if ($name === '' || $itemUrl === '') {
                continue;
            }
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $name,
                'item' => $itemUrl,
            ];
        }
        $lastIndex = array_key_last($items);
        if ($lastIndex !== null) {
            unset($items[$lastIndex]['item']);
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /* ---------------------------------------------------------------------
     | Business details
     | ------------------------------------------------------------------- */

    /** First phone-like value, normalised to a schema-friendly string. */
    private static function firstPhone(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '' && preg_match('/^[+0-9()\s.-]{7,}$/', $value)) {
                return $value;
            }
        }

        return null;
    }

    /** PostalAddress from the Company Details row, skipping phone-shaped junk. */
    private static function postalAddress(CompanyDetails $company, array $config): array
    {
        $lines = collect([$company->address1, $company->address2, $company->address3])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            // Guard against an address column that actually holds a phone number.
            ->reject(fn ($line) => (bool) preg_match('/^[+0-9()\s.-]{7,}$/', $line))
            ->values();

        $postcode = trim((string) (Setting::get('store_postcode', '') ?? ''));
        $locality = trim((string) ($config['locality'] ?? ''));
        $region = trim((string) ($config['region'] ?? ''));
        $country = trim((string) ($config['country'] ?? 'GB'));

        $address = ['@type' => 'PostalAddress'];
        if ($lines->isNotEmpty()) {
            $address['streetAddress'] = $lines->implode(', ');
        }
        if ($locality !== '') {
            $address['addressLocality'] = $locality;
        }
        if ($region !== '') {
            $address['addressRegion'] = $region;
        }
        if ($postcode !== '') {
            $address['postalCode'] = strtoupper($postcode);
        }
        if ($country !== '') {
            $address['addressCountry'] = $country;
        }

        // A PostalAddress with only @type is meaningless — drop it.
        return count($address) > 1 ? $address : [];
    }

    /** Verified, https social profile URLs only. */
    private static function socialProfiles(CompanyDetails $company): array
    {
        $hosts = [
            $company->facebook => ['facebook.com'],
            $company->instagram => ['instagram.com'],
            $company->twitter => ['x.com', 'twitter.com'],
            $company->linkedin => ['linkedin.com'],
            $company->youtube => ['youtube.com'],
            $company->tiktok => ['tiktok.com'],
        ];

        $urls = [];
        foreach ($hosts as $url => $allowedHosts) {
            $safe = self::safeSocialUrl((string) $url, $allowedHosts);
            if ($safe !== null) {
                $urls[] = $safe;
            }
        }

        return array_values(array_unique($urls));
    }

    /** Absolute areaServed nodes from config names. */
    private static function areaServed(array $config): array
    {
        $names = config('seo.area_served', []);
        if (! is_array($names)) {
            return [];
        }

        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->map(fn ($name) => ['@type' => 'City', 'name' => $name])
            ->values()
            ->all();
    }

    private static function safeSocialUrl(string $url, array $allowedHosts): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url) ?: [];
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return null;
        }
        foreach ($allowedHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Parse free-text opening hours ("Mon–Sat 9am–9pm, Sun 10am–6pm") into
     * openingHoursSpecification. Returns [] when the text cannot be trusted.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function openingHours(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }
        // Normalise dashes and unify separators.
        $text = str_replace(['–', '—', ' to ', ' To '], ['-', '-', '-', '-'], $text);
        $text = strtolower($text);

        $days = ['mo' => 'Monday', 'tu' => 'Tuesday', 'we' => 'Wednesday', 'th' => 'Thursday', 'fr' => 'Friday', 'sa' => 'Saturday', 'su' => 'Sunday'];
        $order = array_keys($days);

        $specs = [];
        foreach (preg_split('/[,;]+/', $text) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            // Days: optional range, else a single day, else "daily/everyday".
            $dayKeys = [];
            if (str_contains($chunk, 'daily') || str_contains($chunk, 'everyday') || str_contains($chunk, 'every day')) {
                $dayKeys = $order;
            } elseif (preg_match('/\b(mon|tue|tues|wed|thu|thur|thurs|fri|sat|sun)[a-z]*\s*-\s*(mon|tue|tues|wed|thu|thur|thurs|fri|sat|sun)[a-z]*/', $chunk, $m)) {
                $start = array_search(substr($m[1], 0, 2), $order, true);
                $end = array_search(substr($m[2], 0, 2), $order, true);
                if ($start !== false && $end !== false) {
                    $count = count($order);
                    for ($i = $start; ; $i = ($i + 1) % $count) {
                        $dayKeys[] = $order[$i];
                        if ($i === $end) {
                            break;
                        }
                    }
                }
            } elseif (preg_match_all('/\b(mon|tue|tues|wed|thu|thur|thurs|fri|sat|sun)[a-z]*/', $chunk, $m)) {
                foreach ($m[1] as $day) {
                    $key = substr($day, 0, 2);
                    if (in_array($key, $order, true)) {
                        $dayKeys[] = $key;
                    }
                }
            }
            $dayKeys = array_values(array_unique($dayKeys));

            // Times: "9am-9pm", "09:00-21:00", "9.30am - 9pm".
            if (! preg_match('/(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm)?\s*-\s*(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm)?/', $chunk, $t)) {
                continue;
            }
            $opens = self::to24Hour((int) $t[1], $t[2] ?? null, $t[3] ?? null);
            $closes = self::to24Hour((int) $t[4], $t[5] ?? null, $t[6] ?? null);
            if ($opens === null || $closes === null || $dayKeys === []) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_map(fn ($key) => 'https://schema.org/'.($days[$key] ?? ''), $dayKeys),
                'opens' => $opens,
                'closes' => $closes,
            ];
        }

        return $specs;
    }

    private static function to24Hour(int $hour, ?string $minutes, ?string $meridiem): ?string
    {
        if ($hour < 0 || $hour > 24) {
            return null;
        }
        if ($meridiem !== null) {
            $hour = $hour % 12;
            if ($meridiem === 'pm') {
                $hour += 12;
            }
        }
        if ($hour > 23) {
            $hour = 0;
        }
        $minute = $minutes !== null ? (int) $minutes : 0;
        if ($minute < 0 || $minute > 59) {
            $minute = 0;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
