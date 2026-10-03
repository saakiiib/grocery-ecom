<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Saved address book (Home, Work…). */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->latest();
    }

    public function defaultDeliveryAddress(): ?Address
    {
        return $this->addresses->firstWhere('is_default_delivery', true)
            ?? $this->addresses->first();
    }

    public function defaultBillingAddress(): ?Address
    {
        return $this->addresses->firstWhere('is_default_billing', true)
            ?? $this->defaultDeliveryAddress();
    }

    /**
     * Seed the book from the legacy single-address profile columns once.
     */
    public function ensureAddressBook(): void
    {
        if ($this->addresses()->exists()) {
            return;
        }
        if (! $this->address && ! $this->postcode) {
            return;
        }
        $this->addresses()->create([
            'label' => 'Home',
            'name' => $this->name,
            'phone' => $this->phone ?? '',
            'address' => $this->address ?? '',
            'city' => $this->city ?? '',
            'postcode' => $this->postcode ?? '',
            'is_default_delivery' => true,
            'is_default_billing' => true,
        ]);
    }
}
