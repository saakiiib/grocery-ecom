<?php

use App\Http\Controllers\CheckoutController;
use App\Mail\OrderDelivered;
use App\Mail\OrderPlaced;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function shopAdmin(): User
{
    return User::create([
        'name' => 'Admin', 'email' => 'shop-admin@example.com',
        'password' => bcrypt('password'), 'user_type' => 1,
    ]);
}

function shopFixtures(): array
{
    $cat = Category::create(['name' => 'Fresh Meat', 'slug' => 'fresh-meat']);
    $group = OptionGroup::create(['name' => 'Pack Size', 'slug' => 'pack-size', 'type' => 'buttons', 'sort_order' => 0]);
    $v500 = OptionValue::create(['option_group_id' => $group->id, 'label' => '500g', 'slug' => '500g', 'sort_order' => 0]);
    $v1kg = OptionValue::create(['option_group_id' => $group->id, 'label' => '1kg', 'slug' => '1kg', 'sort_order' => 1]);
    $cat->optionGroups()->sync([$group->id => ['sort_order' => 0]]);

    $product = Product::create(['category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg', 'highlights' => "Grass fed\nFresh"]);
    $a = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'offer_price' => 10.99, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $a->values()->sync([$v500->id]);
    $b = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-1KG', 'mrp' => 22.99, 'is_default' => false, 'in_stock' => true, 'status' => true, 'sort_order' => 1]);
    $b->values()->sync([$v1kg->id]);

    return compact('cat', 'group', 'v500', 'v1kg', 'product', 'a', 'b');
}

function shopSetup(): array
{
    foreach ([
        ['slug' => 'new', 'name' => 'New', 'color' => '#B45309', 'is_final' => false],
        ['slug' => 'confirmed', 'name' => 'Confirmed', 'color' => '#1D4ED8', 'is_final' => false],
        ['slug' => 'packed', 'name' => 'Packed', 'color' => '#6D28D9', 'is_final' => false],
        ['slug' => 'out_for_delivery', 'name' => 'Out for Delivery', 'color' => '#0E7490', 'is_final' => false],
        ['slug' => 'delivered', 'name' => 'Delivered', 'color' => '#1A2E22', 'is_final' => true],
        ['slug' => 'cancelled', 'name' => 'Cancelled', 'color' => '#B91C1C', 'is_final' => true],
    ] as $i => $s) {
        OrderStatus::create([...$s, 'sort_order' => $i, 'is_active' => true]);
    }

    $slot = DeliverySlot::create([
        'name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00',
        'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true,
    ]);

    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    return ['slot' => $slot];
}

function shopperUser(): User
{
    return User::create([
        'name' => 'Shopper', 'email' => 'shopper@example.com',
        'password' => bcrypt('password'), 'user_type' => 0,
    ]);
}

/** Valid checkout payload using the first bookable date. */
function checkoutPayload(int $slotId, string $method = 'cod'): array
{
    $dates = DeliverySlot::bookableDates();

    return [
        'name' => 'Shopper Name', 'phone' => '07123456789', 'email' => 'shopper@example.com',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'Shopper Name', 'billing_phone' => '07123456789',
        'billing_address' => '1 Market Street', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'substitution' => 'substitute',
        'delivery_date' => array_key_first($dates),
        'delivery_slot_id' => $slotId,
        'payment_method' => $method,
        'privacy' => true,
    ];
}

test('bag lives in the session and is priced from the database', function () {
    $f = shopFixtures();
    shopSetup();

    // Browser sends ids only — no prices accepted.
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('subtotal', 21.98);

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])
        ->assertOk()
        ->assertJsonPath('count', 3)
        ->assertJsonPath('subtotal', 44.97);

    expect(session('bag'))->toBe([$f['a']->id => 2, $f['b']->id => 1]);

    $this->postJson(route('bag.update'), ['variant_id' => $f['a']->id, 'qty' => 1])
        ->assertOk()
        ->assertJsonPath('count', 2);

    $this->postJson(route('bag.remove'), ['variant_id' => $f['b']->id])
        ->assertOk()
        ->assertJsonPath('count', 1);

    $this->get(route('bag'))->assertOk()->assertSee('Lamb Leg', false);
});

test('out of stock variants cannot enter the bag', function () {
    $f = shopFixtures();
    shopSetup();
    $f['a']->update(['in_stock' => false]);

    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Sorry, that item is out of stock.');

    expect(session('bag', []))->toBe([]);
});

