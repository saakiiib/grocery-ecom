<?php

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function webhookFixtures(): array
{
    foreach (['new', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'cancelled'] as $i => $slug) {
        OrderStatus::create(['slug' => $slug, 'name' => ucfirst($slug), 'color' => '#111', 'sort_order' => $i, 'is_active' => true]);
    }
    $admin = User::create(['name' => 'Admin', 'email' => 'hook-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('admin');
}

function webhookOrder(string $method, string $reference): Order
{
    $new = OrderStatus::where('slug', 'new')->firstOrFail();

    return Order::create([
        'number' => 'EGF-'.random_int(50001, 59999),
        'name' => 'Shopper', 'phone' => '07123456789', 'email' => 'hook-shop@example.com',
        'address' => '1 Market St', 'city' => 'Leeds', 'postcode' => 'LS1 1AA',
        'delivery_date' => now()->addDay()->toDateString(),
        'subtotal' => 40.00, 'delivery_fee' => 0, 'total' => 40.00,
        'payment_method' => $method, 'payment_status' => 'unpaid', 'payment_reference' => $reference,
        'status_id' => $new->id, 'status_slug' => 'new',
    ]);
}

function stripeSigned(string $payload, string $secret): array
{
    $t = time();

    return ['payload' => $payload, 'header' => 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, $secret)];
}

function stripeEvent(string $type, string $intentId, string $number): string
{
    return json_encode(['id' => 'evt_test', 'type' => $type, 'data' => ['object' => [
        'id' => $intentId,
        'status' => $type === 'payment_intent.succeeded' ? 'succeeded' : 'requires_payment_method',
        'currency' => 'gbp',
        'amount_received' => $type === 'payment_intent.succeeded' ? 4000 : 0,
        'metadata' => ['order_number' => $number],
    ]]]);
}

test('stripe webhooks need configuration and a valid signature', function () {
    $this->postJson(route('webhooks.stripe'), [])->assertStatus(503);

    Setting::put('stripe_webhook_secret', 'whsec_test');
    $this->postJson(route('webhooks.stripe'), [], ['Stripe-Signature' => 't=123,v1=bad'])
        ->assertStatus(400);
});

test('stripe success confirms and mails exactly once, even replayed', function () {
    webhookFixtures();
    Setting::put('stripe_webhook_secret', 'whsec_test');
    Mail::fake();
    $order = webhookOrder('stripe', 'pi_test_1');

    $signed = stripeSigned(stripeEvent('payment_intent.succeeded', 'pi_test_1', $order->number), 'whsec_test');
    $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => $signed['header']], $signed['payload'])
        ->assertOk()->assertJsonPath('ok', true);

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->status_slug)->toBe('confirmed');
    Mail::assertSent(OrderPlaced::class, 1);

    // Replay: still exactly one mail, still paid.
    $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => $signed['header']], $signed['payload'])->assertOk();
    Mail::assertSent(OrderPlaced::class, 1);
});

test('stripe webhook refuses a successful intent for the wrong order amount', function () {
    webhookFixtures();
    Setting::put('stripe_webhook_secret', 'whsec_test');
    $order = webhookOrder('stripe', 'pi_wrong_amount');
    $event = json_decode(stripeEvent('payment_intent.succeeded', 'pi_wrong_amount', $order->number), true);
    $event['data']['object']['amount_received'] = 1;
    $signed = stripeSigned(json_encode($event), 'whsec_test');

    $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => $signed['header']], $signed['payload'])->assertOk();

    expect($order->fresh()->payment_status)->toBe('unpaid')
        ->and($order->fresh()->status_slug)->toBe('new');
});

test('stripe failure cancels untouched orders, unknown events ack', function () {
    webhookFixtures();
    Setting::put('stripe_webhook_secret', 'whsec_test');
    $order = webhookOrder('stripe', 'pi_test_2');

    $signed = stripeSigned(stripeEvent('payment_intent.payment_failed', 'pi_test_2', $order->number), 'whsec_test');
    $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => $signed['header']], $signed['payload'])->assertOk();
    expect($order->fresh()->status_slug)->toBe('cancelled');

    $signed = stripeSigned(json_encode(['id' => 'evt_x', 'type' => 'charge.refunded', 'data' => ['object' => []]]), 'whsec_test');
    $this->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => $signed['header']], $signed['payload'])->assertOk();
});

function paypalFakes(): void
{
    Setting::put('paypal_client_id', 'cid');
    Setting::put('paypal_secret', 'sec');
    Setting::put('paypal_webhook_id', 'WH-1');
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'tok'], 200),
        '*/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
        '*/payments/captures/*' => Http::response(['id' => 'CAP-1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'GBP', 'value' => '40.00']], 200),
    ]);
}

function paypalEvent(string $type, string $captureId): array
{
    return ['id' => 'EVT-1', 'event_type' => $type, 'resource' => ['id' => $captureId]];
}

test('paypal webhooks verify transmission and confirm on completed capture', function () {
    webhookFixtures();
    paypalFakes();
    Mail::fake();
    $order = webhookOrder('paypal', 'CAP-1');

    $this->postJson(route('webhooks.paypal'), paypalEvent('PAYMENT.CAPTURE.COMPLETED', 'CAP-1'))
        ->assertOk()->assertJsonPath('ok', true);

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->status_slug)->toBe('confirmed');
    Mail::assertSent(OrderPlaced::class, 1);

    // Replay stays single.
    $this->postJson(route('webhooks.paypal'), paypalEvent('PAYMENT.CAPTURE.COMPLETED', 'CAP-1'))->assertOk();
    Mail::assertSent(OrderPlaced::class, 1);
});

test('paypal denies cancel untouched orders', function () {
    webhookFixtures();
    paypalFakes();
    $order = webhookOrder('paypal', 'CAP-9');

    $this->postJson(route('webhooks.paypal'), paypalEvent('PAYMENT.CAPTURE.DENIED', 'CAP-9'))->assertOk();
    expect($order->fresh()->status_slug)->toBe('cancelled');
});

test('paypal rejects unverified transmissions', function () {
    webhookFixtures();
    Setting::put('paypal_client_id', 'cid');
    Setting::put('paypal_secret', 'sec');
    Setting::put('paypal_webhook_id', 'WH-1');
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'tok'], 200),
        '*/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE'], 200),
    ]);

    $this->postJson(route('webhooks.paypal'), paypalEvent('PAYMENT.CAPTURE.COMPLETED', 'CAP-9'))
        ->assertStatus(400);
});

test('webhooks require configuration', function () {
    webhookFixtures();

    $this->postJson(route('webhooks.paypal'), ['event_type' => 'X', 'resource' => []])->assertStatus(503);

    Setting::put('paypal_client_id', 'cid');
    Setting::put('paypal_secret', 'sec');
    $this->postJson(route('webhooks.paypal'), ['event_type' => 'X', 'resource' => []])->assertStatus(503);
});
