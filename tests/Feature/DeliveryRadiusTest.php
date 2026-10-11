<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\DeliveryRadius;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/** Fake postcodes.io lookups: [postcode => [lat, lng]], anything else 404s. */
function fakeRadiusPostcodes(array $map): void
{
    Http::fake(function ($request) use ($map) {
        $tail = strtoupper(str_replace([' ', '+', '%20'], '', urldecode($request->url())));
        foreach ($map as $pc => $coords) {
            $key = strtoupper(str_replace(' ', '', $pc));
            if (str_ends_with($tail, $key)) {
                return Http::response(['status' => 200, 'result' => ['postcode' => $pc, 'latitude' => $coords[0], 'longitude' => $coords[1]]], 200);
            }
        }

        return Http::response(['status' => 404, 'error' => 'Postcode not found'], 404);
    });
}

function radiusLeeds(): array
{
    return ['LS1 1AA' => [53.7960, -1.5430], 'LS2 7DY' => [53.8040, -1.5500], 'YO1 1AA' => [53.9623, -1.0819]];
}

function radiusFixtures(): array
{
    foreach (['new', 'confirmed'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111111', 'sort_order' => $i, 'is_active' => true]);
    }
    $slot = DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    $cat = Category::create(['name' => 'Veg', 'slug' => 'veg']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Carrots', 'slug' => 'carrots']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CAR-1KG', 'mrp' => 20.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $admin = User::create(['name' => 'Admin', 'email' => 'radius-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('slot', 'variant', 'admin');
}

function radiusPayload(string $postcode): array
{
    return [
        'name' => 'Shopper Name', 'phone' => '07123456789', 'email' => 'radius-guest@example.com',
        'address' => '1 Market Street', 'city' => 'Leeds', 'postcode' => $postcode,
        'billing_name' => 'Shopper Name', 'billing_phone' => '07123456789',
        'billing_address' => '1 Market Street', 'billing_city' => 'Leeds', 'billing_postcode' => $postcode,
        'substitution' => 'substitute',
        'delivery_speed' => 'standard',
        'payment_method' => 'cod',
        'privacy' => true,
    ];
}

test('no store postcode means everywhere is served', function () {
    expect(DeliveryRadius::storePostcode())->toBeNull()
        ->and(DeliveryRadius::serves('LS1 1AA'))->toBeTrue()
        ->and(DeliveryRadius::serves('EC1A 1BB'))->toBeTrue()
        ->and(DeliveryRadius::check('LS1 1AA')['distance'])->toBeNull();
});

test('nearby postcode is served, far one is refused with miles', function () {
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');
    fakeRadiusPostcodes(radiusLeeds());

    $near = DeliveryRadius::check('ls2 7dy');
    expect($near['ok'])->toBeTrue()
        ->and($near['distance'])->toBeLessThan(10)
        ->and($near['message'])->toContain('miles away');

    $far = DeliveryRadius::check('YO1 1AA');
    $miles = DeliveryRadius::distanceMiles(53.7960, -1.5430, 53.9623, -1.0819);
    expect($far['ok'])->toBeFalse()
        ->and($far['message'])->toBe('Sorry — YO1 1AA is '.number_format($miles, 1).' miles away; we deliver within 10.0 miles of our store.');
});

test('invalid and unverifiable postcodes are refused', function () {
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');
    fakeRadiusPostcodes(radiusLeeds());

    expect(DeliveryRadius::serves('not-a-postcode'))->toBeFalse()
        ->and(DeliveryRadius::check('not-a-postcode')['message'])->toBe('Enter a valid UK postcode, e.g. LS1 1AA.')
        ->and(DeliveryRadius::serves('ZZ9 9ZZ'))->toBeFalse()
        ->and(DeliveryRadius::check('ZZ9 9ZZ')['message'])->toBe('We could not verify ZZ9 9ZZ — please check it and try again.');
});

test('checkout refuses postcodes outside the radius', function () {
    ['variant' => $variant] = radiusFixtures();
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');
    fakeRadiusPostcodes(radiusLeeds());
    session()->put('bag', [$variant->id => 2]);

    $miles = DeliveryRadius::distanceMiles(53.7960, -1.5430, 53.9623, -1.0819);
    $this->postJson(route('checkout.place'), radiusPayload('YO1 1AA'))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Sorry — YO1 1AA is '.number_format($miles, 1).' miles away; we deliver within 10.0 miles of our store.');

    session()->put('bag', [$variant->id => 2]);
    $this->postJson(route('checkout.place'), radiusPayload('ls2 7dy'))->assertOk();
});

test('live postcode check reports distance', function () {
    Setting::put('store_postcode', 'LS1 1AA');
    Setting::put('delivery_radius_miles', '10');
    fakeRadiusPostcodes(radiusLeeds());

    $miles = DeliveryRadius::distanceMiles(53.7960, -1.5430, 53.8040, -1.5500);
    $this->postJson(route('checkout.postcode'), ['postcode' => 'LS2 7DY'])
        ->assertOk()->assertJsonPath('ok', true)->assertJsonPath('distance', round($miles, 1));

    $this->postJson(route('checkout.postcode'), ['postcode' => 'YO1 1AA'])
        ->assertStatus(422)->assertJsonPath('ok', false);
});

test('admin can save the store postcode and radius', function () {
    ['admin' => $admin] = radiusFixtures();

    $this->actingAs($admin)->post(route('shop-settings.update'), [
        'delivery_min_order' => '15.00', 'delivery_free_over' => '50.00',
        'delivery_cutoff_time' => '20:00', 'delivery_today_fee' => '2.99', 'delivery_standard_fee' => '1.99',
        'store_postcode' => 'ls1 1aa', 'delivery_radius_miles' => '10',
        'points_per_pound' => '1', 'points_value' => '0.01', 'points_min_redeem' => '100',
        'paypal_mode' => 'sandbox',
    ])->assertRedirect(route('shop-settings.edit'));

    expect(Setting::get('store_postcode'))->toBe('ls1 1aa')
        ->and(Setting::get('delivery_radius_miles'))->toBe('10')
        ->and(DeliveryRadius::storePostcode())->toBe('LS11AA');

    $this->actingAs($admin)->post(route('shop-settings.update'), [
        'delivery_min_order' => '15.00', 'delivery_free_over' => '50.00',
        'store_postcode' => 'nonsense', 'delivery_radius_miles' => '10',
        'points_per_pound' => '1', 'points_value' => '0.01', 'points_min_redeem' => '100',
        'paypal_mode' => 'sandbox',
    ])->assertSessionHasErrors('store_postcode');
});
