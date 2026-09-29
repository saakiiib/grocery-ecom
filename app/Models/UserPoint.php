<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoint extends Model
{
    protected $guarded = [];

    public const EARN = 'earn';

    public const REDEEM = 'redeem';

    public const REVERSAL = 'reversal';

    public const ADJUST = 'adjust';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Current spendable balance for a user. */
    public static function balance(int $userId): int
    {
        return (int) static::where('user_id', $userId)->sum('points');
    }

    /** Points earned per £1 of goods (setting, default 1). */
    public static function perPound(): float
    {
        return (float) (Setting::get('points_per_pound', '1') ?: 1);
    }

    /** £ value of a single point (setting, default £0.01 → 100 pts = £1). */
    public static function value(): float
    {
        return (float) (Setting::get('points_value', '0.01') ?: 0.01);
    }

    /** Minimum points per redemption (setting, default 100). */
    public static function minRedeem(): int
    {
        return (int) (Setting::get('points_min_redeem', '100') ?: 100);
    }
}
