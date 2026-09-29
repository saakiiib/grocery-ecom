@extends('frontend.layout')
@section('title', 'Fresh groceries, delivered')

@section('content')
<main>
    @if ($slidersJson->isNotEmpty())
        <section class="hero hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="Featured">
            @foreach ($slidersJson as $i => $s)
                <div class="hero-slide {{ $i === 0 ? 'active' : '' }}">
                    <div class="hero-bg"><img src="{{ $s['image'] }}" alt="{{ $s['badge'] ?? $s['title'] ?? 'Offer' }}" @if ($i > 0) loading="lazy" @endif></div>
                    <div class="hero-overlay"></div>
                    <div class="hero-slide-inner">
                        <div class="container">
                            <div class="hero-content">
                                @if ($s['badge'])<span class="hero-badge">{{ $s['badge'] }}</span>@endif
                                @if ($s['title'])<h1>{!! nl2br(e($s['title'])) !!}</h1>@endif
                                @if ($s['subtitle'])<p>{{ $s['subtitle'] }}</p>@endif
                                <div class="hero-ctas">
                                    @if ($s['btn_text'])<a @spa href="{{ $s['btn_url'] ?: route('collections') }}" class="btn btn-primary">{{ $s['btn_text'] }} <span aria-hidden="true">→</span></a>@endif
                                    @if ($s['btn_text2'])<a @spa href="{{ $s['btn_url2'] ?: route('collections') }}" class="btn btn-outline">{{ $s['btn_text2'] }}</a>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="container hero-slide-spacer" aria-hidden="true">
                <div class="hero-content">
                    <span class="hero-badge">.</span>
                    <h1>{{ $slidersJson[0]['title'] ?? '' }}</h1>
                    <p>Placeholder for consistent hero height.</p>
                </div>
            </div>
            <button type="button" class="hero-arrow prev" aria-label="Previous slide"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
            <button type="button" class="hero-arrow next" aria-label="Next slide"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
            <div class="hero-dots" role="tablist">
                @foreach ($slidersJson as $i => $s)
                    <button type="button" class="hero-dot {{ $i === 0 ? 'active' : '' }}" aria-label="Go to slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </section>
    @endif

    <section class="trust-bar">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="truck" /></div>
                    <div><strong>Delivered to your door</strong><span>Free delivery over £50</span></div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="leaf" /></div>
                    <div><strong>Picked for freshness</strong><span>Quality you can feel good about</span></div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="shield-check" /></div>
                    <div><strong>Shop with confidence</strong><span>Carefully selected, every time</span></div>
                </div>
            </div>
        </div>
    </section>

    @if ($categoriesJson->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">The Evergreen Market</p>
                    <h2 class="section-title">Shop by category</h2>
                    <p class="section-desc">From the field to your table — explore what we're picking today.</p>
                </div>
                <div class="cat-grid">
                    @foreach ($categoriesJson as $c)
                        <a @spa href="{{ route('collections', ['category' => $c['slug']]) }}" class="cat-card">
                            <img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" loading="lazy">
                            <div class="cat-card-overlay">
                                <span class="cat-card-label">{{ $c['count'] }} product{{ $c['count'] === 1 ? '' : 's' }}</span>
                                <span class="cat-card-title">{{ $c['name'] }}</span>
                                <span class="cat-card-cta">Shop now →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredCards->isNotEmpty())
        <section class="section" style="background: var(--muted);">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">This week's picks</p>
                    <h2 class="section-title">Fresh favourites</h2>
                    <p class="section-desc">Hand-selected produce and everyday staples our shoppers love.</p>
                </div>
                <div class="product-grid">
                    @foreach ($featuredCards as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
                <div class="text-center" style="margin-top: 2rem;">
                    <a @spa href="{{ route('collections') }}" class="btn btn-ghost">Explore the shop</a>
                </div>
            </div>
        </section>
    @endif

    @if ($offerCards->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">This week only</p>
                    <h2 class="section-title">Offers worth a look</h2>
                    <p class="section-desc">Seasonal savings on produce and pantry favourites.</p>
                </div>
                <div class="product-grid">
                    @foreach ($offerCards as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
                <div class="text-center" style="margin-top: 2rem;">
                    <a @spa href="{{ route('offers') }}" class="btn btn-ghost">All weekly offers</a>
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container text-center">
            <p class="section-label">Ready when you are</p>
            <h2 class="section-title" style="max-width: 520px; margin-left: auto; margin-right: auto;">Good food starts with better ingredients.</h2>
            <p class="section-desc" style="margin-bottom: 1.5rem;">Browse the full market or jump straight into this week's offers.</p>
            <div class="flex items-center gap-2" style="justify-content: center; flex-wrap: wrap;">
                <a @spa href="{{ route('collections') }}" class="btn btn-dark">Start shopping</a>
                <a @spa href="{{ route('offers') }}" class="btn btn-ghost">Weekly offers</a>
            </div>
        </div>
    </section>
</main>
@endsection
