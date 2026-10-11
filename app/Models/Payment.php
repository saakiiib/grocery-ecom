<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per online payment attempt. Attempt history is never overwritten:
 * a retry creates a new row, so disputes and double-charge claims stay
 * traceable. Card and wallet credentials never land here — gateway
 * references only.
 */
class Payment extends Model
{
    public const PENDING = 'pending';

    public const SUCCEEDED = 'succeeded';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Record a fresh gateway attempt (retries create new rows — history is never overwritten). */
    public static function attempt(Order $order, string $reference): Payment
    {
        return static::create([
            'order_id' => $order->id,
            'method' => $order->payment_method,
            'reference' => $reference,
            'amount' => $order->total,
            'currency' => 'GBP',
            'status' => self::PENDING,
        ]);
    }

    /**
     * Move an attempt to its final state. Matches by gateway reference first,
     * falling back to the newest pending row (PayPal swaps its reference from
     * order id to capture id at capture time).
     */
    public static function settle(Order $order, ?string $reference, string $status): ?Payment
    {
        $payment = $reference
            ? static::where('order_id', $order->id)->where('reference', $reference)->first()
            : null;
        if (! $payment) {
            $payment = static::where('order_id', $order->id)
                ->where('method', $order->payment_method)
                ->where('status', self::PENDING)
                ->orderByDesc('id')->first();
        }
        if (! $payment) {
            return null;
        }
        $payment->status = $status;
        if ($reference) {
            $payment->reference = $reference;
        }
        if ($status === self::SUCCEEDED) {
            $payment->paid_at = $payment->paid_at ?? now();
        }
        $payment->save();

        return $payment;
    }

    /** Track gateway refunds against the succeeded attempt (per-attempt audit). */
    public static function recordRefund(Order $order, float $amount): void
    {
        $payment = static::where('order_id', $order->id)
            ->where('status', self::SUCCEEDED)->orderByDesc('id')->first();
        if ($payment) {
            $payment->refunded_amount = round((float) $payment->refunded_amount + $amount, 2);
            $payment->save();
        }
    }

    /** Abandoned orders release their pending attempts (sweeper + manual cancel). */
    public static function cancelPending(Order $order): void
    {
        static::where('order_id', $order->id)->where('status', self::PENDING)
            ->update(['status' => self::CANCELLED]);
    }

    public function label(): string
    {
        return match ($this->method) {
            'stripe' => 'Card (Stripe)',
            'google_pay' => 'Google Pay',
            'apple_pay' => 'Apple Pay',
            'paypal' => 'PayPal',
            default => ucfirst((string) $this->method),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SUCCEEDED => 'Succeeded',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            default => 'Pending',
        };
    }
}
