<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTechSpec extends Model
{
    protected $fillable = ['product_id', 'label', 'value', 'highlight', 'sort_order'];

    protected function casts(): array
    {
        return ['highlight' => 'boolean', 'sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
