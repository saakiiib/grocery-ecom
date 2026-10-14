<?php

namespace App\Support;

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
