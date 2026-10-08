<?php

namespace App\Support;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * "Frequently bought together" from real order history.
 * Cancelled orders never count. Empty history yields nothing —
 * callers fall back to category neighbours.
 */
class PairsWell
{
    /** Ordered product ids most often co-bought with the given products. */
    public static function productIdsFor(array $ids, int $limit = 4): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $orderIds = OrderItem::whereIn('product_id', $ids)
            ->whereHas('order', fn ($q) => $q->where('status_slug', '!=', 'cancelled'))
            ->pluck('order_id')
            ->unique()
            ->values();
        if ($orderIds->isEmpty()) {
            return [];
        }

        return OrderItem::whereIn('order_id', $orderIds)
            ->whereNotIn('product_id', $ids)
            ->whereHas('order', fn ($q) => $q->where('status_slug', '!=', 'cancelled'))
            ->selectRaw('product_id, SUM(qty) as units')
            ->groupBy('product_id')
            ->orderByDesc('units')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Live, in-stock product models in co-buy order. */
    public static function productsFor(array $ids, int $limit = 4): Collection
    {
        $ordered = static::productIdsFor($ids, $limit);
        if ($ordered === []) {
            return collect();
        }
        $models = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()
            ->where('status', true)
            ->whereIn('id', $ordered)
            ->whereHas('variants', fn ($q) => $q->where('status', true)->where('in_stock', true))
            ->get()
            ->keyBy('id');

        return collect($ordered)->map(fn ($id) => $models->get($id))->filter()->values();
    }
}