test('guest can place a cash-on-delivery order end to end', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];

    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $response = $this->postJson(route('checkout.place'), checkoutPayload($slot->id));
    $response->assertOk()->assertJsonPath('ok', true);

    $order = Order::firstOrFail();
    expect($order->number)->toStartWith('EGF-')
        ->and($order->user_id)->toBeNull()
        ->and((float) $order->subtotal)->toBe(44.97)
        ->and((float) $order->delivery_fee)->toBe(2.99)
        ->and((float) $order->total)->toBe(47.96)
        ->and($order->status_slug)->toBe('confirmed')
        ->and($order->payment_status)->toBe('unpaid')
        ->and($order->items)->toHaveCount(2);

    // Prices frozen from the database, not the browser.
    expect((float) $order->items->firstWhere('variant_sku', 'LAMB-500')->unit_price)->toBe(10.99);

    // History: placed → confirmed.
    expect($order->histories->pluck('to_slug')->all())->toBe(['new', 'confirmed']);

    // Bag cleared, success page visible to the guest via session.
    expect(session('bag', []))->toBe([]);
    $response->assertJsonPath('redirect', route('order.success', $order->number));
    $this->get(route('order.success', $order->number))->assertOk()->assertSee($order->number, false);
});

test('order emails go out on placement and delivery', function () {
    Mail::fake();
    $f = shopFixtures();
    $slot = shopSetup()['slot'];

    // Guest with a checkout email gets the receipt.
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $payload = array_merge(checkoutPayload($slot->id), ['email' => 'guest@example.com']);
    $this->postJson(route('checkout.place'), $payload)->assertOk()->assertJsonPath('ok', true);

    $order = Order::firstOrFail();
    Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('guest@example.com'));

    // Guest without any email is sent back — receipts need somewhere to go.
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $payload = checkoutPayload($slot->id);
    unset($payload['email']);
    $this->postJson(route('checkout.place'), $payload)->assertStatus(422)->assertJsonValidationErrors('email');
    Mail::assertSent(OrderPlaced::class, 1);

    // Delivered triggers the thank-you mail (points note included when earned).
    $order->changeStatus('packed', null);
    $order->changeStatus('out_for_delivery', null);
    $order->changeStatus('delivered', null);
    Mail::assertSent(OrderDelivered::class, fn ($mail) => $mail->hasTo('guest@example.com'));
});

test('bag lines resolve variant, product and placeholder images', function () {
    $f = shopFixtures();
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 1])->assertOk();

    // Nothing stored → placeholder.
    $data = $this->getJson(route('bag.data'))->assertOk()->json();
    expect($data['lines'][0]['image'])->toEndWith('placeholder.webp');

    // Absolute hero URL passes through untouched.
    $f['product']->update(['hero_image' => 'https://images.unsplash.com/photo-x']);
    $data = $this->getJson(route('bag.data'))->assertOk()->json();
    expect($data['lines'][0]['image'])->toBe('https://images.unsplash.com/photo-x');

    // Stored paths already carry their folder — never doubled.
    $f['product']->update(['hero_image' => 'uploads/products/lamb.webp']);
    $f['a']->update(['image' => 'uploads/products/variants/lamb-500.webp']);
    $data = $this->getJson(route('bag.data'))->assertOk()->json();
    expect($data['lines'][0]['image'])->toEndWith('uploads/products/variants/lamb-500.webp')
        ->and(substr_count($data['lines'][0]['image'], 'uploads/products/'))->toBe(1);
});

