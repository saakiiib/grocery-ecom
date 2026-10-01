<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Favourited product ids for a user, memoized per request (guests: empty). */
    public static function idsFor(?int $userId): array
    {
        static $cache = [];

        if (! $userId) {
            return [];
        }
        if (! array_key_exists($userId, $cache)) {
            $cache[$userId] = static::where('user_id', $userId)->pluck('product_id')->all();
        }

        return $cache[$userId];
    }

    public static function isFavourited(?int $userId, int $productId): bool
    {
        return in_array($productId, static::idsFor($userId), true);
    }
}
