<?php

use App\Http\Controllers\BagController;
use App\Models\BogoOffer;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bogoFixtures(): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Chicken', 'slug' => 'chicken', 'status' => true]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CHK-500', 'mrp' => 10.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'bogo-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('product', 'variant', 'admin');
}

function bogoLive(int $productId, ?int $variantId = null, int $buy = 2, int $free = 1): BogoOffer
{
    return BogoOffer::create([
        'product_id' => $productId, 'product_variant_id' => $variantId,
        'buy_qty' => $buy, 'free_qty' => $free, 'status' => true, 'sort_order' => 0,
    ]);
}

test('free units follow completed groups only', function () {
    $f = bogoFixtures();
    $offer = bogoLive($f['product']->id);

    expect($offer->freeUnitsFor(2))->toBe(0)
        ->and($offer->freeUnitsFor(3))->toBe(1)
        ->and($offer->freeUnitsFor(4))->toBe(1)
        ->and($offer->freeUnitsFor(6))->toBe(2)
        ->and($offer->label())->toBe('Buy 2 Get 1 FREE');
});

test('bag prices free units server-side', function () {
    $f = bogoFixtures();
    bogoLive($f['product']->id);

    session()->put('bag', [$f['variant']->id => 3]);
    $bag = BagController::detailed();

    expect($bag['subtotal'])->toBe(20.00)
        ->and($bag['bogo_discount'])->toBe(10.00)
        ->and($bag['lines'][0]['promo_label'])->toBe('Buy 2 Get 1 FREE')
        ->and($bag['lines'][0]['free_qty'])->toBe(1)
        ->and($bag['lines'][0]['line_total'])->toBe(20.00);
});

test('below threshold and inactive offers change nothing', function () {
    $f = bogoFixtures();
    bogoLive($f['product']->id);

    session()->put('bag', [$f['variant']->id => 2]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(20.00)->and($bag['bogo_discount'])->toBe(0.0)
        ->and($bag['lines'][0]['promo_label'])->toBeNull();

    BogoOffer::query()->update(['status' => false]);
    session()->put('bag', [$f['variant']->id => 3]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(30.00)->and($bag['bogo_discount'])->toBe(0.0);
});

test('expired and future offers stay out', function () {
    $f = bogoFixtures();
    bogoLive($f['product']->id)->update(['ends_at' => now()->subDay()]);
    BogoOffer::create(['product_id' => $f['product']->id, 'buy_qty' => 1, 'free_qty' => 1, 'status' => true, 'starts_at' => now()->addDay()]);

    session()->put('bag', [$f['variant']->id => 4]);
    $bag = BagController::detailed();
    expect($bag['bogo_discount'])->toBe(0.0)->and($bag['subtotal'])->toBe(40.00);
});

test('variant-specific offers beat product-wide ones', function () {
    $f = bogoFixtures();
    bogoLive($f['product']->id, null, 2, 1);
    bogoLive($f['product']->id, $f['variant']->id, 1, 1);

    session()->put('bag', [$f['variant']->id => 2]);
    $bag = BagController::detailed();
    expect($bag['lines'][0]['promo_label'])->toBe('Buy 1 Get 1 FREE')
        ->and($bag['bogo_discount'])->toBe(10.00);
});

test('checkout totals and snapshots carry the promo', function () {
    $f = bogoFixtures();
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');
    bogoLive($f['product']->id);

    session()->put('bag', [$f['variant']->id => 3]);
    $redirect = $this->postJson(route('checkout.place'), [
        'name' => 'N', 'phone' => '07', 'email' => 'bogo-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'substitution' => 'substitute',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id, 'payment_method' => 'cod', 'privacy' => true,
    ])->assertOk()->json('redirect');
    preg_match('/EGF-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();

    // 3 × £10 minus 1 free = £20 goods + £2.99 fee.
    expect((float) $order->subtotal)->toBe(20.00)
        ->and((float) $order->total)->toBe(22.99)
        ->and($order->items->first()->promo_label)->toBe('Buy 2 Get 1 FREE')
        ->and($order->items->first()->free_qty)->toBe(1);
});

test('cards and details page advertise live bogos', function () {
    $f = bogoFixtures();
    bogoLive($f['product']->id);

    $this->get(route('shop'))->assertOk()->assertSee('Buy 2 Get 1 FREE');
    $this->get(route('product.show', $f['product']->slug))
        ->assertOk()->assertSee('Buy 2 Get 1 FREE')->assertSee('Free units appear in your bag');
});

test('admin can manage bogo offers', function () {
    $f = bogoFixtures();

    $this->actingAs($f['admin'])->get(route('bogo.index'))->assertOk()->assertSee('BOGO Offers');

    $this->actingAs($f['admin'])->post(route('bogo.store'), [
        'product_id' => $f['product']->id, 'product_variant_id' => $f['variant']->id,
        'buy_qty' => 2, 'free_qty' => 1, 'is_active' => '1',
    ])->assertRedirect(route('bogo.index'));
    $offer = BogoOffer::firstOrFail();
    expect($offer->product_variant_id)->toBe($f['variant']->id);

    $this->actingAs($f['admin'])->postJson(route('bogo.store'), [
        'product_id' => $f['product']->id, 'product_variant_id' => 999999, 'buy_qty' => 2, 'free_qty' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('product_variant_id');

    $this->actingAs($f['admin'])->post(route('bogo.toggleStatus'), ['id' => $offer->id])->assertOk();
    expect($offer->fresh()->status)->toBeFalse();

    $this->actingAs($f['admin'])->delete(route('bogo.delete', $offer->id))->assertRedirect();
    expect(BogoOffer::count())->toBe(0);
});
