<?php

use App\Mail\ResetPasswordMail;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

function resetShopper(): User
{
    return User::create([
        'name' => 'Reset Shopper',
        'email' => 'reset@example.com',
        'password' => Hash::make('old-password-1'),
        'user_type' => 0,
        'status' => 1,
    ]);
}

test('forgot form and reset form render', function () {
    $this->get(route('password.request'))->assertOk()->assertSee('Email reset link', false);
    $this->get(route('password.reset', 'dummy-token'))->assertOk()->assertSee('Reset password', false);
});

test('reset link request emails the shopper with the branded template', function () {
    Mail::fake();
    $user = resetShopper();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && str_contains($mail->url, 'password/reset/')
            && str_contains($mail->url, 'email=');
    });
    expect(Password::getRepository()->exists($user, 'nope'))->toBeFalse();

    // Branded template compiles with a real render (Mail::fake never compiles views).
    $rendered = (new ResetPasswordMail($user, route('password.reset', 't'), 60))->render();
    expect($rendered)->toContain('Choose a new password')->toContain(route('password.reset', 't', false));
});

test('unknown email reads as success without sending', function () {
    Mail::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertNothingSent();
});

test('reset with a valid token changes the password', function () {
    $user = resetShopper();
    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'new-password-1'])
        ->assertRedirect(route('home'));
});

test('reset with a bad token fails cleanly', function () {
    $user = resetShopper();

    $this->post(route('password.update'), [
        'token' => 'wrong-token',
        'email' => $user->email,
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertRedirect()->assertSessionHasErrors('email');
});

test('admin mail test sends a probe email', function () {
    Mail::fake();
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'user_type' => 1]);

    $this->actingAs($admin)
        ->post(route('shop-settings.mail-test'), ['test_email' => 'probe@example.com'])
        ->assertRedirect(route('shop-settings.edit'))
        ->assertSessionHas('status');

    Mail::assertSent(TestMail::class, fn ($mail) => $mail->hasTo('probe@example.com'));

    $rendered = (new TestMail)->render();
    expect($rendered)->toContain('mail settings work');
});

test('mail test rejects a bad address', function () {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'user_type' => 1]);

    $this->actingAs($admin)
        ->post(route('shop-settings.mail-test'), ['test_email' => 'not-an-email'])
        ->assertSessionHasErrors('test_email');
});

test('shop settings mail fields save', function () {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'user_type' => 1]);

    $this->actingAs($admin)->get(route('shop-settings.edit'))->assertOk()->assertSee('SMTP host', false);

    $this->actingAs($admin)->post(route('shop-settings.update'), [
        'announcement_enabled' => '0',
        'promo_enabled' => '0',
        'social_proof_enabled' => '1',
        'invoice_format' => 'a4',
        'delivery_min_order' => '15',
        'delivery_free_over' => '50',
        'delivery_cutoff_time' => '20:00',
        'delivery_today_fee' => '2.99',
        'delivery_standard_fee' => '2.99',
        'delivery_radius_miles' => '10',
        'points_per_pound' => '1',
        'points_value' => '0.01',
        'points_min_redeem' => '100',
        'paypal_mode' => 'sandbox',
        'mail_host' => 'smtp.example.com',
        'mail_port' => '587',
        'mail_encryption' => 'tls',
        'mail_username' => 'shop@example.com',
        'mail_password' => 'secret',
        'mail_from_address' => 'orders@example.com',
        'mail_from_name' => 'Alam Mini Market',
    ])->assertRedirect(route('shop-settings.edit'));

    expect(Setting::get('mail_host'))->toBe('smtp.example.com');
    expect(MailSettings::source())->toBe('settings');
});

test('app forgot-password is generic and app reset-password works', function () {
    Mail::fake();
    $user = resetShopper();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()
        ->assertJsonPath('message', 'If an account uses that email address, a reset link is on its way.');
    Mail::assertSent(ResetPasswordMail::class);

    $this->postJson('/api/auth/forgot-password', ['email' => 'ghost@example.com'])->assertOk();
    $this->postJson('/api/auth/forgot-password', ['email' => 'bad'])->assertStatus(422);

    $token = Password::createToken($user);
    $this->postJson('/api/auth/reset-password', [
        'token' => 'wrong',
        'email' => $user->email,
        'password' => 'brand-new-1',
        'password_confirmation' => 'brand-new-1',
    ])->assertStatus(422);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'brand-new-1',
        'password_confirmation' => 'brand-new-1',
    ])->assertOk();

    $this->postJson('/api/auth/login', ['login' => $user->email, 'password' => 'brand-new-1'])->assertOk();
});
