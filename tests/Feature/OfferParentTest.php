<?php

use App\Http\Controllers\BagController;
use App\Models\BogoOffer;
use App\Models\BundleOffer;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function offerFixtures(): array
{
    $cat = Category::create(['name' => 'Meat', 'slug' => 'meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Chicken', 'slug' => 'chicken', 'status' => true]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CHK-500', 'mrp' => 10.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'offer-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('product', 'variant', 'admin');
}

function offerLive(string $name = 'Weekend'): Offer
{
    return Offer::create(['name' => $name, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'status' => true, 'sort_order' => 0]);
}

test('attached rows follow the parent window and switch', function () {
    $f = offerFixtures();
    $parent = offerLive();

    $bogo = BogoOffer::create(['offer_id' => $parent->id, 'product_id' => $f['product']->id, 'buy_qty' => 2, 'free_qty' => 1, 'status' => true]);
    $flash = FlashSale::create(['offer_id' => $parent->id, 'product_id' => $f['product']->id, 'promo_price' => 8.00, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'status' => true]);
    $bundle = BundleOffer::create(['offer_id' => $parent->id, 'name' => 'Deal', 'required_qty' => 2, 'bundle_price' => 15.00, 'status' => true]);

    // No own dates — parent window governs.
    expect($bogo->isLive())->toBeTrue()
        ->and($flash->isLive())->toBeTrue()
        ->and($bundle->isLive())->toBeTrue();

    $parent->update(['ends_at' => now()->subMinute()]);
    expect($bogo->fresh()->isLive())->toBeFalse()
        ->and($flash->fresh()->isLive())->toBeFalse()
        ->and($bundle->fresh()->isLive())->toBeFalse();
});

test('switching the parent off pauses everything at once', function () {
    $f = offerFixtures();
    $parent = offerLive();
    BogoOffer::create(['offer_id' => $parent->id, 'product_id' => $f['product']->id, 'buy_qty' => 2, 'free_qty' => 1, 'status' => true]);

    session()->put('bag', [$f['variant']->id => 3]);
    expect(BagController::detailed()['bogo_discount'])->toBe(10.00);

    $parent->update(['status' => false]);
    expect(BagController::detailed()['bogo_discount'])->toBe(0.0);
});

test('standalone rows ignore parents entirely', function () {
    $f = offerFixtures();
    offerLive();
    BogoOffer::create(['product_id' => $f['product']->id, 'buy_qty' => 2, 'free_qty' => 1, 'status' => true]);

    session()->put('bag', [$f['variant']->id => 3]);
    expect(BagController::detailed()['bogo_discount'])->toBe(10.00);
});

test('admin can run a campaign with items underneath', function () {
    $f = offerFixtures();

    $this->actingAs($f['admin'])->get(route('offers.index'))->assertOk()->assertSee('Offers');

    $this->actingAs($f['admin'])->post(route('offers.store'), [
        'name' => 'Weekend', 'starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDay()->format('Y-m-d\TH:i'), 'is_active' => '1',
    ])->assertRedirect(route('offers.index'));
    $offer = Offer::firstOrFail();

    $this->actingAs($f['admin'])->post(route('bogo.store'), [
        'product_id' => $f['product']->id, 'offer_id' => $offer->id, 'buy_qty' => 2, 'free_qty' => 1, 'is_active' => '1',
    ])->assertRedirect(route('bogo.index'));

    $this->actingAs($f['admin'])->get(route('offers.edit', $offer->id))
        ->assertOk()->assertSee('Weekend')->assertSee('Chicken');

    $this->actingAs($f['admin'])->post(route('offers.toggleStatus'), ['id' => $offer->id])->assertOk();
    expect($offer->fresh()->status)->toBeFalse();
    expect(BogoOffer::firstOrFail()->isLive())->toBeFalse();

    $this->actingAs($f['admin'])->delete(route('offers.delete', $offer->id))->assertRedirect();
    expect(BogoOffer::firstOrFail()->offer_id)->toBeNull();
});
