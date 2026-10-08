<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedProofGrocery(): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'offer_price' => 10.99,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    foreach (['new', 'confirmed', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }

    return compact('product', 'variant');
}

function seedProofOrder(string $number, string $statusSlug, string $created): void
{
    $f = ['product' => Product::first(), 'variant' => ProductVariant::first()];
    $status = OrderStatus::where('slug', $statusSlug)->firstOrFail();
    $order = Order::create([
        'number' => $number, 'name' => 'Rahim Uddin', 'phone' => '07', 'email' => 'proof@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 10.99, 'delivery_fee' => 0, 'total' => 10.99, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'status_id' => $status->id, 'status_slug' => $status->slug,
        'created_at' => $created, 'updated_at' => $created,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $f['product']->id, 'product_variant_id' => $f['variant']->id,
        'product_name' => 'Lamb Leg', 'variant_sku' => 'LAMB-500', 'unit_price' => 10.99, 'qty' => 1, 'line_total' => 10.99,
    ]);
}

test('recent order shows an anonymised sales toast', function () {
    seedProofGrocery();
    seedProofOrder('EGF-30001', 'confirmed', now()->subHours(2)->toDateTimeString());

    $this->get('/')->assertOk()
        ->assertSee('data-proof', false)
        ->assertSee('Rahim', false)
        ->assertSee('Lamb Leg', false)
        ->assertDontSee('Rahim Uddin', false);
});

test('cancelled and stale orders never show', function () {
    seedProofGrocery();
    seedProofOrder('EGF-30001', 'cancelled', now()->subHours(2)->toDateTimeString());
    seedProofOrder('EGF-30002', 'confirmed', now()->subDays(10)->toDateTimeString());

    $this->get('/')->assertOk()->assertDontSee('data-proof', false);
});

test('admin can switch the popup off', function () {
    seedProofGrocery();
    seedProofOrder('EGF-30001', 'confirmed', now()->subHours(2)->toDateTimeString());
    Setting::put('social_proof_enabled', '0');

    $this->get('/')->assertOk()->assertDontSee('data-proof', false);
});
