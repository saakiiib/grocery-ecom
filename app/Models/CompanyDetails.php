<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanyDetails extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('company_details'));
    }

    /** Single cached row shared by every view — one query per request at most. */
    public static function cached(): self
    {
        return Cache::remember('company_details', 3600, fn () => static::firstOrCreate());
    }
}
