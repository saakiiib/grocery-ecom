@extends('frontend.layout')
@section('title', 'Delivery information')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Good to know</p>
            <h1>Delivery information</h1>
            <p>When we deliver, what it costs, and where we deliver.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;max-width:860px;">
        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">The essentials</h2>
            <div class="summary-row" style="align-items:center;">
                <span><strong>Minimum order</strong><br><span class="text-muted">£{{ number_format($minOrder, 2) }} of goods per delivery.</span></span>
            </div>
            <div class="summary-row" style="align-items:center;border-bottom:0;">
                <span><strong>Free delivery</strong><br><span class="text-muted">On all orders over £{{ number_format($freeOver, 2) }}.</span></span>
            </div>
            @if ($storePostcode !== '')
            <div class="summary-row" style="align-items:center;border-bottom:0;">
                <span><strong>Delivery area</strong><br><span class="text-muted">Within {{ number_format($radiusMiles, 1) }} miles of {{ strtoupper($storePostcode) }} — checked automatically from your postcode.</span></span>
            </div>
            @endif
        </div>
        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">Delivery options</h2>
            <div class="summary-row" style="align-items:center;">
                <span><strong>Today delivery</strong><br><span class="text-muted">Order before {{ $cutoffTime }} — arrives today.</span></span>
                <span style="font-weight:700;">{{ $todayFee > 0 ? '£'.number_format($todayFee, 2) : 'Free' }}</span>
            </div>
            <div class="summary-row" style="align-items:center;">
                <span><strong>Standard shipping</strong><br><span class="text-muted">Next working day delivery.</span></span>
                <span style="font-weight:700;">{{ $standardFee > 0 ? '£'.number_format($standardFee, 2) : 'Free' }}</span>
            </div>
            <p class="text-muted" style="font-size:14px;margin-top:1rem;">After {{ $cutoffTime }}, only next working day delivery is available. Free delivery over £{{ number_format($freeOver, 2) }} applies to both. Everything travels cold-packed.</p>
        </div>
        <div class="text-center">
            <a @spa href="{{ route('shop') }}" class="btn btn-dark">Start shopping</a>
        </div>
    </div>
</main>
@endsection
