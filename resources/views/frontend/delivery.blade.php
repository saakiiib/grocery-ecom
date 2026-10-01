@extends('frontend.layout')
@section('title', 'Delivery information')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Good to know</p>
            <h1>Delivery information</h1>
            <p>When we deliver, what it costs, and how to pick your slot.</p>
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
        </div>
        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">Time windows</h2>
            @if ($slots->isNotEmpty())
                @foreach ($slots as $slot)
                    <div class="summary-row" style="align-items:center;">
                        <span>{{ $slot->label() }}</span>
                        <span style="font-weight:700;">{{ $slot->fee > 0 ? '£' . number_format($slot->fee, 2) : 'Free' }}</span>
                    </div>
                @endforeach
            @else
                <p class="text-muted">Slots are being set up — check back soon.</p>
            @endif
            <p class="text-muted" style="font-size:14px;margin-top:1rem;">Order before 8pm for next-day slots. Everything travels cold-packed.</p>
        </div>
        <div class="text-center">
            <a @spa href="{{ route('shop') }}" class="btn btn-dark">Start shopping</a>
        </div>
    </div>
</main>
@endsection
