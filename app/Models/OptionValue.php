<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionValue extends Model
{
    protected $fillable = [
        'option_group_id', 'label', 'slug', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_values', 'option_value_id', 'variant_id');
    }
}
