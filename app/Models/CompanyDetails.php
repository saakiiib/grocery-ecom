<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanyDetails extends Model
{
    protected $guarded = [];

    protected static ?self $memo = null;

    protected static function booted(): void
    {
        static::saved(function () {
            static::$memo = null;
            Cache::forget('company_details');
        });
    }

    /** Single cached row shared by every view — one query per request at most. */
    public static function cached(): self
    {
        return static::$memo ??= Cache::remember('company_details', 3600, fn () => static::firstOrCreate());
    }

    /** Drop the in-process memo — the test suite calls this between tests. */
    public static function flushMemo(): void
    {
        static::$memo = null;
        Cache::forget('company_details');
    }

    /**
     * Google Maps embed HTML stored in the admin. Inject a title when the
     * pasted iframe does not already provide one.
     */
    public function mapEmbed(): string
    {
        $html = trim((string) $this->google_map);

        if ($html === '' || ! preg_match('/<iframe\b/i', $html)) {
            return $html;
        }

        if (preg_match('/<iframe\b[^>]*\stitle\s*=/i', $html)) {
            return $html;
        }

        $title = htmlspecialchars(($this->company_name ?: 'Store').' location on Google Maps', ENT_QUOTES, 'UTF-8');

        return preg_replace('/<iframe\b/i', '<iframe title="'.$title.'"', $html, 1) ?? $html;
    }
}
