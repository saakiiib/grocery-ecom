<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fromStatus(): ?OrderStatus
    {
        return $this->from_slug ? OrderStatus::where('slug', $this->from_slug)->first() : null;
    }

    public function toStatus(): ?OrderStatus
    {
        return OrderStatus::where('slug', $this->to_slug)->first();
    }
}
