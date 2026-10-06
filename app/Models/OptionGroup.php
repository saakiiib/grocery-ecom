<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class OptionGroup extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('egf_catalog'));
        static::deleted(fn () => Cache::forget('egf_catalog'));
    }

    protected $fillable = [
        'name', 'slug', 'type', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('sort_order');
    }

    public function activeValues(): HasMany
    {
        return $this->values()->where('status', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_option_group')
            ->withPivot('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_group')
            ->withPivot('sort_order');
    }
}
