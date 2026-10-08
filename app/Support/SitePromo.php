<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;

/**
 * Announcement bar + welcome promo modal content.
 *
 * Stored as plain Setting keys so no migration is needed and the
 * existing Shop Settings screen stays the single CMS for them.
 */
class SitePromo
{
    public static function announcement(): ?array
    {
        if (Setting::get('announcement_enabled', '0') !== '1') {
            return null;
        }
        $text = trim((string) (Setting::get('announcement_text', '') ?? ''));
        if ($text === '') {
            return null;
        }

        return [
            'text' => $text,
            'link_text' => trim((string) (Setting::get('announcement_link_text', '') ?? '')),
            'link_url' => trim((string) (Setting::get('announcement_link_url', '') ?? '')),
        ];
    }

    /**
     * Recent-order social proof: anonymized (first name + city + item),
     * last 7 days, never cancelled. Null when disabled or nothing recent.
     */
    public static function socialProof(int $limit = 8): ?array
    {
        if (Setting::get('social_proof_enabled', '1') !== '1') {
            return null;
        }
        $orders = Order::with('items')
            ->where('status_slug', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->take($limit)
            ->get();
        if ($orders->isEmpty()) {
            return null;
        }

        $slugs = Product::whereIn('id', $orders->flatMap(fn ($o) => $o->items->pluck('product_id'))->filter()->unique()->values())
            ->pluck('slug', 'id');

        return $orders->map(function ($order) use ($slugs) {
            $item = $order->items->first();
            $slug = $item && $item->product_id ? $slugs->get($item->product_id) : null;

            return [
                'name' => trim(explode(' ', (string) $order->name)[0] ?: 'A shopper'),
                'city' => (string) $order->city,
                'item' => $item?->product_name ?? 'groceries',
                'ago' => $order->created_at->diffForHumans(),
                'url' => $slug ? route('product.show', $slug) : route('shop'),
            ];
        })->values()->all();
    }

    public static function promo(): ?array
    {
        if (Setting::get('promo_enabled', '0') !== '1') {
            return null;
        }
        $title = trim((string) (Setting::get('promo_title', '') ?? ''));
        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => trim((string) (Setting::get('promo_subtitle', '') ?? '')),
            'coupon' => trim((string) (Setting::get('promo_coupon', '') ?? '')),
            'button_text' => trim((string) (Setting::get('promo_button_text', '') ?? '')),
            'button_url' => trim((string) (Setting::get('promo_button_url', '') ?? '')),
        ];
    }
}
