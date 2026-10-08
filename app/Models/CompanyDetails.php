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
}
