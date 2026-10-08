<?php

use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedDealGrocery(float $mrp = 12.00, float $promo = 8.00): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Chicken', 'slug' => 'chicken', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'CHK-500', 'mrp' => $mrp, 'offer_price' => null,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    $sale = FlashSale::create([
        'product_id' => $product->id, 'promo_price' => $promo,
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(5), 'status' => true, 'sort_order' => 0,
    ]);

    return compact('product', 'variant', 'sale');
}

test('homepage shows deal of the day with countdown when a flash is live', function () {
    seedDealGrocery();

    $this->get('/')->assertOk()
        ->assertSee('Deal of the day', false)
        ->assertSee('Chicken', false)
        ->assertSee('data-deal-countdown', false);
});

test('homepage hides the deal when no flash is live', function () {
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    Product::create(['category_id' => $cat->id, 'name' => 'Chicken', 'slug' => 'chicken', 'status' => true]);

    $this->get('/')->assertOk()->assertDontSee('Deal of the day', false);
});

test('expired flash never becomes the deal', function () {
    $f = seedDealGrocery();
    $f['sale']->update(['ends_at' => now()->subHour()]);

    $this->get('/')->assertOk()->assertDontSee('Deal of the day', false);
});

test('soonest-ending flash wins the deal', function () {
    $f = seedDealGrocery();
    $cat = Category::first();
    $late = Product::create(['category_id' => $cat->id, 'name' => 'Beef', 'slug' => 'beef', 'status' => true]);
    $lateVariant = ProductVariant::create([
        'product_id' => $late->id, 'sku' => 'BEF-500', 'mrp' => 20.00, 'offer_price' => null,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    FlashSale::create([
        'product_id' => $late->id, 'promo_price' => 15.00,
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDays(3), 'status' => true, 'sort_order' => 0,
    ]);

    $html = $this->get('/')->assertOk()->getContent();
    expect(substr_count($html, 'Deal of the day'))->toBe(1);
    expect($html)->toContain('Chicken');
});

test('api home carries the deal with an end time', function () {
    seedDealGrocery();

    $this->getJson('/api/home')->assertOk()
        ->assertJsonPath('deal_of_day.card.name', 'Chicken')
        ->assertJsonStructure(['deal_of_day' => ['card', 'ends_at', 'ends_label']]);
});

test('api home has no deal without a live flash', function () {
    $this->getJson('/api/home')->assertOk()->assertJsonPath('deal_of_day', null);
});
