<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A retired product slug that should 301 to the product's current URL.
 * Rows are written automatically when a product slug changes (rename).
 */
class ProductRedirect extends Model
{
    protected $fillable = ['product_id', 'old_slug'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
