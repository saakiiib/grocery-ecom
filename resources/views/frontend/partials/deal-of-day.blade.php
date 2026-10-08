@if (! empty($dealOfDay))
    <section class="section deal-day" data-deal-day>
        <div class="container deal-inner">
            <div class="deal-copy">
                <p class="section-label">Deal of the day</p>
                <h2 class="section-title">{{ $dealOfDay['card']['name'] }}</h2>
                <p class="section-desc">Flash price ends {{ $dealOfDay['ends_label'] }} — when it's gone, it's gone.</p>
                <div class="deal-price">
                    @if ($dealOfDay['card']['priceNum'] !== null)<strong>£{{ number_format($dealOfDay['card']['priceNum'], 2) }}</strong>@endif
                    @if ($dealOfDay['card']['oldNum'])<s>£{{ number_format($dealOfDay['card']['oldNum'], 2) }}</s>@endif
                    @if ($dealOfDay['card']['savePct'])<span class="promo-tag">Save {{ $dealOfDay['card']['savePct'] }}%</span>@endif
                </div>
                <div class="deal-countdown" data-deal-countdown="{{ $dealOfDay['ends_at'] }}" role="timer" aria-label="Time left on this deal">
                    <div class="deal-unit"><strong data-deal-d>0</strong><span>Days</span></div>
                    <div class="deal-unit"><strong data-deal-h>0</strong><span>Hrs</span></div>
                    <div class="deal-unit"><strong data-deal-m>0</strong><span>Min</span></div>
                    <div class="deal-unit"><strong data-deal-s>0</strong><span>Sec</span></div>
                </div>
                <div class="deal-ctas">
                    <a @spa href="{{ $dealOfDay['card']['url'] }}" class="btn btn-primary">Grab the deal</a>
                    <a @spa href="{{ route('shop.offers') }}" class="btn btn-ghost">All offers</a>
                </div>
            </div>
            <a @spa href="{{ $dealOfDay['card']['url'] }}" class="deal-media" aria-label="{{ $dealOfDay['card']['name'] }}">
                <img src="{{ $dealOfDay['card']['imgAbs'] }}" alt="{{ $dealOfDay['card']['name'] }}" loading="lazy">
            </a>
        </div>
    </section>
@endif
