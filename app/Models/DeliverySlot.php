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

    /**
     * Bookable delivery dates: the next $days days, skipping today when every
     * slot's cutoff hour has already passed. Returns [Y-m-d => “Mon 30 Sep”].
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
            $dates[$date->format('Y-m-d')] = $date->format('D j M');
        }

        return $dates;
    }
}
