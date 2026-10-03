<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\CompanyDetails;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
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

    /** Webhook secrets resolve the same way as gateway keys: .env first, settings fallback. */
    public static function stripeWebhookSecret(): ?string
    {
        return config('services.stripe.webhook_secret') ?: Setting::get('stripe_webhook_secret') ?: null;
    }

    public static function paypalWebhookId(): ?string
    {
        return config('services.paypal.webhook_id') ?: Setting::get('paypal_webhook_id') ?: null;
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
            // Guests must leave an email — otherwise no receipt can reach them.
            'email' => [auth()->check() ? 'nullable' : 'required', 'email', 'max:255'],
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'billing_name' => 'required|string|max:100',
            'billing_phone' => 'required|string|max:30',
            'billing_address' => 'required|string|max:500',
            'billing_city' => 'required|string|max:100',
            'billing_postcode' => 'required|string|max:20',
            'save_address' => 'nullable|boolean',
            'save_label' => 'nullable|string|max:50',
            'substitution' => 'required|in:substitute,refund,call',
            'notes' => 'nullable|string|max:1000',
            'delivery_date' => 'required|date_format:Y-m-d',
            'delivery_slot_id' => 'required|integer',
            'payment_method' => 'required|in:cod,stripe,paypal',
            'points_redeem' => 'nullable|integer|min:0|max:1000000',
            'coupon_code' => 'nullable|string|max:50',
            'privacy' => 'accepted',
        ]);

        if (! array_key_exists($data['delivery_date'], DeliverySlot::bookableDates())) {
            return response()->json(['message' => 'Please choose a valid delivery day.'], 422);
        }

        if (! DeliveryZone::serves($data['postcode'])) {
            return response()->json(['message' => 'Sorry — we don\'t deliver to '.$data['postcode'].' yet.'], 422);
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

                // Coupon — revalidated server-side, shoppers only. Row-locked so
                // concurrent checkouts cannot over-redeem a limited coupon.
                $coupon = null;
                $couponDiscount = 0.0;
                if (! empty($data['coupon_code'])) {
                    $coupon = Coupon::findByCode($data['coupon_code']);
                    if (! $coupon) {
                        throw new CouponRejected('That coupon does not exist.');
                    }
                    $coupon = Coupon::where('id', $coupon->id)->lockForUpdate()->firstOrFail();
                    $check = $coupon->checkFor(auth()->id(), $priced['subtotal']);
                    if (! $check['ok']) {
                        throw new CouponRejected($check['message']);
                    }
                    $couponDiscount = $check['discount'];
                }

                /** @var Order $order */
                // VAT is included in shelf prices: snapshot the VAT portion of the total.
                $total = max(0, round($priced['subtotal'] + $priced['fee'] - $pointsDiscount - $couponDiscount, 2));
                $vatPercent = (float) (CompanyDetails::cached()->vat_percent ?? 0);
                $vatAmount = $vatPercent > 0 ? round($total * $vatPercent / (100 + $vatPercent), 2) : 0.0;
                $order = Order::create([
                    'number' => 'PENDING',
                    'user_id' => auth()->id(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'postcode' => $data['postcode'],
                    'billing_name' => $data['billing_name'],
                    'billing_phone' => $data['billing_phone'],
                    'billing_address' => $data['billing_address'],
                    'billing_city' => $data['billing_city'],
                    'billing_postcode' => $data['billing_postcode'],
                    'notes' => $data['notes'] ?? null,
                    'substitution_preference' => $data['substitution'],
                    'delivery_date' => $data['delivery_date'],
                    'delivery_slot_id' => $priced['slot']->id,
                    'delivery_slot_label' => $priced['slot']->label(),
                    'subtotal' => $priced['subtotal'],
                    'delivery_fee' => $priced['fee'],
                    'total' => $total,
                    'vat_percent' => $vatPercent,
                    'vat_amount' => $vatAmount,
                    'points_redeemed' => $pointsRedeem,
                    'points_discount' => $pointsDiscount,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'coupon_discount' => $couponDiscount,
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
                        'order_id' => $order->id,
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
                        'promo_label' => $line['promo_label'] ?? null,
                        'free_qty' => $line['free_qty'] ?? 0,
                    ]);
                }

                $order->histories()->create([
                    'from_slug' => null,
                    'to_slug' => $status->slug,
                    'changed_by' => auth()->id(),
                    'note' => 'Order placed ('.$order->paymentLabel().').',
                ]);

                // Remember the shopper's details for next time (never overwrite,
                // and never steal a phone number that belongs to another account).
                if ($order->user_id) {
                    $profile = ['address' => $order->address, 'city' => $order->city, 'postcode' => $order->postcode];
                    foreach (['address', 'city', 'postcode'] as $field) {
                        if ($order->user->$field) {
                            unset($profile[$field]);
                        }
                    }
                    if (! $order->user->phone && ! User::where('phone', $order->phone)->where('id', '!=', $order->user_id)->exists()) {
                        $profile['phone'] = $order->phone;
                    }
                    if ($profile !== []) {
                        $order->user->update($profile);
                    }
                    if (! empty($data['save_address'])) {
                        $exists = $order->user->addresses()
                            ->where('address', $order->address)
                            ->where('postcode', $order->postcode)
                            ->exists();
                        if (! $exists) {
                            $order->user->addresses()->create([
                                'label' => $data['save_label'] ?: 'Home',
                                'name' => $order->name,
                                'phone' => $order->phone,
                                'address' => $order->address,
                                'city' => $order->city,
                                'postcode' => $order->postcode,
                                'is_default_delivery' => false,
                                'is_default_billing' => false,
                            ]);
                        }
                    }
                }

                return $order;
            });
        } catch (CouponRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'We could not place your order — please try again.'], 500);
        }

        session(['last_order_id' => $order->id]);

        if ($data['payment_method'] === 'cod') {
            $order->changeStatus('confirmed', auth()->id(), 'Cash on delivery — pay the driver.');
            Order::sendMail($order->receiptEmail(), new OrderPlaced($order));
            BagController::clear();

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
            BagController::clear();

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
        BagController::clear();

        return response()->json([
            'ok' => true,
            'paypal' => true,
            'paypal_order_id' => $ppOrderId,
            'order_number' => $order->number,
        ]);
    }

    /**
     * Validate a coupon code against the current bag. Shoppers only —
     * guests are told to sign in (JSON, never a login redirect).
     */
    public function coupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:50']);

        $coupon = Coupon::findByCode($data['code']);
        if (! $coupon) {
            return response()->json(['ok' => false, 'message' => 'That coupon does not exist.'], 422);
        }

        $subtotal = BagController::detailed()['subtotal'] ?? 0.0;
        $check = $coupon->checkFor(auth()->id(), (float) $subtotal);
        if (! $check['ok']) {
            return response()->json(['ok' => false, 'message' => $check['message']], 422);
        }

        return response()->json([
            'ok' => true,
            'code' => $coupon->code,
            'discount' => $check['discount'],
            'message' => $coupon->code.' applied — £'.number_format($check['discount'], 2).' off.',
        ]);
    }

    /**
     * Live postcode eligibility for the checkout form (JSON, never trusted —
     * place() rechecks against the database).
     */
    public function postcode(Request $request): JsonResponse
    {
        $data = $request->validate(['postcode' => 'required|string|max:20']);

        $zone = DeliveryZone::matching($data['postcode']);
        if ($zone === null && DeliveryZone::active()->exists()) {
            return response()->json(['ok' => false, 'message' => 'Sorry — we don\'t deliver to '.$data['postcode'].' yet.'], 422);
        }

        return response()->json([
            'ok' => true,
            'zone' => $zone?->name,
            'message' => $zone ? 'Good news — we deliver to '.$data['postcode'].' ('.$zone->name.').' : 'Good news — we deliver to '.$data['postcode'].'.',
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
        Order::sendMail($order->receiptEmail(), new OrderPlaced($order));
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

    /**
     * Refund a paid Stripe order (partial allowed). Returns the Stripe refund id.
     *
     * @throws \RuntimeException when the gateway refuses.
     */
    public static function stripeRefund(Order $order, float $amount): string
    {
        if (! static::stripeConfigured()) {
            throw new \RuntimeException('Card payments are not configured.');
        }
        if (! $order->payment_reference) {
            throw new \RuntimeException('No Stripe payment reference on this order.');
        }

        $res = Http::asForm()
            ->withBasicAuth(static::stripeSecret(), '')
            ->post('https://api.stripe.com/v1/refunds', [
                'payment_intent' => $order->payment_reference,
                'amount' => (int) round($amount * 100),
                'metadata[order_number]' => $order->number,
            ]);

        if ($res->failed() || ! $res->json('id')) {
            throw new \RuntimeException('Stripe refund failed: '.$res->body());
        }

        return $res->json('id');
    }

    /**
     * Cancel a previous uncaptured Stripe intent (pay retry). Best effort —
     * a failure here must never block the fresh intent.
     */
    public static function stripeCancelIntent(string $intentId): void
    {
        if (! static::stripeConfigured()) {
            return;
        }

        $res = Http::asForm()
            ->withBasicAuth(static::stripeSecret(), '')
            ->post('https://api.stripe.com/v1/payment_intents/'.$intentId.'/cancel');

        if ($res->failed()) {
            throw new \RuntimeException('Stripe cancel failed: '.$res->body());
        }
    }

    /* ---------------- PayPal (raw REST — no SDK dependency) ---------------- */

    /** Public alias for webhook verification (same token endpoint). */
    public static function paypalTokenForWebhook(): string
    {
        return static::paypalToken();
    }

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

    /**
     * Refund a captured PayPal payment (partial allowed). Returns the PayPal refund id.
     *
     * @throws \RuntimeException when the gateway refuses.
     */
    public static function paypalRefund(Order $order, float $amount): string
    {
        if (! static::paypalConfigured()) {
            throw new \RuntimeException('PayPal is not configured.');
        }
        if (! $order->payment_reference) {
            throw new \RuntimeException('No PayPal capture reference on this order.');
        }

        $res = Http::withToken(static::paypalToken())
            ->post(static::paypalBaseUrl().'/v2/payments/captures/'.$order->payment_reference.'/refund', [
                'amount' => [
                    'currency_code' => 'GBP',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]);

        if ($res->failed() || ! $res->json('id')) {
            throw new \RuntimeException('PayPal refund failed: '.$res->body());
        }

        return $res->json('id');
    }
}
