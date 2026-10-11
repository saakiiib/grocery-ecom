<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/** Stripe fakes with a fresh intent id per attempt; verify echoes the stored order. */
function fakeLedgerStripe(): void
{
    $counter = 0;
    Http::fake(function ($request) use (&$counter) {
        $url = (string) $request->url();
        if (! str_contains($url, 'api.stripe.com')) {
            return Http::response([], 404);
        }
        if ($request->method() === 'POST' && str_ends_with($url, '/cancel')) {
            return Http::response(['id' => 'pi_cancelled', 'status' => 'canceled'], 200);
        }
        if ($request->method() === 'POST' && str_contains($url, '/v1/refunds')) {
            return Http::response(['id' => 're_ledger_1', 'status' => 'succeeded'], 200);
        }
        if ($request->method() === 'POST') {
            $counter++;

            return Http::response(['id' => 'pi_ledger_'.$counter, 'client_secret' => 'cs_ledger_'.$counter, 'status' => 'requires_payment_method'], 200);
        }

        $id = basename(parse_url($url, PHP_URL_PATH));
        $order = Order::where('payment_reference', $id)->first() ?? Order::orderByDesc('id')->first();
        $cents = $order ? (int) round((float) $order->total * 100) : 0;

        return Http::response(['id' => $id, 'status' => 'succeeded', 'currency' => 'gbp', 'amount_received' => $cents, 'metadata' => ['order_number' => $order?->number]], 200);
    });
}

function ledgerSetup(): array
{
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    Setting::put('stripe_publishable', 'pk_test_ledger');
    Setting::put('stripe_secret', 'sk_test_ledger');
    Setting::put('stripe_webhook_secret', 'whsec_ledger');
    fakeLedgerStripe();

    return ['fixtures' => $f, 'slot' => $slot];
}

function ledgerPlace(object $t, string $method = 'stripe'): Order
{
    ['fixtures' => $f, 'slot' => $slot] = ledgerSetup();
    $t->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $t->postJson(route('checkout.place'), checkoutPayload($slot->id, $method))->assertOk();

    return Order::orderByDesc('id')->firstOrFail();
}

function ledgerSignedWebhook(object $t, string $eventId, string $type, string $intentId, string $number, int $amount): void
{
    $payload = json_encode(['id' => $eventId, 'type' => $type, 'data' => ['object' => [
        'id' => $intentId,
        'status' => $type === 'payment_intent.succeeded' ? 'succeeded' : 'requires_payment_method',
        'currency' => 'gbp',
        'amount_received' => $amount,
        'metadata' => ['order_number' => $number],
    ]]]);
    $ts = time();
    $t->call('POST', route('webhooks.stripe'), [], [], [], ['HTTP_Stripe-Signature' => 't='.$ts.',v1='.hash_hmac('sha256', $ts.'.'.$payload, 'whsec_ledger')], $payload)->assertOk();
}

test('placing an order opens a pending ledger row', function () {
    $order = ledgerPlace($this);

    $rows = $order->payments;
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->method)->toBe('stripe')
        ->and($rows->first()->reference)->toBe('pi_ledger_1')
        ->and($rows->first()->status)->toBe(Payment::PENDING)
        ->and((float) $rows->first()->amount)->toBe((float) $order->total);
});

test('confirming settles the ledger row', function () {
    $order = ledgerPlace($this);

    $this->postJson(route('checkout.payment-confirm'), ['order_number' => $order->number, 'payment_method' => 'stripe'])->assertOk();

    $row = $order->payments()->firstOrFail();
    expect($row->status)->toBe(Payment::SUCCEEDED)->and($row->paid_at)->not->toBeNull();
});

test('retry keeps history: superseded attempt cancelled, fresh one pending', function () {
    ['fixtures' => $f, 'slot' => $slot] = ledgerSetup();
    $user = shopperUser();
    $this->actingAs($user)->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $number = $this->actingAs($user)->postJson(route('checkout.place'), checkoutPayload($slot->id, 'stripe'))->assertOk()->json('order_number');

    $retry = $this->actingAs($user)->postJson(route('account.pay', $number), [])->assertOk();
    expect($retry->json('client_secret'))->toBe('cs_ledger_2');

    $rows = Payment::where('order_id', Order::where('number', $number)->firstOrFail()->id)->orderBy('id')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->status)->toBe(Payment::CANCELLED)
        ->and($rows[0]->reference)->toBe('pi_ledger_1')
        ->and($rows[1]->status)->toBe(Payment::PENDING)
        ->and($rows[1]->reference)->toBe('pi_ledger_2');
});

test('webhook success settles the row and replays log once', function () {
    $order = ledgerPlace($this);
    $cents = (int) round((float) $order->total * 100);

    ledgerSignedWebhook($this, 'evt_ledger_1', 'payment_intent.succeeded', 'pi_ledger_1', $order->number, $cents);
    ledgerSignedWebhook($this, 'evt_ledger_1', 'payment_intent.succeeded', 'pi_ledger_1', $order->number, $cents);

    expect($order->refresh()->payment_status)->toBe('paid')
        ->and(WebhookEvent::where('provider', 'stripe')->where('event_id', 'evt_ledger_1')->count())->toBe(1)
        ->and($order->payments()->firstOrFail()->status)->toBe(Payment::SUCCEEDED);
});

test('failed webhook marks the attempt failed and cancels the order', function () {
    $order = ledgerPlace($this);

    ledgerSignedWebhook($this, 'evt_ledger_2', 'payment_intent.payment_failed', 'pi_ledger_1', $order->number, 0);

    expect($order->refresh()->status_slug)->toBe('cancelled')
        ->and($order->payments()->firstOrFail()->status)->toBe(Payment::FAILED);
});

test('refunds track against the succeeded attempt', function () {
    $order = ledgerPlace($this);
    $admin = shopAdmin();
    $this->postJson(route('checkout.payment-confirm'), ['order_number' => $order->number, 'payment_method' => 'stripe'])->assertOk();

    $this->actingAs($admin)->post(route('orders.refund', $order->id), ['amount' => 5.00, 'reason' => 'Short item'])
        ->assertRedirect(route('orders.show', $order->id));

    expect($order->payments()->firstOrFail()->refunded_amount)->toBe('5.00');
});

test('abandoning checkout cancels the pending attempt', function () {
    $order = ledgerPlace($this);

    $this->postJson(route('checkout.cancel'), ['order_number' => $order->number])->assertOk();

    expect($order->payments()->firstOrFail()->status)->toBe(Payment::CANCELLED);
});

test('admin order page shows the attempts timeline', function () {
    $order = ledgerPlace($this);
    $admin = shopAdmin();

    $this->actingAs($admin)->get(route('orders.show', $order->id))->assertOk()
        ->assertSee('Attempts', false)
        ->assertSee('pi_ledger_1', false);
});
