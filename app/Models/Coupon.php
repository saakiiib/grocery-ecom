<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'expires_at' => 'datetime',
            'status' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function usesCount(): int
    {
        return $this->orders()->count();
    }

    public function usesCountFor(int $userId): int
    {
        return $this->orders()->where('user_id', $userId)->count();
    }

    /**
     * Validate this coupon for a shopper and goods subtotal.
     *
     * @return array{ok: bool, message?: string, discount?: float}
     */
    public function checkFor(?int $userId, float $subtotal): array
    {
        if (! $this->status) {
            return ['ok' => false, 'message' => 'That coupon is no longer active.'];
        }
        if (! $userId) {
            return ['ok' => false, 'message' => 'Sign in to use coupons.'];
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['ok' => false, 'message' => 'That coupon expired on '.$this->expires_at->format('j M Y').'.'];
        }
        if ($subtotal < (float) $this->min_order) {
            return ['ok' => false, 'message' => 'That coupon needs a £'.number_format($this->min_order, 2).' order.'];
        }
        if ($this->max_uses !== null && $this->usesCount() >= $this->max_uses) {
            return ['ok' => false, 'message' => 'That coupon has all been used up.'];
        }
        if ($this->usesCountFor($userId) >= $this->max_per_user) {
            return ['ok' => false, 'message' => 'You have already used that coupon.'];
        }

        $discount = $this->type === 'fixed'
            ? min((float) $this->value, $subtotal)
            : round($subtotal * (float) $this->value / 100, 2);

        return ['ok' => true, 'discount' => max(0.0, $discount)];
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }
}
