<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/** Products this shopper bought before (non-cancelled orders, most recent first). */
class BuyAgain
{
    public static function productIdsFor(int $userId, int $limit = 8): array
    {
        $orderIds = Order::where('user_id', $userId)
            ->where('status_slug', '!=', 'cancelled')
            ->orderByDesc('id')
            ->limit(40)
            ->pluck('id');
        if ($orderIds->isEmpty()) {
            return [];
        }
        $ids = OrderItem::whereIn('order_id', $orderIds)
            ->whereNotNull('product_id')
            ->orderByDesc('id')
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take($limit)
            ->values()
            ->all();

        return $ids;
    }

    public static function productsFor(int $userId, int $limit = 8): Collection
    {
        $ids = static::productIdsFor($userId, $limit);
        if ($ids === []) {
            return collect();
        }
        $byId = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()
            ->where('status', true)
            ->whereIn('id', $ids)
            ->whereHas('variants', fn ($q) => $q->where('status', true)->where('in_stock', true))
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $byId->get($id))->filter()->values();
    }
}
