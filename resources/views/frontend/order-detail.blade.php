@extends('frontend.layout')
@section('title', 'Order ' . $order->number)

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>{{ $order->number }}</h1>
            <p>Placed {{ $order->created_at->format('l j F, H:i') }} · {{ $order->paymentLabel() }} · {{ ucfirst($order->payment_status) }}</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;max-width:760px;">
        @if (session('status'))
            <div class="auth-card" style="border-color:#1A2E22;margin-bottom:1.5rem;">{{ session('status') }}</div>
        @endif

        @if (! $order->isPaid() && $order->payment_method !== 'cod' && in_array($order->status_slug, ['new', 'confirmed'], true))
            <div class="auth-card" style="margin-bottom:1.5rem;border-color:#B45309;">
                <h2 style="font-size:1.25rem;margin-bottom:0.5rem;">Payment still pending</h2>
                <p class="text-muted" style="margin-bottom:1rem;">Your {{ $order->paymentLabel() }} payment did not finish — complete it below and we will start packing.</p>
                <div id="od-error" style="display:none;color:#B91C1C;font-size:14px;margin-bottom:1rem;"></div>
                @if ($order->payment_method === 'stripe' && $payPublishable)
                    <div id="od-card-element" style="border:1px solid var(--border,#e5e2da);border-radius:10px;padding:0.8rem;margin-bottom:1rem;"></div>
                    <button type="button" id="od-pay-btn" class="btn btn-dark btn-block">Pay £{{ number_format($order->total, 2) }} now</button>
                @elseif ($order->payment_method === 'paypal' && $payPaypalClient)
                    <div id="od-paypal-buttons"></div>
                @else
                    <p class="text-muted">{{ $order->paymentLabel() }} is unavailable right now — please contact us to arrange payment.</p>
                @endif
            </div>
        @endif

        <div class="auth-card" style="margin-bottom:1.5rem;">
            @php $st = $order->status; @endphp
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                <span class="status-pill" @if ($st) style="background:{{ $st->color }}22;color:{{ $st->color }};border:1px solid {{ $st->color }}55;" @endif>{{ $st?->name ?? ucfirst($order->status_slug) }}</span>
                <form method="POST" action="{{ route('account.reorder', $order->number) }}">
                    @csrf
                    <button type="submit" class="btn btn-dark btn-sm">Buy everything again</button>
                </form>
            </div>
            @foreach ($order->items as $item)
                <div class="summary-row" style="align-items:start;">
                    <span>{{ $item->qty }} × {{ $item->product_name }}<br><span class="text-muted">{{ $item->pack_label }}</span></span>
                    <span>£{{ number_format($item->line_total, 2) }}</span>
                </div>
            @endforeach
            <div class="summary-row"><span>Subtotal</span><span>£{{ number_format($order->subtotal, 2) }}</span></div>
            <div class="summary-row"><span>Delivery ({{ $order->delivery_date->format('D j M') }} · {{ $order->delivery_slot_label }})</span><span>{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</span></div>
            <div class="summary-row total"><span>Total</span><span>£{{ number_format($order->total, 2) }}</span></div>
            <p class="text-muted">Delivering to {{ $order->address }}, {{ $order->city }} {{ $order->postcode }} · {{ $order->phone }}</p>
        </div>

        <div class="auth-card">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">Order journey</h2>
            @foreach ($order->histories as $h)
                @php $to = $h->toStatus(); @endphp
                <div class="summary-row" style="align-items:start;">
                    <span>
                        <span class="status-pill" @if ($to) style="background:{{ $to->color }}22;color:{{ $to->color }};border:1px solid {{ $to->color }}55;" @endif>{{ $to?->name ?? ucfirst(str_replace('_', ' ', $h->to_slug)) }}</span>
                        @if ($h->note)<br><span class="text-muted">{{ $h->note }}</span>@endif
                    </span>
                    <span class="text-muted">{{ $h->created_at->format('j M, H:i') }}</span>
                </div>
            @endforeach
        </div>

        <a @spa href="{{ route('account') }}" class="btn btn-ghost" style="margin-top:1.5rem;">← All orders</a>
        @if (in_array($order->status_slug, ['new', 'confirmed'], true) && (! $order->isPaid() || $order->payment_method === 'cod'))
            <form method="POST" action="{{ route('account.cancel', $order->number) }}" style="margin-top:1rem;" onsubmit="return confirm('Cancel {{ $order->number }}?');">
                @csrf
                <button type="submit" class="btn btn-ghost">Cancel this order</button>
            </form>
        @elseif ($order->isPaid() && in_array($order->status_slug, ['new', 'confirmed'], true))
            <p class="text-muted" style="margin-top:1rem;font-size:14px;">Need to change a paid order? <a @spa href="{{ route('contact') }}">Contact us</a> and we will refund if it has not been packed.</p>
        @endif
    </div>
</main>
@endsection

@section('script')
@if (! $order->isPaid() && $order->payment_method === 'stripe' && $payPublishable)
<script src="https://js.stripe.com/v3/"></script>
@endif
@if (! $order->isPaid() && $order->payment_method === 'paypal' && $payPaypalClient)
<script src="https://www.paypal.com/sdk/js?client-id={{ $payPaypalClient }}&currency=GBP&intent=capture"></script>
@endif
<script>
    /* Pay-now resume for an unpaid order — var only, SPA re-runnable. */
    function egfOrderPayInit() {
        var btn = document.getElementById('od-pay-btn');
        var ppWrap = document.getElementById('od-paypal-buttons');
        var errBox = document.getElementById('od-error');
        if (errBox && errBox._egfBound) return;
        if (errBox) errBox._egfBound = true;
        if (!btn && !ppWrap) return;

        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        var method = '{{ $order->payment_method }}';
        var stripe = null;
        var card = null;

        function showError(msg) {
            errBox.textContent = msg;
            errBox.style.display = '';
        }
        function post(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload || {})
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok) throw json;
                    return json;
                });
            });
        }
        function confirmAndGo(orderNumber) {
            return post('{{ route('checkout.payment-confirm') }}', { order_number: orderNumber, payment_method: method })
                .then(function (done) { window.location.href = done.redirect; });
        }

        if (btn && window.Stripe) {
            stripe = window.Stripe('{{ $payPublishable }}');
            card = stripe.elements().create('card');
            card.mount('#od-card-element');
            btn.addEventListener('click', function () {
                btn.disabled = true;
                post('{{ route('account.pay', $order->number) }}', {}).then(function (json) {
                    stripe.confirmCardPayment(json.client_secret, { payment_method: { card: card } }).then(function (result) {
                        if (result.error) {
                            showError(result.error.message || 'Your card was declined.');
                            btn.disabled = false;
                            return;
                        }
                        confirmAndGo(json.order_number).catch(function (err) {
                            showError((err && err.message) || 'Payment confirmation failed.');
                            btn.disabled = false;
                        });
                    });
                }).catch(function (err) {
                    showError((err && err.message) || 'Payment could not be started.');
                    btn.disabled = false;
                });
            });
        }

        if (ppWrap && window.paypal) {
            var ppOrderNumber = null;
            window.paypal.Buttons({
                createOrder: function () {
                    return post('{{ route('account.pay', $order->number) }}', {}).then(function (json) {
                        ppOrderNumber = json.order_number;
                        return json.paypal_order_id;
                    }).catch(function (err) {
                        showError((err && err.message) || 'PayPal could not be started.');
                        throw err;
                    });
                },
                onApprove: function () {
                    return confirmAndGo(ppOrderNumber).catch(function (err) {
                        showError((err && err.message) || 'Payment confirmation failed.');
                    });
                },
                onCancel: function () {
                    post('{{ route('checkout.cancel') }}', { order_number: '{{ $order->number }}' }).catch(function () {});
                    showError('PayPal was closed — no money moved. You can try again any time.');
                },
                onError: function () { showError('PayPal ran into a problem — please try again.'); }
            }).render('#od-paypal-buttons');
        }
    }
    document.addEventListener('spa:loaded', egfOrderPayInit);
    egfOrderPayInit();
</script>
@endsection
