<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fixFixtures(): array
{
    foreach (['new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $admin = User::create(['name' => 'Admin', 'email' => 'fix-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('admin');
}

function fixPaidOrder(): Order
{
    $new = OrderStatus::where('slug', 'new')->firstOrFail();

    return Order::create([
        'number' => 'EGF-'.random_int(40001, 49999),
        'name' => 'Shopper', 'phone' => '07123456789',
        'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->toDateString(),
        'subtotal' => 40.00, 'delivery_fee' => 0, 'total' => 40.00,
        'payment_method' => 'stripe', 'payment_status' => 'paid', 'payment_reference' => 'pi_x',
        'status_id' => $new->id, 'status_slug' => 'new',
    ]);
}

test('admin cannot cancel a paid online order before refunding it', function () {
    $f = fixFixtures();
    $order = fixPaidOrder();

    $this->actingAs($f['admin'])->post(route('orders.updateStatus', $order->id), ['status' => 'cancelled'])
        ->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');

    expect($order->fresh()->status_slug)->toBe('new');
});

test('paid orders cannot be paid for twice via retry after refund', function () {
    $f = fixFixtures();
    $user = User::create(['name' => 'S', 'email' => 'fix-shop@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $order = fixPaidOrder();
    $order->update(['user_id' => $user->id, 'refunded_amount' => 40.00, 'payment_status' => 'refunded']);

    $this->actingAs($user)->postJson(route('account.pay', $order->number))
        ->assertStatus(422)->assertJsonPath('message', 'That order has a refund on it — please contact us before paying again.');
});

test('a cancelled order gives its single-use coupon back', function () {
    fixFixtures();
    $user = User::create(['name' => 'S', 'email' => 'fix-coupon@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $coupon = Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 5, 'status' => true, 'max_per_user' => 1]);
    $order = fixPaidOrder();
    $order->update(['user_id' => $user->id, 'coupon_id' => $coupon->id, 'coupon_code' => 'ONCE', 'coupon_discount' => 5]);

    expect($coupon->checkFor($user->id, 40.00)['ok'])->toBeFalse();

    $order->changeStatus('cancelled', null, 'test');

    expect($coupon->checkFor($user->id, 40.00)['ok'])->toBeTrue();
});

test('login failures share one message and redirects stay on-site', function () {
    fixFixtures();
    $user = User::create(['name' => 'S', 'email' => 'fix-login@example.com', 'password' => bcrypt('password'), 'user_type' => 0, 'status' => 1]);

    $this->post(route('login'), ['login' => 'fix-login@example.com', 'password' => 'wrongpw'])
        ->assertRedirect()->assertSessionHasErrors(['login' => 'These credentials do not match our records.']);

    $this->post(route('login'), ['login' => 'nobody@example.com', 'password' => 'whatever'])
        ->assertRedirect()->assertSessionHasErrors(['login' => 'These credentials do not match our records.']);

    $this->post(route('login'), ['login' => 'fix-login@example.com', 'password' => 'password', 'redirect' => 'https://evil.example/phish'])
        ->assertRedirect(route('home'));
    expect(auth()->check())->toBeTrue();
    auth()->logout();

    $this->post(route('login'), ['login' => 'fix-login@example.com', 'password' => 'password', 'redirect' => '/shop'])
        ->assertRedirect('/shop');
});

test('tracking matches +44 phone format', function () {
    fixFixtures();
    $order = fixPaidOrder();

    $this->post(route('track.lookup'), ['number' => $order->number, 'phone' => '+447123456789'])
        ->assertOk()->assertSee($order->number);
});

test('contact messages have a size cap', function () {
    $this->postJson(route('contact.store'), [
        'name' => 'N', 'email' => 'n@example.com', 'message' => str_repeat('a', 5001),
    ])->assertStatus(422)->assertJsonValidationErrors('message');
});

test('illegal status moves are refused with a clear message', function () {
    $f = fixFixtures();
    $order = fixPaidOrder();

    $this->actingAs($f['admin'])->post(route('orders.updateStatus', $order->id), ['status' => 'delivered'])
        ->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');
    expect($order->fresh()->status_slug)->toBe('new');

    expect(fn () => $order->changeStatus('packed', null))->toThrow(LogicException::class);
});
