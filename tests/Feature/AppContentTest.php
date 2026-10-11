<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function appContentFixtures(): array
{
    foreach ([
        ['slug' => 'new', 'name' => 'New'],
        ['slug' => 'confirmed', 'name' => 'Confirmed'],
        ['slug' => 'cancelled', 'name' => 'Cancelled'],
    ] as $i => $s) {
        OrderStatus::create([...$s, 'color' => '#111111', 'sort_order' => $i, 'is_active' => true, 'is_final' => $s['slug'] === 'cancelled']);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $shopper = User::create(['name' => 'Shopper', 'email' => 'app-content-shopper@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);

    return compact('slot', 'variant', 'shopper');
}

test('app content endpoint exposes about and legal copy', function () {
    appContentFixtures();

    $details = CompanyDetails::cached();
    $details->update([
        'about_us' => '<p>Our fresh story</p>',
        'privacy_policy' => '<p>Privacy body</p>',
        'terms_and_conditions' => '<p>Terms body</p>',
        'refund_policy' => '<p>Refund body</p>',
    ]);

    $this->getJson('/api/content')->assertOk()->assertJson([
        'about_us' => '<p>Our fresh story</p>',
        'privacy_policy' => '<p>Privacy body</p>',
        'terms_and_conditions' => '<p>Terms body</p>',
        'refund_policy' => '<p>Refund body</p>',
    ])->assertJsonStructure(['company']);
});

test('app can download its own order invoice pdf', function () {
    ['slot' => $slot, 'variant' => $variant, 'shopper' => $shopper] = appContentFixtures();

    $this->actingAs($shopper);
    session()->put('bag', [$variant->id => 2]);
    $redirect = $this->postJson(route('checkout.place'), [
        'name' => 'Shopper Name', 'phone' => '07123456789', 'email' => 'app-content-shopper@example.com',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'Billing Name', 'billing_phone' => '07987654321',
        'billing_address' => '9 Bill Road', 'billing_city' => 'York', 'billing_postcode' => 'YO1 1AA',
        'substitution' => 'refund',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id,
        'payment_method' => 'cod',
        'privacy' => true,
    ])->assertOk()->json('redirect');
    preg_match('/[A-Z]+-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();

    $res = $this->actingAs($shopper)->get('/api/orders/'.$order->number.'/invoice');
    $res->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('application/pdf');
    expect($res->getContent())->toContain('%PDF');
});

test('invoice pdf is hidden from strangers', function () {
    ['slot' => $slot, 'variant' => $variant, 'shopper' => $shopper] = appContentFixtures();

    $this->actingAs($shopper);
    session()->put('bag', [$variant->id => 2]);
    $redirect = $this->postJson(route('checkout.place'), [
        'name' => 'Shopper Name', 'phone' => '07123456789', 'email' => 'app-content-shopper@example.com',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'Billing Name', 'billing_phone' => '07987654321',
        'billing_address' => '9 Bill Road', 'billing_city' => 'York', 'billing_postcode' => 'YO1 1AA',
        'substitution' => 'refund',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id,
        'payment_method' => 'cod',
        'privacy' => true,
    ])->assertOk()->json('redirect');
    preg_match('/[A-Z]+-\d+/', $redirect, $m);

    $stranger = User::create(['name' => 'Stranger', 'email' => 'app-content-stranger@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    session()->forget('last_order_id');
    $this->actingAs($stranger)->get('/api/orders/'.$m[0].'/invoice')->assertNotFound();
});
