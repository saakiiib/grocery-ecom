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

            <div class="cart-layout" style="align-items:start;">
                <form id="egf-checkout-form" class="auth-card" style="margin:0;">
                    @csrf
                    <h2 style="font-size:1.25rem;margin-bottom:1.25rem;">Where is it going?</h2>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-name">Full name</label>
                            <input type="text" id="co-name" name="name" required maxlength="100" value="{{ old('name', $shopper->name ?? '') }}" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label for="co-phone">Phone</label>
                            <input type="tel" id="co-phone" name="phone" required maxlength="30" value="{{ old('phone', $shopper->phone ?? '') }}" autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="co-address">Street address</label>
                        <input type="text" id="co-address" name="address" required maxlength="500" value="{{ old('address', $shopper->address ?? '') }}" placeholder="Flat, street" autocomplete="street-address">
                    </div>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="co-city">Town / City</label>
                            <input type="text" id="co-city" name="city" required maxlength="100" value="{{ old('city', $shopper->city ?? '') }}" autocomplete="address-level2">
                        </div>
                        <div class="form-group">
                            <label for="co-postcode">Postcode</label>
                            <input type="text" id="co-postcode" name="postcode" required maxlength="20" value="{{ old('postcode', $shopper->postcode ?? '') }}" placeholder="e.g. SW1A 1AA" autocomplete="postal-code">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="co-notes">Delivery notes <span class="text-muted">(optional)</span></label>
                        <input type="text" id="co-notes" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Gate code, leave with neighbour…">
                    </div>

                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;">When should it arrive?</h2>
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

                    <h2 style="font-size:1.25rem;margin:1.5rem 0 1rem;">How would you like to pay?</h2>
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
                    <div class="form-group pay-grid" id="co-methods">
                        <label class="pay-card">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <span class="pay-card-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                            </span>
                            <span class="pay-card-text"><strong>Cash on delivery</strong><span class="text-muted">Pay at your door</span></span>
                            <span class="pay-card-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <span class="pay-card-flag">Most flexible</span>
                        </label>
                        @if ($stripeOn)
                            <label class="pay-card">
                                <input type="radio" name="payment_method" value="stripe">
                                <span class="pay-card-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                </span>
                                <span class="pay-card-text"><strong>Card now</strong><span class="text-muted">Visa · Mastercard · Amex</span></span>
                                <span class="pay-card-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                                <span class="pay-card-flag">Fast &amp; secure</span>
                            </label>
                        @endif
                        @if ($paypalOn)
                            <label class="pay-card pay-card-paypal">
                                <input type="radio" name="payment_method" value="paypal">
                                <span class="pay-card-icon paypal-mark"><em>Pay</em><strong>Pal</strong></span>
                                <span class="pay-card-text"><strong>PayPal</strong><span class="text-muted">Buyer protection included</span></span>
                                <span class="pay-card-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                            </label>
                        @endif
                    </div>
                    @if (! $stripeOn || ! $paypalOn)
                        <p class="text-muted" style="font-size:13px;margin:-0.25rem 0 1rem;">
                            @if (! $stripeOn && ! $paypalOn)
                                Online payments are being switched on — cash on delivery works today.
                            @elseif (! $stripeOn)
                                Card payments are being switched on — PayPal or cash on delivery work today.
                            @else
                                PayPal is being switched on — card or cash on delivery work today.
                            @endif
                        </p>
                    @endif
                    <div class="form-group" id="co-card-wrap" style="display:none;">
                        <label>Card details</label>
                        <div id="co-card-element" style="border:1px solid var(--border,#e5e2da);border-radius:10px;padding:0.8rem;"></div>
                    </div>
                    <div id="co-paypal-buttons" style="display:none;margin-bottom:1rem;"></div>

                    <div id="co-error" style="display:none;color:#B91C1C;font-size:14px;margin-bottom:1rem;"></div>
                    <button type="submit" id="co-submit" class="btn btn-dark btn-block">Place order · <span data-co-total>£{{ number_format($bag['subtotal'], 2) }}</span></button>
                    <p class="text-muted" style="font-size:13px;margin-top:0.75rem;">By placing this order you agree to our <a @spa href="{{ route('terms') }}">terms</a>. Prices are confirmed from our shelves before anything is charged.</p>
                </form>

                <aside class="cart-summary">
                    <h3>Your bag ({{ $bag['count'] }})</h3>
                    @foreach ($bag['lines'] as $item)
                        <div class="summary-row" style="align-items:start;">
                            <span>{{ $item['qty'] }} × {{ $item['name'] }}<br><span class="text-muted">{{ $item['pack'] }}</span></span>
                            <span>£{{ number_format($item['line_total'], 2) }}</span>
                        </div>
                    @endforeach
                    <div class="summary-row"><span>Subtotal</span><span data-co-subtotal>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                    <div class="summary-row" data-co-points style="display:none;"><span>Loyalty points</span><span>−£0.00</span></div>
                    <div class="summary-row"><span>Delivery</span><span data-co-fee>Calculated…</span></div>
                    <div class="summary-row total"><span>Total</span><span data-co-total>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                    <a @spa href="{{ route('bag') }}" class="btn btn-ghost btn-block" style="margin-top:1rem;">Back to bag</a>
                </aside>
            </div>
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
        var stripe = null;
        var card = null;

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
            document.querySelectorAll('[data-co-fee]').forEach(function (el) { el.textContent = fee === 0 ? 'Free' : money(fee); });
            document.querySelectorAll('[data-co-total]').forEach(function (el) { el.textContent = money(subtotal + fee - disc); });
            var ptsRow = document.querySelector('[data-co-points]');
            if (ptsRow) {
                ptsRow.style.display = disc > 0 ? '' : 'none';
                ptsRow.querySelector('span:last-child').textContent = '−' + money(disc);
            }
        }
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
            return {
                name: document.getElementById('co-name').value,
                phone: document.getElementById('co-phone').value,
                address: document.getElementById('co-address').value,
                city: document.getElementById('co-city').value,
                postcode: document.getElementById('co-postcode').value,
                notes: document.getElementById('co-notes').value,
                delivery_date: document.getElementById('co-date').value,
                delivery_slot_id: slotSel.value,
                payment_method: method(),
                points_redeem: pointsInput ? (parseInt(pointsInput.value, 10) || 0) : 0
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
