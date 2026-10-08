@extends('frontend.layout')
@section('title', 'Loyalty points')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Rewards</p>
            <h1>Loyalty points</h1>
            <p>Everyday shopping that pays you back.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;max-width:860px;">
        @auth
            <div class="auth-card" style="margin-bottom:1.5rem;text-align:center;">
                <p class="text-muted" style="font-size:14px;">Your balance</p>
                <p style="font-size:2.25rem;font-weight:700;margin:0.25rem 0;">{{ $balance }} <span class="text-muted" style="font-size:1rem;font-weight:400;">points · worth £{{ number_format($balance * $rates['value'], 2) }}</span></p>
                <a @spa href="{{ route('account.loyalty') }}" class="btn btn-dark btn-sm" style="margin-top:0.5rem;">View my points</a>
            </div>
        @endauth
        <div class="auth-card" style="margin-bottom:1.5rem;">
            <h2 style="font-size:1.25rem;margin-bottom:1rem;">How it works</h2>
            <div class="summary-row" style="align-items:center;">
                <span><strong>1. Shop as usual</strong><br><span class="text-muted">No card, no codes — points track automatically on your account.</span></span>
            </div>
            <div class="summary-row" style="align-items:center;">
                <span><strong>2. Earn on delivery</strong><br><span class="text-muted">Get {{ $rates['perPound'] }} point per £1 of goods when your order is delivered.</span></span>
            </div>
            <div class="summary-row" style="align-items:center;border-bottom:0;">
                <span><strong>3. Spend at checkout</strong><br><span class="text-muted">Use {{ $rates['minRedeem'] }}+ points for money off (100 points = £{{ number_format(100 * $rates['value'], 2) }}). Cancelled orders refund your points.</span></span>
            </div>
        </div>
        @guest
            <div class="text-center">
                <a href="{{ route('register') }}" class="btn btn-dark">Create an account to earn</a>
                <p class="text-muted" style="font-size:14px;margin-top:0.75rem;">Guests can shop without an account, but points need one.</p>
            </div>
        @endguest
    </div>
</main>
@endsection
