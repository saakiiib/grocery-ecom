<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\PairsWell;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPairsGrocery(): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $make = function (string $name, string $slug, string $sku) use ($cat) {
        $product = Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => $slug, 'status' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => $sku, 'mrp' => 10.00, 'offer_price' => 9.00,
            'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
        ]);

        return compact('product', 'variant');
    };
    $rice = $make('Rice', 'rice', 'RICE-1KG');
    $beans = $make('Beans', 'beans', 'BEAN-500');
    $lone = $make('Salt', 'salt', 'SALT-1KG');
    foreach (['new', 'confirmed', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }

    return compact('rice', 'beans', 'lone');
}

function seedPairOrder(string $number, string $statusSlug, array $items): Order
{
    $status = OrderStatus::where('slug', $statusSlug)->firstOrFail();
    $order = Order::create([
        'number' => $number, 'name' => 'N', 'phone' => '07', 'email' => 'pairs@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 20, 'delivery_fee' => 0, 'total' => 20, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'status_id' => $status->id, 'status_slug' => $status->slug,
    ]);
    foreach ($items as [$product, $variant]) {
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_variant_id' => $variant->id,
            'product_name' => $product->name, 'variant_sku' => $variant->sku, 'unit_price' => 10, 'qty' => 1, 'line_total' => 10,
        ]);
    }

    return $order;
}

test('co-bought products pair up, cancelled orders never count', function () {
    $f = seedPairsGrocery();
    seedPairOrder('EGF-10001', 'confirmed', [[$f['rice']['product'], $f['rice']['variant']], [$f['beans']['product'], $f['beans']['variant']]]);
    seedPairOrder('EGF-10002', 'cancelled', [[$f['rice']['product'], $f['rice']['variant']], [$f['lone']['product'], $f['lone']['variant']]]);

    expect(PairsWell::productIdsFor([$f['rice']['product']->id]))->toBe([$f['beans']['product']->id])
        ->and(PairsWell::productIdsFor([$f['beans']['product']->id]))->toBe([$f['rice']['product']->id])
        ->and(PairsWell::productIdsFor([$f['lone']['product']->id]))->toBe([]);
});

test('details page and api expose pairs well with', function () {
    $f = seedPairsGrocery();
    seedPairOrder('EGF-10001', 'confirmed', [[$f['rice']['product'], $f['rice']['variant']], [$f['beans']['product'], $f['beans']['variant']]]);

    $this->get('/product/rice')->assertOk()
        ->assertSee('Pairs well with', false)
        ->assertSee('Beans', false);

    $this->getJson('/api/products/rice')->assertOk()
        ->assertJsonPath('pairs_well_with.0.name', 'Beans');
});

test('bag suggests co-bought items missing from the bag', function () {
    $f = seedPairsGrocery();
    seedPairOrder('EGF-10001', 'confirmed', [[$f['rice']['product'], $f['rice']['variant']], [$f['beans']['product'], $f['beans']['variant']]]);

    session()->put('bag', [$f['rice']['variant']->id => 1]);

    $this->get('/bag')->assertOk()
        ->assertSee('Complete your basket', false)
        ->assertSee('Beans', false);

    $this->getJson('/api/bag')->assertOk()
        ->assertJsonPath('suggestions.0.name', 'Beans');
});

test('no history means no pairs and no sections', function () {
    seedPairsGrocery();

    $this->get('/product/rice')->assertOk()->assertDontSee('Pairs well with', false);
    $this->getJson('/api/products/rice')->assertOk()->assertJsonPath('pairs_well_with', []);
});
