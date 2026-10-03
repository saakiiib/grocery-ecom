<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewFixtures(): array
{
    $cat = Category::create(['name' => 'Fresh Meat', 'slug' => 'fresh-meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg']);
    ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $shopper = User::create(['name' => 'Shopper', 'email' => 'review-shopper@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'review-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('product', 'shopper', 'admin');
}

function reviewPayload(int $productId): array
{
    return ['product_id' => $productId, 'rating' => 5, 'title' => 'Great quality', 'body' => 'Really fresh and tasty.'];
}

test('guests are sent to login when submitting a review', function () {
    ['product' => $product] = reviewFixtures();

    $this->postJson(route('reviews.store'), reviewPayload($product->id))
        ->assertUnauthorized();
});

test('a signed-in shopper can review and it shows on the product page', function () {
    ['product' => $product, 'shopper' => $shopper] = reviewFixtures();

    $this->actingAs($shopper)
        ->postJson(route('reviews.store'), reviewPayload($product->id))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('avg', 5)
        ->assertJsonPath('count', 1);

    expect(ProductReview::count())->toBe(1);

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertSee('Ratings & reviews', false)
        ->assertSee('Great quality')
        ->assertSee('5.0 out of 5');
});

test('review input is validated', function () {
    ['product' => $product, 'shopper' => $shopper] = reviewFixtures();

    $this->actingAs($shopper)->postJson(route('reviews.store'), [
        'product_id' => $product->id, 'rating' => 6, 'body' => 'Too high.',
    ])->assertStatus(422)->assertJsonValidationErrors('rating');

    $this->actingAs($shopper)->postJson(route('reviews.store'), [
        'product_id' => $product->id, 'rating' => 4,
    ])->assertStatus(422)->assertJsonValidationErrors('body');

    $this->actingAs($shopper)->postJson(route('reviews.store'), [
        'product_id' => 999999, 'rating' => 4, 'body' => 'Ghost product.',
    ])->assertStatus(422)->assertJsonValidationErrors('product_id');

    expect(ProductReview::count())->toBe(0);
});

test('one review per shopper per product — resubmitting updates it', function () {
    ['product' => $product, 'shopper' => $shopper] = reviewFixtures();

    $this->actingAs($shopper)->postJson(route('reviews.store'), reviewPayload($product->id))->assertOk();
    $this->actingAs($shopper)->postJson(route('reviews.store'), [
        'product_id' => $product->id, 'rating' => 3, 'title' => 'Changed my mind', 'body' => 'It was just okay.',
    ])->assertOk()->assertJsonPath('avg', 3)->assertJsonPath('count', 1);

    expect(ProductReview::count())->toBe(1)
        ->and(ProductReview::first()->rating)->toBe(3);
});

test('hidden reviews stay out of the average and the page', function () {
    ['product' => $product, 'shopper' => $shopper] = reviewFixtures();
    $other = User::create(['name' => 'Other', 'email' => 'review-other@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);

    $this->actingAs($shopper)->postJson(route('reviews.store'), reviewPayload($product->id))->assertOk();
    ProductReview::create(['product_id' => $product->id, 'user_id' => $other->id, 'rating' => 1, 'body' => 'Hidden one.', 'status' => false]);

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertSee('5.0 out of 5')
        ->assertDontSee('Hidden one.');
});

test('a shopper can remove their own review but not someone elses', function () {
    ['product' => $product, 'shopper' => $shopper] = reviewFixtures();
    $other = User::create(['name' => 'Other', 'email' => 'review-other2@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $mine = ProductReview::create(['product_id' => $product->id, 'user_id' => $shopper->id, 'rating' => 5, 'body' => 'Mine.', 'status' => true]);
    $theirs = ProductReview::create(['product_id' => $product->id, 'user_id' => $other->id, 'rating' => 4, 'body' => 'Theirs.', 'status' => true]);

    $this->actingAs($shopper)->deleteJson(route('reviews.destroy', $theirs->id))->assertNotFound();

    $this->actingAs($shopper)->deleteJson(route('reviews.destroy', $mine->id))
        ->assertOk()->assertJsonPath('count', 1);

    expect(ProductReview::count())->toBe(1);
});

test('admin can edit a review', function () {
    ['product' => $product, 'shopper' => $shopper, 'admin' => $admin] = reviewFixtures();
    $review = ProductReview::create(['product_id' => $product->id, 'user_id' => $shopper->id, 'rating' => 5, 'title' => 'Great', 'body' => 'Really fresh.', 'status' => true]);

    $this->actingAs($admin)->getJson(route('reviews.edit', $review->id))
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.rating', 5);

    $this->actingAs($admin)->postJson(route('reviews.update'), [
        'id' => $review->id, 'rating' => 2, 'title' => 'Corrected', 'body' => 'Actually just okay.',
    ])->assertOk()->assertJsonPath('success', true);

    expect($review->fresh()->rating)->toBe(2)
        ->and($review->fresh()->title)->toBe('Corrected');

    $this->actingAs($admin)->postJson(route('reviews.update'), [
        'id' => $review->id, 'rating' => 9, 'body' => 'Bad rating.',
    ])->assertStatus(422)->assertJsonValidationErrors('rating');
});

test('admin can list, hide and delete reviews', function () {
    ['product' => $product, 'shopper' => $shopper, 'admin' => $admin] = reviewFixtures();
    $review = ProductReview::create(['product_id' => $product->id, 'user_id' => $shopper->id, 'rating' => 5, 'body' => 'Nice.', 'status' => true]);

    $this->actingAs($admin)->get(route('reviews.index'))->assertOk();

    $this->actingAs($admin)->postJson(route('reviews.toggleStatus'), ['id' => $review->id])
        ->assertOk()->assertJsonPath('success', true);
    expect($review->fresh()->status)->toBeFalse();

    $this->get(route('product.show', $product->slug))->assertOk()->assertDontSee('5.0 out of 5');

    $this->actingAs($admin)->deleteJson(route('reviews.delete', $review->id))->assertOk();
    expect(ProductReview::count())->toBe(0);
});