test('a shopper journeys from registration to reorder to cancel', function () {
    Mail::fake();
    $f = shopFixtures();
    $slot = shopSetup()['slot'];

    // 1. Register on the same users table.
    $this->post(route('register.store'), [
        'name' => 'Journey', 'email' => 'journey@example.com',
        'password' => '123456', 'password_confirmation' => '123456',
    ])->assertRedirect(route('account'));
    expect(auth()->check())->toBeTrue();

    // 2. Browse shop + details.
    $this->get('/shop')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/product/lamb-leg')->assertOk();

    // 3. Fill the bag past the minimum.
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $this->get(route('bag'))->assertOk();

    // 4. Checkout COD with a receipt email.
    $payload = array_merge(checkoutPayload($slot->id), ['email' => 'journey@example.com']);
    $this->postJson(route('checkout.place'), $payload)->assertOk()->assertJsonPath('ok', true);
    $order = Order::firstOrFail();
    Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('journey@example.com'));

    // 5. Success page, account history, guest-style tracking.
    $this->get(route('order.success', $order->number))->assertOk();
    $this->get(route('account'))->assertOk()->assertSee($order->number, false);
    $this->post(route('track.lookup'), ['number' => $order->number, 'phone' => '07123456789'])
        ->assertOk()->assertSee($order->number, false);

    // 6. Reorder refills the bag, then cancel the untouched COD order.
    $this->post(route('account.reorder', $order->number))->assertRedirect(route('bag'));
    expect(session('bag'))->not->toBe([]);
    $this->post(route('account.cancel', $order->number))->assertRedirect(route('account'));
    expect($order->refresh()->status_slug)->toBe('cancelled');
});

test('coupons discount orders within their rules', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();
    Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'status' => true, 'max_per_user' => 1]);

    // Guests are refused with JSON, never a login redirect.
    $this->postJson(route('checkout.coupon'), ['code' => 'SAVE10'])
        ->assertStatus(422)->assertJsonPath('message', 'Sign in to use coupons.');

    // Shoppers validate + place with a lowercase code.
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $this->actingAs($user)->postJson(route('checkout.coupon'), ['code' => 'save10'])
        ->assertOk()->assertJsonPath('discount', 2.2);

    $payload = array_merge(checkoutPayload($slot->id), ['coupon_code' => 'save10']);
    $this->actingAs($user)->postJson(route('checkout.place'), $payload)->assertOk();
    $order = Order::firstOrFail();
    expect($order->coupon_code)->toBe('SAVE10')
        ->and((float) $order->coupon_discount)->toBe(2.2)
        ->and((float) $order->total)->toBe(22.77);

    // Same shopper cannot reuse a single-use coupon.
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $this->actingAs($user)->postJson(route('checkout.place'), $payload)->assertStatus(422);
});

test('coupons respect expiry, caps and minimums', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();

    Coupon::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 5, 'expires_at' => now()->subDay(), 'status' => true]);
    Coupon::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 5, 'min_order' => 100, 'status' => true]);
    Coupon::create(['code' => 'MAXED', 'type' => 'fixed', 'value' => 5, 'max_uses' => 0, 'status' => true]);

    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    foreach (['OLD', 'BIG', 'MAXED', 'NOPE'] as $code) {
        $this->actingAs($user)->postJson(route('checkout.coupon'), ['code' => $code])->assertStatus(422);
    }

    Coupon::create(['code' => 'FIVER', 'type' => 'fixed', 'value' => 5, 'status' => true]);
    $this->actingAs($user)->postJson(route('checkout.coupon'), ['code' => 'fiver'])
        ->assertOk()->assertJsonPath('discount', 5);
});

test('checkout requires privacy consent', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $this->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();

    $payload = checkoutPayload($slot->id);
    unset($payload['privacy']);
    $this->postJson(route('checkout.place'), $payload)->assertStatus(422)->assertInvalid('privacy');

    $html = $this->get(route('checkout'))->assertOk()->getContent();
    expect($html)->toContain('pay-tiles')
        ->toContain('co-privacy')
        ->toContain('privacy policy');
});

test('checkout rejects empty bag, minimum order, bad slot and bad date', function () {
    shopFixtures();
    $slot = shopSetup()['slot'];

    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))
        ->assertStatus(422)->assertJsonPath('message', 'Your bag is empty.');

    // Only £10.99 in the bag — below the £15 minimum.
    $this->postJson(route('bag.add'), ['variant_id' => ProductVariant::where('sku', 'LAMB-500')->first()->id])->assertOk();
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))
        ->assertStatus(422)->assertJsonPath('message', 'The minimum order for delivery is £15.00.');

    $payload = checkoutPayload(99999);
    // Add enough for the minimum first.
    $this->postJson(route('bag.add'), ['variant_id' => ProductVariant::where('sku', 'LAMB-1KG')->first()->id])->assertOk();
    $this->postJson(route('checkout.place'), $payload)
        ->assertStatus(422)->assertJsonPath('message', 'Please choose a delivery slot.');

    $payload = checkoutPayload($slot->id);
    $payload['delivery_date'] = '2000-01-01';
    $this->postJson(route('checkout.place'), $payload)
        ->assertStatus(422)->assertJsonPath('message', 'Please choose a valid delivery day.');

    expect(Order::count())->toBe(0);
});

