<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Offer extends Model
{
    protected $fillable = ['name', 'starts_at', 'ends_at', 'status', 'sort_order'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function bogos(): HasMany
    {
        return $this->hasMany(BogoOffer::class);
    }

    public function flashes(): HasMany
    {
        return $this->hasMany(FlashSale::class);
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(BundleOffer::class);
    }

    /** Live right now (status + window). */
    public function isLive(?Carbon $at = null): bool
    {
        if (! $this->status) {
            return false;
        }
        $at = $at ?? now();
        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($at)) {
            return false;
        }

        return true;
    }

    public function itemCount(): int
    {
        return $this->bogos()->count() + $this->flashes()->count() + $this->bundles()->count();
    }
}
