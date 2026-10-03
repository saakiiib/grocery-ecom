<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function zoneFixtures(): array
{
    foreach (['new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $admin = User::create(['name' => 'Admin', 'email' => 'zone-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('slot', 'variant', 'admin');
}

function zonePayload(int $slotId, string $postcode): array
{
    return [
        'name' => 'Shopper Name', 'phone' => '07123456789',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => $postcode,
        'billing_name' => 'Shopper Name', 'billing_phone' => '07123456789',
        'billing_address' => '1 Market Street', 'billing_city' => 'Leeds', 'billing_postcode' => $postcode,
        'substitution' => 'call',
        'delivery_date' => array_key_first(DeliverySlot::bookableDates()),
        'delivery_slot_id' => $slotId,
        'payment_method' => 'cod',
        'privacy' => true,
    ];
}

function zoneLeeds(): DeliveryZone
{
    $zone = DeliveryZone::create(['name' => 'Leeds', 'is_active' => true, 'sort_order' => 0]);
    $zone->postcodes()->createMany([['prefix' => 'LS1'], ['prefix' => 'LS2']]);

    return $zone;
}

test('empty zone list means everywhere is served', function () {
    expect(DeliveryZone::serves('LS1 1AA'))->toBeTrue()
        ->and(DeliveryZone::serves('EC1A 1BB'))->toBeTrue()
        ->and(DeliveryZone::matching('LS1 1AA'))->toBeNull();
});

test('matching ignores case and spaces, longest prefix wins', function () {
    $leeds = zoneLeeds();
    $central = DeliveryZone::create(['name' => 'Central', 'is_active' => true, 'sort_order' => 1]);
    $central->postcodes()->create(['prefix' => 'LS1']);

    // LS1 matches both zones; tie resolves to one of them without error.
    expect(DeliveryZone::matching('ls1 1aa')?->id)->toBeIn([$leeds->id, $central->id])
        ->and(DeliveryZone::matching('LS2 7DY')?->id)->toBe($leeds->id)
        ->and(DeliveryZone::matching('YO1 1AA'))->toBeNull()
        ->and(DeliveryZone::serves('YO1 1AA'))->toBeFalse()
        ->and(DeliveryZone::serves(''))->toBeFalse();
});

test('inactive zones are ignored', function () {
    $zone = zoneLeeds();
    $zone->update(['is_active' => false]);

    expect(DeliveryZone::serves('LS1 1AA'))->toBeFalse();
});

test('checkout refuses postcodes outside the zones', function () {
    ['slot' => $slot, 'variant' => $variant] = zoneFixtures();
    zoneLeeds();
    session()->put('bag', [$variant->id => 2]);

    $this->postJson(route('checkout.place'), zonePayload($slot->id, 'YO1 1AA'))
        ->assertStatus(422)->assertJsonPath('message', 'Sorry — we don\'t deliver to YO1 1AA yet.');

    $this->postJson(route('checkout.place'), zonePayload($slot->id, 'ls2 7dy'))->assertOk();
});

test('live postcode check reports eligibility', function () {
    zoneLeeds();

    $this->postJson(route('checkout.postcode'), ['postcode' => 'LS1 1AA'])
        ->assertOk()->assertJsonPath('ok', true)->assertJsonPath('zone', 'Leeds');

    $this->postJson(route('checkout.postcode'), ['postcode' => 'YO1 1AA'])
        ->assertStatus(422)->assertJsonPath('ok', false);
});

test('admin can manage zones', function () {
    ['admin' => $admin] = zoneFixtures();

    $this->actingAs($admin)->get(route('delivery-zones.index'))->assertOk()->assertSee('Delivery Zones');

    $this->actingAs($admin)->post(route('delivery-zones.store'), [
        'name' => 'York', 'prefixes' => "YO1\nyo2 \nYO1\n", 'sort_order' => 0, 'is_active' => '1',
    ])->assertRedirect(route('delivery-zones.index'));

    $zone = DeliveryZone::where('name', 'York')->firstOrFail();
    expect($zone->postcodes()->pluck('prefix')->all())->toEqualCanonicalizing(['YO1', 'YO2']);

    $this->actingAs($admin)->post(route('delivery-zones.toggleStatus'), ['id' => $zone->id])->assertOk();
    expect($zone->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->delete(route('delivery-zones.delete', $zone->id))->assertRedirect();
    expect(DeliveryZone::count())->toBe(0);
});
