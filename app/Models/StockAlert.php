<?php

namespace App\Models;

use App\Mail\StockAlertMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

class StockAlert extends Model
{
    public const TYPES = ['back_in_stock', 'price_drop'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
            'is_sent' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Effective shelf price for a variant (flash applied, never above mrp). */
    public static function effectivePrice(ProductVariant $variant): float
    {
        $price = (float) $variant->sellingPrice();
        $hit = FlashSale::priceFor($variant->id, $variant->product_id);
        if ($hit && $hit['price'] < $price) {
            $price = $hit['price'];
        }

        return round($price, 2);
    }

    /** Send every pending alert a restock or price drop now satisfies. */
    public static function fulfillFor(ProductVariant $variant): int
    {
        $variant->loadMissing('product');
        $inStock = (bool) $variant->in_stock && (bool) $variant->status
            && $variant->product && (bool) $variant->product->status;
        $price = static::effectivePrice($variant);

        $pending = static::where('is_sent', false)
            ->where('product_id', $variant->product_id)
            ->where(function ($q) use ($variant) {
                $q->where('product_variant_id', $variant->id)->orWhereNull('product_variant_id');
            })
            ->get()
            ->filter(fn ($alert) => $alert->type === 'back_in_stock'
                ? $inStock
                : ($inStock && $alert->target_price !== null && $price < (float) $alert->target_price));

        foreach ($pending as $alert) {
            Mail::to($alert->email)->send(new StockAlertMail($alert, $variant, $price));
            $alert->update(['is_sent' => true, 'sent_at' => now()]);
        }

        return $pending->count();
    }

    /** Fulfill across a whole product (variant edited, flash changed, made permanent). */
    public static function fulfillForProduct(int $productId): int
    {
        $sent = 0;
        foreach (ProductVariant::where('product_id', $productId)->get() as $variant) {
            $sent += static::fulfillFor($variant);
        }

        return $sent;
    }
}
