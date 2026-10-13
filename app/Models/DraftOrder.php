<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftOrder extends Model
{
    protected $fillable = ['draft_number', 'user_id', 'payload', 'payment_method', 'payment_reference', 'status'];

    protected $casts = ['payload' => 'array'];

    /** Gateway helpers use the same order-shaped fields for drafts and orders. */
    public function getNumberAttribute(): string
    {
        return $this->draft_number;
    }

    public function getTotalAttribute(): float
    {
        return (float) data_get($this->payload, 'total', 0);
    }
}
