<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepeatSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'next_run_at' => 'date',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
