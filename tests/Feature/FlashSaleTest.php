<?php

use App\Http\Controllers\BagController;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function flashFixtures(): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Chicken', 'slug' => 'chicken', 'status' => true]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CHK-500', 'mrp' => 12.00, 'offer_price' => 10.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'flash-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('product', 'variant', 'admin');
}

function flashLive(int $productId, ?int $variantId = null, float $price = 8.00): FlashSale
{
    return FlashSale::create([
        'product_id' => $productId, 'product_variant_id' => $variantId, 'promo_price' => $price,
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'status' => true, 'sort_order' => 0,
    ]);
}

test('flash beats the shelf price while live, then expires on its own', function () {
    $f = flashFixtures();

    expect(FlashSale::priceFor($f['variant']->id, $f['product']->id))->toBeNull();

    flashLive($f['product']->id);
    expect(FlashSale::priceFor($f['variant']->id, $f['product']->id)['price'])->toBe(8.00);

    session()->put('bag', [$f['variant']->id => 1]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(8.00);
});

test('future and expired windows stay out', function () {
    $f = flashFixtures();
    FlashSale::create(['product_id' => $f['product']->id, 'promo_price' => 5.00, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'status' => true]);
    FlashSale::create(['product_id' => $f['product']->id, 'promo_price' => 4.00, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'status' => true]);

    session()->put('bag', [$f['variant']->id => 1]);
    expect(BagController::detailed()['subtotal'])->toBe(10.00);
});

test('flash items join the offers rail and filter', function () {
    $f = flashFixtures();
    ProductVariant::where('id', $f['variant']->id)->update(['offer_price' => null]);
    flashLive($f['product']->id, $f['variant']->id, 9.00);

    $this->get(route('home'))->assertOk()->assertSee('Flash');
    $this->get(route('shop.offers'))->assertOk()->assertSee('Chicken');
});

test('checkout charges the flash price', function () {
    $f = flashFixtures();
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');
    flashLive($f['product']->id);

    session()->put('bag', [$f['variant']->id => 2]);
    $redirect = $this->postJson(route('checkout.place'), [
        'name' => 'N', 'phone' => '07', 'email' => 'flash-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'substitution' => 'substitute',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id, 'payment_method' => 'cod', 'privacy' => true,
    ])->assertOk()->json('redirect');
    preg_match('/EGF-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();

    expect((float) $order->subtotal)->toBe(16.00)
        ->and((float) $order->items->first()->unit_price)->toBe(8.00);
});

test('details page shows the flash panel and countdown', function () {
    $f = flashFixtures();
    flashLive($f['product']->id);

    $this->get(route('product.show', $f['product']->slug))
        ->assertOk()->assertSee('Flash £8.00')->assertSee('ends');
});

test('admin can manage flash sales and make them permanent', function () {
    $f = flashFixtures();

    $this->actingAs($f['admin'])->get(route('flash.index'))->assertOk()->assertSee('Flash Sales');

    $payload = [
        'product_id' => $f['product']->id, 'promo_price' => 8.00,
        'starts_at' => now()->subHour()->format('Y-m-d\TH:i'), 'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'is_active' => '1',
    ];
    $this->actingAs($f['admin'])->post(route('flash.store'), $payload)->assertRedirect(route('flash.index'));
    expect(FlashSale::count())->toBe(1);

    $this->actingAs($f['admin'])->postJson(route('flash.store'), [...$payload, 'promo_price' => 99.00])
        ->assertStatus(422);

    $sale = FlashSale::firstOrFail();
    $this->actingAs($f['admin'])->post(route('flash.permanent', $sale->id))->assertRedirect(route('flash.index'));
    expect((float) $f['variant']->fresh()->offer_price)->toBe(8.00);

    $this->actingAs($f['admin'])->delete(route('flash.delete', $sale->id))->assertRedirect();
    expect(FlashSale::count())->toBe(0);
});
