<?php

namespace App\Models;

use App\Http\Controllers\BagController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingList extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    /** Add every still-available line to the session bag. Returns [added, skipped]. */
    public function addAllToBag(): array
    {
        $bag = BagController::bag();
        $added = 0;
        $skipped = [];
        foreach ($this->items()->with('variant.product')->get() as $item) {
            $variant = $item->variant;
            if (! $variant || ! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                $skipped[] = $variant?->product?->name ?? 'Unavailable item';

                continue;
            }
            $bag[$variant->id] = min(($bag[$variant->id] ?? 0) + $item->qty, 99);
            $added++;
        }
        session()->put(BagController::SESSION_KEY, $bag);

        return [$added, $skipped];
    }
}
