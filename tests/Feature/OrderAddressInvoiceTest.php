<?php

use App\Mail\OrderDelivered;
use App\Mail\OrderPlaced;
use App\Mail\OrderStatusUpdated;
use App\Models\Address;
use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function addrFixtures(): array
{
    foreach ([
        ['slug' => 'new', 'name' => 'New'],
        ['slug' => 'confirmed', 'name' => 'Confirmed'],
        ['slug' => 'packed', 'name' => 'Packed'],
        ['slug' => 'out_for_delivery', 'name' => 'Out for Delivery'],
        ['slug' => 'delivered', 'name' => 'Delivered'],
        ['slug' => 'cancelled', 'name' => 'Cancelled'],
    ] as $i => $s) {
        OrderStatus::create([...$s, 'color' => '#111111', 'sort_order' => $i, 'is_active' => true, 'is_final' => in_array($s['slug'], ['delivered', 'cancelled'], true)]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $shopper = User::create(['name' => 'Shopper', 'email' => 'addr-shopper@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    $admin = User::create(['name' => 'Admin', 'email' => 'addr-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('slot', 'product', 'variant', 'shopper', 'admin');
}

function addrPayload(int $slotId, array $over = []): array
{
    return array_merge([
        'name' => 'Shopper Name', 'phone' => '07123456789',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'Billing Name', 'billing_phone' => '07987654321',
        'billing_address' => '9 Bill Road', 'billing_city' => 'York', 'billing_postcode' => 'YO1 1AA',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slotId,
        'payment_method' => 'cod',
        'privacy' => true,
    ], $over);
}

function addrPlaceOrder($test, array $f, ?User $user = null): Order
{
    $bag = [$f['variant']->id => 2];
    if ($user) {
        $test->actingAs($user);
    }
    session()->put('bag', $bag);
    $payload = addrPayload($f['slot']->id, ['email' => 'addr-guest@example.com']);
    $redirect = $test->postJson(route('checkout.place'), $payload)->assertOk()->json('redirect');
    preg_match('/EGF-\d+/', $redirect, $m);

    return Order::where('number', $m[0])->firstOrFail();
}

test('checkout requires billing fields', function () {
    ['slot' => $slot, 'variant' => $variant] = addrFixtures();
    session()->put('bag', [$variant->id => 2]);

    $payload = addrPayload($slot->id);
    unset($payload['billing_postcode']);

    $this->postJson(route('checkout.place'), $payload)
        ->assertStatus(422)->assertJsonValidationErrors('billing_postcode');
});

test('split bill-to and ship-to snapshot onto the order', function () {
    $f = addrFixtures();
    $order = addrPlaceOrder($this, $f);

    expect($order->address)->toBe('1 Market Street')
        ->and($order->billing_address)->toBe('9 Bill Road')
        ->and($order->billing_city)->toBe('York')
        ->and($order->billTo()['name'])->toBe('Billing Name');
});

test('vat snapshots from company details', function () {
    $f = addrFixtures();
    CompanyDetails::cached()->update(['vat_percent' => 20]);

    $order = addrPlaceOrder($this, $f);

    // 2 × £20 + £2.99 fee = £42.99; VAT portion = 42.99 × 20/120.
    expect((float) $order->vat_percent)->toBe(20.0)
        ->and((float) $order->vat_amount)->toBe(round(42.99 * 20 / 120, 2));
});

test('address book keeps two defaults and rolls them on delete', function () {
    ['shopper' => $shopper] = addrFixtures();

    $home = ['label' => 'Home', 'name' => 'Shopper', 'phone' => '071', 'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA'];
    $this->actingAs($shopper)->post(route('account.addresses.store'), $home)->assertRedirect();
    $first = Address::first();
    expect($first->is_default_delivery)->toBeTrue()->and($first->is_default_billing)->toBeTrue();

    $work = ['label' => 'Work', 'name' => 'Shopper', 'phone' => '072', 'address' => '2 Office Rd', 'city' => 'Leeds', 'postcode' => 'LS2 2BB'];
    $this->actingAs($shopper)->post(route('account.addresses.store'), $work)->assertRedirect();
    $second = Address::where('label', 'Work')->firstOrFail();
    expect($second->is_default_delivery)->toBeFalse();

    $this->actingAs($shopper)->post(route('account.addresses.default', $second->id), ['type' => 'delivery'])->assertRedirect();
    expect($second->fresh()->is_default_delivery)->toBeTrue()
        ->and($first->fresh()->is_default_delivery)->toBeFalse();

    $this->actingAs($shopper)->delete(route('account.addresses.destroy', $second->id))->assertRedirect();
    expect($first->fresh()->is_default_delivery)->toBeTrue()
        ->and(Address::count())->toBe(1);
});

test('account page lists the address book', function () {
    ['shopper' => $shopper] = addrFixtures();
    $shopper->addresses()->create(['label' => 'Home', 'name' => 'Shopper', 'phone' => '071', 'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA', 'is_default_delivery' => true, 'is_default_billing' => true]);

    $this->actingAs($shopper)->get(route('account'))->assertOk()->assertSee('My addresses')->assertSee('Default delivery');
});

test('every status move mails once with the right subject', function () {
    $f = addrFixtures();
    Mail::fake();
    $order = addrPlaceOrder($this, $f);

    Mail::assertSent(OrderPlaced::class, 1);

    $order->changeStatus('packed', $f['admin']->id);
    Mail::assertSent(OrderStatusUpdated::class, fn ($m) => $m->toSlug === 'packed' && str_contains($m->envelope()->subject, 'packed'));

    $order->changeStatus('out_for_delivery', $f['admin']->id);
    Mail::assertSent(OrderStatusUpdated::class, fn ($m) => $m->toSlug === 'out_for_delivery' && str_contains($m->envelope()->subject, 'on its way'));

    $order->changeStatus('cancelled', $f['admin']->id);
    Mail::assertSent(OrderStatusUpdated::class, fn ($m) => $m->toSlug === 'cancelled' && str_contains($m->envelope()->subject, 'cancelled'));

    Mail::assertSent(OrderDelivered::class, 0);
    Mail::assertSent(OrderStatusUpdated::class, 3);
});

test('delivered still sends only the delivered mail', function () {
    $f = addrFixtures();
    Mail::fake();
    $order = addrPlaceOrder($this, $f);

    $order->changeStatus('delivered', $f['admin']->id);

    Mail::assertSent(OrderDelivered::class, 1);
    Mail::assertSent(OrderStatusUpdated::class, 0);
});

test('admin can view and download the invoice', function () {
    $f = addrFixtures();
    $order = addrPlaceOrder($this, $f);

    $this->actingAs($f['admin'])->get(route('orders.invoice', $order->id))
        ->assertOk()->assertSee($order->number)->assertSee('Bill to')->assertSee('Ship to');

    $pdf = $this->actingAs($f['admin'])->get(route('orders.invoicePdf', $order->id))->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});
