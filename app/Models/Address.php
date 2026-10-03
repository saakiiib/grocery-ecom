<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'label', 'name', 'phone', 'address', 'city', 'postcode',
        'is_default_delivery', 'is_default_billing',
    ];

    protected function casts(): array
    {
        return [
            'is_default_delivery' => 'boolean',
            'is_default_billing' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** One-line summary for dropdowns and invoices. */
    public function line(): string
    {
        return trim($this->address.', '.$this->city.' '.$this->postcode);
    }
}