test('free delivery over the threshold and tampered stock are handled', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    Setting::put('delivery_free_over', '20.00');

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk(); // £22.99
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();

    $order = Order::firstOrFail();
    expect((float) $order->delivery_fee)->toBe(0.0)
        ->and((float) $order->total)->toBe(22.99);
});

test('online methods are refused without keys, then work with faked gateways', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'stripe'))
        ->assertStatus(422)->assertJsonPath('message', 'Card payment is not available right now — please choose another method.');
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'paypal'))
        ->assertStatus(422)->assertJsonPath('message', 'PayPal is not available right now — please choose another method.');

    Setting::put('stripe_publishable', 'pk_test_x');
    Setting::put('stripe_secret', 'sk_test_x');
    Setting::put('paypal_client_id', 'pp-id');
    Setting::put('paypal_secret', 'pp-secret');

    Http::fake(function ($request) {
        $url = (string) $request->url();
        if (str_contains($url, 'api.stripe.com')) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => 'pi_123', 'client_secret' => 'cs_123', 'status' => 'requires_payment_method'], 200);
            }

            return Http::response(['id' => 'pi_123', 'status' => 'succeeded'], 200);
        }
        if (str_contains($url, '/v1/oauth2/token')) {
            return Http::response(['access_token' => 'tok'], 200);
        }
        if (str_contains($url, '/capture')) {
            return Http::response([
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED']]]]],
            ], 200);
        }

        return Http::response(['id' => 'PP-ORDER-1'], 200);
    });
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'stripe'))
        ->assertOk()->assertJsonPath('client_secret', 'cs_123');

    $stripeOrder = Order::orderByDesc('id')->first();
    expect($stripeOrder->status_slug)->toBe('new');

    $confirm = $this->postJson(route('checkout.payment-confirm'), [
        'order_number' => $stripeOrder->number, 'payment_method' => 'stripe',
    ]);
    if ($confirm->status() !== 200) {
        dump($confirm->json());
    }
    $confirm->assertOk()->assertJsonPath('ok', true);

    $stripeOrder->refresh();
    expect($stripeOrder->payment_status)->toBe('paid')
        ->and($stripeOrder->status_slug)->toBe('confirmed');

    // PayPal: token → create → capture, all faked.
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $place = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'paypal'))->assertOk();
    $ppNumber = $place->json('order_number');

    $this->postJson(route('checkout.payment-confirm'), [
        'order_number' => $ppNumber, 'payment_method' => 'paypal',
    ])->assertOk()->assertJsonPath('ok', true);

    $ppOrder = Order::where('number', $ppNumber)->first();
    expect($ppOrder->payment_status)->toBe('paid')
        ->and($ppOrder->payment_reference)->toBe('CAP-1');
});

test('shoppers register on the same users table and keep their bag', function () {
    shopFixtures();
    shopSetup();

    $this->get(route('register'))->assertOk()->assertSee('Join Evergreen', false);

    $response = $this->post(route('register.store'), [
        'name' => 'New Shopper', 'email' => 'new@example.com',
        'password' => '123456', 'password_confirmation' => '123456',
    ]);
    $response->assertRedirect(route('account'));

    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect($user->user_type)->toBe(0)
        ->and(auth()->check())->toBeTrue();

    // Duplicate email points at sign-in instead of crashing.
    auth()->logout();
    $this->post(route('register.store'), [
        'name' => 'Dup', 'email' => 'new@example.com',
        'password' => '123456', 'password_confirmation' => '123456',
    ])->assertSessionHasErrors('email');
});

