<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'subtotal' => 'decimal:2',
            'reminded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
