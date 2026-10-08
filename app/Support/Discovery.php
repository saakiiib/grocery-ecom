<?php

namespace App\Support;

use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Discovery rails for the homepage: new arrivals, bestsellers (all-time
 * units from non-cancelled orders) and trending (recent sales velocity).
 * Empty history yields empty collections — callers decide fallbacks.
 */
class Discovery
{
    public static function newIn(Collection $products, int $limit = 4): Collection
    {
        return $products->where('status', true)->sortByDesc('created_at')->take($limit)->values();
    }

    /** Product ids ordered by units sold, optionally within the last $days. */
    public static function rankedIds(?int $days = null, int $limit = 8): array
    {
        $query = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('status_slug', '!=', 'cancelled'))
            ->whereNotNull('product_id');
        if ($days !== null) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        return $query->selectRaw('product_id, SUM(qty) as units')
            ->groupBy('product_id')
            ->orderByDesc('units')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function bestsellers(Collection $products, int $limit = 4): Collection
    {
        return static::orderedByIds($products, static::rankedIds(null, $limit * 2), $limit);
    }

    public static function trending(Collection $products, int $days = 14, int $limit = 4): Collection
    {
        return static::orderedByIds($products, static::rankedIds($days, $limit * 2), $limit);
    }

    private static function orderedByIds(Collection $products, array $ids, int $limit): Collection
    {
        if ($ids === []) {
            return collect();
        }
        $byId = $products->where('status', true)->keyBy('id');

        return collect($ids)->map(fn ($id) => $byId->get($id))->filter()->take($limit)->values();
    }
}
