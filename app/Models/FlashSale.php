<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FlashSale extends Model
{
    protected $fillable = [
        'offer_id', 'product_id', 'product_variant_id', 'promo_price',
        'starts_at', 'ends_at', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'promo_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => 'boolean',
            'sort_order' => 'integer',
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

    /** Parent campaign — its window and switch govern attached rows. */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /** Live right now (status + window + parent campaign when attached). Flash without an end is just an offer price. */
    public function isLive(?Carbon $at = null): bool
    {
        if (! $this->status) {
            return false;
        }
        $at = $at ?? now();
        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }
        if (! $this->ends_at || $this->ends_at->lt($at)) {
            return false;
        }
        $parent = $this->offer;
        if ($parent && ! $parent->isLive($at)) {
            return false;
        }

        return true;
    }

    /** All live rows (promo tables stay small). */
    public static function liveAll(): Collection
    {
        return static::with('offer')->where('status', true)->orderBy('sort_order')->orderBy('id')->get()->filter(fn ($s) => $s->isLive())->values();
    }

    /**
     * Preloaded map for a cycle: ['variants' => [variantId => ['price', 'ends']], 'products' => [productId => [...]].
     * Lowest price wins on overlap; variant-specific beats product-wide.
     */
    public static function liveMap(?Collection $live = null): array
    {
        $map = ['variants' => [], 'products' => []];
        foreach ($live ?? static::liveAll() as $sale) {
            $entry = ['price' => (float) $sale->promo_price, 'ends' => $sale->ends_at];
            if ($sale->product_variant_id) {
                $id = $sale->product_variant_id;
                if (! isset($map['variants'][$id]) || $entry['price'] < $map['variants'][$id]['price']) {
                    $map['variants'][$id] = $entry;
                }
            } else {
                $id = $sale->product_id;
                if (! isset($map['products'][$id]) || $entry['price'] < $map['products'][$id]['price']) {
                    $map['products'][$id] = $entry;
                }
            }
        }

        return $map;
    }

    /** Flash entry for a variant (specific beats product-wide), or null. */
    public static function priceFor(?int $variantId, ?int $productId, ?array $map = null): ?array
    {
        $map = $map ?? static::liveMap();

        return $map['variants'][$variantId] ?? $map['products'][$productId] ?? null;
    }
}
