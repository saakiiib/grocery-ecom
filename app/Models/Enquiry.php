<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'postcode', 'topic', 'product_id',
        'config_summary', 'guide_price', 'message', 'source_page', 'status',
    ];

    protected function casts(): array
    {
        return ['guide_price' => 'decimal:2', 'status' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
