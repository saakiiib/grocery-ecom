<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BogoOffer extends Model
{
    protected $fillable = [
        'offer_id', 'product_id', 'product_variant_id', 'buy_qty', 'free_qty',
        'starts_at', 'ends_at', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'buy_qty' => 'integer',
            'free_qty' => 'integer',
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

    /** Live right now (status + own window + parent campaign when attached). */
    public function isLive(?Carbon $at = null): bool
    {
        if (! $this->status) {
            return false;
        }
        $at = $at ?? now();
        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($at)) {
            return false;
        }
        $parent = $this->offer;
        if ($parent && ! $parent->isLive($at)) {
            return false;
        }

        return true;
    }

    public function label(): string
    {
        return 'Buy '.$this->buy_qty.' Get '.$this->free_qty.' FREE';
    }

    /** Free units earned for this quantity (same-variant counting). */
    public function freeUnitsFor(int $qty): int
    {
        $group = $this->buy_qty + $this->free_qty;
        if ($group <= 0 || $qty < $group) {
            return 0;
        }

        return (int) floor($qty / $group) * $this->free_qty;
    }

    /** All live offers (promo tables stay small; callers reuse per cycle). */
    public static function liveAll(): Collection
    {
        return static::with('offer')->where('status', true)->orderBy('sort_order')->orderBy('id')->get()->filter(fn ($o) => $o->isLive())->values();
    }

    /** Best live offer for a variant (specific beats product-wide). */
    public static function forVariant(?int $variantId, ?int $productId): ?self
    {
        return static::matchIn(static::liveAll(), $variantId, $productId);
    }

    /** Same match against a preloaded live collection (one query per cycle). */
    public static function matchIn(Collection $live, ?int $variantId, ?int $productId): ?self
    {
        $pool = $live->filter(fn ($o) => $o->product_id === $productId
            && ($o->product_variant_id === null || $o->product_variant_id === $variantId))->values();
        if ($pool->isEmpty()) {
            return null;
        }

        return $pool->sortByDesc(fn ($o) => $o->product_variant_id === null ? 0 : 1)->first();
    }
}
