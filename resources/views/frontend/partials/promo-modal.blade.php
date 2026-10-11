@if (! empty($promo))
    <div class="promo-modal" data-promo-modal hidden>
        <div class="promo-overlay" data-promo-close></div>
        <div class="promo-card" role="dialog" aria-modal="true" aria-label="{{ $promo['title'] }}">
            <button type="button" class="promo-close" data-promo-close aria-label="Close offer">
                <x-icon name="x" />
            </button>
            <p class="section-label">Today only</p>
            <h2 class="promo-title">{{ $promo['title'] }}</h2>
            @if ($promo['subtitle'])
                <p class="promo-sub">{{ $promo['subtitle'] }}</p>
            @endif
            @if ($promo['coupon'])
                <p class="promo-coupon">Use code <strong data-promo-code>{{ $promo['coupon'] }}</strong>
                    <button type="button" class="btn btn-ghost btn-sm" data-promo-copy>Copy</button>
                </p>
            @endif
            @if (! empty($offerCards) && $offerCards->isNotEmpty())
                <div class="promo-products">
                    @foreach ($offerCards->take(4) as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            @endif
            <div class="promo-ctas">
                <a @spa href="{{ $promo['button_url'] ?: route('shop.offers') }}" class="btn btn-primary">{{ $promo['button_text'] ?: 'Shop today\'s deals' }}</a>
                <button type="button" class="btn btn-ghost" data-promo-close>Continue shopping</button>
            </div>
            <label class="pick-row is-inline promo-quiet" style="margin-top:0.9rem;font-size:0.8rem;"><input type="checkbox" data-promo-quiet> <span class="pick-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span> <span>Don't show again</span></label>
        </div>
    </div>
@endif
