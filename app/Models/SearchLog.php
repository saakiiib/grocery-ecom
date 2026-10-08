<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $guarded = [];

    public static function record(string $query, int $hits): void
    {
        $query = mb_substr(trim(mb_strtolower($query)), 0, 120);
        if (mb_strlen($query) < 2) {
            return;
        }
        try {
            static::create(['query' => $query, 'hits' => $hits]);
        } catch (\Throwable $e) {
            // Logging never breaks search.
        }
    }

    public static function trending(int $limit = 6, int $days = 30): array
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query, COUNT(*) as searches, MAX(hits) as hits')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit($limit)
            ->pluck('query')
            ->all();
    }
}
