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
            <label class="promo-quiet"><input type="checkbox" data-promo-quiet> Don't show again</label>
        </div>
    </div>
@endif
