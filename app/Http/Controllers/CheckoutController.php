<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Api\OrderPayload;
use App\Mail\OrderPlaced;
use App\Models\CompanyDetails;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\DraftOrder;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\UserPoint;
use App\Support\Delivery;
use App\Support\DeliveryRadius;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /** All online wallets ride the same Stripe pipe — one account, one webhook. */
    public const STRIPE_METHODS = ['stripe', 'google_pay', 'apple_pay'];

    public const METHODS = ['cod', 'stripe', 'paypal', 'google_pay', 'apple_pay'];

    public static function isStripeMethod(?string $method): bool
    {
        return in_array($method, static::STRIPE_METHODS, true);
    }

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
     * $speedOrSlot accepts the new 'today'/'standard' speed or a legacy
     * delivery_slots id (old app clients, old tests).
     *
     * @return array{ok: bool, message?: string, lines?: array, subtotal?: float, fee?: float, total?: float, slot?: DeliverySlot|null, speed?: string, date?: string, label?: string}
     */
    public static function priceBag(int|string $speedOrSlot = 'standard', bool $pickup = false): array
    {
        $bag = BagController::detailed();

        $unavailable = collect($bag['lines'])->where('available', false)->pluck('name')->all();
        if ($unavailable !== []) {
            $availableIds = collect($bag['lines'])->where('available', true)->mapWithKeys(fn ($l) => [(int) $l['variant_id'] => (int) $l['qty']])->all();
            session()->put(BagController::SESSION_KEY, $availableIds);
        }

        if ($bag['count'] === 0) {
            return ['ok' => false, 'message' => $unavailable !== []
                ? 'Sorry — '.implode(', ', $unavailable).' is no longer available. Your bag was updated, please review it.'
                : 'Your bag is empty.'];
        }

        if ($unavailable !== []) {
            return ['ok' => false, 'message' => 'Sorry — '.implode(', ', $unavailable).' is no longer available. Your bag was updated, please review it.'];
        }

        $subtotal = $bag['subtotal'];
        $min = Setting::money('delivery_min_order', 15.00);
        if (! $pickup && $subtotal < $min) {
            return ['ok' => false, 'message' => 'The minimum order for delivery is £'.number_format($min, 2).'.'];
        }

        $slot = null;
        $speed = is_string($speedOrSlot) && in_array($speedOrSlot, Delivery::speeds(), true)
            ? $speedOrSlot
            : null;

        if ($speed === null) {
            // Legacy path: numeric delivery_slots id from old clients/tests.
            $slot = DeliverySlot::where('is_active', true)->find($speedOrSlot);
            if (! $slot) {
                return ['ok' => false, 'message' => 'Please choose a delivery option.'];
            }
            $fee = ($pickup || $subtotal >= Setting::money('delivery_free_over', 50.00)) ? 0.0 : (float) $slot->fee;

            return [
                'ok' => true,
                'lines' => $bag['lines'],
                'subtotal' => round($subtotal, 2),
                'fee' => round($fee, 2),
                'total' => round($subtotal + $fee, 2),
                'slot' => $slot,
                'speed' => null,
                'date' => null,
                'label' => $slot->label(),
            ];
        }

        $fee = Delivery::feeFor($speed, $subtotal, $pickup);

        return [
            'ok' => true,
            'lines' => $bag['lines'],
            'subtotal' => round($subtotal, 2),
            'fee' => round($fee, 2),
            'total' => round($subtotal + $fee, 2),
            'slot' => null,
            'speed' => $speed,
            'date' => Delivery::dateFor($speed),
            'label' => Delivery::labelFor($speed, $pickup),
        ];
    }

    /** Create the order from the session bag. Online methods return payment credentials for the JS SDKs. */
    public function place(Request $request): JsonResponse
    {
        $pickup = $request->input('fulfillment') === 'pickup';
        $deliveryAddressRule = $pickup ? 'nullable' : 'required';
        $billingRule = $pickup ? 'nullable' : 'required';
        // Normalise phones before validating: spaces/dashes/brackets carry no
        // meaning, and an emptied billing phone becomes null (skips the regex).
        $request->merge([
            'phone' => static::normalizePhone($request->input('phone')),
            'billing_phone' => static::normalizePhone($request->input('billing_phone')),
        ]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+44|0)(?:7\d{9}|[1235789]\d{9})$/'],
            // Guests must leave an email — otherwise no receipt can reach them.
            'email' => [auth()->check() ? 'nullable' : 'required', 'email:rfc', 'max:255'],
            'address' => [$deliveryAddressRule, 'string', 'max:500'],
            'city' => [$deliveryAddressRule, 'string', 'max:100'],
            'postcode' => [$deliveryAddressRule, 'string', 'max:20'],
            'billing_name' => [$billingRule, 'string', 'max:100'],
            'billing_phone' => [$billingRule, 'string', 'max:30', 'regex:/^(?:\+44|0)(?:7\d{9}|[1235789]\d{9})$/'],
            'billing_address' => [$billingRule, 'string', 'max:500'],
            'billing_city' => [$billingRule, 'string', 'max:100'],
            'billing_postcode' => [$billingRule, 'string', 'max:20'],
            'save_address' => 'nullable|boolean',
            'save_label' => 'nullable|string|max:50',
            'substitution' => 'required|in:substitute,refund,call',
            'notes' => 'nullable|string|max:1000',
            'delivery_speed' => 'nullable|in:today,standard',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'delivery_slot_id' => 'nullable|integer',
            'fulfillment' => 'nullable|in:delivery,pickup',
            'payment_method' => 'required|in:cod,stripe,paypal,google_pay,apple_pay',
            'points_redeem' => 'nullable|integer|min:0|max:1000000',
            'coupon_code' => 'nullable|string|max:50',
            'privacy' => 'accepted',
        ], [
            'phone.regex' => 'Enter a valid UK phone number, e.g. 07123 456789 or +44 7123 456789.',
            'billing_phone.regex' => 'Enter a valid UK billing phone number, e.g. 07123 456789.',
        ]);

        // New two-speed flow (Today / Next working day). Legacy slot+date
        // payloads from old app clients still take the old branch below.
        $speed = $data['delivery_speed'] ?? null;
        if ($speed === null && ($data['fulfillment'] ?? null) === 'pickup') {
            $speed = Delivery::SPEED_STANDARD;
        } elseif ($speed === null && empty($data['delivery_slot_id'])) {
            $speed = Delivery::todayAvailable() ? Delivery::SPEED_TODAY : Delivery::SPEED_STANDARD;
        }
        $deliveryDate = null;
        $deliveryLabel = null;
        $priceKey = null;
        if ($speed !== null) {
            $pickup = ($data['fulfillment'] ?? 'delivery') === 'pickup';
            if ($speed === Delivery::SPEED_TODAY && ! Delivery::todayAvailable()) {
                return response()->json(['message' => "Today's cutoff has passed (".Delivery::cutoffTime().') — please choose Next working day.'], 422);
            }
            $deliveryDate = Delivery::dateFor($speed);
            $deliveryLabel = Delivery::labelFor($speed, $pickup);
            $priceKey = $speed;
        } else {
            if (empty($data['delivery_date']) || empty($data['delivery_slot_id'])) {
                return response()->json(['message' => 'Please choose a delivery option.'], 422);
            }
            if (! array_key_exists($data['delivery_date'], DeliverySlot::bookableDates())) {
                return response()->json(['message' => 'Please choose a valid delivery day.'], 422);
            }

            $chosenSlot = DeliverySlot::where('is_active', true)->find($data['delivery_slot_id']);
            if ($chosenSlot && $chosenSlot->cutoffPassed($data['delivery_date'])) {
                return response()->json(['message' => 'That time window just closed for today — please pick tomorrow or another slot.'], 422);
            }
            $deliveryDate = $data['delivery_date'];
            $deliveryLabel = $chosenSlot?->label();
            $priceKey = (int) $data['delivery_slot_id'];
        }

        $pickup = ($data['fulfillment'] ?? 'delivery') === 'pickup';
        if (! $pickup) {
            $area = DeliveryRadius::check($data['postcode']);
            if (! $area['ok']) {
                return response()->json(['message' => $area['message']], 422);
            }
        }

        if (static::isStripeMethod($data['payment_method'] ?? null) && ! static::stripeConfigured()) {
            return response()->json(['message' => 'Card payment is not available right now — please choose another method.'], 422);
        }
        if ($data['payment_method'] === 'paypal' && ! static::paypalConfigured()) {
            return response()->json(['message' => 'PayPal is not available right now — please choose another method.'], 422);
        }

        $priced = static::priceBag($priceKey ?? Delivery::SPEED_STANDARD, $pickup);
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
            $draft = DB::transaction(function () use ($data, $priced, $pointsRedeem, $pointsDiscount, $pickup, $deliveryDate, $deliveryLabel) {
                // Lock existing ledger rows and recheck the balance inside the
                // transaction so two simultaneous checkouts cannot spend the
                // same points twice.
                if ($pointsRedeem > 0) {
                    $lockedPoints = UserPoint::where('user_id', auth()->id())
                        ->lockForUpdate()->get(['points']);
                    $balance = (int) $lockedPoints->sum('points');
                    $maxPoints = (int) min($balance, floor($priced['subtotal'] / UserPoint::value()));
                    if ($pointsRedeem < UserPoint::minRedeem() || $pointsRedeem > $maxPoints) {
                        throw new PointsRejected('Your loyalty balance changed. Please review your points and try again.');
                    }
                }

                // Coupon — revalidated server-side, shoppers only. Row-locked so
                // concurrent checkouts cannot over-redeem a limited coupon.
                $coupon = null;
                $couponDiscount = 0.0;
                if (! empty($data['coupon_code'])) {
                    $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))
                        ->lockForUpdate()->first();
                    if (! $coupon) {
                        throw new CouponRejected('That coupon does not exist.');
                    }
                    $check = $coupon->checkFor(auth()->id(), $priced['subtotal']);
                    if (! $check['ok']) {
                        throw new CouponRejected($check['message']);
                    }
                    // Close the race: the coupon row is locked, but usesCount reads
                    // committed orders — recount under lock so concurrent checkouts
                    // serialize on the orders index instead of both passing max_uses.
                    $lockedUses = $coupon->orders()->where('status_slug', '!=', 'cancelled')->lockForUpdate()->count();
                    if ($coupon->max_uses !== null && $lockedUses >= $coupon->max_uses) {
                        throw new CouponRejected('That coupon has all been used up.');
                    }
                    $lockedMine = $coupon->orders()->where('user_id', auth()->id())->where('status_slug', '!=', 'cancelled')->lockForUpdate()->count();
                    if ($lockedMine >= $coupon->max_per_user) {
                        throw new CouponRejected('You have already used that coupon.');
                    }
                    $couponDiscount = $check['discount'];
                }

                // VAT is included in shelf prices: snapshot the VAT portion of the total.
                $total = max(0, round($priced['subtotal'] + $priced['fee'] - $pointsDiscount - $couponDiscount, 2));
                $vatPercent = (float) (CompanyDetails::cached()->vat_percent ?? 0);
                $vatAmount = $vatPercent > 0 ? round($total * $vatPercent / (100 + $vatPercent), 2) : 0.0;
                $draft = DraftOrder::create([
                    'draft_number' => 'DRAFT-'.uniqid(),
                    'user_id' => auth()->id(),
                    'payload' => [
                        'data' => $data,
                        'coupon_id' => $coupon?->id,
                        'priced' => $priced,
                        'points_redeem' => $pointsRedeem,
                        'points_discount' => $pointsDiscount,
                        'coupon_discount' => $couponDiscount,
                        'pickup' => $pickup,
                        'delivery_date' => $deliveryDate,
                        'delivery_label' => $deliveryLabel,
                        'total' => $total,
                        'vat_percent' => $vatPercent,
                        'vat_amount' => $vatAmount,
                    ],
                    'payment_method' => $data['payment_method'],
                    'status' => 'pending',
                ]);

                return $draft;
            });
        } catch (CouponRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PointsRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'We could not place your order — please try again.'], 500);
        }

        session(['last_draft_id' => $draft->id]);

        if ($data['payment_method'] === 'cod') {
            $order = $this->promoteDraftToOrder($draft);
            try {
                $order->changeStatus('confirmed', auth()->id(), $pickup ? 'Cash on collection — pay in store.' : 'Cash on delivery — pay the driver.');
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'We could not confirm your order.'], 500);
            }
            Order::sendMail($order->receiptEmail(), new OrderPlaced($order));
            BagController::clear();
            session(['last_order_id' => $order->id]);

            return response()->json(['ok' => true, 'redirect' => route('order.success', $order->number)]);
        }

        // Build a lightweight value object so gateway helpers (stripeIntent /
        // paypalCreateOrder) get the fields they need without a real Order row.
        $gw = new \stdClass;
        $gw->total = $draft->payload['total'] ?? 0;
        $gw->number = $draft->draft_number;
        $gw->payment_method = $draft->payment_method;

        if (static::isStripeMethod($data['payment_method'] ?? null)) {
            try {
                $intent = static::stripeIntent($gw);
            } catch (\Throwable $e) {
                report($e);
                $draft->delete();

                return response()->json(['message' => 'Card payment could not be started — please try again or choose another method.'], 422);
            }
            $draft->payment_reference = $intent['id'];
            $draft->save();

            return response()->json([
                'ok' => true,
                'stripe' => true,
                'publishable' => static::stripePublishable(),
                'client_secret' => $intent['client_secret'],
                'order_number' => $draft->draft_number,   // app sends this back to paymentConfirm
                'payment_method' => $draft->payment_method,
            ]);
        }

        try {
            $ppOrderId = static::paypalCreateOrder($gw);
        } catch (\Throwable $e) {
            report($e);
            $draft->delete();

            return response()->json(['message' => 'PayPal could not be started — please try again or choose another method.'], 422);
        }
        $draft->payment_reference = $ppOrderId;
        $draft->save();

        return response()->json([
            'ok' => true,
            'paypal' => true,
            'paypal_order_id' => $ppOrderId,
            'approve_url' => static::paypalCheckoutUrl($ppOrderId),
            'order_number' => $draft->draft_number,   // app sends this back to paymentConfirm
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
     * place() rechecks the radius).
     */
    public function postcode(Request $request): JsonResponse
    {
        $data = $request->validate(['postcode' => 'required|string|max:20']);

        $area = DeliveryRadius::check($data['postcode']);
        if (! $area['ok']) {
            return response()->json(['ok' => false, 'message' => $area['message']], 422);
        }

        return response()->json([
            'ok' => true,
            'distance' => $area['distance'] === null ? null : round($area['distance'], 1),
            'message' => $area['message'],
        ]);
    }

    /**
     * Called by the Stripe/PayPal JS after the shopper approves payment.
     * Verifies with the gateway — never trusts the browser.
     */
    public function paymentConfirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            // The existing browser and mobile clients call this field
            // order_number. A DRAFT-* value is still the identifier until
            // the gateway payment succeeds and the real order is created.
            'order_number' => 'required|string',
            'payment_method' => 'required|in:stripe,paypal,google_pay,apple_pay',
        ]);

        $draft = DraftOrder::where('draft_number', $data['order_number'])->first();
        if (! $draft) {
            $order = Order::where('number', $data['order_number'])->firstOrFail();
            if (! $this->maySee($order)) {
                return response()->json(['message' => 'Order not found.'], 404);
            }
            if ($order->isPaid()) {
                return response()->json(['ok' => true, 'redirect' => route('order.success', $order->number)]);
            }

            return response()->json(['message' => 'Not yet paid — please try again.'], 422);
        }
        if (! $this->maySeeDraft($draft)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
        if ($draft->payment_method !== $data['payment_method']) {
            return response()->json(['message' => 'Payment method mismatch.'], 422);
        }

        try {
            $paid = $data['payment_method'] === 'paypal'
                ? $this->paypalCapture($draft)
                : $this->stripeVerify($draft);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'We could not confirm your payment — please try again.'], 422);
        }

        if (! $paid) {
            return response()->json(['message' => 'Your payment has not completed yet — please try again.'], 422);
        }

        $order = $this->promoteDraftToOrder($draft);
        $order->payment_status = 'paid';
        $order->amount_paid = $order->total;
        $order->save();
        $changed = $order->status_slug === 'new' ? $order->changeStatus('confirmed', $order->user_id, 'Paid online ('.$order->paymentLabel().').') : null;
        if ($changed) {
            Order::sendMail($order->receiptEmail(), new OrderPlaced($order));
        }
        Payment::attempt($order, $order->payment_reference);
        Payment::settle($order, $order->payment_reference, Payment::SUCCEEDED);
        BagController::clear();
        session(['last_order_id' => $order->id]);

        return response()->json(['ok' => true, 'redirect' => route('order.success', $order->number)]);
    }

    public function success(string $number)
    {
        $order = Order::with(['items', 'histories', 'status'])->where('number', $number)->firstOrFail();

        if (! $this->maySee($order)) {
            abort(404);
        }

        // Safety net: webhook-paid orders never touch the session bag
        // (no session in a webhook), so clear it here when this visit
        // follows the order it just placed. Idempotent.
        if (session('last_order_id') === $order->id) {
            BagController::clear();
        }

        return spa('frontend.order-success', compact('order'));
    }

    /**
     * Abandon an unpaid online order (PayPal cancel button, closed card tab).
     * In the draft flow (order_number starts with DRAFT-) we simply delete
     * the draft row — no real Order exists yet so there is nothing to cancel.
     * Legacy flow: cancels the real Order as before.
     */
    public function cancel(Request $request): JsonResponse
    {
        $number = (string) $request->input('order_number', '');

        // Draft flow: just delete the draft and return ok.
        if (str_starts_with($number, 'DRAFT-')) {
            $draft = DraftOrder::where('draft_number', $number)->first();
            if (! $draft || ! $this->maySeeDraft($draft)) {
                return response()->json(['message' => 'Order not found.'], 404);
            }
            $draft->delete();

            return response()->json(['ok' => true]);
        }

        $data = $request->validate(['order_number' => 'required|string|exists:orders,number']);

        $order = Order::where('number', $data['order_number'])->firstOrFail();

        if (! $this->maySee($order)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }
        if ($order->isPaid() || $order->status_slug !== 'new') {
            return response()->json(['message' => 'That order can no longer be cancelled online.'], 422);
        }

        $order->changeStatus('cancelled', $order->user_id, 'Payment abandoned at checkout.');
        Payment::cancelPending($order);

        return response()->json(['ok' => true]);
    }

    /** Downloadable invoice PDF for the shopper (owner, or the guest session that just placed it). */
    public function invoicePdf(string $number)
    {
        $order = Order::with(['items', 'status', 'user'])->where('number', $number)->firstOrFail();

        if (! $this->maySee($order)) {
            abort(404);
        }

        return Pdf::loadView('admin.orders.invoice', OrderController::invoiceData($order))
            ->setPaper(Setting::get('invoice_format', 'a4') === 'pos' ? [0, 0, 226.77, 2000] : 'a4')
            ->download('invoice-'.$order->number.'.pdf');
    }

    /** JSON twin of success(): the order a shopper (or the guest who just placed it) may see. */
    public function successJson(string $number): JsonResponse
    {
        $order = Order::with(['items', 'histories', 'status'])->where('number', $number)->firstOrFail();

        if (! $this->maySee($order)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(['order' => OrderPayload::make($order)]);
    }

    private function maySee(Order $order): bool
    {
        if (auth()->check() && (auth()->user()->user_type == 1 || $order->user_id === auth()->id())) {
            return true;
        }

        return session('last_order_id') === $order->id;
    }

    private function maySeeDraft(DraftOrder $draft): bool
    {
        if (auth()->check() && (auth()->user()->user_type == 1 || $draft->user_id === auth()->id())) {
            return true;
        }

        return session('last_draft_id') === $draft->id;
    }

    /**
     * Strip formatting from a phone number (spaces, dashes, brackets).
     * Empty input becomes null so optional fields skip validation.
     */
    private static function normalizePhone(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = preg_replace('/[\s\-().]/', '', (string) $value);

        return $clean === '' ? null : $clean;
    }

    /* ---------------- Stripe (raw API — no SDK dependency) ---------------- */

    /** @return array{id: string, client_secret: string} */
    public static function stripeIntent(object $order): array
    {
        $res = Http::asForm()
            ->withBasicAuth(static::stripeSecret(), '')
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($order->total * 100),
                'currency' => 'gbp',
                'automatic_payment_methods[enabled]' => 'true',
                'metadata[order_number]' => $order->number,
                'metadata[wallet]' => $order->payment_method,
                'description' => 'Alam Mini Market order '.$order->number,
            ]);

        if ($res->failed()) {
            throw new \RuntimeException('Stripe error: '.$res->body());
        }

        return ['id' => $res->json('id'), 'client_secret' => $res->json('client_secret')];
    }

    private function stripeVerify(object $order): bool
    {
        if (! $order->payment_reference) {
            return false;
        }

        $res = Http::withBasicAuth(static::stripeSecret(), '')
            ->get('https://api.stripe.com/v1/payment_intents/'.$order->payment_reference);

        return $res->ok() && static::stripePaymentMatches((array) $res->json(), $order);
    }

    /** Accept only the exact successful intent created for this order and amount. */
    public static function stripePaymentMatches(array $intent, object $order): bool
    {
        $intentId = $intent['id'] ?? null;
        $referenceMatches = is_string($intentId)
            && str_starts_with($intentId, 'pi_')
            && is_string($order->payment_reference)
            && $order->payment_reference !== ''
            && hash_equals($order->payment_reference, $intentId);

        return $referenceMatches
            && ($intent['metadata']['order_number'] ?? null) === $order->number
            && ($intent['status'] ?? null) === 'succeeded'
            && strtolower((string) ($intent['currency'] ?? '')) === 'gbp'
            && (int) ($intent['amount_received'] ?? 0) === (int) round((float) $order->total * 100);
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

    public static function paypalCheckoutUrl(string $orderId): string
    {
        $host = static::paypalMode() === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com';

        return "https://{$host}/checkoutnow?token={$orderId}";
    }

    public static function paypalCreateOrder(object $order): string
    {
        $res = Http::withToken(static::paypalToken())
            ->post(static::paypalBaseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->number,
                    'description' => 'Alam Mini Market order '.$order->number,
                    'amount' => [
                        'currency_code' => 'GBP',
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'Alam Mini Market',
                    'locale' => 'en-GB',
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => url('/checkout/paypal-return'),
                    'cancel_url' => url('/checkout/paypal-cancel'),
                ],
            ]);

        if ($res->failed() || ! $res->json('id')) {
            throw new \RuntimeException('PayPal order failed: '.$res->body());
        }

        return $res->json('id');
    }

    public function paypalReturn(Request $request)
    {
        return response('<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Payment Approved</title><style>body{font-family:system-ui,-apple-system,sans-serif;text-align:center;padding:48px 20px;background:#FAF7EF;color:#1A2E22;}h2{color:#1B4332;margin-bottom:8px;}p{color:#555;font-size:15px;}</style></head><body><h2>Payment approved!</h2><p>Please wait, returning you to your order...</p></body></html>', 200, ['Content-Type' => 'text/html']);
    }

    public function paypalCancel(Request $request)
    {
        return response('<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Payment Cancelled</title><style>body{font-family:system-ui,-apple-system,sans-serif;text-align:center;padding:48px 20px;background:#FAF7EF;color:#1A2E22;}h2{color:#B91C1C;margin-bottom:8px;}p{color:#555;font-size:15px;}</style></head><body><h2>Payment cancelled</h2><p>Returning to checkout...</p></body></html>', 200, ['Content-Type' => 'text/html']);
    }

    private function paypalCapture(object $order): bool
    {
        if (! $order->payment_reference) {
            Log::warning('PayPal capture skipped: empty payment_reference.', ['order' => $order->number]);

            return false;
        }

        // Empty JSON object with a JSON content type: PayPal rejects both a
        // literal `[]` body (MALFORMED_REQUEST_JSON) and a body-less request
        // (415 UNSUPPORTED_MEDIA_TYPE) on the capture endpoint.
        $res = Http::withToken(static::paypalToken())
            ->withBody('{}')
            ->send('POST', static::paypalBaseUrl().'/v2/checkout/orders/'.$order->payment_reference.'/capture');

        if ($res->failed()) {
            Log::warning('PayPal capture refused.', [
                'order' => $order->number,
                'reference' => $order->payment_reference,
                'http' => $res->status(),
                'paypal' => static::paypalErrorSummary($res->json()),
            ]);

            return false;
        }

        $unit = $res->json('purchase_units.0', []);
        if (! is_array($unit)) {
            Log::warning('PayPal capture has no purchase unit.', ['order' => $order->number, 'reference' => $order->payment_reference]);

            return false;
        }
        $capture = collect($unit['payments']['captures'] ?? [])->first(fn ($c) => ($c['status'] ?? null) === 'COMPLETED'
            && is_string($c['id'] ?? null)
            && $c['id'] !== ''
            && strtoupper((string) ($c['amount']['currency_code'] ?? '')) === 'GBP'
            && (int) round((float) ($c['amount']['value'] ?? 0) * 100) === (int) round((float) $order->total * 100)
        );

        if (($unit['reference_id'] ?? null) === $order->number && $capture) {
            $order->payment_reference = $capture['id'];
            $order->save();
            Log::info('PayPal capture ok.', ['order' => $order->number, 'capture' => $capture['id']]);

            return true;
        }

        Log::warning('PayPal capture not completed.', [
            'order' => $order->number,
            'reference' => $order->payment_reference,
            'reference_id' => $unit['reference_id'] ?? null,
            'statuses' => collect($unit['payments']['captures'] ?? [])->pluck('status')->all(),
        ]);

        return false;
    }

    /**
     * One-line PayPal error: name + first issue (e.g. ORDER_NOT_APPROVED).
     */
    private static function paypalErrorSummary(mixed $body): string
    {
        if (! is_array($body)) {
            return substr((string) $body, 0, 200);
        }
        $issue = $body['details'][0]['issue'] ?? $body['details'][0]['description'] ?? null;

        return trim(($body['name'] ?? 'ERROR').($issue ? ': '.$issue : '').(isset($body['message']) && ! $issue ? ' '.$body['message'] : ''));
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

    private function promoteDraftToOrder(DraftOrder $draft): Order
    {
        $p = $draft->payload ?? [];
        $data = $p['data'] ?? [];
        $priced = $p['priced'] ?? [];
        $pickup = $p['pickup'] ?? false;
        $deliveryDate = $p['delivery_date'] ?? null;
        $deliveryLabel = $p['delivery_label'] ?? null;
        $pointsRedeem = $p['points_redeem'] ?? 0;
        $total = $p['total'] ?? 0;
        $vatPercent = $p['vat_percent'] ?? 0;
        $vatAmount = $p['vat_amount'] ?? 0;

        $status = OrderStatus::where('slug', 'new')->where('is_active', true)->firstOrFail();
        $order = Order::create([
            'number' => 'PENDING',
            'user_id' => $draft->user_id,
            'name' => $data['name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? null,
            'address' => $pickup ? 'Click & Collect' : ($data['address'] ?? ''),
            'city' => $pickup ? '' : ($data['city'] ?? ''),
            'postcode' => $pickup ? '' : ($data['postcode'] ?? ''),
            'billing_name' => $pickup ? null : ($data['billing_name'] ?? null),
            'billing_phone' => $pickup ? null : ($data['billing_phone'] ?? null),
            'billing_address' => $pickup ? null : ($data['billing_address'] ?? null),
            'billing_city' => $pickup ? null : ($data['billing_city'] ?? null),
            'billing_postcode' => $pickup ? null : ($data['billing_postcode'] ?? null),
            'notes' => $data['notes'] ?? null,
            'substitution_preference' => $data['substitution'] ?? 'substitute',
            'delivery_date' => $deliveryDate,
            'delivery_slot_id' => $priced['slot']['id'] ?? null,
            'delivery_slot_label' => $deliveryLabel ?? $priced['label'] ?? null,
            'subtotal' => $priced['subtotal'] ?? 0,
            'delivery_fee' => $priced['fee'] ?? 0,
            'total' => $total,
            'vat_percent' => $vatPercent,
            'vat_amount' => $vatAmount,
            'points_redeemed' => $pointsRedeem,
            'points_discount' => $p['points_discount'] ?? 0,
            'coupon_id' => $p['coupon_id'] ?? null,
            'coupon_code' => ! empty($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null,
            'coupon_discount' => $p['coupon_discount'] ?? 0,
            'payment_method' => $draft->payment_method,
            'payment_reference' => $draft->payment_reference,
            'payment_status' => 'unpaid',
            'fulfillment' => $pickup ? 'pickup' : 'delivery',
            'status_id' => $status->id,
            'status_slug' => $status->slug,
        ]);
        $order->number = 'AMM-'.(10000 + $order->id);
        $order->save();
        $draft->delete();
        if ($pointsRedeem > 0) {
            $order->points()->create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => -$pointsRedeem,
                'type' => UserPoint::REDEEM,
                'description' => 'Spent on '.$order->number,
            ]);
        }
        foreach ($priced['lines'] ?? [] as $line) {
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
            'changed_by' => $draft->user_id,
            'note' => 'Order placed ('.$order->paymentLabel().').',
        ]);

        return $order;
    }
}
