<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function refundFixtures(): array
{
    foreach (['new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111111', 'sort_order' => $i, 'is_active' => true]);
    }
    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'refund-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('variant', 'admin');
}

function refundPaidOrder(string $method = 'stripe', float $total = 42.99): Order
{
    $new = OrderStatus::where('slug', 'new')->firstOrFail();

    return Order::create([
        'number' => 'EGF-'.random_int(20001, 29999),
        'user_id' => null,
        'name' => 'Shopper', 'phone' => '071', 'email' => 'refund-shop@example.com',
        'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->toDateString(),
        'subtotal' => $total, 'delivery_fee' => 0, 'total' => $total,
        'payment_method' => $method, 'payment_status' => 'paid',
        'payment_reference' => $method === 'stripe' ? 'pi_test_123' : 'CAPTURE-123',
        'status_id' => $new->id, 'status_slug' => 'new',
    ]);
}

test('admin can partially then fully refund a stripe order', function () {
    $f = refundFixtures();
    $admin = $f['admin'];
    $order = refundPaidOrder('stripe');
    Setting::put('stripe_secret', 'sk_test_123');
    Setting::put('stripe_publishable', 'pk_test_123');

    Http::fake(['*api.stripe.com/*' => Http::response(['id' => 're_1', 'status' => 'succeeded'], 200)]);

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 10.00, 'reason' => 'Missing item'])
        ->assertRedirect(route('orders.show', $order->id));
    Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/refunds') && (int) $r['amount'] === 1000);

    expect($order->fresh()->refunded_amount)->toBe('10.00')
        ->and($order->fresh()->payment_status)->toBe('partially_refunded')
        ->and($order->fresh()->refundableAmount())->toBe(32.99);

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 32.99])
        ->assertRedirect(route('orders.show', $order->id));

    expect($order->fresh()->payment_status)->toBe('refunded')
        ->and($order->fresh()->refundableAmount())->toBe(0.0);
    expect($order->histories()->where('note', 'like', '%Refunded £10.00%')->exists())->toBeTrue();
});

test('paypal refunds hit the capture endpoint', function () {
    $f = refundFixtures();
    $admin = $f['admin'];
    $order = refundPaidOrder('paypal', 30.00);
    Setting::put('paypal_client_id', 'cid');
    Setting::put('paypal_secret', 'sec');

    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'tok'], 200),
        '*/refund' => Http::response(['id' => 'REF-1', 'status' => 'COMPLETED'], 201),
    ]);

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 30.00])->assertRedirect();

    Http::assertSent(fn ($r) => str_contains($r->url(), '/CAPTURE-123/refund'));
    expect($order->fresh()->payment_status)->toBe('refunded');
});

test('over-refunds, cod and unpaid orders are refused', function () {
    $f = refundFixtures();
    $admin = $f['admin'];
    $order = refundPaidOrder('stripe', 20.00);

    Http::fake(['api.stripe.com/*' => Http::response(['id' => 're_1'], 200)]);

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 20.01])
        ->assertSessionHasErrors('amount');
    expect($order->fresh()->refunded_amount)->toBe('0.00');

    $order->update(['payment_method' => 'cod', 'payment_status' => 'unpaid']);
    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 5.00])
        ->assertSessionHasErrors('amount');
});

test('a refused gateway leaves the order untouched', function () {
    $f = refundFixtures();
    $admin = $f['admin'];
    $order = refundPaidOrder('stripe');
    Setting::put('stripe_secret', 'sk_test_123');
    Setting::put('stripe_publishable', 'pk_test_123');

    Http::fake(['*api.stripe.com/*' => Http::response(['error' => ['message' => 'Charge already refunded']], 400)]);

    $response = $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 5.00]);
    $response->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');

    expect($order->fresh()->refunded_amount)->toBe('0.00')
        ->and($order->fresh()->payment_status)->toBe('paid');
});

test('refunds recompute the vat slice of the remainder', function () {
    $f = refundFixtures();
    $admin = $f['admin'];
    $order = refundPaidOrder('stripe', 120.00);
    $order->update(['vat_percent' => 20, 'vat_amount' => 20.00]);
    Setting::put('stripe_secret', 'sk_test_123');
    Setting::put('stripe_publishable', 'pk_test_123');

    Http::fake(['*api.stripe.com/*' => Http::response(['id' => 're_vat'], 200)]);

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 20.00])->assertRedirect();

    // £100 left at 20% inclusive → £16.67 VAT.
    expect((float) $order->fresh()->vat_amount)->toBe(16.67);
});
