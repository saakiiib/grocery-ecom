<?php

use App\Http\Controllers\BagController;
use App\Models\ApiToken;
use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RepeatSchedule;
use App\Models\Setting;
use App\Models\ShoppingList;
use App\Models\User;
use App\Support\BuyAgain;
use App\Support\RepeatRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRepeatGrocery(): array
{
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);
    $make = function (string $name, string $slug, string $sku) use ($cat) {
        $product = Product::create(['category_id' => $cat->id, 'name' => $name, 'slug' => $slug, 'status' => true]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'sku' => $sku, 'mrp' => 10.00, 'offer_price' => 9.00,
            'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
        ]);

        return compact('product', 'variant');
    };
    $user = User::create(['name' => 'Shopper', 'email' => 'shopper@example.com', 'password' => bcrypt('password'), 'user_type' => 0]);
    foreach (['new', 'confirmed', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    DeliverySlot::create(['name' => 'Morning', 'starts_at' => '08:00', 'ends_at' => '12:00', 'fee' => 2.99, 'cutoff_hour' => 20, 'sort_order' => 0, 'is_active' => true]);
    Setting::put('delivery_min_order', '5.00');
    Setting::put('delivery_free_over', '50.00');

    return ['rice' => $make('Rice', 'rice', 'RICE-1KG'), 'beans' => $make('Beans', 'beans', 'BEAN-500'), 'user' => $user];
}

function seedRepeatOrder(array $f, string $number, string $statusSlug = 'confirmed'): Order
{
    $status = OrderStatus::where('slug', $statusSlug)->firstOrFail();
    $order = Order::create([
        'number' => $number, 'user_id' => $f['user']->id, 'name' => 'Shopper', 'phone' => '07', 'email' => 'shopper@example.com',
        'address' => '1 M St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->format('Y-m-d'),
        'subtotal' => 18, 'delivery_fee' => 0, 'total' => 18, 'payment_method' => 'cod',
        'payment_status' => 'unpaid', 'status_id' => $status->id, 'status_slug' => $status->slug,
    ]);
    foreach (['rice', 'beans'] as $key) {
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $f[$key]['product']->id, 'product_variant_id' => $f[$key]['variant']->id,
            'product_name' => $f[$key]['product']->name, 'variant_sku' => $f[$key]['variant']->sku,
            'unit_price' => 9, 'qty' => 1, 'line_total' => 9,
        ]);
    }

    return $order;
}

test('buy-it-again rails from real orders, skipping cancelled ones', function () {
    $f = seedRepeatGrocery();
    seedRepeatOrder($f, 'EGF-40001', 'confirmed');
    seedRepeatOrder($f, 'EGF-40002', 'cancelled');

    expect(BuyAgain::productIdsFor($f['user']->id))->toHaveCount(2);

    $this->actingAs($f['user'])->get('/account')->assertOk()
        ->assertSee('Buy it again', false)
        ->assertSee('Rice', false);
    $this->withToken(ApiToken::issue($f['user']))->getJson('/api/buy-again')->assertOk()
        ->assertJsonCount(2, 'products');
});

test('shopping lists fill the bag in one tap', function () {
    $f = seedRepeatGrocery();

    $this->actingAs($f['user'])->post(route('lists.store'), ['name' => 'Weekly shop'])
        ->assertRedirect(route('lists.index'));
    $list = ShoppingList::firstOrFail();

    $this->actingAs($f['user'])->post(route('lists.items.store', $list->id), ['variant_id' => $f['rice']['variant']->id, 'qty' => 2])
        ->assertRedirect(route('lists.index'));
    $this->actingAs($f['user'])->get(route('lists.index'))->assertOk()->assertSee('Weekly shop', false);

    $this->actingAs($f['user'])->post(route('lists.addAll', $list->id))->assertRedirect(route('bag'));
    expect(BagController::bag())->toBe([$f['rice']['variant']->id => 2]);

    $api = $this->withToken(ApiToken::issue($f['user']));
    $api->getJson('/api/lists')->assertOk()->assertJsonCount(1, 'lists');
    // Web add-all already put 2 in the shared session bag; API adds 2 more.
    $api->postJson('/api/lists/'.$list->id.'/add-all')->assertOk()
        ->assertJsonPath('count', 4);
});

test('weekly repeat rebuilds from shelf prices and rolls forward', function () {
    $f = seedRepeatGrocery();
    $order = seedRepeatOrder($f, 'EGF-40001');

    $this->actingAs($f['user'])->post(route('account.repeat', $order->number))->assertRedirect(route('account'));
    $schedule = RepeatSchedule::firstOrFail();
    expect($schedule->next_run_at->format('Y-m-d'))->toBe(today()->addWeek()->format('Y-m-d'));

    $schedule->update(['next_run_at' => today()]);
    $this->artisan('app:run-repeats')->assertSuccessful();

    $fresh = Order::where('number', '!=', 'EGF-40001')->firstOrFail();
    expect($fresh->status_slug)->toBe('new')
        ->and($fresh->items)->toHaveCount(2)
        ->and($schedule->fresh()->next_run_at->format('Y-m-d'))->toBe(today()->addWeek()->format('Y-m-d'));

    $this->actingAs($f['user'])->post(route('account.repeat.cancel', $schedule->id))->assertRedirect(route('account'));
    expect($schedule->fresh()->is_active)->toBeFalse();
});

test('repeat skips unavailable lines and dead schedules', function () {
    $f = seedRepeatGrocery();
    $order = seedRepeatOrder($f, 'EGF-40001');
    $f['beans']['variant']->update(['in_stock' => false]);

    $this->actingAs($f['user'])->post(route('account.repeat', $order->number))->assertRedirect(route('account'));
    $schedule = RepeatSchedule::firstOrFail();
    $schedule->update(['next_run_at' => today()]);

    $result = RepeatRunner::runDue();
    expect($result['created'])->toBe(1);

    $fresh = Order::where('number', '!=', 'EGF-40001')->firstOrFail();
    expect($fresh->items)->toHaveCount(1);

    // Everything gone: rolls forward, no order.
    $f['rice']['variant']->update(['in_stock' => false]);
    RepeatSchedule::firstOrFail()->update(['next_run_at' => today()]);
    $result = RepeatRunner::runDue();
    expect($result['created'])->toBe(0)->and($result['skipped'])->toBe(1);
});

test('admin manages repeats', function () {
    $f = seedRepeatGrocery();
    $order = seedRepeatOrder($f, 'EGF-40001');
    $this->actingAs($f['user'])->post(route('account.repeat', $order->number))->assertRedirect(route('account'));

    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);
    $this->actingAs($admin)->get(route('repeats.index'))->assertOk()->assertSee('Weekly Repeats', false);
    $this->actingAs($admin)->delete(route('repeats.delete', RepeatSchedule::first()->id))
        ->assertRedirect(route('repeats.index'));
    expect(RepeatSchedule::count())->toBe(0);
});
