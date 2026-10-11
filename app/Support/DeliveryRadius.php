<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Radius delivery: one store postcode + a miles radius from Shop Settings.
 * Postcodes resolve to lat/lng via postcodes.io (free, no key), cached for
 * 30 days so repeat postcodes never hit the network. Distances are
 * straight-line (as the crow flies) miles.
 *
 * Fail-closed throughout: blank store postcode means everywhere (open),
 * but once a store is set, an invalid or unverifiable postcode is refused.
 */
class DeliveryRadius
{
    /** Store postcode from settings, normalized (no spaces, uppercase), or null when unset. */
    public static function storePostcode(): ?string
    {
        $raw = trim((string) (Setting::get('store_postcode', '') ?? ''));
        if ($raw === '' || ! static::validFormat($raw)) {
            return null;
        }

        return static::normalize($raw);
    }

    public static function radiusMiles(): float
    {
        return Setting::money('delivery_radius_miles', 10.0);
    }

    /** Uppercase, no spaces — the canonical postcode key. */
    public static function normalize(string $postcode): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $postcode));
    }

    public static function validFormat(?string $postcode): bool
    {
        return (bool) preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]?\s?[0-9][A-Z]{2}$/i', trim((string) $postcode));
    }

    /** [lat, lng] for a postcode, cached; null when unverifiable (fail-closed). */
    public static function coordinates(string $postcode): ?array
    {
        $cached = Cache::get('postcode.'.static::normalize($postcode));
        if (is_array($cached) && isset($cached[0], $cached[1])) {
            return [(float) $cached[0], (float) $cached[1]];
        }

        try {
            $res = Http::timeout(5)->get('https://api.postcodes.io/postcodes/'.urlencode(trim($postcode)));
        } catch (\Throwable) {
            return null;
        }

        if (! $res->ok()) {
            return null;
        }
        $lat = $res->json('result.latitude');
        $lng = $res->json('result.longitude');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $coords = [(float) $lat, (float) $lng];
        Cache::put('postcode.'.static::normalize($postcode), $coords, now()->addDays(30));

        return $coords;
    }

    /** Straight-line miles between two [lat, lng] pairs. */
    public static function distanceMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * 3958.8 * asin(min(1, sqrt($a)));
    }

    /** Miles from the store to a postcode; null when open (no store set) or unverifiable. */
    public static function distanceFromStore(string $postcode): ?float
    {
        $store = static::storePostcode();
        if ($store === null || ! static::validFormat($postcode)) {
            return null;
        }
        $from = static::coordinates($store);
        $to = static::coordinates($postcode);
        if ($from === null || $to === null) {
            return null;
        }

        return static::distanceMiles($from[0], $from[1], $to[0], $to[1]);
    }

    /**
     * Eligibility check with a shopper-facing message.
     *
     * @return array{ok: bool, message: string, distance: float|null}
     */
    public static function check(?string $postcode): array
    {
        $store = static::storePostcode();
        if ($store === null) {
            return ['ok' => true, 'message' => 'Good news — we deliver to '.trim((string) $postcode).'.', 'distance' => null];
        }

        $code = trim((string) $postcode);
        if (! static::validFormat($code)) {
            return ['ok' => false, 'message' => 'Enter a valid UK postcode, e.g. LS1 1AA.', 'distance' => null];
        }

        $miles = static::distanceFromStore($code);
        if ($miles === null) {
            return ['ok' => false, 'message' => 'We could not verify '.$code.' — please check it and try again.', 'distance' => null];
        }

        $radius = static::radiusMiles();
        if ($miles <= $radius) {
            return ['ok' => true, 'message' => 'Good news — we deliver to '.$code.' ('.number_format($miles, 1).' miles away).', 'distance' => $miles];
        }

        return ['ok' => false, 'message' => 'Sorry — '.$code.' is '.number_format($miles, 1).' miles away; we deliver within '.number_format($radius, 1).' miles of our store.', 'distance' => $miles];
    }

    public static function serves(?string $postcode): bool
    {
        return static::check($postcode)['ok'];
    }
}
