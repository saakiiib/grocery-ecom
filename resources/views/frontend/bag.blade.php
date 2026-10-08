@extends('frontend.layout')
@section('title', 'Your bag')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Your bag</h1>
            <p>Review your items before checkout.</p>
        </div>
    </div>
    <div class="container">
        @if (session('bag_notice'))
            <div class="auth-card" style="border-color:#1A2E22;margin-bottom:1.5rem;">{{ session('bag_notice') }}</div>
        @endif
        <div class="cart-layout">
            <div data-bag-items>
                @if (empty($bag['lines']))
                    <div class="empty-state">
                        <h2>Your bag is empty</h2>
                        <p>Browse the market and add some fresh finds.</p>
                        <a @spa href="{{ route('shop') }}" class="btn btn-dark">Shop groceries</a>
                    </div>
                @else
                    @foreach ($bag['lines'] as $item)
                        <div class="cart-item" data-key="{{ $item['variant_id'] }}">
                            <a @spa href="{{ $item['slug'] ? route('product.show', $item['slug']) : route('shop') }}">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" loading="lazy">
                            </a>
                            <div>
                                <div class="cart-item-name">{{ $item['name'] }}</div>
                                @if ($item['pack'])<div class="cart-item-meta">{{ $item['pack'] }}</div>@endif
                                @if ($item['promo_label'])<div><span class="promo-tag">{{ $item['promo_label'] }}{{ ($item['free_qty'] ?? 0) > 0 ? ' · '.$item['free_qty'].' free' : '' }}</span></div>@endif
                                @if ($item['available'])
                                    <div class="cart-item-price">£{{ number_format($item['price'], 2) }}</div>
                                @else
                                    <div class="cart-item-meta" style="color:#B91C1C">No longer available</div>
                                @endif
                                <div class="qty-control mt-1" style="display:inline-flex">
                                    <button type="button" data-bag-minus aria-label="Decrease">−</button>
                                    <span data-bag-qty>{{ $item['qty'] }}</span>
                                    <button type="button" data-bag-plus aria-label="Increase">+</button>
                                </div>
                            </div>
                            <div class="cart-item-actions">
                                <div class="cart-item-price">£{{ number_format($item['line_total'], 2) }}</div>
                                <button type="button" class="icon-btn" data-bag-remove aria-label="Remove" title="Remove">
                                    <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <aside class="cart-summary">
                @include('frontend.partials.delivery-progress', ['subtotal' => $bag['subtotal'] ?? 0, 'minOrder' => $minOrder ?? 15, 'freeOver' => $freeOver ?? 50])
                <h3>Order summary</h3>
                <div class="summary-row"><span>Subtotal</span><span data-summary-subtotal>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                <div class="summary-row" data-summary-bogo @if (($bag['bogo_discount'] ?? 0) <= 0) style="display:none;" @endif><span>BOGO savings</span><span data-summary-bogo-amount>−£{{ number_format($bag['bogo_discount'] ?? 0, 2) }}</span></div>
                <div class="summary-row" data-summary-bundle @if (($bag['bundle_discount'] ?? 0) <= 0) style="display:none;" @endif><span>Bundle savings</span><span data-summary-bundle-amount>−£{{ number_format($bag['bundle_discount'] ?? 0, 2) }}</span></div>
                <div class="summary-row"><span>Delivery</span><span>Calculated at checkout</span></div>
                <div class="summary-row total"><span>Total</span><span data-summary-total>£{{ number_format($bag['subtotal'], 2) }}</span></div>
                <a @spa href="{{ route('checkout') }}" class="btn btn-dark btn-block" style="margin-top:1.25rem;">Proceed to checkout</a>
                <a @spa href="{{ route('shop') }}" class="btn btn-ghost btn-block" style="margin-top:0.5rem;">Continue shopping</a>
            </aside>
        </div>
    </div>
</main>
@endsection
