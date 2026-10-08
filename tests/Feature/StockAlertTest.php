<?php

use App\Mail\StockAlertMail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function seedAlertGrocery(bool $inStock = false, ?float $offer = null): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Rice', 'slug' => 'rice', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'RICE-1KG', 'mrp' => 10.00, 'offer_price' => $offer,
        'is_default' => true, 'in_stock' => $inStock, 'status' => true, 'sort_order' => 0,
    ]);

    return compact('product', 'variant');
}

function alertAdmin(): User
{
    return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);
}

test('out-of-stock page offers the watch and subscribing is idempotent', function () {
    $f = seedAlertGrocery(false);

    $this->get('/product/rice')->assertOk()
        ->assertSee('out of stock', false)
        ->assertSee('Notify me', false);

    $payload = ['email' => 'Watcher@Example.com', 'product_id' => $f['product']->id, 'product_variant_id' => $f['variant']->id, 'type' => 'back_in_stock'];
    $this->postJson(route('notify.store'), $payload)->assertOk()->assertJson(['success' => true]);
    $this->postJson(route('notify.store'), $payload)->assertOk();
    $this->postJson('/api/notify', [...$payload, 'email' => 'app@example.com'])->assertOk();

    expect(StockAlert::where('email', 'watcher@example.com')->count())->toBe(1);
    $this->postJson(route('notify.store'), [...$payload, 'email' => 'bad'])->assertStatus(422);
});

test('restock sends the mail and closes the alert', function () {
    Mail::fake();
    $f = seedAlertGrocery(false);
    $admin = alertAdmin();

    $this->postJson(route('notify.store'), [
        'email' => 'watcher@example.com', 'product_id' => $f['product']->id,
        'product_variant_id' => $f['variant']->id, 'type' => 'back_in_stock',
    ])->assertOk();

    $this->actingAs($admin)->post(route('product-variants.toggleStock'), ['id' => $f['variant']->id])->assertOk();

    Mail::assertSent(StockAlertMail::class, fn ($mail) => $mail->hasTo('watcher@example.com'));
    expect(StockAlert::first()->is_sent)->toBeTrue();
});

test('no mail while still out of stock', function () {
    Mail::fake();
    $f = seedAlertGrocery(false);

    $this->postJson(route('notify.store'), [
        'email' => 'watcher@example.com', 'product_id' => $f['product']->id,
        'product_variant_id' => $f['variant']->id, 'type' => 'back_in_stock',
    ])->assertOk();

    StockAlert::fulfillFor($f['variant']->fresh());

    Mail::assertNothingSent();
    expect(StockAlert::first()->is_sent)->toBeFalse();
});

test('price drop below the watched price sends the mail', function () {
    Mail::fake();
    $f = seedAlertGrocery(true);
    $admin = alertAdmin();

    $this->postJson(route('notify.store'), [
        'email' => 'watcher@example.com', 'product_id' => $f['product']->id,
        'product_variant_id' => $f['variant']->id, 'type' => 'price_drop',
    ])->assertOk();
    expect((float) StockAlert::first()->target_price)->toBe(10.00);

    $this->actingAs($admin)->post(route('product-variants.update', $f['variant']->id), [
        'mrp' => 10.00, 'offer_price' => 7.50,
    ])->assertOk();

    Mail::assertSent(StockAlertMail::class, fn ($mail) => $mail->hasTo('watcher@example.com'));
});

test('admin reviews and deletes alerts', function () {
    $f = seedAlertGrocery(false);
    StockAlert::create([
        'email' => 'watcher@example.com', 'product_id' => $f['product']->id,
        'product_variant_id' => $f['variant']->id, 'type' => 'back_in_stock', 'target_price' => 10.00,
    ]);
    $admin = alertAdmin();

    $this->get(route('stock-alerts.index'))->assertRedirect(route('login'));
    $this->actingAs($admin)->get(route('stock-alerts.index'))->assertOk()->assertSee('watcher@example.com', false);
    $this->actingAs($admin)->delete(route('stock-alerts.delete', StockAlert::first()->id))
        ->assertRedirect(route('stock-alerts.index'));
    expect(StockAlert::count())->toBe(0);
});
