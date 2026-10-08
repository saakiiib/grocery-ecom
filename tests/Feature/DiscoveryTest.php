<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedDiscoveryGrocery(): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $make = function (string $name, string $slug, string $sku, string $created) use ($cat) {
        $product = Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => $slug, 'status' => true, 'created_at' => $created, 'updated_at' => $created]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => $sku, 'mrp' => 10.00, 'offer_price' => 9.00,
            'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
        ]);

        return compact('product', 'variant');
    };
    $old = $make('Old Rice', 'old-rice', 'OLD-1', now()->subDays(30)->toDateTimeString());
    $new = $make('New Rice', 'new-rice', 'NEW-1', now()->toDateTimeString());
    foreach (['new', 'confirmed', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }

    return compact('old', 'new');
}

function seedDiscoveryOrder(string $number, string $statusSlug, array $items, string $created): void
{
    $status = OrderStatus::where('slug', $statusSlug)->firstOrFail();
    $order = Order::create([
        'number' => $number, 'name' => 'N', 'phone' => '07', 'email' => 'disco@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 20, 'delivery_fee' => 0, 'total' => 20, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'status_id' => $status->id, 'status_slug' => $status->slug,
        'created_at' => $created, 'updated_at' => $created,
    ]);
    foreach ($items as [$product, $variant, $qty]) {
        $item = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_variant_id' => $variant->id,
            'product_name' => $product->name, 'variant_sku' => $variant->sku, 'unit_price' => 10, 'qty' => $qty, 'line_total' => 10 * $qty,
        ]);
        $item->update(['created_at' => $created]);
    }
}

test('homepage shows new arrivals newest first', function () {
    seedDiscoveryGrocery();

    $html = $this->get('/')->assertOk()->assertSee('New arrivals', false)->getContent();
    expect(strpos($html, 'New Rice') < strpos($html, 'Old Rice'))->toBeTrue();
});

test('bestsellers rank by units and skip cancelled orders', function () {
    $f = seedDiscoveryGrocery();
    seedDiscoveryOrder('EGF-20001', 'confirmed', [[$f['old']['product'], $f['old']['variant'], 5]], now()->subDays(30)->toDateTimeString());
    seedDiscoveryOrder('EGF-20002', 'cancelled', [[$f['new']['product'], $f['new']['variant'], 50]], now()->toDateTimeString());

    $this->get('/')->assertOk()->assertSee('Bestsellers', false)->assertSee('Old Rice', false);
    $this->getJson('/api/home')->assertOk()->assertJsonPath('bestsellers.0.name', 'Old Rice');
});

test('trending only counts the last 14 days', function () {
    $f = seedDiscoveryGrocery();
    seedDiscoveryOrder('EGF-20001', 'confirmed', [[$f['old']['product'], $f['old']['variant'], 5]], now()->subDays(30)->toDateTimeString());

    // Only stale sales: trending falls back to the offers rail.
    $this->getJson('/api/home')->assertOk()->assertJsonPath('trending.0.name', 'New Rice');

    seedDiscoveryOrder('EGF-20002', 'confirmed', [[$f['old']['product'], $f['old']['variant'], 2]], now()->toDateTimeString());

    $this->getJson('/api/home')->assertOk()->assertJsonPath('trending.0.name', 'Old Rice');
});

test('recently viewed follows the shopper around', function () {
    seedDiscoveryGrocery();

    $this->get('/')->assertOk()->assertDontSee('Recently viewed', false);
    $this->get('/product/old-rice')->assertOk();
    $this->get('/product/new-rice')->assertOk()
        ->assertSee('Recently viewed', false)
        ->assertSee('Old Rice', false);
});

test('api home carries every discovery rail', function () {
    seedDiscoveryGrocery();

    $this->getJson('/api/home')->assertOk()
        ->assertJsonStructure(['new_in' => [['name']], 'bestsellers', 'trending'])
        ->assertJsonPath('new_in.0.name', 'New Rice');
});
