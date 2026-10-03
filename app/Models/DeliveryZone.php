<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function postcodes(): HasMany
    {
        return $this->hasMany(DeliveryZonePostcode::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Normalize for prefix matching: uppercase, no spaces. */
    public static function normalize(string $postcode): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $postcode));
    }

    /**
     * Find the first active zone serving this postcode (longest prefix wins).
     */
    public static function matching(?string $postcode): ?self
    {
        $code = static::normalize($postcode ?? '');
        if ($code === '') {
            return null;
        }

        $rows = DeliveryZonePostcode::query()
            ->whereHas('zone', fn ($q) => $q->active())
            ->orderByRaw('LENGTH(prefix) DESC')
            ->get(['delivery_zone_id', 'prefix']);

        foreach ($rows as $row) {
            if (str_starts_with($code, strtoupper((string) preg_replace('/\s+/', '', $row->prefix)))) {
                return static::find($row->delivery_zone_id);
            }
        }

        return null;
    }

    /** True when no zones are configured (open delivery) or one matches. */
    public static function serves(?string $postcode): bool
    {
        if (! static::exists()) {
            return true;
        }

        return static::matching($postcode) !== null;
    }
}
