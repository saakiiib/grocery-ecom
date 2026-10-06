<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class ProductVariant extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('egf_catalog'));
        static::deleted(fn () => Cache::forget('egf_catalog'));
    }

    protected $fillable = [
        'product_id', 'sku', 'mrp', 'offer_price', 'image',
        'in_stock', 'is_default', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'mrp' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'in_stock' => 'boolean',
            'is_default' => 'boolean',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): BelongsToMany
    {
        return $this->belongsToMany(OptionValue::class, 'product_variant_values', 'variant_id', 'option_value_id');
    }

    /** Effective selling price: offer when set and below mrp, else mrp. */
    public function sellingPrice(): float
    {
        if ($this->offer_price !== null && (float) $this->offer_price < (float) $this->mrp) {
            return (float) $this->offer_price;
        }

        return (float) $this->mrp;
    }

    /** Human summary of the combination, e.g. "1kg / Thick / Fat On". */
    public function combinationLabel(): string
    {
        if (! $this->relationLoaded('values')) {
            $this->load('values.group');
        }

        return $this->values->sortBy('group.sort_order')->map(fn ($v) => $v->label)->values()->join(' / ');
    }
}
