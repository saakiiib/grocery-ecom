<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    public const GROUPS = ['config', 'finish', 'glazing', 'upgrade'];

    protected $fillable = [
        'product_id', 'group', 'name', 'subtitle', 'price_delta',
        'swatch_color', 'is_default', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' => 'decimal:2',
            'is_default' => 'boolean',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
