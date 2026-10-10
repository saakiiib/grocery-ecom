<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPickupGrocery(): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Rice', 'slug' => 'rice', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'RICE-1KG', 'mrp' => 10.00, 'offer_price' => 10.00,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '5.00');
    Setting::put('delivery_free_over', '50.00');

    return compact('variant', 'slot');
}

function pickupPayload(int $slotId, string $date, string $fulfillment = 'pickup'): array
{
    return [
        'name' => 'N', 'phone' => '07', 'email' => 'pickup-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'ZZ9 9ZZ',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'ZZ9 9ZZ',
        'substitution' => 'substitute',
        'delivery_date' => $date, 'delivery_slot_id' => $slotId, 'fulfillment' => $fulfillment,
        'payment_method' => 'cod', 'privacy' => true,
    ];
}

test('pickup is free and skips the delivery zone check', function () {
    $f = seedPickupGrocery();
    Setting::put('delivery_min_order', '15.00');
    DeliveryZone::create(['name' => 'Leeds only', 'is_active' => true])
        ->postcodes()->create(['prefix' => 'LS']);

    session()->put('bag', [$f['variant']->id => 1]);
    $date = array_key_first(DeliverySlot::bookableDates());
    $payload = pickupPayload($f['slot']->id, $date);
    foreach (['address', 'city', 'postcode', 'billing_name', 'billing_phone', 'billing_address', 'billing_city', 'billing_postcode'] as $field) {
        $payload[$field] = '';
    }
    $redirect = $this->postJson(route('checkout.place'), $payload)
        ->assertOk()->json('redirect');

    preg_match('/EGF-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();
    expect($order->fulfillment)->toBe('pickup')
        ->and((float) $order->delivery_fee)->toBe(0.0)
        ->and($order->address)->toBe('Click & Collect')
        ->and($order->postcode)->toBe('')
        ->and($order->billing_address)->toBeNull()
        ->and($order->paymentLabel())->toBe('Cash on Collection');
});

test('delivery still enforces the zone', function () {
    $f = seedPickupGrocery();
    DeliveryZone::create(['name' => 'Leeds only', 'is_active' => true])
        ->postcodes()->create(['prefix' => 'LS']);

    session()->put('bag', [$f['variant']->id => 1]);
    $date = array_key_first(DeliverySlot::bookableDates());
    $this->postJson(route('checkout.place'), pickupPayload($f['slot']->id, $date, 'delivery'))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => "Sorry — we don't deliver to ZZ9 9ZZ yet."]);
});

test('checkout and tracking show the pickup option', function () {
    $f = seedPickupGrocery();
    session()->put('bag', [$f['variant']->id => 1]);

    $this->get('/checkout')->assertOk()->assertSee('Click &amp; Collect', false);

    $order = Order::create([
        'number' => 'EGF-45001', 'name' => 'N', 'phone' => '07', 'email' => 't@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 10, 'delivery_fee' => 0, 'total' => 10, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'fulfillment' => 'pickup',
        'status_id' => OrderStatus::where('slug', 'new')->first()->id, 'status_slug' => 'new',
    ]);
    $this->post('/track', ['number' => 'EGF-45001', 'phone' => '07'])->assertOk()
        ->assertSee('Click &amp; Collect', false);
});
