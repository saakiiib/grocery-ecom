@extends('frontend.layout')
@section('title', 'Order ' . $order->number)

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Thank you, {{ $order->name }}!</h1>
            <p>Order <strong>{{ $order->number }}</strong> is {{ strtolower($order->status?->name ?? $order->status_slug) }} — we will take it from here.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;max-width:760px;">
        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">When will it arrive?</h2>
            <p style="font-size:1.05rem;"><strong>{{ $order->delivery_date ? $order->delivery_date->format('l j F') : '—' }}</strong> · {{ $order->delivery_slot_label }}</p>
            <p class="text-muted">Delivering to {{ $order->address }}, {{ $order->city }} {{ $order->postcode }} · {{ $order->phone }}</p>
            @if ($order->notes)<p class="text-muted">Note: {{ $order->notes }}</p>@endif
        </div>

        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">What you ordered</h2>
            @foreach ($order->items as $item)
                <div class="summary-row" style="align-items:start;">
                    <span>{{ $item->qty }} × {{ $item->product_name }}@if ($item->status !== 'ok') <span class="status-pill">Unavailable — refunded</span>@endif
                        @if ($item->promo_label)<span class="promo-tag">{{ $item->promo_label }}</span>@endif
                        <br><span class="text-muted">{{ $item->pack_label }}</span></span>
                    <span>£{{ number_format($item->line_total, 2) }}</span>
                </div>
            @endforeach
            <div class="summary-row"><span>Subtotal</span><span>£{{ number_format($order->subtotal, 2) }}</span></div>
            @if ($order->points_discount > 0)<div class="summary-row"><span>Loyalty points ({{ $order->points_redeemed }})</span><span>−£{{ number_format($order->points_discount, 2) }}</span></div>@endif
            @if ($order->coupon_discount > 0)<div class="summary-row"><span>Coupon {{ $order->coupon_code }}</span><span>−£{{ number_format($order->coupon_discount, 2) }}</span></div>@endif
            <div class="summary-row"><span>Delivery</span><span>{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</span></div>
            @if ($order->vat_amount > 0)<div class="summary-row"><span class="text-muted">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</span><span class="text-muted">£{{ number_format($order->vat_amount, 2) }}</span></div>@endif
            <div class="summary-row total"><span>Total</span><span>£{{ number_format($order->total, 2) }}</span></div>
            <div class="summary-row"><span>Payment</span><span>{{ $order->paymentLabel() }} · {{ $order->paymentStatusLabel() }}</span></div>
        </div>

        @include('frontend.partials.order-journey')

        <div style="display:flex;gap:0.75rem;margin-top:1.5rem;flex-wrap:wrap;">
            <a @spa href="{{ route('shop') }}" class="btn btn-dark">Keep shopping</a>
            @auth
                @if (auth()->user()->user_type == 0)
                    <a @spa href="{{ route('account') }}" class="btn btn-ghost">Track in my account</a>
                @endif
            @else
                <a href="{{ route('register') }}" class="btn btn-ghost">Create an account to track orders</a>
            @endauth
        </div>
    </div>
</main>
@endsection
