<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Gateway webhooks — the safety net beside the synchronous checkout confirm.
 *
 * The shopper-facing flow (paymentConfirm) stays instant; webhooks catch what
 * it misses (closed tab, lost session, delayed capture). Every handler is
 * idempotent: replays and races resolve to a single paid state, never dupes.
 */
class WebhookController extends Controller
{
    /* ---------------- Stripe (HMAC-signed, raw payload) ---------------- */

    public function stripe(Request $request): JsonResponse
    {
        $secret = CheckoutController::stripeWebhookSecret();
        if (! $secret) {
            return response()->json(['message' => 'Stripe webhooks are not configured.'], 503);
        }

        $payload = $request->getContent();
        if (! static::stripeSignatureOk($request->header('Stripe-Signature', ''), $payload, $secret)) {
            return response()->json(['message' => 'Bad signature.'], 400);
        }

        $event = json_decode($payload, true) ?? [];
        $type = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];

        if ($type === 'payment_intent.succeeded') {
            $order = $this->findStripeOrder($object);
            if ($order) {
                $this->confirmPaid($order, 'stripe', 'Paid online (Card (Stripe), webhook).');
            }

            return response()->json(['ok' => true]);
        }

        if ($type === 'payment_intent.payment_failed') {
            $order = $this->findStripeOrder($object);
            if ($order && ! $order->isPaid() && $order->status_slug === 'new') {
                $order->changeStatus('cancelled', null, 'Card payment failed (webhook).');
            }

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private function findStripeOrder(array $object): ?Order
    {
        $intentId = $object['id'] ?? null;
        if ($intentId) {
            $order = Order::where('payment_method', 'stripe')->where('payment_reference', $intentId)->first();
            if ($order) {
                return $order;
            }
        }
        $number = $object['metadata']['order_number'] ?? null;
        if ($number) {
            return Order::where('payment_method', 'stripe')->where('number', $number)->first();
        }

        return null;
    }

    public static function stripeSignatureOk(string $header, string $payload, string $secret): bool
    {
        $timestamp = null;
        $signature = null;
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signature = $value;
            }
        }
        if (! $timestamp || ! $signature) {
            return false;
        }
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$payload, $secret), $signature);
    }

    /* ---------------- PayPal (verified transmission + re-fetch) ---------------- */

    public function paypal(Request $request): JsonResponse
    {
        if (! CheckoutController::paypalConfigured()) {
            return response()->json(['message' => 'PayPal is not configured.'], 503);
        }
        $webhookId = CheckoutController::paypalWebhookId();
        if (! $webhookId) {
            return response()->json(['message' => 'PayPal webhooks are not configured.'], 503);
        }

        $event = $request->json()->all() ?? [];
        if (! static::paypalTransmissionOk($request, $webhookId, $event)) {
            return response()->json(['message' => 'Unverified transmission.'], 400);
        }

        $type = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = $resource['id'] ?? null;
            $order = $captureId ? Order::where('payment_method', 'paypal')->where('payment_reference', $captureId)->first() : null;
            if ($order && static::paypalCaptureCompleted($captureId)) {
                $this->confirmPaid($order, 'paypal', 'Paid online (PayPal, webhook).');
            }

            return response()->json(['ok' => true]);
        }

        if ($type === 'PAYMENT.CAPTURE.DENIED') {
            $captureId = $resource['id'] ?? null;
            $order = $captureId ? Order::where('payment_method', 'paypal')->where('payment_reference', $captureId)->first() : null;
            if ($order && ! $order->isPaid() && $order->status_slug === 'new') {
                $order->changeStatus('cancelled', null, 'PayPal payment denied (webhook).');
            }

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private static function paypalTransmissionOk(Request $request, string $webhookId, array $event): bool
    {
        try {
            $token = CheckoutController::paypalTokenForWebhook();
            $res = Http::withToken($token)
                ->post(CheckoutController::paypalBaseUrl().'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $request->header('Paypal-Auth-Algo'),
                    'cert_url' => $request->header('Paypal-Cert-Url'),
                    'transmission_id' => $request->header('Paypal-Transmission-Id'),
                    'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
                    'transmission_time' => $request->header('Paypal-Transmission-Time'),
                    'webhook_id' => $webhookId,
                    'webhook_event' => $event,
                ]);

            return $res->ok() && strtoupper((string) $res->json('verification_status')) === 'SUCCESS';
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private static function paypalCaptureCompleted(string $captureId): bool
    {
        try {
            $token = CheckoutController::paypalTokenForWebhook();
            $res = Http::withToken($token)
                ->get(CheckoutController::paypalBaseUrl().'/v2/payments/captures/'.$captureId);

            return $res->ok() && strtoupper((string) $res->json('status')) === 'COMPLETED';
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Mark a gateway-paid order confirmed exactly once. Replays, races with
     * the synchronous confirm, and unknown orders all end here safely.
     */
    private function confirmPaid(Order $order, string $method, string $note): bool
    {
        if ($order->payment_method !== $method) {
            return false;
        }
        if ($order->isPaid()) {
            return true;
        }
        if (in_array($order->status_slug, ['cancelled', 'delivered'], true)) {
            return false;
        }

        $order->payment_status = 'paid';
        $order->amount_paid = $order->total;
        $order->save();
        if ($order->status_slug === 'new') {
            $order->changeStatus('confirmed', null, $note);
            Order::sendMail($order->receiptEmail(), new OrderPlaced($order));
        }

        return true;
    }
}
