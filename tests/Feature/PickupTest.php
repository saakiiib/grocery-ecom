<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\DeliveryRadius;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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
        'name' => 'N', 'phone' => '07123456789', 'email' => 'pickup-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'ZZ9 9ZZ',
        'billing_name' => 'N', 'billing_phone' => '07987654321', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'ZZ9 9ZZ',
        'substitution' => 'substitute',
        'delivery_date' => $date, 'delivery_slot_id' => $slotId, 'fulfillment' => $fulfillment,
        'payment_method' => 'cod', 'privacy' => true,
    ];
}

test('pickup is free and skips the delivery radius check', function () {
    $f = seedPickupGrocery();
    Setting::put('delivery_min_order', '15.00');
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');

    session()->put('bag', [$f['variant']->id => 1]);
    $date = array_key_first(DeliverySlot::bookableDates());
    $payload = pickupPayload($f['slot']->id, $date);
    foreach (['address', 'city', 'postcode', 'billing_name', 'billing_phone', 'billing_address', 'billing_city', 'billing_postcode'] as $field) {
        $payload[$field] = '';
    }
    $redirect = $this->postJson(route('checkout.place'), $payload)
        ->assertOk()->json('redirect');

    preg_match('/[A-Z]+-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();
    expect($order->fulfillment)->toBe('pickup')
        ->and((float) $order->delivery_fee)->toBe(0.0)
        ->and($order->address)->toBe('Click & Collect')
        ->and($order->postcode)->toBe('')
        ->and($order->billing_address)->toBeNull()
        ->and($order->paymentLabel())->toBe('Cash on Collection');
});

test('delivery still enforces the radius', function () {
    $f = seedPickupGrocery();
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');
    Http::fake(function ($request) {
        $tail = strtoupper(str_replace([' ', '+', '%20'], '', urldecode($request->url())));
        if (str_ends_with($tail, 'LS11AA')) {
            return Http::response(['status' => 200, 'result' => ['postcode' => 'LS1 1AA', 'latitude' => 53.7960, 'longitude' => -1.5430]], 200);
        }
        if (str_ends_with($tail, 'ZZ99ZZ')) {
            return Http::response(['status' => 200, 'result' => ['postcode' => 'ZZ9 9ZZ', 'latitude' => 51.5074, 'longitude' => -0.1278]], 200);
        }

        return Http::response(['status' => 404, 'error' => 'Postcode not found'], 404);
    });

    session()->put('bag', [$f['variant']->id => 1]);
    $date = array_key_first(DeliverySlot::bookableDates());
    $miles = DeliveryRadius::distanceMiles(53.7960, -1.5430, 51.5074, -0.1278);
    $this->postJson(route('checkout.place'), pickupPayload($f['slot']->id, $date, 'delivery'))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Sorry — ZZ9 9ZZ is '.number_format($miles, 1).' miles away; we deliver within 10.0 miles of our store.']);
});

test('checkout and tracking show the pickup option', function () {
    $f = seedPickupGrocery();
    session()->put('bag', [$f['variant']->id => 1]);

    $this->get('/checkout')->assertOk()->assertSee('Click &amp; Collect', false)
        ->assertSee('data-date-label', false)->assertSee('Today delivery', false)->assertSee('Standard shipping', false);

    $order = Order::create([
        'number' => 'EGF-45001', 'name' => 'N', 'phone' => '07123456789', 'email' => 't@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 10, 'delivery_fee' => 0, 'total' => 10, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'fulfillment' => 'pickup',
        'status_id' => OrderStatus::where('slug', 'new')->first()->id, 'status_slug' => 'new',
    ]);
    $this->post('/track', ['number' => 'EGF-45001', 'phone' => '07123456789'])->assertOk()
        ->assertSee('Click &amp; Collect', false);
});
