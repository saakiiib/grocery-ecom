<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DeliverySlot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function label(): string
    {
        return $this->name.' ('.$this->starts_at.' – '.$this->ends_at.')';
    }

    /** Active slots in display order. */
    public static function ordered(): Collection
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    /** True when this slot can no longer be booked for the given date (cutoff passed today). */
    public function cutoffPassed(?string $date = null): bool
    {
        $date ??= now()->format('Y-m-d');
        if ($date !== now()->format('Y-m-d')) {
            return false;
        }

        return (int) now()->format('H') >= (int) $this->cutoff_hour;
    }

    /** Slots bookable for a date — past-cutoff slots drop out when the date is today. */
    public static function bookableForDate(?string $date = null): Collection
    {
        $date ??= now()->format('Y-m-d');

        return static::ordered()->reject(fn ($slot) => $slot->cutoffPassed($date))->values();
    }

    /**
     * Bookable delivery dates: the next $days days, skipping today when every
     * slot's cutoff hour has already passed. Labels carry Today/Tomorrow.
     * Returns [Y-m-d => “Today · Mon 30 Sep”].
     *
     * @return array<string, string>
     */
    public static function bookableDates(int $days = 5): array
    {
        $dates = [];
        $now = now();
        $slots = static::ordered();
        $latestCutoff = (int) ($slots->max('cutoff_hour') ?? 20);

        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->addDays($i);
            if ($i === 0 && (int) $now->format('H') >= $latestCutoff) {
                continue;
            }
            $label = $date->format('D j M');
            if ($date->isToday()) {
                $label = 'Today · '.$label;
            } elseif ($date->isTomorrow()) {
                $label = 'Tomorrow · '.$label;
            }
            $dates[$date->format('Y-m-d')] = $label;
        }

        return $dates;
    }

    /**
     * Same-day status for the checkout message: whether today is still
     * bookable, the latest order-by hour, and the earliest date label.
     *
     * @return array{today_available: bool, order_by_hour: int, earliest: string}
     */
    public static function todayStatus(): array
    {
        $slots = static::ordered();
        $hour = (int) now()->format('H');
        $open = $slots->reject(fn ($slot) => $hour >= (int) $slot->cutoff_hour)->values();
        $dates = static::bookableDates();

        return [
            'today_available' => $open->isNotEmpty(),
            'order_by_hour' => (int) ($slots->max('cutoff_hour') ?? 20),
            'earliest' => $dates ? reset($dates) : '',
        ];
    }
}
