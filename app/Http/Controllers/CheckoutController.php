<?php

namespace App\Http\Controllers;

use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\UserPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public const METHODS = ['cod', 'stripe', 'paypal'];

    public static function stripeConfigured(): bool
    {
        return (bool) (static::stripeSecret() && static::stripePublishable());
    }

    public static function paypalConfigured(): bool
    {
        return (bool) (static::paypalClientId() && static::paypalSecret());
    }

    /** Credentials resolve from .env first, Shop Settings as fallback. */
    public static function stripePublishable(): ?string
    {
        return config('services.stripe.publishable') ?: Setting::get('stripe_publishable') ?: null;
    }

    public static function stripeSecret(): ?string
    {
        return config('services.stripe.secret') ?: Setting::get('stripe_secret') ?: null;
    }

    public static function paypalClientId(): ?string
    {
        return config('services.paypal.client_id') ?: Setting::get('paypal_client_id') ?: null;
    }

    public static function paypalSecret(): ?string
    {
        return config('services.paypal.secret') ?: Setting::get('paypal_secret') ?: null;
    }

    public static function paypalMode(): string
    {
        return config('services.paypal.mode') ?: Setting::get('paypal_mode', 'sandbox');
    }

    /** Where each gateway's live credentials come from: '.env', 'settings', or null. */
    public static function credentialSource(string $gateway): ?string
    {
        if ($gateway === 'stripe') {
            if (config('services.stripe.publishable') && config('services.stripe.secret')) {
                return '.env';
            }

            return static::stripeConfigured() ? 'settings' : null;
        }

        if (config('services.paypal.client_id') && config('services.paypal.secret')) {
            return '.env';
        }

        return static::paypalConfigured() ? 'settings' : null;
    }

    public static function paypalBaseUrl(): string
    {
        return static::paypalMode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Validate the session bag and price the order from the database.
     * Returns [lines, subtotal, fee, total, slot] or an error response array.
     *
     * @return array{ok: bool, message?: string, lines?: array, subtotal?: float, fee?: float, total?: float, slot?: DeliverySlot}
     */
    public static function priceBag(int $slotId, ?float $subtotalOverride = null): array
    {
        $bag = BagController::detailed();

        if ($bag['count'] === 0) {
            return ['ok' => false, 'message' => 'Your bag is empty.'];
        }

        $unavailable = collect($bag['lines'])->where('available', false)->pluck('name')->all();
        if ($unavailable !== []) {
            return ['ok' => false, 'message' => 'Sorry — '.implode(', ', $unavailable).' is no longer available. Your bag was updated, please review it.'];
        }

        $subtotal = $subtotalOverride ?? $bag['subtotal'];
        $min = Setting::money('delivery_min_order', 15.00);
        if ($subtotal < $min) {
            return ['ok' => false, 'message' => 'The minimum order for delivery is £'.number_format($min, 2).'.'];
        }

        $slot = DeliverySlot::where('is_active', true)->find($slotId);
        if (! $slot) {
            return ['ok' => false, 'message' => 'Please choose a delivery slot.'];
        }

        $fee = $subtotal >= Setting::money('delivery_free_over', 50.00) ? 0.0 : (float) $slot->fee;

        return [
            'ok' => true,
            'lines' => $bag['lines'],
            'subtotal' => round($subtotal, 2),
            'fee' => round($fee, 2),
            'total' => round($subtotal + $fee, 2),
            'slot' => $slot,
        ];
    }

    /** Create the order from the session bag. Online methods return payment credentials for the JS SDKs. */
    public function place(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'delivery_date' => 'required|date_format:Y-m-d',
            'delivery_slot_id' => 'required|integer',
            'payment_method' => 'required|in:cod,stripe,paypal',
            'points_redeem' => 'nullable|integer|min:0|max:1000000',
        ]);

        if (! array_key_exists($data['delivery_date'], DeliverySlot::bookableDates())) {
            return response()->json(['message' => 'Please choose a valid delivery day.'], 422);
        }

        if ($data['payment_method'] === 'stripe' && ! static::stripeConfigured()) {
            return response()->json(['message' => 'Card payment is not available right now — please choose another method.'], 422);
        }
        if ($data['payment_method'] === 'paypal' && ! static::paypalConfigured()) {
            return response()->json(['message' => 'PayPal is not available right now — please choose another method.'], 422);
        }

        $priced = static::priceBag($data['delivery_slot_id']);
        if (! $priced['ok']) {
            return response()->json(['message' => $priced['message']], 422);
        }

        // Loyalty redemption (registered shoppers only, validated against the ledger).
        $pointsRedeem = 0;
        $pointsDiscount = 0.0;
        if (! empty($data['points_redeem'])) {
            if (! auth()->check()) {
                return response()->json(['message' => 'Sign in to spend loyalty points.'], 422);
            }
            $balance = UserPoint::balance(auth()->id());
            $minRedeem = UserPoint::minRedeem();
            $maxPoints = (int) min($balance, floor($priced['subtotal'] / UserPoint::value()));
            if ($data['points_redeem'] < $minRedeem) {
                return response()->json(['message' => 'You can spend points in blocks of '.$minRedeem.' or more.'], 422);
            }
            if ($data['points_redeem'] > $maxPoints) {
                return response()->json(['message' => 'You only have '.$balance.' points to spend on this order.'], 422);
            }
            $pointsRedeem = (int) $data['points_redeem'];
            $pointsDiscount = round($pointsRedeem * UserPoint::value(), 2);
        }

        try {
            $order = DB::transaction(function () use ($data, $priced, $pointsRedeem, $pointsDiscount) {
                $status = OrderStatus::where('slug', 'new')->where('is_active', true)->firstOrFail();

                /** @var Order $order */
                $order = Order::create([
                    'number' => 'PENDING',
                    'user_id' => auth()->id(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'postcode' => $data['postcode'],
                    'notes' => $data['notes'] ?? null,
                    'delivery_date' => $data['delivery_date'],
                    'delivery_slot_id' => $priced['slot']->id,
                    'delivery_slot_label' => $priced['slot']->label(),
                    'subtotal' => $priced['subtotal'],
                    'delivery_fee' => $priced['fee'],
                    'total' => round($priced['subtotal'] + $priced['fee'] - $pointsDiscount, 2),
                    'points_redeemed' => $pointsRedeem,
                    'points_discount' => $pointsDiscount,
                    'payment_method' => $data['payment_method'],
                    'payment_status' => 'unpaid',
                    'status_id' => $status->id,
                    'status_slug' => $status->slug,
                ]);
                $order->number = 'EGF-'.(10000 + $order->id);
                $order->save();

                if ($pointsRedeem > 0) {
                    $order->points()->create([
                        'user_id' => $order->user_id,
                        'points' => -$pointsRedeem,
                        'type' => UserPoint::REDEEM,
                        'description' => 'Spent on '.$order->number,
                    ]);
                }

                foreach ($priced['lines'] as $line) {
                    $order->items()->create([
                        'product_variant_id' => $line['variant_id'],
                        'product_id' => ProductVariant::find($line['variant_id'])?->product_id,
                        'product_name' => $line['name'],
                        'variant_sku' => $line['sku'],
                        'pack_label' => $line['pack'],
                        'unit_price' => $line['price'],
                        'qty' => $line['qty'],
                        'line_total' => $line['line_total'],
                    ]);
                }

                $order->histories()->create([
                    'from_slug' => null,
                    'to_slug' => $status->slug,
                    'changed_by' => auth()->id(),
                    'note' => 'Order placed ('.$order->paymentLabel().').',
                ]);

                // Remember the shopper's details for next time.
                if ($order->user_id) {
                    $order->user->update([
                        'phone' => $order->user->phone ?? $order->phone,
                        'address' => $order->user->address ?? $order->address,
                        'city' => $order->user->city ?? $order->city,
                        'postcode' => $order->user->postcode ?? $order->postcode,
                    ]);
                }

                return $order;
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'We could not place your order — please try again.'], 500);
        }

        BagController::clear();
        session(['last_order_id' => $order->id]);

        if ($data['payment_method'] === 'cod') {
            $order->changeStatus('confirmed', auth()->id(), 'Cash on delivery — pay the driver.');

            return response()->json([
                'ok' => true,
                'redirect' => route('order.success', $order->number),
            ]);
        }

        if ($data['payment_method'] === 'stripe') {
            try {
                $intent = static::stripeIntent($order);
            } catch (\Throwable $e) {
                report($e);
                $order->changeStatus('cancelled', null, 'Card payment could not be started.');

                return response()->json(['message' => 'Card payment could not be started — please try again or choose another method.'], 422);
            }
            $order->payment_reference = $intent['id'];
            $order->save();

            return response()->json([
                'ok' => true,
                'stripe' => true,
                'publishable' => static::stripePublishable(),
                'client_secret' => $intent['client_secret'],
                'order_number' => $order->number,
            ]);
        }

        try {
            $ppOrderId = static::paypalCreateOrder($order);
        } catch (\Throwable $e) {
            report($e);
            $order->changeStatus('cancelled', null, 'PayPal payment could not be started.');

            return response()->json(['message' => 'PayPal could not be started — please try again or choose another method.'], 422);
        }
        $order->payment_reference = $ppOrderId;
        $order->save();

        return response()->json([
            'ok' => true,
            'paypal' => true,
            'paypal_order_id' => $ppOrderId,
            'order_number' => $order->number,
        ]);
    }

    /**
     * Called by the Stripe/PayPal JS after the shopper approves payment.
     * Verifies with the gateway — never trusts the browser.
     */
    public function paymentConfirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => 'required|string|exists:orders,number',
            'payment_method' => 'required|in:stripe,paypal',
        ]);

        $order = Order::where('number', $data['order_number'])->firstOrFail();

        if (! $this->maySee($order)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
        if ($order->isPaid()) {
            return response()->json(['ok' => true, 'redirect' => route('order.success', $order->number)]);
        }
        if ($order->payment_method !== $data['payment_method']) {
            return response()->json(['message' => 'Payment method mismatch.'], 422);
        }

        try {
            $paid = $data['payment_method'] === 'stripe'
                ? $this->stripeVerify($order)
                : $this->paypalCapture($order);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'We could not confirm your payment — please try again.'], 422);
        }

        if (! $paid) {
            return response()->json(['message' => 'Your payment has not completed yet — please try again.'], 422);
        }

        $order->payment_status = 'paid';
        $order->save();
        $order->changeStatus('confirmed', $order->user_id, 'Paid online ('.$order->paymentLabel().').');
        session(['last_order_id' => $order->id]);

        return response()->json(['ok' => true, 'redirect' => route('order.success', $order->number)]);
    }

    public function success(string $number)
    {
        $order = Order::with(['items', 'histories', 'status'])->where('number', $number)->firstOrFail();

        if (! $this->maySee($order)) {
            abort(404);
        }

        return spa('frontend.order-success', compact('order'));
    }

    /**
     * Abandon an unpaid online order (PayPal cancel button, closed card tab).
     * Only untouched 'new' orders can vanish this way — nothing paid is touched.
     */
    public function cancel(Request $request): JsonResponse
    {
        $data = $request->validate(['order_number' => 'required|string|exists:orders,number']);

        $order = Order::where('number', $data['order_number'])->firstOrFail();

        if (! $this->maySee($order)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
        if ($order->isPaid() || $order->status_slug !== 'new') {
            return response()->json(['message' => 'That order can no longer be cancelled online.'], 422);
        }

        $order->changeStatus('cancelled', $order->user_id, 'Payment abandoned at checkout.');

        return response()->json(['ok' => true]);
    }

    private function maySee(Order $order): bool
    {
        if (auth()->check() && (auth()->user()->user_type == 1 || $order->user_id === auth()->id())) {
            return true;
        }

        return session('last_order_id') === $order->id;
    }

    /* ---------------- Stripe (raw API — no SDK dependency) ---------------- */

    /** @return array{id: string, client_secret: string} */
    public static function stripeIntent(Order $order): array
    {
        $res = Http::asForm()
            ->withBasicAuth(static::stripeSecret(), '')
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($order->total * 100),
                'currency' => 'gbp',
                'automatic_payment_methods[enabled]' => 'true',
                'metadata[order_number]' => $order->number,
                'description' => 'Evergreen Foods order '.$order->number,
            ]);

        if ($res->failed()) {
            throw new \RuntimeException('Stripe error: '.$res->body());
        }

        return ['id' => $res->json('id'), 'client_secret' => $res->json('client_secret')];
    }

    private function stripeVerify(Order $order): bool
    {
        if (! $order->payment_reference) {
            return false;
        }

        $res = Http::withBasicAuth(static::stripeSecret(), '')
            ->get('https://api.stripe.com/v1/payment_intents/'.$order->payment_reference);

        return $res->ok() && $res->json('status') === 'succeeded';
    }

    /* ---------------- PayPal (raw REST — no SDK dependency) ---------------- */

    private static function paypalToken(): string
    {
        $res = Http::asForm()
            ->withBasicAuth(static::paypalClientId(), static::paypalSecret())
            ->post(static::paypalBaseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($res->failed() || ! $res->json('access_token')) {
            throw new \RuntimeException('PayPal auth failed: '.$res->body());
        }

        return $res->json('access_token');
    }

    public static function paypalCreateOrder(Order $order): string
    {
        $res = Http::withToken(static::paypalToken())
            ->post(static::paypalBaseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->number,
                    'description' => 'Evergreen Foods order '.$order->number,
                    'amount' => [
                        'currency_code' => 'GBP',
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                ]],
            ]);

        if ($res->failed() || ! $res->json('id')) {
            throw new \RuntimeException('PayPal order failed: '.$res->body());
        }

        return $res->json('id');
    }

    private function paypalCapture(Order $order): bool
    {
        if (! $order->payment_reference) {
            return false;
        }

        $res = Http::withToken(static::paypalToken())
            ->post(static::paypalBaseUrl().'/v2/checkout/orders/'.$order->payment_reference.'/capture', []);

        if ($res->failed()) {
            return false;
        }

        $captures = $res->json('purchase_units.0.payments.captures', []);
        $completed = collect($captures)->contains(fn ($c) => ($c['status'] ?? null) === 'COMPLETED');

        if ($completed) {
            $order->payment_reference = $captures[0]['id'] ?? $order->payment_reference;
            $order->save();
        }

        return $completed;
    }
}
