<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
    }

    public function itemCount(): int
    {
        return $this->items->sum('qty');
    }

    public function paymentLabel(): string
    {
        return match ($this->payment_method) {
            'stripe' => 'Card (Stripe)',
            'paypal' => 'PayPal',
            default => 'Cash on Delivery',
        };
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Move the order to a new status, recording history. Returns the history row,
     * or null when the status did not change.
     */
    public function changeStatus(string $toSlug, ?int $changedBy = null, ?string $note = null): ?OrderStatusHistory
    {
        $to = OrderStatus::where('slug', $toSlug)->where('is_active', true)->firstOrFail();

        if ($this->status_slug === $to->slug) {
            return null;
        }

        $from = $this->status_slug;
        $this->status_id = $to->id;
        $this->status_slug = $to->slug;
        $this->save();

        return $this->histories()->create([
            'from_slug' => $from,
            'to_slug' => $to->slug,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }
}