test('account shows orders, reorders into the bag, and saves profile', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();

    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['a']->id, 'qty' => 2])->assertOk();
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $this->actingAs($user)->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();

    $order = Order::firstOrFail();
    expect($order->user_id)->toBe($user->id);
    // Checkout remembered the shopper's details.
    expect($user->refresh()->city)->toBe('Leeds');

    $this->actingAs($user)->get(route('account'))->assertOk()->assertSee($order->number, false);
    $this->actingAs($user)->get(route('account.order', $order->number))->assertOk()->assertSee('Buy everything again', false);

    // Another shopper cannot see it.
    $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $this->actingAs($other)->get(route('account.order', $order->number))->assertNotFound();

    // Buy again refills the session bag (bag was cleared at checkout).
    expect(session('bag', []))->toBe([]);
    $this->actingAs($user)->post(route('account.reorder', $order->number))
        ->assertRedirect(route('bag'))->assertSessionHas('bag_notice');
    expect(BagControllerCount())->toBe(3);

    // Unavailable lines are skipped with a note.
    $f['b']->update(['in_stock' => false]);
    session()->forget('bag');
    $this->actingAs($user)->post(route('account.reorder', $order->number))->assertRedirect(route('bag'));
    expect(session('bag'))->toBe([$f['a']->id => 2]);

    // Profile.
    $this->actingAs($user)->post(route('account.profile'), [
        'name' => 'Renamed', 'phone' => '07999999999', 'city' => 'York',
    ])->assertRedirect(route('account').'#details');
    expect($user->refresh()->name)->toBe('Renamed');

    // Guests are sent to sign in.
    auth()->logout();
    $this->get(route('account'))->assertRedirect(route('login'));
});

function BagControllerCount(): int
{
    return array_sum(session('bag', []));
}

test('admin manages orders, statuses write history, settings and slots save', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $admin = shopAdmin();

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();
    $order = Order::firstOrFail();

    // Guest orders are visible in admin (rows arrive over ajax).
    $this->actingAs($admin)->get(route('orders.index'))->assertOk();
    $this->actingAs($admin)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('orders.index'))
        ->assertOk()->assertJsonPath('recordsTotal', 1)
        ->assertSee($order->number, false);
    $this->actingAs($admin)->get(route('orders.show', $order->id))->assertOk()->assertSee('Change status', false);

    // Status change appends history with the admin attached.
    $this->actingAs($admin)->post(route('orders.updateStatus', $order->id), ['status' => 'packed', 'note' => 'Boxed'])
        ->assertRedirect(route('orders.show', $order->id));
    $order->refresh();
    expect($order->status_slug)->toBe('packed')
        ->and($order->histories->pluck('to_slug')->all())->toBe(['new', 'confirmed', 'packed']);
    expect($order->histories->last()->changed_by)->toBe($admin->id);

    // Same status twice adds no row.
    $this->actingAs($admin)->post(route('orders.updateStatus', $order->id), ['status' => 'packed']);
    expect($order->refresh()->histories)->toHaveCount(3);

    // Statuses are dynamic: rename + recolour flows everywhere.
    $this->actingAs($admin)->post(route('order-statuses.update'), [
        'id' => OrderStatus::where('slug', 'packed')->first()->id,
        'name' => 'Packed & Ready', 'color' => '#123456', 'sort_order' => 2, 'is_active' => true,
    ])->assertRedirect(route('order-statuses.index'));
    $this->actingAs($admin)->get(route('orders.show', $order->id))->assertSee('Packed &amp; Ready', false);

    // Shoppers are kept out of admin (bounced to sign-in).
    $this->actingAs(shopperUser())->get(route('orders.index'))->assertRedirect(route('login'));

    // Delivery slots + shop settings.
    $this->actingAs($admin)->post(route('delivery-slots.store'), [
        'name' => 'Night', 'starts_at' => '18:00', 'ends_at' => '21:00',
        'fee' => 4.99, 'cutoff_hour' => 20, 'is_active' => true,
    ])->assertRedirect(route('delivery-slots.index'));
    expect(DeliverySlot::where('name', 'Night')->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('shop-settings.update'), [
        'delivery_min_order' => '20.00', 'delivery_free_over' => '60.00',
        'points_per_pound' => '1', 'points_value' => '0.01', 'points_min_redeem' => '100',
        'paypal_mode' => 'sandbox',
    ])->assertRedirect(route('shop-settings.edit'));
    expect(Setting::get('delivery_min_order'))->toBe('20.00');
});

test('gateway credentials resolve from env first with settings fallback', function () {
    shopFixtures();
    shopSetup();

    expect(CheckoutController::stripeConfigured())->toBeFalse()
        ->and(CheckoutController::credentialSource('stripe'))->toBeNull();

    config()->set('services.stripe.publishable', 'pk_env');
    config()->set('services.stripe.secret', 'sk_env');
    expect(CheckoutController::stripeConfigured())->toBeTrue()
        ->and(CheckoutController::credentialSource('stripe'))->toBe('.env')
        ->and(CheckoutController::stripePublishable())->toBe('pk_env');

    config()->set('services.stripe.publishable', null);
    config()->set('services.stripe.secret', null);
    Setting::put('stripe_publishable', 'pk_db');
    Setting::put('stripe_secret', 'sk_db');
    expect(CheckoutController::stripeConfigured())->toBeTrue()
        ->and(CheckoutController::credentialSource('stripe'))->toBe('settings');
});

