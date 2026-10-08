<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedProgressGrocery(float $price = 10.99): ProductVariant
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Rice', 'slug' => 'rice']);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'RICE-1KG', 'mrp' => $price, 'offer_price' => $price,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);

    return $variant;
}

test('bag json carries delivery thresholds', function () {
    $this->getJson('/api/bag')->assertOk()
        ->assertJsonFragment(['min_order' => 15])
        ->assertJsonFragment(['free_over' => 50]);

    Setting::put('delivery_min_order', '20');
    Setting::put('delivery_free_over', '60');

    $this->getJson('/api/bag')->assertOk()
        ->assertJsonFragment(['min_order' => 20])
        ->assertJsonFragment(['free_over' => 60]);
});

test('bag page shows minimum-order progress when empty', function () {
    $this->get('/bag')->assertOk()
        ->assertSee('delivery-progress', false)
        ->assertSee('Minimum order', false);
});

test('bag page nudges toward free delivery then unlocks', function () {
    $variant = seedProgressGrocery(10.99);
    Setting::put('delivery_min_order', '5');
    Setting::put('delivery_free_over', '50');

    $this->postJson('/bag/add', ['variant_id' => $variant->id, 'qty' => 1])->assertOk()
        ->assertJsonFragment(['min_order' => 5])
        ->assertJsonFragment(['free_over' => 50]);

    $this->get('/bag')->assertOk()->assertSee('Add £', false)->assertSee('FREE delivery', false);

    Setting::put('delivery_free_over', '5');

    $this->get('/bag')->assertOk()->assertSee('unlocked FREE delivery', false);
    $this->get('/checkout')->assertOk()->assertSee('unlocked FREE delivery', false);
});

test('checkout page shows progress bar', function () {
    $variant = seedProgressGrocery();
    $this->postJson('/bag/add', ['variant_id' => $variant->id, 'qty' => 1])->assertOk();

    $this->get('/checkout')->assertOk()
        ->assertSee('delivery-progress', false)
        ->assertSee('Free delivery over', false);
});
