<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    protected static array $memo = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::$memo[$key] ??= Cache::rememberForever('setting.'.$key, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        unset(static::$memo[$key]);
        Cache::forget('setting.'.$key);
    }

    /** Drop the in-process memo (plus its cache rows) — the test suite calls this between tests. */
    public static function flushMemo(): void
    {
        foreach (array_keys(static::$memo) as $key) {
            Cache::forget('setting.'.$key);
        }
        static::$memo = [];
    }

    /** Shop money rules with safe numeric fallbacks. */
    public static function money(string $key, float $default): float
    {
        return (float) (static::get($key, (string) $default) ?? $default);
    }
}
