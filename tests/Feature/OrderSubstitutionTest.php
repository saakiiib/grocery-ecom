<?php

use App\Mail\OrderStatusUpdated;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function subFixtures(): array
{
    foreach (['new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111111', 'sort_order' => $i, 'is_active' => true]);
    }
    $admin = User::create(['name' => 'Admin', 'email' => 'sub-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('admin');
}

function subOrder(string $method = 'stripe', string $status = 'paid', string $preference = 'refund'): Order
{
    $new = OrderStatus::where('slug', 'new')->firstOrFail();
    $order = Order::create([
        'number' => 'EGF-'.random_int(30001, 39999),
        'name' => 'Shopper', 'phone' => '071',
        'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'substitution_preference' => $preference,
        'delivery_date' => now()->addDay()->toDateString(),
        'subtotal' => 30.00, 'delivery_fee' => 0, 'total' => 30.00,
        'payment_method' => $method, 'payment_status' => $status, 'amount_paid' => $status === 'paid' ? 30.00 : 0,
        'payment_reference' => 'pi_test_sub',
        'status_id' => $new->id, 'status_slug' => 'new',
    ]);
    $order->items()->create(['product_name' => 'Carrots', 'variant_sku' => 'CAR-1KG', 'pack_label' => '1kg', 'unit_price' => 10.00, 'qty' => 2, 'line_total' => 20.00]);
    $order->items()->create(['product_name' => 'Apples', 'variant_sku' => 'APP-1KG', 'pack_label' => '1kg', 'unit_price' => 10.00, 'qty' => 1, 'line_total' => 10.00]);

    return $order->fresh();
}

function subStripeKeys(): void
{
    Setting::put('stripe_secret', 'sk_test_123');
    Setting::put('stripe_publishable', 'pk_test_123');
}

test('checkout stores the substitution preference', function () {
    subFixtures();
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');
    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    session()->put('bag', [$variant->id => 2]);

    $payload = [
        'name' => 'N', 'phone' => '07', 'email' => 'sub-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id, 'payment_method' => 'cod', 'privacy' => true,
    ];

    $this->postJson(route('checkout.place'), $payload)->assertStatus(422)->assertJsonValidationErrors('substitution');

    $redirect = $this->postJson(route('checkout.place'), [...$payload, 'substitution' => 'call'])->assertOk()->json('redirect');
    preg_match('/EGF-\d+/', $redirect, $m);
    expect(Order::where('number', $m[0])->firstOrFail()->substitution_preference)->toBe('call');
});

test('unavailable line refunds and marks on paid orders', function () {
    $f = subFixtures();
    subStripeKeys();
    $order = subOrder();
    $item = $order->items()->where('product_name', 'Carrots')->firstOrFail();

    Http::fake(['*api.stripe.com/*' => Http::response(['id' => 're_sub', 'status' => 'succeeded'], 200)]);

    $this->actingAs($f['admin'])->post(route('orders.items.unavailable', [$order->id, $item->id]))
        ->assertRedirect(route('orders.show', $order->id));

    Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/refunds') && (int) $r['amount'] === 2000);

    expect($item->fresh()->status)->toBe('unavailable')
        ->and((float) $order->fresh()->refunded_amount)->toBe(20.00)
        ->and($order->fresh()->payment_status)->toBe('partially_refunded');

    $html = (new OrderStatusUpdated($order->fresh()->load('items'), 'packed'))->render();
    expect($html)->toContain('unavailable — refunded');
});

test('cod lines mark unavailable with no gateway call', function () {
    $f = subFixtures();
    $order = subOrder('cod', 'unpaid');
    $item = $order->items()->firstOrFail();

    Http::fake();

    $this->actingAs($f['admin'])->post(route('orders.items.unavailable', [$order->id, $item->id]))
        ->assertRedirect(route('orders.show', $order->id));

    Http::assertNothingSent();
    expect($item->fresh()->status)->toBe('unavailable')
        ->and((float) $order->fresh()->refunded_amount)->toBe(0.0);
});

test('repeat marks and final orders are refused', function () {
    $f = subFixtures();
    subStripeKeys();
    $order = subOrder();
    $item = $order->items()->firstOrFail();

    Http::fake(['*api.stripe.com/*' => Http::response(['id' => 're_x'], 200)]);

    $this->actingAs($f['admin'])->post(route('orders.items.unavailable', [$order->id, $item->id]))->assertRedirect();
    $this->actingAs($f['admin'])->post(route('orders.items.unavailable', [$order->id, $item->id]))
        ->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');

    $order->update(['status_slug' => 'delivered']);
    $other = $order->items()->where('status', 'ok')->firstOrFail();
    $this->actingAs($f['admin'])->post(route('orders.items.unavailable', [$order->id, $other->id]))
        ->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');
});
