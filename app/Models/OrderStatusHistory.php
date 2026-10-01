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
        static $cache = [];

        if (! $this->from_slug) {
            return null;
        }

        return $cache['from:'.$this->from_slug] ??= OrderStatus::where('slug', $this->from_slug)->first();
    }

    public function toStatus(): ?OrderStatus
    {
        static $cache = [];

        return $cache['to:'.$this->to_slug] ??= OrderStatus::where('slug', $this->to_slug)->first();
    }
}
