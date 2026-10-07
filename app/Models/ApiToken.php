<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Issue a token; only the SHA-256 hash is stored. Returns the plain token. */
    public static function issue(User $user, string $name = 'mobile'): string
    {
        $plain = Str::random(60);
        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
        ]);

        return $plain;
    }
}