test('abandoned paypal orders cancel cleanly, paid ones never do', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    Setting::put('paypal_client_id', 'pp-id');
    Setting::put('paypal_secret', 'pp-secret');

    Http::fake(function ($request) {
        $url = (string) $request->url();
        if (str_contains($url, '/v1/oauth2/token')) {
            return Http::response(['access_token' => 'tok'], 200);
        }
        if (str_contains($url, '/capture')) {
            return Http::response([
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAP-9', 'status' => 'COMPLETED']]]]],
            ], 200);
        }

        return Http::response(['id' => 'PP-ABANDON'], 200);
    });

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $number = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'paypal'))->assertOk()->json('order_number');

    $this->postJson(route('checkout.cancel'), ['order_number' => $number])->assertOk()->assertJsonPath('ok', true);

    $order = Order::where('number', $number)->first();
    expect($order->status_slug)->toBe('cancelled')
        ->and($order->histories->pluck('to_slug')->all())->toBe(['new', 'cancelled']);

    // A paid order refuses cancellation.
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $paidNumber = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'paypal'))->assertOk()->json('order_number');
    $this->postJson(route('checkout.payment-confirm'), ['order_number' => $paidNumber, 'payment_method' => 'paypal'])->assertOk();
    $this->postJson(route('checkout.cancel'), ['order_number' => $paidNumber])->assertStatus(422);
});

test('shoppers can pay again for unpaid orders and cancel untouched ones', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();
    Setting::put('stripe_publishable', 'pk_test_x');
    Setting::put('stripe_secret', 'sk_test_x');

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && str_contains((string) $request->url(), 'payment_intents')) {
            return Http::response(['id' => 'pi_retry', 'client_secret' => 'cs_retry'], 200);
        }

        return Http::response(['id' => 'pi_retry', 'status' => 'succeeded'], 200);
    });

    // Unpaid stripe order (payment abandoned at checkout).
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $number = $this->actingAs($user)->postJson(route('checkout.place'), checkoutPayload($slot->id, 'stripe'))->assertOk()->json('order_number');

    // Pay-now from the account returns fresh credentials and completes.
    $pay = $this->actingAs($user)->postJson(route('account.pay', $number))->assertOk();
    expect($pay->json('client_secret'))->toBe('cs_retry');
    $this->actingAs($user)->postJson(route('checkout.payment-confirm'), ['order_number' => $number, 'payment_method' => 'stripe'])->assertOk();
    expect(Order::where('number', $number)->first()->payment_status)->toBe('paid');

    // Paid orders cannot be cancelled by the shopper.
    $this->actingAs($user)->post(route('account.cancel', $number))->assertRedirect(route('account.order', $number));

    // A fresh COD order cancels cleanly with history.
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $codNumber = $this->actingAs($user)->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk()->json('redirect');
    $codOrder = Order::orderByDesc('id')->first();
    $this->actingAs($user)->post(route('account.cancel', $codOrder->number))->assertRedirect(route('account'));
    expect($codOrder->refresh()->status_slug)->toBe('cancelled')
        ->and($codOrder->histories->last()->note)->toBe('Cancelled by the shopper.');

    // Other shoppers cannot touch it.
    $other = User::create(['name' => 'Other', 'email' => 'other2@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $this->actingAs($other)->post(route('account.cancel', $codOrder->number))->assertNotFound();
});

test('admin dashboard shows live order stats', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $admin = shopAdmin();

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();
    $order = Order::firstOrFail();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Orders today', false)
        ->assertSee('£'.number_format($order->total, 2), false)
        ->assertSee($order->number, false);
});

test('shoppers can change their password', function () {
    $user = shopperUser();

    $this->actingAs($user)->post(route('account.password'), [
        'current_password' => 'wrong',
        'password' => '654321',
        'password_confirmation' => '654321',
    ])->assertSessionHasErrors('current_password');

    $this->actingAs($user)->post(route('account.password'), [
        'current_password' => 'password',
        'password' => '12345',
        'password_confirmation' => '12345',
    ])->assertSessionHasErrors('password');

    $this->actingAs($user)->post(route('account.password'), [
        'current_password' => 'password',
        'password' => '654321',
        'password_confirmation' => '654321',
    ])->assertRedirect(route('account').'#password')->assertSessionHas('status');

    auth()->logout();
    $this->post(route('login'), ['login' => 'shopper@example.com', 'password' => '654321'])
        ->assertRedirect(route('home'));
});

