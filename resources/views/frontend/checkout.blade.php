@extends('frontend.layout')
@section('title', 'Checkout')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Checkout</h1>
            <p>Where and when should we deliver your groceries?</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if (empty($bag['lines']))
            <div class="empty-state">
                <h2>Your bag is empty</h2>
                <p>Add something fresh before checking out.</p>
                <a @spa href="{{ route('shop') }}" class="btn btn-dark">Shop groceries</a>
            </div>
        @else
            @php($hasUnavailable = collect($bag['lines'] ?? [])->contains(fn ($item) => ! $item['available']))
            @if ($hasUnavailable)
                <div class="auth-card" role="alert" style="border-color:#B91C1C;margin-bottom:1.5rem;">
                    Some items in your bag are no longer available. Return to your bag, remove them, and then try checkout again.
                    <a @spa href="{{ route('bag') }}">Review your bag</a>.
                </div>
            @endif
            @if (session('error'))
                <div class="auth-card" style="border-color:#B91C1C;margin-bottom:1.5rem;">{{ session('error') }}</div>
            @endif

            @auth
                <div class="auth-card" style="margin-bottom:1.5rem;">
                    Checking out as <strong>{{ auth()->user()->name }}</strong>
                    <span class="text-muted">·</span>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('egf-logout').submit();">Not you? Sign out</a>
                    <form id="egf-logout" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                </div>
            @else
                <div class="auth-card" style="margin-bottom:1.5rem;">
                    Checking out as a <strong>guest</strong> — no account needed.
                    <span class="text-muted">·</span>
                    Have an account?
                    <a href="{{ route('login', ['redirect' => route('checkout')]) }}">Sign in</a>
                    <span class="text-muted">or</span>
                    <a href="{{ route('register', ['redirect' => route('checkout')]) }}">create one</a>
                    — your bag comes with you.
                </div>
            @endauth

            <form id="egf-checkout-form" class="cart-layout" data-has-unavailable="{{ $hasUnavailable ? '1' : '0' }}" style="margin:0;align-items:start;">
                @csrf
                <div style="min-width:0;">
                <div class="auth-card" style="margin:0 0 1.5rem;">
                    <h2 style="font-size:1.25rem;margin-bottom:1rem;"><span class="co-step">0</span>Delivery or collection?</h2>
                    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                        <label class="pick-row is-inline"><input type="radio" name="fulfillment" value="delivery" checked> <span class="pick-box"></span> <span>Home delivery</span></label>
                        <label class="pick-row is-inline"><input type="radio" name="fulfillment" value="pickup"> <span class="pick-box"></span> <span>Click &amp; Collect <span class="promo-tag">Free</span></span></label>
                    </div>
                    <p class="text-muted" id="co-pickup-note" role="status" aria-live="polite" style="display:none;font-size:13px;margin:.6rem 0 0;">Pick up from <strong>{{ trim(($company->address1 ?? '').' '.($company->address2 ?? '').' '.($company->address3 ?? '')) ?: 'our store' }}</strong> — we'll email you when it's ready. No delivery fee.</p>
                </div>
                <div class="auth-card" style="margin:0;">
                    <h2 style="font-size:1.25rem;margin-bottom:1.25rem;"><span class="co-step">1</span>Your details</h2>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-name">Full name</label>
                            <input type="text" id="co-name" name="name" required maxlength="100" value="{{ old('name', $defaultDelivery->name ?? $shopper->name ?? '') }}" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label for="co-phone">Phone</label>
                            <input type="tel" id="co-phone" name="phone" required maxlength="30" inputmode="tel" placeholder="e.g. 07123 456789" value="{{ old('phone', $defaultDelivery->phone ?? $shopper->phone ?? '') }}" autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="co-email">Email @auth<span class="text-muted">(for your receipt)</span>@else<span class="text-danger">*</span>@endauth</label>
                        <input type="email" id="co-email" name="email" maxlength="255" @guest required @endguest value="{{ old('email', $shopper->email ?? '') }}" placeholder="you@example.com" autocomplete="email">
                    </div>
                    <div id="co-delivery-fields">
                    @auth
                        @if ($addresses->isNotEmpty())
                            <div class="form-group">
                                <label for="co-delivery-id">Deliver to (your address book)</label>
                                <select id="co-delivery-id">
                                    @foreach ($addresses as $a)
                                        <option value="{{ $a->id }}" @selected($defaultDelivery && $defaultDelivery->id === $a->id)>{{ $a->label }} — {{ $a->line() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <script type="application/json" id="co-address-book">@json($coAddressBook)</script>
                        @endif
                    @endauth
                    <h3 id="co-delivery-title" style="font-size:1rem;margin:1.25rem 0 .75rem;">Delivery address</h3>
                    <div class="form-group">
                        <label for="co-address">Street address</label>
                        <input type="text" id="co-address" name="address" required maxlength="500" value="{{ old('address', $defaultDelivery->address ?? $shopper->address ?? '') }}" placeholder="Flat, street" autocomplete="street-address">
                    </div>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-city">Town / City</label>
                            <input type="text" id="co-city" name="city" required maxlength="100" value="{{ old('city', $defaultDelivery->city ?? $shopper->city ?? '') }}" autocomplete="address-level2">
                        </div>
                        <div class="form-group">
                            <label for="co-postcode">Postcode</label>
                            <input type="text" id="co-postcode" name="postcode" required maxlength="20" value="{{ old('postcode', $defaultDelivery->postcode ?? $shopper->postcode ?? '') }}" placeholder="e.g. SW1A 1AA" autocomplete="postal-code">
                            <p class="text-muted" id="co-postcode-msg" style="font-size:13px;margin-top:0.35rem;display:none;"></p>
                        </div>
                    </div>
                    @auth
                        <div class="form-group">
                            <label class="pick-row is-inline"><input type="checkbox" id="co-save-address" value="1"> <span class="pick-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span> <span>Save this delivery address to my book</span></label>
                            <input type="text" id="co-save-label" maxlength="50" placeholder="Label, e.g. Work" style="max-width:200px;display:none;margin-top:0.5rem;">
                        </div>
                    @endauth
                    <div class="form-group">
                        <label for="co-notes">Delivery notes <span class="text-muted">(optional)</span></label>
                        <input type="text" id="co-notes" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Gate code, leave with neighbour…">
                    </div>
                    </div>
                    <div class="form-group">
                        <label>If something is unavailable</label>
                        <div class="pick-grid cols-3">
                            <label class="pick-row is-inline"><input type="radio" name="substitution" value="substitute" checked> <span class="pick-box"></span> <span>Substitute it with something similar</span></label>
                            <label class="pick-row is-inline"><input type="radio" name="substitution" value="refund"> <span class="pick-box"></span> <span>Remove it and refund me</span></label>
                            <label class="pick-row is-inline"><input type="radio" name="substitution" value="call"> <span class="pick-box"></span> <span>Call me first</span></label>
                        </div>
                    </div>

                    <div id="co-billing-section">
                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;"><span class="co-step">2</span>Billing details</h2>
                    <div class="form-group">
                        <label class="pick-row is-inline"><input type="checkbox" id="co-billing-same" checked> <span class="pick-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span> <span>Billing address is the same as delivery</span></label>
                    </div>
                    <div id="co-billing-block" style="display:none;">
                        @auth
                            @if ($addresses->isNotEmpty())
                                <div class="form-group">
                                    <label for="co-billing-id">Bill to (your address book)</label>
                                    <select id="co-billing-id">
                                        @foreach ($addresses as $a)
                                            <option value="{{ $a->id }}" @selected($defaultBilling && $defaultBilling->id === $a->id)>{{ $a->label }} — {{ $a->line() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endauth
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="co-bill-name">Billing name</label>
                                <input type="text" id="co-bill-name" name="billing_name" maxlength="100" value="{{ old('billing_name', $defaultBilling->name ?? $defaultDelivery->name ?? $shopper->name ?? '') }}" autocomplete="name">
                            </div>
                            <div class="form-group">
                                <label for="co-bill-phone">Billing phone</label>
                                <input type="tel" id="co-bill-phone" name="billing_phone" maxlength="30" value="{{ old('billing_phone', $defaultBilling->phone ?? $defaultDelivery->phone ?? $shopper->phone ?? '') }}" autocomplete="tel">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="co-bill-address">Billing street address</label>
                            <input type="text" id="co-bill-address" name="billing_address" maxlength="500" value="{{ old('billing_address', $defaultBilling->address ?? $defaultDelivery->address ?? $shopper->address ?? '') }}" autocomplete="street-address">
                        </div>
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="co-bill-city">Billing town / City</label>
                                <input type="text" id="co-bill-city" name="billing_city" maxlength="100" value="{{ old('billing_city', $defaultBilling->city ?? $defaultDelivery->city ?? $shopper->city ?? '') }}" autocomplete="address-level2">
                            </div>
                            <div class="form-group">
                                <label for="co-bill-postcode">Billing postcode</label>
                                <input type="text" id="co-bill-postcode" name="billing_postcode" maxlength="20" value="{{ old('billing_postcode', $defaultBilling->postcode ?? $defaultDelivery->postcode ?? $shopper->postcode ?? '') }}" autocomplete="postal-code">
                            </div>
                        </div>
                    </div>
                    </div>

                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;"><span class="co-step">3</span><span id="co-fulfillment-heading">When should it arrive?</span></h2>
                    <div class="pick-grid cols-2" id="co-speed-grid">
                        @foreach ($delivery as $opt)
                            <label class="pick-row @if (! $opt['available']) pick-row-off @endif">
                                <input type="radio" name="delivery_speed" value="{{ $opt['speed'] }}" data-fee="{{ $opt['fee'] }}" data-date="{{ $opt['date'] }}" data-date-label="{{ $opt['date_label'] }}" @checked($opt['speed'] === ($todayAvailable ? 'today' : 'standard')) @disabled(! $opt['available'])>
                                <span class="pick-box"></span>
                                <span>
                                    <strong id="co-speed-{{ $opt['speed'] }}-title">{{ $opt['speed'] === 'today' ? 'Today delivery' : 'Standard shipping' }}</strong><br>
                                    <span class="text-muted" id="co-speed-{{ $opt['speed'] }}-sub">{{ $opt['date_label'] }} · {{ $opt['fee'] > 0 ? '£'.number_format($opt['fee'], 2) : 'Free' }}</span>
                                    @if (! $opt['available'])<br><span style="color:#B91C1C;font-size:13px;">Today's cutoff has passed</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="arriving-line" id="co-arriving">Arriving: <strong>{{ $todayAvailable ? $delivery[0]['date_label'] : $delivery[1]['date_label'] }}</strong> · {{ ($bag['subtotal'] ?? 0) >= $freeOver ? 'Free' : '£'.number_format($todayAvailable ? $todayFee : $standardFee, 2) }}</p>
                    <p class="text-muted" style="font-size:13px;" id="co-cutoff-msg" data-fee-note="Free delivery over £{{ number_format($freeOver, 2) }} · minimum order £{{ number_format($minOrder, 2) }}." data-cutoff="{{ $cutoffTime }}" data-today-available="{{ $todayAvailable ? '1' : '0' }}">
                        @if ($todayAvailable)
                            Order before {{ $cutoffTime }} for delivery today — after that, earliest is next working day.
                        @else
                            Today's cutoff has passed ({{ $cutoffTime }}) — only next working day delivery is available.
                        @endif
                        Free delivery over £{{ number_format($freeOver, 2) }} · minimum order £{{ number_format($minOrder, 2) }}.
                    </p>
                </div>
                </div>

                <aside class="cart-summary">
                    <div id="co-delivery-progress">@include('frontend.partials.delivery-progress', ['subtotal' => $bag['subtotal'] ?? 0, 'minOrder' => $minOrder, 'freeOver' => $freeOver])</div>
                    <h3>Your bag ({{ $bag['count'] }})</h3>
                    @foreach ($bag['lines'] as $item)
                        <div class="summary-row co-line" style="align-items:center;">
                            <img class="co-thumb" src="{{ $item['image'] }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='{{ url('placeholder.webp') }}'">
                            <span style="flex:1;min-width:0;">{{ $item['qty'] }} × {{ $item['name'] }}@if ($item['promo_label']) <span class="promo-tag">{{ $item['promo_label'] }}</span>@endif<br><span class="text-muted">{{ $item['pack'] }}</span>@if (! $item['available'])<br><span style="color:#B91C1C">Unavailable</span>@endif</span>
                            <span>@if ($item['available'])£{{ number_format($item['line_total'], 2) }}@else—@endif</span>
                        </div>
                    @endforeach
                    <div class="summary-row"><span>Subtotal</span><span data-co-subtotal>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                    @if (($bag['bogo_discount'] ?? 0) > 0)<div class="summary-row"><span>BOGO savings</span><span>−£{{ number_format($bag['bogo_discount'], 2) }}</span></div>@endif
                    @if (($bag['bundle_discount'] ?? 0) > 0)<div class="summary-row"><span>Bundle savings</span><span>−£{{ number_format($bag['bundle_discount'], 2) }}</span></div>@endif
                    <div class="summary-row" data-co-coupon style="display:none;"><span>Coupon <strong data-co-coupon-code></strong> <button type="button" data-co-coupon-remove aria-label="Remove coupon" style="border:0;background:none;color:#B91C1C;cursor:pointer;font-size:14px;">×</button></span><span data-co-coupon-amount>−£0.00</span></div>
                    <div class="summary-row" data-co-points style="display:none;"><span>Loyalty points</span><span>−£0.00</span></div>
                    <div class="summary-row"><span data-co-fulfillment-fee-label>Delivery</span><span data-co-fee>Calculated…</span></div>
                    <div class="summary-row total"><span>Total</span><span data-co-total>£{{ number_format($bag['subtotal'], 2) }}</span></div>

                    <h3 style="margin-top:1.5rem;">Coupon code</h3>
                    @auth
                        <div class="coupon-box" id="co-coupon-box">
                            <div class="coupon-row">
                                <input type="text" id="co-coupon" placeholder="Enter code" autocomplete="off" aria-label="Coupon code">
                                <button type="button" class="btn btn-dark btn-sm" id="co-coupon-apply">Apply</button>
                            </div>
                            <p class="coupon-msg" id="co-coupon-msg" style="display:none;"></p>
                            <input type="hidden" id="co-coupon-code" value="">
                        </div>
                    @else
                        <p class="text-muted" style="font-size:14px;"><a href="{{ route('login', ['redirect' => route('checkout')]) }}">Sign in</a> to use a coupon code.</p>
                    @endauth

                    <h3 style="margin-top:1.5rem;"><span class="co-step">4</span>Payment</h3>
                    @auth
                        @if ($pointsBalance >= $pointsMin)
                            <div class="pay-card" style="margin-bottom:0.7rem;cursor:default;" id="co-points-card">
                                <span class="pay-card-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.5 13 17 22l-5-3-5 3 1.5-9"/></svg>
                                </span>
                                <span class="pay-card-text">
                                    <strong>Use loyalty points <span class="text-muted">({{ $pointsBalance }} available)</span></strong>
                                    <span class="text-muted">100 points = £{{ number_format(100 * $pointsValue, 2) }} off</span>
                                </span>
                                <input type="number" id="co-points" min="0" max="{{ $pointsBalance }}" step="{{ $pointsMin }}" value="0" style="width:110px;border:1px solid var(--border);border-radius:8px;padding:0.5rem;" aria-label="Points to spend">
                            </div>
                        @endif
                    @endauth
                    <div class="pay-tiles" id="co-methods">
                        <label class="pay-tile">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <span class="pay-tile-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                            </span>
                            <strong id="co-cash-title">Cash</strong>
                            <span class="text-muted" id="co-cash-note">Pay at door</span>
                            <span class="pay-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        </label>
                        <label class="pay-tile @if (! $stripeOn) pay-tile-off @endif">
                            <input type="radio" name="payment_method" value="stripe" @disabled(! $stripeOn)>
                            <span class="pay-tile-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            </span>
                            <strong>Card</strong>
                            <span class="text-muted">{{ $stripeOn ? 'Visa · MC · Amex' : 'Soon' }}</span>
                            <span class="pay-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        </label>
                        <label class="pay-tile @if (! $paypalOn) pay-tile-off @endif">
                            <input type="radio" name="payment_method" value="paypal" @disabled(! $paypalOn)>
                            <span class="pay-tile-icon paypal-mark"><em>Pay</em><strong>Pal</strong></span>
                            <strong>PayPal</strong>
                            <span class="text-muted">{{ $paypalOn ? 'Protected' : 'Soon' }}</span>
                            <span class="pay-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        </label>
                    </div>
                    @if (! $stripeOn || ! $paypalOn)
                        <p class="text-muted" style="font-size:13px;margin:0.5rem 0 1rem;">Online payments go live as soon as the shop connects its card and PayPal accounts — cash on delivery works today.</p>
                    @endif
                    <div class="form-group" id="co-card-wrap" style="display:none;">
                        <label>Payment details</label>
                        <div id="co-card-element" style="border:1px solid var(--border,#e5e2da);border-radius:10px;padding:0.8rem;"></div>
                        <p class="text-muted" id="co-stripe-note" style="font-size:13px;margin-top:0.5rem;">Same page, no redirect — your order is already placed, just complete payment below.</p>
                    </div>
                    <div id="co-express-element" style="display:none;margin-top:0.6rem;"></div>
                    <div id="co-paypal-buttons" style="display:none;margin-bottom:1rem;"></div>
                    <p class="text-muted" id="co-paypal-privacy-hint" style="display:none;font-size:13px;margin-bottom:1rem;">Please agree to the privacy policy and terms first — then the PayPal buttons will appear here.</p>

                    <div id="co-error" style="display:none;color:#B91C1C;font-size:14px;margin-bottom:1rem;"></div>
                    <label class="privacy-check pick-row" style="margin-bottom:1.25rem;">
                        <input type="checkbox" id="co-privacy">
                        <span class="pick-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <span>I agree to the <a @spa href="{{ route('privacy') }}">privacy policy</a> and <a @spa href="{{ route('terms') }}">terms of service</a></span>
                    </label>
                    <button type="submit" id="co-submit" class="btn btn-dark btn-block">Place order · <span data-co-total>£{{ number_format($bag['subtotal'], 2) }}</span></button>
                    <a @spa href="{{ route('bag') }}" class="btn btn-ghost btn-block" style="margin-top:0.75rem;">Back to bag</a>
                </aside>
            </form>
        @endif
    </div>
</main>
@endsection

@section('script')
@if ($stripeOn)
<script src="https://js.stripe.com/v3/"></script>
@endif
@if ($paypalOn)
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClient }}&currency=GBP&intent=capture"></script>
@endif
<script>
    /* Checkout flow — var only, re-runnable under the SPA engine. */
    function egfCheckoutInit() {
        var form = document.getElementById('egf-checkout-form');
        if (!form || form._egfBound) return;
        form._egfBound = true;

        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        var subtotal = parseFloat(form.dataset.subtotal || '{{ $bag['subtotal'] ?? 0 }}') || 0;
        var freeOver = parseFloat('{{ $freeOver }}') || 0;
        var pointsValue = parseFloat('{{ $pointsValue }}') || 0;
        var pointsBalance = parseInt('{{ $pointsBalance }}', 10) || 0;
        var cutoffMsg = document.getElementById('co-cutoff-msg');
        var cutoffBase = cutoffMsg ? cutoffMsg.textContent : '';
        var pointsInput = document.getElementById('co-points');
        var errBox = document.getElementById('co-error');
        var submitBtn = document.getElementById('co-submit');
        var couponInput = document.getElementById('co-coupon');
        var couponHidden = document.getElementById('co-coupon-code');
        var couponMsg = document.getElementById('co-coupon-msg');
        var couponDiscount = 0;
        var stripe = null;
        var stripeElements = null;
        var payElement = null;
        var expressElement = null;
        /* Phase 2 of a Stripe-family payment: the order is placed (server-side
        amount locked) and the shopper completes payment in-page. */
        var stripePhase = null;

        function isStripeFamily(m) { return m === 'stripe' || m === 'google_pay' || m === 'apple_pay'; }
        function grandTotal() {
            var fee = (fulfillment() === 'pickup' || subtotal >= freeOver) ? 0 : speedFee();
            return Math.max(0, subtotal + fee - pointsDiscount() - couponDiscount);
        }
        /* Totals changed after the order was placed — drop back to phase 1 so
        the next submit re-prices (the locked intent amount must match). */
        function resetStripePhase() {
            if (!stripePhase) return;
            stripePhase = null;
            if (payElement) { try { payElement.unmount(); } catch (e) {} payElement = null; }
            if (expressElement) { try { expressElement.unmount(); } catch (e) {} expressElement = null; }
            stripeElements = null;
            var wrap = document.getElementById('co-card-wrap');
            if (wrap) wrap.style.display = 'none';
            var ex = document.getElementById('co-express-element');
            if (ex) { ex.style.display = 'none'; ex.innerHTML = ''; }
            if (submitBtn) { submitBtn.disabled = false; submitBtn.style.display = method() === 'paypal' ? 'none' : ''; submitBtn.innerHTML = 'Place order · <span data-co-total>' + money(grandTotal()) + '</span>'; submitBtn._orig = submitBtn.innerHTML; }
            paintTotals();
        }

        /* Address book: picking an entry fills the inputs; billing defaults to delivery. */
        var bookEl = document.getElementById('co-address-book');
        var book = {};
        try { book = bookEl ? JSON.parse(bookEl.textContent) : {}; } catch (e) { book = {}; }
        var deliverySel = document.getElementById('co-delivery-id');
        var billingSame = document.getElementById('co-billing-same');
        var billingBlock = document.getElementById('co-billing-block');
        var deliveryFields = document.getElementById('co-delivery-fields');
        var billingSection = document.getElementById('co-billing-section');
        var billingSel = document.getElementById('co-billing-id');
        var saveBox = document.getElementById('co-save-address');
        var saveLabel = document.getElementById('co-save-label');
        function fill(prefix, d) {
            if (!d) return;
            var map = { name: 'name', phone: 'phone', address: 'address', city: 'city', postcode: 'postcode' };
            Object.keys(map).forEach(function (k) {
                var el = document.getElementById(prefix + map[k]);
                if (el && d[k] !== undefined) el.value = d[k];
            });
        }
        function val(id) {
            var el = document.getElementById(id);
            return el ? el.value : '';
        }
        if (deliverySel) {
            deliverySel.addEventListener('change', function () {
                fill('co-', book[deliverySel.value]);
                if (billingSame && billingSame.checked) syncBillingFromDelivery();
            });
        }
        function syncBillingFromDelivery() {
            ['name', 'phone', 'address', 'city', 'postcode'].forEach(function (k) {
                var from = document.getElementById('co-' + k);
                var to = document.getElementById('co-bill-' + k);
                if (from && to) to.value = from.value;
            });
        }
        if (billingSel) {
            billingSel.addEventListener('change', function () { fill('co-bill-', book[billingSel.value]); });
        }
        if (billingSame && billingBlock) {
            billingSame.addEventListener('change', function () {
                if (billingSame.checked) syncBillingFromDelivery();
                syncFulfillmentUI();
            });
        }
        if (saveBox && saveLabel) {
            saveBox.addEventListener('change', function () {
                saveLabel.style.display = saveBox.checked ? '' : 'none';
            });
        }

        /* Live postcode eligibility — informational only, place() enforces. */
        var postcodeEl = document.getElementById('co-postcode');
        var postcodeMsg = document.getElementById('co-postcode-msg');
        var postcodeTimer = null;
        var postcodeOk = true;
        if (postcodeEl && postcodeMsg) {
            postcodeEl.addEventListener('input', function () {
                postcodeMsg.style.display = 'none';
                postcodeOk = true;
                clearTimeout(postcodeTimer);
                var code = postcodeEl.value.trim();
                if (code.length < 3) return;
                postcodeTimer = setTimeout(function () {
                    post('{{ route('checkout.postcode') }}', { postcode: code }).then(function (json) {
                        postcodeMsg.textContent = json.message || '';
                        postcodeMsg.style.color = '#166534';
                        postcodeMsg.style.display = '';
                        postcodeOk = true;
                    }).catch(function (err) {
                        postcodeMsg.textContent = (err && err.message) || 'We could not check that postcode.';
                        postcodeMsg.style.color = '#B91C1C';
                        postcodeMsg.style.display = '';
                        postcodeOk = false;
                    });
                }, 600);
            });
        }

        function money(n) { return '£' + Number(n).toFixed(2); }
        function toast(msg) {
            if (window.EGF && window.EGF.showToast) window.EGF.showToast(msg);
        }
        function showError(msg) {
            errBox.textContent = msg;
            errBox.style.display = '';
            errBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
            toast(msg);
        }
        /* UK phone (07 mobile / 01-02 landline / +44) and email, mirrored server-side. */
        function validUKPhone(v) {
            var d = String(v || '').replace(/[\s\-().]/g, '');
            return /^(?:\+44|0)(?:7\d{9}|[1235789]\d{9})$/.test(d);
        }
        function validEmail(v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(v || '').trim());
        }
        /* Lock the Place-order button while the order is being placed. */
        function lockSubmit() {
            if (!submitBtn) return;
            if (submitBtn._orig === undefined) submitBtn._orig = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Ordering…';
        }
        function unlockSubmit() {
            if (!submitBtn) return;
            if (submitBtn._orig !== undefined) submitBtn.innerHTML = submitBtn._orig;
            submitBtn.disabled = false;
        }
        function speed() {
            var checked = form.querySelector('input[name="delivery_speed"]:checked');
            return checked ? checked.value : 'standard';
        }
        function speedFee() {
            var checked = form.querySelector('input[name="delivery_speed"]:checked');
            return checked ? (parseFloat(checked.getAttribute('data-fee') || 0) || 0) : 0;
        }
        /* Plain-words confirmation of the chosen speed + what it costs. */
        function paintArriving() {
            var line = document.getElementById('co-arriving');
            if (!line) return;
            var checked = form.querySelector('input[name="delivery_speed"]:checked');
            var dayText = checked ? (checked.getAttribute('data-date-label') || '') : '';
            var fee = (fulfillment() === 'pickup' || subtotal >= freeOver) ? 0 : speedFee();
            line.textContent = (fulfillment() === 'pickup' ? 'Collection: ' : 'Arriving: ') + dayText + ' · ' + (fee === 0 ? 'Free' : money(fee));
        }
        /* Collection is always free — repaint the option sub-labels on fulfillment switch. */
        function paintSpeedSubs() {
            var pickup = fulfillment() === 'pickup';
            form.querySelectorAll('input[name="delivery_speed"]').forEach(function (radio) {
                var sub = document.getElementById('co-speed-' + radio.value + '-sub');
                if (!sub) return;
                var fee = parseFloat(radio.getAttribute('data-fee') || 0) || 0;
                var show = (pickup || subtotal >= freeOver) ? 'Free' : money(fee);
                sub.textContent = (radio.getAttribute('data-date-label') || '') + ' · ' + show;
            });
        }
        function pointsDiscount() {
            if (!pointsInput) return 0;
            var pts = Math.max(0, Math.min(parseInt(pointsInput.value, 10) || 0, pointsBalance));
            return Math.min(pts * pointsValue, subtotal);
        }
        function fulfillment() {
            var checked = form.querySelector('input[name="fulfillment"]:checked');
            return checked ? checked.value : 'delivery';
        }
        var lastFulfillment = null;
        function disableFields(container, disabled) {
            if (!container) return;
            container.querySelectorAll('input, select, textarea, button').forEach(function (el) { el.disabled = disabled; });
        }
        function syncFulfillmentUI() {
            var currentFulfillment = fulfillment();
            var pickup = currentFulfillment === 'pickup';
            var fulfillmentChanged = currentFulfillment !== lastFulfillment;
            lastFulfillment = currentFulfillment;
            if (deliveryFields) {
                deliveryFields.style.display = pickup ? 'none' : '';
                disableFields(deliveryFields, pickup);
            }
            if (billingSection) billingSection.style.display = pickup ? 'none' : '';
            if (billingSame && billingBlock) {
                billingBlock.style.display = pickup || billingSame.checked ? 'none' : '';
                disableFields(billingBlock, pickup || billingSame.checked);
            }
            var heading = document.getElementById('co-fulfillment-heading');
            var feeLabel = document.querySelector('[data-co-fulfillment-fee-label]');
            var cashNote = document.getElementById('co-cash-note');
            if (heading) heading.textContent = pickup ? 'When can you collect?' : 'When should it arrive?';
            if (feeLabel) feeLabel.textContent = pickup ? 'Collection' : 'Delivery';
            if (cashNote) cashNote.textContent = pickup ? 'Pay on collection' : 'Pay at door';
            var todayTitle = document.getElementById('co-speed-today-title');
            var standardTitle = document.getElementById('co-speed-standard-title');
            if (todayTitle) todayTitle.textContent = pickup ? 'Collect today' : 'Today delivery';
            if (standardTitle) standardTitle.textContent = pickup ? 'Collect next working day' : 'Standard shipping';
            paintSpeedSubs();
            var progress = document.getElementById('co-delivery-progress');
            if (progress) progress.style.display = pickup ? 'none' : '';
            if (pickup) {
                clearTimeout(postcodeTimer);
                postcodeOk = true;
                if (postcodeMsg) postcodeMsg.style.display = 'none';
            } else if (fulfillmentChanged && postcodeEl && postcodeEl.value.trim().length >= 3) {
                postcodeEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (cutoffMsg && !pickup) cutoffMsg.textContent = cutoffBase;
            if (cutoffMsg && pickup) cutoffMsg.textContent = 'Choose today or next working day for collection. Collection is free.';
            paintTotals();
        }
        function paintTotals() {
            var fee = (fulfillment() === 'pickup' || subtotal >= freeOver) ? 0 : speedFee();            var note = document.getElementById('co-pickup-note');
            if (note) note.style.display = fulfillment() === 'pickup' ? '' : 'none';
            var disc = pointsDiscount();
            var total = Math.max(0, subtotal + fee - disc - couponDiscount);
            document.querySelectorAll('[data-co-fee]').forEach(function (el) { el.textContent = fee === 0 ? 'Free' : money(fee); });
            document.querySelectorAll('[data-co-total]').forEach(function (el) { el.textContent = money(total); });
            var ptsRow = document.querySelector('[data-co-points]');
            if (ptsRow) {
                ptsRow.style.display = disc > 0 ? '' : 'none';
                ptsRow.querySelector('span:last-child').textContent = '−' + money(disc);
            }
            var cpnRow = document.querySelector('[data-co-coupon]');
            if (cpnRow) {
                cpnRow.style.display = couponDiscount > 0 ? '' : 'none';
                cpnRow.querySelector('[data-co-coupon-amount]').textContent = '−' + money(couponDiscount);
                var codeEl = cpnRow.querySelector('[data-co-coupon-code]');
                if (codeEl && couponHidden) codeEl.textContent = couponHidden.value;
            }
            paintArriving();
        }
        function couponMessage(text, ok) {
            if (!couponMsg) return;
            couponMsg.textContent = text;
            couponMsg.style.display = text ? '' : 'none';
            couponMsg.style.color = ok ? '#1A2E22' : '#B91C1C';
            if (text) toast(text);
        }
        function clearCoupon() {
            resetStripePhase();
            couponDiscount = 0;
            if (couponHidden) couponHidden.value = '';
            if (couponInput) couponInput.value = '';
            couponMessage('', true);
            paintTotals();
        }
        var applyBtn = document.getElementById('co-coupon-apply');
        if (applyBtn && couponInput) {
            applyBtn.addEventListener('click', function () {
                var code = couponInput.value.trim();
                if (!code) { couponMessage('Enter a coupon code first.', false); return; }
                applyBtn.disabled = true;
                post('{{ route('checkout.coupon') }}', { code: code }).then(function (json) {
                    applyBtn.disabled = false;
                    resetStripePhase();
                    couponDiscount = parseFloat(json.discount) || 0;
                    if (couponHidden) couponHidden.value = json.code || code.toUpperCase();
                    couponMessage(json.message || 'Coupon applied.', true);
                    paintTotals();
                }).catch(function (err) {
                    applyBtn.disabled = false;
                    couponMessage((err && err.message) || 'That coupon did not work.', false);
                });
            });
        }
        var removeBtn = document.querySelector('[data-co-coupon-remove]');
        if (removeBtn) removeBtn.addEventListener('click', clearCoupon);
        form.querySelectorAll('input[name="delivery_speed"]').forEach(function (radio) {
            radio.addEventListener('change', function () { resetStripePhase(); paintTotals(); });
        });
        if (pointsInput) pointsInput.addEventListener('input', function () { resetStripePhase(); paintTotals(); });
        /* Contact/address changed after the order was placed — drop back to
        phase 1 so the next submit re-places with fresh details. */
        ['co-name', 'co-phone', 'co-email', 'co-address', 'co-city', 'co-postcode', 'co-notes', 'co-bill-name', 'co-bill-phone', 'co-bill-address', 'co-bill-city', 'co-bill-postcode'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', resetStripePhase);
        });
        form.querySelectorAll('input[name="fulfillment"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                resetStripePhase();
                syncFulfillmentUI();
                paintTotals();
            });
        });
        syncFulfillmentUI();
        paintTotals();

        function method() {
            var checked = form.querySelector('input[name="payment_method"]:checked');
            return checked ? checked.value : 'cod';
        }
        form.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
            radio.addEventListener('change', function () { resetStripePhase(); syncMethod(); });
        });
        var privacyBox = document.getElementById('co-privacy');
        if (privacyBox) privacyBox.addEventListener('change', function () { errBox.style.display = 'none'; syncMethod(); });
        function privacyOk() {
            var privacy = document.getElementById('co-privacy');
            return !privacy || privacy.checked;
        }
        function syncMethod() {
            if (form.dataset.hasUnavailable === '1') {
                submitBtn.disabled = true;
                submitBtn.style.display = 'none';
                document.getElementById('co-paypal-buttons').style.display = 'none';
                document.getElementById('co-paypal-privacy-hint').style.display = 'none';
                return;
            }
            var m = method();
            /* Phase-2 panels are driven by startStripePhase, not the tiles. */
            if (!stripePhase) {
                document.getElementById('co-card-wrap').style.display = 'none';
                /* PayPal buttons stay locked until privacy is agreed. */
                var allowPaypal = m === 'paypal' && privacyOk();
                document.getElementById('co-paypal-buttons').style.display = allowPaypal ? '' : 'none';
                document.getElementById('co-paypal-privacy-hint').style.display = (m === 'paypal' && !privacyOk()) ? '' : 'none';
                submitBtn.style.display = m === 'paypal' ? 'none' : '';
            }
            if (m === 'paypal' && privacyOk()) renderPaypalButtons();
        }
        /* Phase 2: mount Stripe's tabbed Payment Element (Card, Revolut Pay…)
        and, for resumed wallet orders, the one-tap wallet buttons — all on this page. */
        function startStripePhase(json) {
            if (!window.Stripe) { showError('Payment form could not load — please reload and try again.'); unlockSubmit(); return; }
            var m = json.payment_method || method();
            stripe = window.Stripe(json.publishable || '');
            if (!json.publishable) { showError('Card payments are not set up yet — please choose cash.'); unlockSubmit(); return; }
            stripeElements = stripe.elements({ clientSecret: json.client_secret });
            stripePhase = { orderNumber: json.order_number, method: m, amount: grandTotal() };
            payElement = stripeElements.create('payment', {
                layout: 'tabs',
                wallets: { applePay: 'auto', googlePay: 'auto' }
            });
            payElement.mount('#co-card-element');
            document.getElementById('co-card-wrap').style.display = '';
            if (m === 'google_pay' || m === 'apple_pay') {
                try {
                    expressElement = stripeElements.create('expressCheckout');
                    expressElement.mount('#co-express-element');
                    document.getElementById('co-express-element').style.display = '';
                    expressElement.on('confirm', function () {
                        lockSubmit();
                        var phase = stripePhase;
                        stripe.confirmPayment({ elements: stripeElements, redirect: 'if_required' }).then(function (result) {
                            if (result.error) {
                                showError(result.error.message || 'Wallet payment failed — please try again.');
                                unlockSubmit();
                                return;
                            }
                            confirmAndGo(phase.orderNumber, phase.method).catch(function (err) {
                                showError((err && err.message) || 'Payment confirmation failed.');
                                unlockSubmit();
                            });
                        });
                    });
                    expressElement.on('cancel', function () {
                        submitBtn.disabled = false;
                        showError('Wallet was closed — no money moved. Tap it again or pay by card below.');
                    });
                } catch (e) { expressElement = null; }
            }
            submitBtn.disabled = false;
            submitBtn.style.display = '';
            submitBtn.innerHTML = 'Pay ' + money(stripePhase.amount) + ' securely';
            submitBtn._orig = submitBtn.innerHTML;
            document.getElementById('co-card-wrap').scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        /* Phase-2 submit: confirm the card/wallet details against the placed order. */
        function confirmStripePhase() {
            if (!stripe || !stripeElements || !stripePhase) { showError('Payment form is not ready — please try again.'); return; }
            lockSubmit();
            var phase = stripePhase;
            stripe.confirmPayment({ elements: stripeElements, redirect: 'if_required' }).then(function (result) {
                if (result.error) {
                    showError(result.error.message || 'Your card was declined.');
                    unlockSubmit();
                    return;
                }
                confirmAndGo(phase.orderNumber, phase.method).catch(function (err) {
                    showError((err && err.message) || 'Payment confirmation failed.');
                    unlockSubmit();
                });
            });
        }

        function post(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (json) {
                    if (!res.ok) {
                        if (res.status === 419) throw { message: 'Your session expired — please reload the page and try again.' };
                        throw (json && json.message) ? json : { message: 'Something went wrong — please try again.' };
                    }
                    return json;
                });
            });
        }
        function payload() {
            var isPickup = fulfillment() === 'pickup';
            if (billingSame && billingSame.checked) syncBillingFromDelivery();
            var sub = form.querySelector('input[name="substitution"]:checked');
            return {
                name: document.getElementById('co-name').value,
                phone: document.getElementById('co-phone').value,
                email: document.getElementById('co-email').value,
                address: isPickup ? '' : document.getElementById('co-address').value,
                city: isPickup ? '' : document.getElementById('co-city').value,
                postcode: isPickup ? '' : document.getElementById('co-postcode').value,
                billing_name: isPickup ? '' : (val('co-bill-name') || document.getElementById('co-name').value),
                billing_phone: isPickup ? '' : (val('co-bill-phone') || document.getElementById('co-phone').value),
                billing_address: isPickup ? '' : (val('co-bill-address') || document.getElementById('co-address').value),
                billing_city: isPickup ? '' : (val('co-bill-city') || document.getElementById('co-city').value),
                billing_postcode: isPickup ? '' : (val('co-bill-postcode') || document.getElementById('co-postcode').value),
                save_address: !isPickup && saveBox && saveBox.checked ? 1 : 0,
                save_label: saveLabel ? saveLabel.value : '',
                substitution: sub ? sub.value : 'substitute',
                fulfillment: fulfillment(),
                notes: document.getElementById('co-notes').value,
                delivery_speed: speed(),
                payment_method: method(),
                points_redeem: pointsInput ? (parseInt(pointsInput.value, 10) || 0) : 0,
                coupon_code: couponHidden ? couponHidden.value : '',
                privacy: (document.getElementById('co-privacy') || {}).checked ? 1 : 0
            };
        }
        function confirmAndGo(orderNumber, pm) {
            return post('{{ route('checkout.payment-confirm') }}', { order_number: orderNumber, payment_method: pm })
                .then(function (done) { window.location.href = done.redirect; });
        }

        var paypalRendered = false;
        var lastPaypalOrder = null;
        function renderPaypalButtons() {
            if (!window.paypal || paypalRendered) return;
            paypalRendered = true;
            window.paypal.Buttons({
                createOrder: function () {
                    if (!privacyOk()) {
                        showError('Please agree to the privacy policy and terms first.');
                        return Promise.reject(new Error('privacy'));
                    }
                    lockSubmit();
                    return post('{{ route('checkout.place') }}', payload()).then(function (json) {
                        lastPaypalOrder = json.order_number;
                        return json.paypal_order_id;
                    }).catch(function (err) {
                        unlockSubmit();
                        showError((err && err.message) || 'PayPal could not be started.');
                        throw err;
                    });
                },
                onApprove: function () {
                    return confirmAndGo(lastPaypalOrder, 'paypal').catch(function (err) {
                        showError((err && err.message) || 'Payment confirmation failed.');
                    });
                },
                onCancel: function () {
                    unlockSubmit();
                    // Keep the order so the shopper can simply retry (matches
                    // the order-detail pay-resume behaviour).
                    lastPaypalOrder = null;
                    showError('PayPal was closed — no money moved. Your order is kept, so you can try again any time.');
                },
                onError: function () {
                    unlockSubmit();
                    showError('PayPal ran into a problem — please try again.');
                }
            }).render('#co-paypal-buttons');
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            /* Phase 2: the order is placed — confirm the card/wallet in-page. */
            if (stripePhase) { confirmStripePhase(); return; }
            if (typeof postcodeOk !== 'undefined' && !postcodeOk) {
                showError(postcodeMsg && postcodeMsg.textContent ? postcodeMsg.textContent : 'Sorry — that postcode is outside our delivery area.');
                return;
            }
            var privacy = document.getElementById('co-privacy');
            if (privacy && !privacy.checked) {
                showError('Please agree to the privacy policy and terms first.');
                return;
            }
            var phoneEl = document.getElementById('co-phone');
            if (phoneEl && !validUKPhone(phoneEl.value)) {
                showError('Enter a valid UK phone number, e.g. 07123 456789.');
                phoneEl.focus();
                return;
            }
            var emailEl = document.getElementById('co-email');
            if (emailEl && (emailEl.hasAttribute('required') || emailEl.value.trim() !== '') && !validEmail(emailEl.value)) {
                showError('Enter a valid email address, e.g. you@example.com.');
                emailEl.focus();
                return;
            }
            lockSubmit();
            var m = method();
            if (m === 'paypal') {
                document.getElementById('co-paypal-buttons').style.display = '';
                document.getElementById('co-card-wrap').style.display = 'none';
                submitBtn.style.display = 'none';
                renderPaypalButtons();
                unlockSubmit();
                return;
            }
            post('{{ route('checkout.place') }}', payload()).then(function (json) {
                if (json.redirect) { window.location.href = json.redirect; return; }
                if (json.stripe) { startStripePhase(json); return; }
                showError('Unexpected response — please try again.');
                unlockSubmit();
            }).catch(function (err) {
                var msg = (err && err.message) || 'Something went wrong — please try again.';
                if (err && err.errors) {
                    var first = Object.values(err.errors)[0];
                    msg = Array.isArray(first) ? first[0] : first;
                }
                showError(msg);
                unlockSubmit();
            });
        });

        syncMethod();
    }
    document.addEventListener('spa:loaded', egfCheckoutInit);
    egfCheckoutInit();
</script>
@endsection
