<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockAlert;

class StockWatchController extends Controller
{
    public function index()
    {
        $outOfStock = ProductVariant::with(['product:id,name,slug', 'values.group'])
            ->where(function ($q) {
                $q->where('in_stock', false)->orWhere('status', false);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(25, ['*'], 'oos');
        $variantIds = $outOfStock->getCollection()->pluck('id');
        $alertCounts = StockAlert::where('is_sent', false)
            ->whereIn('product_variant_id', $variantIds)
            ->selectRaw('product_variant_id, COUNT(*) as c')
            ->groupBy('product_variant_id')
            ->pluck('c', 'product_variant_id');

        $expiring = ProductVariant::with(['product:id,name,slug'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(14))
            ->orderBy('expires_at')
            ->paginate(25, ['*'], 'exp');

        return view('admin.stock-watch.index', compact('outOfStock', 'alertCounts', 'expiring'));
    }
}
