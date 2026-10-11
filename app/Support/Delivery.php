<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Two-speed delivery: Today vs Standard (next working day).
 * One daily cutoff from Shop Settings — no per-slot timetables.
 */
class Delivery
{
    public const SPEED_TODAY = 'today';

    public const SPEED_STANDARD = 'standard';

    public static function speeds(): array
    {
        return [self::SPEED_TODAY, self::SPEED_STANDARD];
    }

    /** Daily cutoff as HH:MM (24h). Last moment a Today order can be placed. */
    public static function cutoffTime(): string
    {
        $raw = (string) (Setting::get('delivery_cutoff_time', '20:00') ?? '20:00');
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $raw)) {
            return '20:00';
        }

        return $raw;
    }

    public static function todayFee(): float
    {
        return Setting::money('delivery_today_fee', 2.99);
    }

    public static function standardFee(): float
    {
        return Setting::money('delivery_standard_fee', 2.99);
    }

    /** True while now is strictly before today's cutoff. */
    public static function todayAvailable(?\DateTimeInterface $at = null): bool
    {
        $now = $at ? now()->setTime((int) $at->format('H'), (int) $at->format('i')) : now();

        return $now->format('H:i') < self::cutoffTime();
    }

    public static function todayDate(): string
    {
        return now()->format('Y-m-d');
    }

    public static function standardDate(): string
    {
        return now()->addDay()->format('Y-m-d');
    }

    public static function dateFor(string $speed): string
    {
        return $speed === self::SPEED_TODAY ? self::todayDate() : self::standardDate();
    }

    /** Human date chip: "Today · Sun 11 Oct" / "Next working day · Mon 12 Oct". */
    public static function dateLabelFor(string $speed): string
    {
        if ($speed === self::SPEED_TODAY) {
            return 'Today · '.now()->format('D j M');
        }

        return 'Next working day · '.now()->addDay()->format('D j M');
    }

    /** Stored on orders.delivery_slot_label (kept column, new meaning). */
    public static function labelFor(string $speed, bool $pickup = false): string
    {
        if ($pickup) {
            return $speed === self::SPEED_TODAY ? 'Collection Today' : 'Collection Next Working Day';
        }

        return $speed === self::SPEED_TODAY ? 'Today Delivery' : 'Standard Delivery';
    }

    /** Fee for a speed — pickup is always free, free-over-£X frees both speeds. */
    public static function feeFor(string $speed, float $subtotal, bool $pickup = false): float
    {
        if ($pickup || $subtotal >= Setting::money('delivery_free_over', 50.00)) {
            return 0.0;
        }

        return $speed === self::SPEED_TODAY ? self::todayFee() : self::standardFee();
    }

    /**
     * Checkout options for the current bag: today (cutoff-gated) + standard.
     *
     * @return array<int, array{speed: string, date: string, date_label: string, label: string, fee: float, available: bool}>
     */
    public static function options(float $subtotal, bool $pickup = false): array
    {
        $todayOpen = self::todayAvailable();

        return [
            [
                'speed' => self::SPEED_TODAY,
                'date' => self::todayDate(),
                'date_label' => self::dateLabelFor(self::SPEED_TODAY),
                'label' => self::labelFor(self::SPEED_TODAY, $pickup),
                'fee' => self::feeFor(self::SPEED_TODAY, $subtotal, $pickup),
                'available' => $todayOpen,
            ],
            [
                'speed' => self::SPEED_STANDARD,
                'date' => self::standardDate(),
                'date_label' => self::dateLabelFor(self::SPEED_STANDARD),
                'label' => self::labelFor(self::SPEED_STANDARD, $pickup),
                'fee' => self::feeFor(self::SPEED_STANDARD, $subtotal, $pickup),
                'available' => true,
            ],
        ];
    }
}
