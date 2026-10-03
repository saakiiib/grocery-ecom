<?php

namespace App\Models;

use App\Mail\OrderDelivered;
use App\Mail\OrderStatusUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

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
            'coupon_discount' => 'decimal:2',
            'points_discount' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
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

    /** Billing snapshot, falling back to the delivery snapshot for legacy orders. */
    public function billTo(): array
    {
        return [
            'name' => $this->billing_name ?: $this->name,
            'phone' => $this->billing_phone ?: $this->phone,
            'address' => $this->billing_address ?: $this->address,
            'city' => $this->billing_city ?: $this->city,
            'postcode' => $this->billing_postcode ?: $this->postcode,
        ];
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'partially_refunded'], true);
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Paid',
            'partially_refunded' => 'Partially refunded',
            'refunded' => 'Refunded',
            default => 'Unpaid',
        };
    }

    public function substitutionLabel(): string
    {
        return match ($this->substitution_preference) {
            'substitute' => 'Substitute with a similar item',
            'refund' => 'Remove it and refund me',
            'call' => 'Call me first',
            default => '—',
        };
    }

    /** Still refundable online: paid online orders minus what was already refunded. */
    public function refundableAmount(): float
    {
        if (! in_array($this->payment_status, ['paid', 'partially_refunded'], true)) {
            return 0.0;
        }
        if (! in_array($this->payment_method, ['stripe', 'paypal'], true)) {
            return 0.0;
        }

        return max(0.0, round((float) $this->total - (float) $this->refunded_amount, 2));
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

        $history = $this->histories()->create([
            'from_slug' => $from,
            'to_slug' => $to->slug,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);

        $this->settlePoints($to->slug);

        if ($to->slug === 'delivered') {
            static::sendMail($this->receiptEmail(), new OrderDelivered($this));
        } elseif (in_array($to->slug, ['packed', 'out_for_delivery', 'cancelled'], true)) {
            // confirmed is covered by OrderPlaced; every other move gets its own update.
            static::sendMail($this->receiptEmail(), new OrderStatusUpdated($this, $to->slug));
        }

        return $history;
    }

    /** Checkout email, else the shopper's account email — null when unknown. */
    public function receiptEmail(): ?string
    {
        return $this->email ?: $this->user?->email;
    }

    /** Fire-and-forget mail — a down mailer must never break orders. */
    public static function sendMail(?string $to, Mailable $mail): void
    {
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Points settle on lifecycle moves, idempotently:
     * delivered → award earn points (registered shoppers only);
     * cancelled → give back anything redeemed on this order.
     */
    public function settlePoints(string $toSlug): void
    {
        if ($toSlug === 'delivered' && $this->user_id && $this->points_earned === 0) {
            $earned = (int) floor(max(0, (float) $this->subtotal - (float) $this->points_discount) * UserPoint::perPound());
            if ($earned > 0) {
                $this->points()->create([
                    'user_id' => $this->user_id,
                    'points' => $earned,
                    'type' => UserPoint::EARN,
                    'description' => 'Earned on '.$this->number,
                ]);
                $this->points_earned = $earned;
                $this->save();
            }
        }

        if ($toSlug === 'cancelled' && $this->user_id && $this->points_redeemed > 0
            && ! $this->points()->where('type', UserPoint::REVERSAL)->exists()) {
            $this->points()->create([
                'user_id' => $this->user_id,
                'points' => $this->points_redeemed,
                'type' => UserPoint::REVERSAL,
                'description' => 'Refunded from cancelled '.$this->number,
            ]);
        }
    }

    public function points(): HasMany
    {
        return $this->hasMany(UserPoint::class);
    }
}
