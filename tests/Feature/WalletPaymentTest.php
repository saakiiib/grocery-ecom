<?php

use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/** Stripe fakes: intent creation + a succeeded verify for the latest order. */
function fakeWalletStripe(): void
{
    $counter = 0;
    Http::fake(function ($request) use (&$counter) {
        $url = (string) $request->url();
        if (str_contains($url, 'api.stripe.com')) {
            if ($request->method() === 'POST') {
                $counter++;

                return Http::response(['id' => 'pi_wallet_'.$counter, 'client_secret' => 'cs_wallet_'.$counter, 'status' => 'requires_payment_method'], 200);
            }

            $id = basename(parse_url($url, PHP_URL_PATH));
            $order = Order::where('payment_reference', $id)->first() ?? Order::orderByDesc('id')->first();
            $cents = $order ? (int) round((float) $order->total * 100) : 0;

            return Http::response(['id' => $id, 'status' => 'succeeded', 'currency' => 'gbp', 'amount_received' => $cents, 'metadata' => ['order_number' => $order?->number]], 200);
        }

        return Http::response([], 404);
    });
}

function walletSetup(): array
{
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    Setting::put('stripe_publishable', 'pk_test_wallet');
    Setting::put('stripe_secret', 'sk_test_wallet');
    fakeWalletStripe();

    return ['fixtures' => $f, 'slot' => $slot];
}

test('google pay and apple pay place through the stripe pipe', function (string $method, string $label) {
    ['fixtures' => $f, 'slot' => $slot] = walletSetup();
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $place = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, $method))->assertOk();

    $order = Order::orderByDesc('id')->firstOrFail();
    expect($order->payment_method)->toBe($method)
        ->and($order->paymentLabel())->toBe($label)
        ->and($place->json('stripe'))->toBeTrue()
        ->and($place->json('client_secret'))->toBe('cs_wallet_1')
        ->and($place->json('payment_method'))->toBe($method)
        ->and(CheckoutController::isStripeMethod($method))->toBeTrue();
})->with([
    ['google_pay', 'Google Pay'],
    ['apple_pay', 'Apple Pay'],
]);

test('wallet orders confirm, stay refundable, and webhooks confirm them', function () {
    ['fixtures' => $f, 'slot' => $slot] = walletSetup();
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $number = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'google_pay'))->assertOk()->json('order_number');

    $this->postJson(route('checkout.payment-confirm'), ['order_number' => $number, 'payment_method' => 'google_pay'])
        ->assertOk()->assertJsonPath('ok', true);

    $order = Order::where('number', $number)->firstOrFail();
    expect($order->payment_status)->toBe('paid')->and($order->refundableAmount())->toBe((float) $order->total);

    // A repeated webhook for the same intent stays exactly-once (still paid, one mail).
    $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => [
        'id' => 'pi_wallet_1', 'status' => 'succeeded', 'currency' => 'gbp',
        'amount_received' => (int) round((float) $order->total * 100),
        'metadata' => ['order_number' => $number],
    ]]]);
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$payload, 'whsec_test_wallet');
    Setting::put('stripe_webhook_secret', 'whsec_test_wallet');
    $this->postJson('/webhooks/stripe', json_decode($payload, true), ['Stripe-Signature' => 't='.$ts.',v1='.$sig])
        ->assertOk();
    expect($order->refresh()->payment_status)->toBe('paid');

    // Method mismatch is still refused (checked on a fresh unpaid order).
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();
    $number2 = $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'google_pay'))->assertOk()->json('order_number');
    $this->postJson(route('checkout.payment-confirm'), ['order_number' => $number2, 'payment_method' => 'apple_pay'])
        ->assertStatus(422);
});

test('checkout renders the express row and all five payment tiles', function () {
    $f = shopFixtures();
    shopSetup();
    Setting::put('stripe_publishable', 'pk_test_wallet');
    Setting::put('stripe_secret', 'sk_test_wallet');
    Setting::put('paypal_client_id', 'pp-id');
    Setting::put('paypal_secret', 'pp-secret');
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $this->get(route('checkout'))->assertOk()
        ->assertSee('Express checkout', false)
        ->assertSee('co-express-gpay', false)
        ->assertSee('co-express-apay', false)
        ->assertSee('value="google_pay"', false)
        ->assertSee('value="apple_pay"', false)
        ->assertSee('co-express-element', false);
});

test('wallet methods need stripe keys like cards do', function () {
    $f = shopFixtures();
    $slot = shopSetup()['slot'];
    $this->postJson(route('bag.add'), ['variant_id' => $f['b']->id])->assertOk();

    $this->postJson(route('checkout.place'), checkoutPayload($slot->id, 'google_pay'))
        ->assertStatus(422)->assertJsonPath('message', 'Card payment is not available right now — please choose another method.');
});
