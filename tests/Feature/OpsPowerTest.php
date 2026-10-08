<?php

use App\Mail\BagReminder;
use App\Models\BagSnapshot;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function seedOpsGrocery(bool $inStock = true): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Rice', 'slug' => 'rice', 'status' => true]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'RICE-1KG', 'mrp' => 10.00, 'offer_price' => 9.00,
        'is_default' => true, 'in_stock' => $inStock, 'status' => true, 'sort_order' => 0,
        'expires_at' => now()->addDays(6)->format('Y-m-d'),
    ]);
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '5.00');
    Setting::put('delivery_free_over', '50.00');

    return compact('product', 'variant', 'admin');
}

test('stock watch lists out-of-stock packs with waiting alerts', function () {
    $f = seedOpsGrocery(false);
    StockAlert::create([
        'email' => 'w@example.com', 'product_id' => $f['product']->id, 'product_variant_id' => $f['variant']->id,
        'type' => 'back_in_stock', 'target_price' => 9.00,
    ]);

    $this->actingAs($f['admin'])->get(route('stock-watch.index'))->assertOk()
        ->assertSee('Out of stock', false)
        ->assertSee('RICE-1KG', false)
        ->assertSee('Expiring within 14 days', false);
});

test('best-before shows on the product page', function () {
    seedOpsGrocery(true);

    $this->get('/product/rice')->assertOk()->assertSee('Best before:', false);
});

test('driver assigns and shows on tracking', function () {
    $f = seedOpsGrocery();
    $order = Order::create([
        'number' => 'EGF-50001', 'name' => 'N', 'phone' => '07123456789', 'email' => 't@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 9, 'delivery_fee' => 0, 'total' => 9, 'payment_method' => 'cod',
        'payment_status' => 'unpaid',
        'status_id' => OrderStatus::where('slug', 'new')->first()->id, 'status_slug' => 'new',
    ]);

    $this->actingAs($f['admin'])->post(route('orders.assignDriver', $order->id), ['driver_name' => 'Kamal'])
        ->assertRedirect(route('orders.show', $order->id));
    expect($order->fresh()->driver_name)->toBe('Kamal');

    $this->post('/track', ['number' => 'EGF-50001', 'phone' => '07123456789'])->assertOk()
        ->assertSee('Your driver:', false)
        ->assertSee('Kamal', false);
});

test('signed-in bags snapshot and stale ones get reminded', function () {
    Mail::fake();
    $f = seedOpsGrocery();
    $user = User::create(['name' => 'Shopper', 'email' => 'shopper@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);

    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['variant']->id, 'qty' => 1])->assertOk();
    $snap = BagSnapshot::where('user_id', $user->id)->firstOrFail();
    expect((float) $snap->subtotal)->toBe(9.00);

    // Fresh bag: no mail.
    $this->artisan('app:remind-bags')->assertSuccessful();
    Mail::assertNothingSent();

    // Stale 25h, nothing ordered since: mail + stamp.
    $snap->update(['updated_at' => now()->subHours(25), 'created_at' => now()->subHours(25)]);
    $this->artisan('app:remind-bags')->assertSuccessful();
    Mail::assertSent(BagReminder::class, fn ($mail) => $mail->hasTo('shopper@example.com'));
    expect($snap->fresh()->reminded_at)->not->toBeNull();

    // Second run: already reminded, quiet.
    Mail::fake();
    $this->artisan('app:remind-bags')->assertSuccessful();
    Mail::assertNothingSent();
});