test('anyone can track an order with number plus checkout phone', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];

    $this->get(route('track'))->assertOk()->assertSee('Track your order', false);

    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $payload = checkoutPayload($slot->id);
    $payload['phone'] = '07123 456789';
    $this->postJson(route('checkout.place'), $payload)->assertOk();
    $order = Order::firstOrFail();

    // Phone matches even with different spacing.
    $this->post(route('track.lookup'), ['number' => strtolower($order->number), 'phone' => '07123456789'])
        ->assertOk()->assertSee($order->number, false)->assertSee('Confirmed', false);

    // Wrong phone or number reveals nothing.
    $this->post(route('track.lookup'), ['number' => $order->number, 'phone' => '07000000000'])
        ->assertOk()->assertSee('could not find that order', false);
    $this->post(route('track.lookup'), ['number' => 'EGF-99999', 'phone' => '07123456789'])
        ->assertOk()->assertSee('could not find that order', false);
});

test('loyalty points are earned on delivery and refunded on cancel', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();
    $admin = shopAdmin();

    expect(UserPoint::balance($user->id))->toBe(0);

    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk(); // £22.99
    $this->actingAs($user)->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();
    $order = Order::firstOrFail();

    foreach (['confirmed', 'packed', 'out_for_delivery'] as $slug) {
        $this->actingAs($admin)->post(route('orders.updateStatus', $order->id), ['status' => $slug])->assertRedirect();
    }
    expect(UserPoint::balance($user->id))->toBe(0);

    $this->actingAs($admin)->post(route('orders.updateStatus', $order->id), ['status' => 'delivered'])->assertRedirect();
    expect($order->refresh()->points_earned)->toBe(22)
        ->and(UserPoint::balance($user->id))->toBe(22);

    $this->actingAs($user)->get(route('account'))->assertOk()->assertSee('22', false);

    // Guest orders never earn.
    auth()->logout();
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $this->postJson(route('checkout.place'), checkoutPayload($slot->id))->assertOk();
    $guest = Order::orderByDesc('id')->first();
    expect($guest->user_id)->toBeNull();
    $this->actingAs($admin)->post(route('orders.updateStatus', $guest->id), ['status' => 'delivered']);
    expect($guest->refresh()->points_earned)->toBe(0);
});

test('shoppers redeem points at checkout within balance and limits', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $user = shopperUser();

    UserPoint::create(['user_id' => $user->id, 'points' => 500, 'type' => UserPoint::EARN, 'description' => 'Test grant']);
    expect(UserPoint::balance($user->id))->toBe(500);

    $payload = function ($pts) use ($slot) {
        $p = checkoutPayload($slot->id);
        $p['points_redeem'] = $pts;

        return $p;
    };

    // Below the 100-point minimum.
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $this->actingAs($user)->postJson(route('checkout.place'), $payload(50))->assertStatus(422);

    // More than the balance.
    $this->actingAs($user)->postJson(route('checkout.place'), $payload(600))->assertStatus(422);

    // Guests cannot spend points.
    auth()->logout();
    $this->postJson(route('checkout.place'), $payload(100))->assertStatus(422);

    // 300 points = £3 off £22.99 + £2.99 delivery.
    $this->actingAs($user)->postJson(route('checkout.place'), $payload(300))->assertOk();
    $order = Order::firstOrFail();
    expect($order->points_redeemed)->toBe(300)
        ->and((float) $order->points_discount)->toBe(3.00)
        ->and((float) $order->total)->toBe(22.98)
        ->and(UserPoint::balance($user->id))->toBe(200);

    // Cancelling gives the points back.
    $this->actingAs($user)->post(route('account.cancel', $order->number))->assertRedirect(route('account'));
    expect(UserPoint::balance($user->id))->toBe(500);
});

test('order statuses cover the full grocery lifecycle', function () {
    shopSetup();

    expect(OrderStatus::ordered()->pluck('slug')->all())->toBe([
        'new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled',
    ]);
});
