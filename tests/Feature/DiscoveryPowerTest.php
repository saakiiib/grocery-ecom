<?php

use App\Http\Controllers\BagController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedDisco2Grocery(): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Chicken Breast', 'slug' => 'chicken-breast', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'CHK-BRST-500', 'mrp' => 8.00, 'offer_price' => 7.00,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('product', 'variant', 'admin');
}

test('search finds packs by SKU on web and api', function () {
    seedDisco2Grocery();

    $this->get('/shop?q=CHK-BRST')->assertOk()->assertSee('Chicken Breast', false);
    $this->getJson('/api/search?q=chk-brst-500')->assertOk()->assertJsonCount(1, 'results');
    $this->getJson('/api/trending-searches')->assertOk()->assertJsonFragment(['chk-brst-500']);
    $this->get('/shop')->assertOk()->assertSee('Trending:', false)->assertSee('chk-brst-500', false);
});

test('short queries are never logged', function () {
    $this->getJson('/api/search?q=x')->assertOk();
    expect(SearchLog::count())->toBe(0);
});

test('recipes list, show and fill the bag in one tap', function () {
    $f = seedDisco2Grocery();
    $recipe = Recipe::create(['title' => 'Chicken Curry', 'slug' => 'chicken-curry', 'body' => 'Cook it low and slow.', 'servings' => 'Serves 4', 'status' => true]);
    $recipe->ingredients()->create(['product_id' => $f['product']->id, 'product_variant_id' => $f['variant']->id, 'qty' => 2]);

    $this->get('/recipes')->assertOk()->assertSee('Chicken Curry', false);
    $this->get('/recipes/chicken-curry')->assertOk()
        ->assertSee('Cook it low and slow', false)
        ->assertSee('Add all ingredients', false);

    $this->post(route('recipes.addAll', $recipe->id))->assertRedirect(route('bag'));
    expect(BagController::bag())->toBe([$f['variant']->id => 2]);

    $this->getJson('/api/recipes')->assertOk()->assertJsonCount(1, 'recipes');
    $this->getJson('/api/recipes/chicken-curry')->assertOk()
        ->assertJsonPath('recipe.title', 'Chicken Curry')
        ->assertJsonPath('ingredients.0.qty', 2);
    $this->postJson('/api/recipes/'.$recipe->id.'/add-all')->assertOk()->assertJsonPath('count', 4);
});

test('admin manages recipes', function () {
    $f = seedDisco2Grocery();

    $this->actingAs($f['admin'])->post(route('admin.recipes.store'), [
        'title' => 'Chicken Curry', 'body' => 'Cook it.', 'servings' => 'Serves 4', 'is_active' => 1,
        'ingredients' => [['product_id' => $f['product']->id, 'variant_id' => '', 'qty' => 2]],
    ])->assertRedirect(route('admin.recipes.edit', Recipe::first()->id));
    expect(Recipe::first()->ingredients)->toHaveCount(1);

    $this->actingAs($f['admin'])->get(route('admin.recipes.index'))->assertOk()->assertSee('Chicken Curry', false);
    $this->actingAs($f['admin'])->delete(route('admin.recipes.delete', Recipe::first()->id))
        ->assertRedirect(route('admin.recipes.index'));
    expect(Recipe::count())->toBe(0);
});

test('halal promise shows shop-wide without tagging every product', function () {
    $f = seedDisco2Grocery();
    $f['product']->update(['is_halal' => false]);

    $this->get('/')->assertOk()
        ->assertSee('100% Halal, always', false)
        ->assertSee('100% Halal promise', false);
    expect($f['product']->fresh()->is_halal)->toBeFalse();
});
