@extends('frontend.layout')
@section('title', 'Checkout')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Checkout</h1>
            <p>Choose when your groceries arrive, then pay your way.</p>
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

            <form id="egf-checkout-form" class="cart-layout" style="margin:0;align-items:start;">
                @csrf
                <div class="auth-card" style="margin:0;">
                    <h2 style="font-size:1.25rem;margin-bottom:1.25rem;"><span class="co-step">1</span>Where is it going?</h2>
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
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-name">Full name</label>
                            <input type="text" id="co-name" name="name" required maxlength="100" value="{{ old('name', $defaultDelivery->name ?? $shopper->name ?? '') }}" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label for="co-phone">Phone</label>
                            <input type="tel" id="co-phone" name="phone" required maxlength="30" value="{{ old('phone', $defaultDelivery->phone ?? $shopper->phone ?? '') }}" autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="co-email">Email @auth<span class="text-muted">(for your receipt)</span>@else<span class="text-danger">*</span>@endauth</label>
                        <input type="email" id="co-email" name="email" maxlength="255" @guest required @endguest value="{{ old('email', $shopper->email ?? '') }}" placeholder="you@example.com" autocomplete="email">
                    </div>
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
                        <div class="form-group" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                            <label style="font-weight:400;"><input type="checkbox" id="co-save-address" value="1"> Save this delivery address to my book</label>
                            <input type="text" id="co-save-label" maxlength="50" placeholder="Label, e.g. Work" style="max-width:200px;display:none;">
                        </div>
                    @endauth
                    <div class="form-group">
                        <label for="co-notes">Delivery notes <span class="text-muted">(optional)</span></label>
                        <input type="text" id="co-notes" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Gate code, leave with neighbour…">
                    </div>
                    <div class="form-group">
                        <label>If something is unavailable</label>
                        <div style="display:grid;gap:.4rem;font-size:14px;">
                            <label style="font-weight:400;"><input type="radio" name="substitution" value="substitute" checked> Substitute it with something similar</label>
                            <label style="font-weight:400;"><input type="radio" name="substitution" value="refund"> Remove it and refund me</label>
                            <label style="font-weight:400;"><input type="radio" name="substitution" value="call"> Call me first</label>
                        </div>
                    </div>

                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;"><span class="co-step">2</span>Who is paying?</h2>
                    <div class="form-group">
                        <label style="font-weight:400;"><input type="checkbox" id="co-billing-same" checked> Billing address is the same as delivery</label>
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

                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;"><span class="co-step">3</span>When should it arrive?</h2>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-date">Delivery day</label>
                            <select id="co-date" name="delivery_date" required>
                                @foreach ($dates as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="co-slot">Time window</label>
                            <select id="co-slot" name="delivery_slot_id" required>
                                @foreach ($slots as $slot)
                                    <option value="{{ $slot->id }}" data-fee="{{ $slot->fee }}">
                                        {{ $slot->label() }} · {{ $slot->fee > 0 ? '£'.number_format($slot->fee, 2) : 'Free' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-muted" style="font-size:13px;">Order before 8pm for next-day slots. Free delivery over £{{ number_format($freeOver, 2) }} · minimum order £{{ number_format($minOrder, 2) }}.</p>
                </div>

                <aside class="cart-summary">
                    <h3>Your bag ({{ $bag['count'] }})</h3>
                    @foreach ($bag['lines'] as $item)
                        <div class="summary-row co-line" style="align-items:center;">
                            <img class="co-thumb" src="{{ $item['image'] }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='{{ url('placeholder.webp') }}'">
                            <span style="flex:1;min-width:0;">{{ $item['qty'] }} × {{ $item['name'] }}@if ($item['promo_label']) <span class="promo-tag">{{ $item['promo_label'] }}</span>@endif<br><span class="text-muted">{{ $item['pack'] }}</span></span>
                            <span>£{{ number_format($item['line_total'], 2) }}</span>
                        </div>
                    @endforeach
                    <div class="summary-row"><span>Subtotal</span><span data-co-subtotal>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                    @if (($bag['bogo_discount'] ?? 0) > 0)<div class="summary-row"><span>BOGO savings</span><span>−£{{ number_format($bag['bogo_discount'], 2) }}</span></div>@endif
                    @if (($bag['bundle_discount'] ?? 0) > 0)<div class="summary-row"><span>Bundle savings</span><span>−£{{ number_format($bag['bundle_discount'], 2) }}</span></div>@endif
                    <div class="summary-row" data-co-coupon style="display:none;"><span>Coupon <strong data-co-coupon-code></strong> <button type="button" data-co-coupon-remove aria-label="Remove coupon" style="border:0;background:none;color:#B91C1C;cursor:pointer;font-size:14px;">×</button></span><span data-co-coupon-amount>−£0.00</span></div>
                    <div class="summary-row" data-co-points style="display:none;"><span>Loyalty points</span><span>−£0.00</span></div>
                    <div class="summary-row"><span>Delivery</span><span data-co-fee>Calculated…</span></div>
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
                            <strong>Cash</strong>
                            <span class="text-muted">Pay at door</span>
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
                        <label>Card details</label>
                        <div id="co-card-element" style="border:1px solid var(--border,#e5e2da);border-radius:10px;padding:0.8rem;"></div>
                    </div>
                    <div id="co-paypal-buttons" style="display:none;margin-bottom:1rem;"></div>

                    <div id="co-error" style="display:none;color:#B91C1C;font-size:14px;margin-bottom:1rem;"></div>
                    <label class="privacy-check">
                        <input type="checkbox" id="co-privacy">
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
        var slotSel = document.getElementById('co-slot');
        var pointsInput = document.getElementById('co-points');
        var errBox = document.getElementById('co-error');
        var submitBtn = document.getElementById('co-submit');
        var couponInput = document.getElementById('co-coupon');
        var couponHidden = document.getElementById('co-coupon-code');
        var couponMsg = document.getElementById('co-coupon-msg');
        var couponDiscount = 0;
        var stripe = null;
        var card = null;

        /* Address book: picking an entry fills the inputs; billing defaults to delivery. */
        var bookEl = document.getElementById('co-address-book');
        var book = {};
        try { book = bookEl ? JSON.parse(bookEl.textContent) : {}; } catch (e) { book = {}; }
        var deliverySel = document.getElementById('co-delivery-id');
        var billingSame = document.getElementById('co-billing-same');
        var billingBlock = document.getElementById('co-billing-block');
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
                billingBlock.style.display = billingSame.checked ? 'none' : '';
                if (billingSame.checked) syncBillingFromDelivery();
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
        function showError(msg) {
            errBox.textContent = msg;
            errBox.style.display = '';
            errBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        function slotFee() {
            var opt = slotSel.options[slotSel.selectedIndex];
            return parseFloat(opt.dataset.fee || 0) || 0;
        }
        function pointsDiscount() {
            if (!pointsInput) return 0;
            var pts = Math.max(0, Math.min(parseInt(pointsInput.value, 10) || 0, pointsBalance));
            return Math.min(pts * pointsValue, subtotal);
        }
        function paintTotals() {
            var fee = subtotal >= freeOver ? 0 : slotFee();
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
        }
        function couponMessage(text, ok) {
            if (!couponMsg) return;
            couponMsg.textContent = text;
            couponMsg.style.display = text ? '' : 'none';
            couponMsg.style.color = ok ? '#1A2E22' : '#B91C1C';
        }
        function clearCoupon() {
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
        slotSel.addEventListener('change', paintTotals);
        if (pointsInput) pointsInput.addEventListener('input', paintTotals);
        paintTotals();

        function method() {
            var checked = form.querySelector('input[name="payment_method"]:checked');
            return checked ? checked.value : 'cod';
        }
        form.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
            radio.addEventListener('change', syncMethod);
        });
        function syncMethod() {
            var m = method();
            document.getElementById('co-card-wrap').style.display = m === 'stripe' ? '' : 'none';
            document.getElementById('co-paypal-buttons').style.display = m === 'paypal' ? '' : 'none';
            submitBtn.style.display = m === 'paypal' ? 'none' : '';
            if (m === 'stripe' && window.Stripe && !stripe) {
                stripe = window.Stripe('{{ \App\Http\Controllers\CheckoutController::stripePublishable() }}');
                var elements = stripe.elements();
                card = elements.create('card');
                card.mount('#co-card-element');
            }
            if (m === 'paypal') renderPaypalButtons();
        }

        function post(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok) throw json;
                    return json;
                });
            });
        }
        function payload() {
            if (billingSame && billingSame.checked) syncBillingFromDelivery();
            var sub = form.querySelector('input[name="substitution"]:checked');
            return {
                name: document.getElementById('co-name').value,
                phone: document.getElementById('co-phone').value,
                email: document.getElementById('co-email').value,
                address: document.getElementById('co-address').value,
                city: document.getElementById('co-city').value,
                postcode: document.getElementById('co-postcode').value,
                billing_name: val('co-bill-name') || document.getElementById('co-name').value,
                billing_phone: val('co-bill-phone') || document.getElementById('co-phone').value,
                billing_address: val('co-bill-address') || document.getElementById('co-address').value,
                billing_city: val('co-bill-city') || document.getElementById('co-city').value,
                billing_postcode: val('co-bill-postcode') || document.getElementById('co-postcode').value,
                save_address: saveBox && saveBox.checked ? 1 : 0,
                save_label: saveLabel ? saveLabel.value : '',
                substitution: sub ? sub.value : 'substitute',
                notes: document.getElementById('co-notes').value,
                delivery_date: document.getElementById('co-date').value,
                delivery_slot_id: slotSel.value,
                payment_method: method(),
                points_redeem: pointsInput ? (parseInt(pointsInput.value, 10) || 0) : 0,
                coupon_code: couponHidden ? couponHidden.value : ''
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
                    submitBtn.disabled = true;
                    return post('{{ route('checkout.place') }}', payload()).then(function (json) {
                        lastPaypalOrder = json.order_number;
                        return json.paypal_order_id;
                    }).catch(function (err) {
                        submitBtn.disabled = false;
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
                    submitBtn.disabled = false;
                    if (lastPaypalOrder) {
                        post('{{ route('checkout.cancel') }}', { order_number: lastPaypalOrder }).catch(function () {});
                        lastPaypalOrder = null;
                    }
                    showError('PayPal was closed — no money moved and no order was kept. Start again whenever you are ready.');
                },
                onError: function () {
                    submitBtn.disabled = false;
                    showError('PayPal ran into a problem — please try again.');
                }
            }).render('#co-paypal-buttons');
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            if (typeof postcodeOk !== 'undefined' && !postcodeOk) {
                showError(postcodeMsg && postcodeMsg.textContent ? postcodeMsg.textContent : 'Sorry — we don\'t deliver to that postcode yet.');
                return;
            }
            var privacy = document.getElementById('co-privacy');
            if (privacy && !privacy.checked) {
                showError('Please agree to the privacy policy and terms first.');
                return;
            }
            submitBtn.disabled = true;
            var m = method();
            post('{{ route('checkout.place') }}', payload()).then(function (json) {
                if (json.redirect) { window.location.href = json.redirect; return; }
                if (json.stripe) {
                    if (!stripe || !card) { showError('Card form is not ready — please try again.'); submitBtn.disabled = false; return; }
                    stripe.confirmCardPayment(json.client_secret, { payment_method: { card: card } }).then(function (result) {
                        if (result.error) {
                            showError(result.error.message || 'Your card was declined.');
                            submitBtn.disabled = false;
                            return;
                        }
                        confirmAndGo(json.order_number, 'stripe').catch(function (err) {
                            showError((err && err.message) || 'Payment confirmation failed.');
                            submitBtn.disabled = false;
                        });
                    });
                    return;
                }
                showError('Unexpected response — please try again.');
                submitBtn.disabled = false;
            }).catch(function (err) {
                var msg = (err && err.message) || 'Something went wrong — please try again.';
                if (err && err.errors) {
                    var first = Object.values(err.errors)[0];
                    msg = Array.isArray(first) ? first[0] : first;
                }
                showError(msg);
                submitBtn.disabled = false;
            });
        });

        syncMethod();
    }
    document.addEventListener('spa:loaded', egfCheckoutInit);
    egfCheckoutInit();
</script>
@endsection
