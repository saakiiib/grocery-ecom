<?php

use App\Http\Controllers\BagController;
use App\Models\BundleOffer;
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

function bundleFixtures(): array
{
    $cat = Category::create(['name' => 'Rice', 'slug' => 'rice']);
    $mk = function (string $name, string $slug, float $mrp, string $sku) use ($cat) {
        $p = Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => $slug, 'status' => true]);
        $v = ProductVariant::create(['product_id' => $p->id, 'sku' => $sku, 'mrp' => $mrp, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

        return [$p, $v];
    };
    [$p1, $v1] = $mk('Basmati', 'basmati', 10.00, 'RICE-1');
    [$p2, $v2] = $mk('Jasmine', 'jasmine', 12.00, 'RICE-2');
    [$p3, $v3] = $mk('Brown', 'brown', 14.00, 'RICE-3');
    $admin = User::create(['name' => 'Admin', 'email' => 'bundle-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('cat', 'p1', 'p2', 'p3', 'v1', 'v2', 'v3', 'admin');
}

function bundleLive(array $f, int $n = 3, float $price = 30.00): BundleOffer
{
    $b = BundleOffer::create(['name' => 'Rice Deal', 'required_qty' => $n, 'bundle_price' => $price, 'status' => true, 'sort_order' => 0]);
    $b->categories()->sync([$f['cat']->id]);

    return $b;
}

test('cheapest lines group first with exact totals', function () {
    $f = bundleFixtures();
    bundleLive($f);

    session()->put('bag', [$f['v1']->id => 1, $f['v2']->id => 1, $f['v3']->id => 1]);
    $bag = BagController::detailed();

    // 10 + 12 + 14 = 36 shelf → bundle 30, saving 6, split proportionally.
    expect($bag['subtotal'])->toBe(30.00)
        ->and($bag['bundle_discount'])->toBe(6.00)
        ->and($bag['lines'][0]['line_total'])->toBe(8.33)
        ->and($bag['lines'][1]['line_total'])->toBe(10.00)
        ->and($bag['lines'][2]['line_total'])->toBe(11.67)
        ->and($bag['lines'][0]['promo_label'])->toBe('Any 3 for £30.00');
});

test('leftovers stay at shelf price and groups re-form', function () {
    $f = bundleFixtures();
    bundleLive($f);

    session()->put('bag', [$f['v3']->id => 4]);
    $bag = BagController::detailed();

    // 4 × 14: one group of 3 → 30, one leftover 14 = 44.
    expect($bag['subtotal'])->toBe(44.00)->and($bag['bundle_discount'])->toBe(12.00);

    session()->put('bag', [$f['v3']->id => 2]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(28.00)->and($bag['bundle_discount'])->toBe(0.0)
        ->and($bag['lines'][0]['promo_label'])->toBeNull();
});

test('bundle skips worthless groupings', function () {
    $f = bundleFixtures();
    bundleLive($f, 2, 30.00);

    session()->put('bag', [$f['v1']->id => 2]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(20.00)->and($bag['bundle_discount'])->toBe(0.0)
        ->and($bag['lines'][0]['promo_label'])->toBeNull();
});

test('new packs in a pooled category join automatically', function () {
    $f = bundleFixtures();
    $b = bundleLive($f);
    $p4 = Product::create(['category_id' => $f['cat']->id, 'name' => 'Wild', 'slug' => 'wild', 'status' => true]);
    $v4 = ProductVariant::create(['product_id' => $p4->id, 'sku' => 'RICE-4', 'mrp' => 9.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    expect($b->poolVariantIds())->toContain($v4->id);

    session()->put('bag', [$f['v1']->id => 1, $f['v2']->id => 1, $v4->id => 1]);
    $bag = BagController::detailed();
    expect($bag['subtotal'])->toBe(30.00)->and($bag['bundle_discount'])->toBe(1.00);
});

test('checkout snapshots bundle labels', function () {
    $f = bundleFixtures();
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');
    bundleLive($f);

    session()->put('bag', [$f['v1']->id => 1, $f['v2']->id => 1, $f['v3']->id => 1]);
    $redirect = $this->postJson(route('checkout.place'), [
        'name' => 'N', 'phone' => '07', 'email' => 'bun-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'substitution' => 'substitute',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slot->id, 'payment_method' => 'cod', 'privacy' => true,
    ])->assertOk()->json('redirect');
    preg_match('/EGF-\d+/', $redirect, $m);
    $order = Order::where('number', $m[0])->firstOrFail();

    expect((float) $order->subtotal)->toBe(30.00)
        ->and($order->items->firstWhere('variant_sku', 'RICE-1')->promo_label)->toBe('Any 3 for £30.00');
});

test('cards and details advertise bundles', function () {
    $f = bundleFixtures();
    bundleLive($f);

    $this->get(route('shop'))->assertOk()->assertSee('Any 3 for £30.00');
    $this->get(route('product.show', $f['p1']->slug))
        ->assertOk()->assertSee('Any 3 for £30.00')->assertSee('mix & match', false);
});

test('admin can manage bundles with pools', function () {
    $f = bundleFixtures();

    $this->actingAs($f['admin'])->get(route('bundles.index'))->assertOk()->assertSee('Bundles');

    $this->actingAs($f['admin'])->post(route('bundles.store'), [
        'name' => 'Rice Deal', 'required_qty' => 3, 'bundle_price' => 30,
        'categories' => [$f['cat']->id], 'variants' => [$f['v1']->id], 'is_active' => '1',
    ])->assertRedirect(route('bundles.index'));
    $bundle = BundleOffer::firstOrFail();
    expect($bundle->categories()->count())->toBe(1)->and($bundle->variants()->count())->toBe(1);

    $this->actingAs($f['admin'])->postJson(route('bundles.store'), [
        'name' => 'Empty', 'required_qty' => 2, 'bundle_price' => 5,
    ])->assertStatus(422);

    $this->actingAs($f['admin'])->post(route('bundles.toggleStatus'), ['id' => $bundle->id])->assertOk();
    expect($bundle->fresh()->status)->toBeFalse();

    $this->actingAs($f['admin'])->delete(route('bundles.delete', $bundle->id))->assertRedirect();
    expect(BundleOffer::count())->toBe(0);
});
