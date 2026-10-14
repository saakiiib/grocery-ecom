<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\ResetPasswordMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $attributes = [
        'status' => 1,
        'user_type' => 0,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'user_type' => 'integer',
        ];
    }

    /** Saved address book (Home, Work…). */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->latest();
    }

    /** Branded reset email instead of the framework default notification. */
    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));
        $minutes = (int) (config('auth.passwords.users.expire', 60) ?? 60);

        Mail::to($this->getEmailForPasswordReset())->send(new ResetPasswordMail($this, $url, $minutes));
    }

    /** Named shopping lists (Weekly shop, BBQ…). */
    public function shoppingLists(): HasMany
    {
        return $this->hasMany(ShoppingList::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
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
