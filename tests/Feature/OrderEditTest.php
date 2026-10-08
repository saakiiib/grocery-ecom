<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedEditableGrocery(): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $make = function (string $name, string $slug, string $sku, float $mrp, ?float $offer) use ($cat) {
        $product = Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => $slug, 'status' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => $sku, 'mrp' => $mrp, 'offer_price' => $offer,
            'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
        ]);

        return compact('product', 'variant');
    };
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);
    foreach (['new', 'confirmed', 'packed', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '5.00');
    Setting::put('delivery_free_over', '50.00');

    return ['rice' => $make('Rice', 'rice', 'RICE-1KG', 10.00, 9.00), 'beans' => $make('Beans', 'beans', 'BEAN-500', 6.00, null), 'admin' => $admin, 'slot' => $slot];
}

function seedEditableOrder(array $f, string $statusSlug = 'new', string $payment = 'unpaid', float $paid = 0.0, string $method = 'cod'): Order
{
    $status = OrderStatus::where('slug', $statusSlug)->firstOrFail();
    $order = Order::create([
        'number' => 'EGF-6'.random_int(1000, 9999), 'name' => 'N', 'phone' => '07', 'email' => 'e@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'delivery_slot_id' => $f['slot']->id, 'delivery_slot_label' => $f['slot']->label(),
        'subtotal' => 9.00, 'delivery_fee' => 2.99, 'total' => 11.99,
        'payment_method' => $method, 'payment_status' => $payment, 'amount_paid' => $paid,
        'status_id' => $status->id, 'status_slug' => $status->slug,
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $f['rice']['product']->id, 'product_variant_id' => $f['rice']['variant']->id,
        'product_name' => 'Rice', 'variant_sku' => 'RICE-1KG', 'pack_label' => '1KG',
        'unit_price' => 9.00, 'qty' => 1, 'line_total' => 9.00,
    ]);

    return $order;
}

test('admin adds a pack at the shelf price and totals rebuild', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f);

    $this->actingAs($f['admin'])->post(route('orders.items.store', $order->id), [
        'variant_id' => $f['beans']['variant']->id, 'qty' => 2,
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect((float) $order->subtotal)->toBe(21.00)
        ->and((float) $order->delivery_fee)->toBe(2.99)
        ->and((float) $order->total)->toBe(23.99)
        ->and($order->items)->toHaveCount(2)
        ->and($order->histories()->where('note', 'like', '%Added%')->exists())->toBeTrue();
});

test('adding enough flips the order to free delivery', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f);

    $this->actingAs($f['admin'])->post(route('orders.items.store', $order->id), [
        'variant_id' => $f['beans']['variant']->id, 'qty' => 8,
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect((float) $order->subtotal)->toBe(57.00)
        ->and((float) $order->delivery_fee)->toBe(0.0)
        ->and((float) $order->total)->toBe(57.00);
});

test('bulk save changes qty and price, zero removes', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f);
    $item = $order->items()->firstOrFail();

    $this->actingAs($f['admin'])->post(route('orders.items.save', $order->id), [
        'items' => [['id' => $item->id, 'qty' => 3, 'unit_price' => 8.00]],
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect((float) $order->subtotal)->toBe(24.00)->and((float) $order->total)->toBe(26.99);

    $this->actingAs($f['admin'])->post(route('orders.items.save', $order->id), [
        'items' => [['id' => $item->id, 'qty' => 0, 'unit_price' => 8.00]],
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect($order->items()->count())->toBe(0)
        ->and((float) $order->subtotal)->toBe(0.0)
        ->and((float) $order->total)->toBe(2.99);
});

test('paid orders keep their paid money straight through edits', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f, 'confirmed', 'paid', 11.99, 'stripe');

    // Shrink the bill: refundable grows, paid untouched.
    $item = $order->items()->firstOrFail();
    $this->actingAs($f['admin'])->post(route('orders.items.save', $order->id), [
        'items' => [['id' => $item->id, 'qty' => 1, 'unit_price' => 5.00]],
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect((float) $order->total)->toBe(7.99)
        ->and((float) $order->amount_paid)->toBe(11.99)
        ->and($order->refundableAmount())->toBe(11.99)
        ->and($order->payment_status)->toBe('paid');

    // Grow past what was paid: balance-due warning, paid untouched.
    $this->actingAs($f['admin'])->post(route('orders.items.store', $order->id), [
        'variant_id' => $f['beans']['variant']->id, 'qty' => 2,
    ])->assertRedirect(route('orders.show', $order->id));

    $order->refresh();
    expect((float) $order->total)->toBe(19.99)
        ->and((float) $order->amount_paid)->toBe(11.99)
        ->and($order->balanceDue())->toBe(8.00);
});

test('unavailable packs and final orders refuse edits', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f);
    $f['beans']['variant']->update(['in_stock' => false]);

    $this->actingAs($f['admin'])->post(route('orders.items.store', $order->id), [
        'variant_id' => $f['beans']['variant']->id, 'qty' => 1,
    ])->assertRedirect(route('orders.show', $order->id))->assertSessionHas('error');
    expect($order->items()->count())->toBe(1);

    $done = seedEditableOrder($f, 'delivered');
    $this->actingAs($f['admin'])->post(route('orders.items.store', $done->id), [
        'variant_id' => $f['rice']['variant']->id, 'qty' => 1,
    ])->assertRedirect(route('orders.show', $done->id))->assertSessionHas('error');
    $this->actingAs($f['admin'])->post(route('orders.items.save', $done->id), [
        'items' => [['id' => $done->items()->first()->id, 'qty' => 5, 'unit_price' => 9.00]],
    ])->assertRedirect(route('orders.show', $done->id))->assertSessionHas('error');
    $this->actingAs($f['admin'])->delete(route('orders.items.delete', [$done->id, $done->items()->first()->id]))
        ->assertRedirect(route('orders.show', $done->id))->assertSessionHas('error');
});

test('paid markers record gross received on new payments', function () {
    $f = seedEditableGrocery();
    $order = seedEditableOrder($f, 'new', 'unpaid', 0.0, 'stripe');

    $order->update(['payment_status' => 'paid', 'amount_paid' => $order->total]);
    expect($order->refundableAmount())->toBe(11.99);
    expect(seedEditableOrder($f, 'new', 'paid', 11.99, 'cod')->refundableAmount())->toBe(0.0);
});
