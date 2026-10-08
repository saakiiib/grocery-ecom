<?php

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedCutoffGrocery(float $price = 20.00): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Rice', 'slug' => 'rice']);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'RICE-1KG', 'mrp' => $price, 'offer_price' => $price,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    $slot = DeliverySlot::create(['name' => 'Evening', 'starts_at' => '17:00', 'ends_at' => '20:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '15.00');
    Setting::put('delivery_free_over', '50.00');

    return compact('variant', 'slot');
}

function cutoffPayload(int $slotId, string $date): array
{
    return [
        'name' => 'N', 'phone' => '07', 'email' => 'cutoff-guest@example.com', 'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'billing_name' => 'N', 'billing_phone' => '07', 'billing_address' => '1 M St', 'billing_city' => 'Leeds', 'billing_postcode' => 'LS1 1AA',
        'substitution' => 'substitute',
        'delivery_date' => $date, 'delivery_slot_id' => $slotId, 'payment_method' => 'cod', 'privacy' => true,
    ];
}

test('dates carry today and tomorrow labels with cutoff message on checkout', function () {
    Carbon::setTestNow('2026-10-08 10:00');
    try {
        $f = seedCutoffGrocery();
        session()->put('bag', [$f['variant']->id => 1]);

        $dates = DeliverySlot::bookableDates();
        expect($dates[Carbon::now()->format('Y-m-d')])->toStartWith('Today')
            ->and($dates[Carbon::now()->addDay()->format('Y-m-d')])->toStartWith('Tomorrow');

        $status = DeliverySlot::todayStatus();
        expect($status['today_available'])->toBeTrue()->and($status['order_by_hour'])->toBe(20);

        $this->get('/checkout')->assertOk()
            ->assertSee('Today ·', false)
            ->assertSee('for delivery today', false);
    } finally {
        Carbon::setTestNow();
    }
});

test('past-cutoff slot is rejected for today but works for tomorrow', function () {
    Carbon::setTestNow('2026-10-08 10:00');
    try {
        $f = seedCutoffGrocery();
        foreach (['new', 'confirmed'] as $i => $slug) {
            OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
        }
        $closed = DeliverySlot::create(['name' => 'Early', 'starts_at' => '06:00', 'ends_at' => '08:00', 'fee' => 1.99, 'cutoff_hour' => 5, 'sort_order' => 1, 'is_active' => true]);

        $today = Carbon::now()->format('Y-m-d');
        $tomorrow = Carbon::now()->addDay()->format('Y-m-d');
        expect($closed->cutoffPassed($today))->toBeTrue()
            ->and($f['slot']->cutoffPassed($today))->toBeFalse()
            ->and($closed->cutoffPassed($tomorrow))->toBeFalse();

        session()->put('bag', [$f['variant']->id => 1]);
        $this->postJson(route('checkout.place'), cutoffPayload($closed->id, $today))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'That time window just closed for today — please pick tomorrow or another slot.']);

        session()->put('bag', [$f['variant']->id => 1]);
        $this->postJson(route('checkout.place'), cutoffPayload($closed->id, $tomorrow))->assertOk();
    } finally {
        Carbon::setTestNow();
    }
});

test('today disappears once every cutoff passes', function () {
    Carbon::setTestNow('2026-10-08 21:30');
    try {
        $f = seedCutoffGrocery();
        session()->put('bag', [$f['variant']->id => 1]);

        $dates = DeliverySlot::bookableDates();
        expect(array_key_exists(Carbon::now()->format('Y-m-d'), $dates))->toBeFalse();
        expect(DeliverySlot::todayStatus()['today_available'])->toBeFalse();

        $this->get('/checkout')->assertOk()->assertSee("Today's cutoff has passed", false);
    } finally {
        Carbon::setTestNow();
    }
});

test('api checkout init carries cutoff data', function () {
    Carbon::setTestNow('2026-10-08 10:00');
    try {
        seedCutoffGrocery();

        $this->getJson('/api/checkout/init')->assertOk()
            ->assertJsonPath('slots.0.cutoff_hour', 20)
            ->assertJsonPath('cutoff_status.today_available', true)
            ->assertJsonPath('cutoff_status.order_by_hour', 20);
    } finally {
        Carbon::setTestNow();
    }
});
