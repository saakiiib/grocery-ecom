<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $guarded = [];

    protected static array $statusCache = [];

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
        if (! $this->from_slug) {
            return null;
        }

        return static::$statusCache['from:'.$this->from_slug] ??= OrderStatus::where('slug', $this->from_slug)->first();
    }

    public function toStatus(): ?OrderStatus
    {
        return static::$statusCache['to:'.$this->to_slug] ??= OrderStatus::where('slug', $this->to_slug)->first();
    }

    /** Drop the in-process memo — the test suite calls this between tests. */
    public static function flushCache(): void
    {
        static::$statusCache = [];
    }
}
