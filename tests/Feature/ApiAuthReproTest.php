<?php

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tokenShopperFixtures(): array
{
    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots', 'status' => true]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 2.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $shopper = User::create(['name' => 'Hearts', 'email' => 'hearts@example.com', 'password' => bcrypt('password'), 'user_type' => 0, 'status' => 1]);

    return compact('product', 'variant', 'shopper');
}

function bearer(array $f): array
{
    return ['Authorization' => 'Bearer '.ApiToken::issue($f['shopper'])];
}

test('bearer token can toggle favourites as the very first request', function () {
    $f = tokenShopperFixtures();

    $this->withHeaders(bearer($f))
        ->postJson('/api/favourites/toggle', ['product_id' => $f['product']->id])
        ->assertOk()
        ->assertJsonPath('favourited', true);

    $this->withHeaders(bearer($f))
        ->postJson('/api/favourites/toggle', ['product_id' => $f['product']->id])
        ->assertOk()
        ->assertJsonPath('favourited', false);
});

test('bearer token can post and delete a review as the very first request', function () {
    $f = tokenShopperFixtures();

    $id = $this->withHeaders(bearer($f))
        ->postJson('/api/reviews', ['product_id' => $f['product']->id, 'rating' => 5, 'title' => 'Fresh', 'body' => 'Very fresh carrots.'])
        ->assertOk()
        ->json('review.id');

    expect($id)->not->toBeNull();

    $this->withHeaders(bearer($f))->deleteJson('/api/reviews/'.$id)->assertOk();
});

test('bearer token can read lists and orders', function () {
    $f = tokenShopperFixtures();

    $this->withHeaders(bearer($f))->getJson('/api/lists')->assertOk()->assertJsonStructure(['lists']);
    $this->withHeaders(bearer($f))->getJson('/api/orders')->assertOk();
    $this->withHeaders(bearer($f))->postJson('/api/lists', ['name' => 'Weekly'])->assertOk();
});
