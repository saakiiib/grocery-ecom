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
                                    @if ($s['btn_text'])<a @spa href="{{ $s['btn_url'] ?: route('shop') }}" class="btn btn-primary">{{ $s['btn_text'] }} <span aria-hidden="true">→</span></a>@endif
                                    @if ($s['btn_text2'])<a @spa href="{{ $s['btn_url2'] ?: route('shop') }}" class="btn btn-outline">{{ $s['btn_text2'] }}</a>@endif
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
            @php
                $hygiene = \App\Models\Setting::get('hygiene_rating', '');
                $googleRating = \App\Models\Setting::get('google_rating', '');
                $googleUrl = \App\Models\Setting::get('google_reviews_url', '');
            @endphp
            @if ($hygiene || $googleRating)
                <div class="ratings-strip">
                    @if ($hygiene)
                        <span class="rating-pill is-hygiene"><x-icon name="shield-check" /> Food Hygiene Rating: {{ $hygiene }}/5</span>
                    @endif
                    @if ($googleRating)
                        @if ($googleUrl)
                            <a href="{{ $googleUrl }}" target="_blank" rel="noopener" class="rating-pill is-google"><x-icon name="star" /> Rated {{ $googleRating }} on Google — read reviews</a>
                        @else
                            <span class="rating-pill is-google"><x-icon name="star" /> Rated {{ $googleRating }} on Google</span>
                        @endif
                    @endif
                </div>
            @endif
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
                        <a @spa href="{{ route('shop.category', ['category' => $c['slug']]) }}" class="cat-card">
                            <img src="{{ $c['image'] }}" alt="{{ $c['name'] }}" loading="lazy">
                            <div class="cat-card-overlay">
                                <span class="cat-card-label">{{ $c['count'] }} product{{ $c['count'] === 1 ? '' : 's' }}</span>
                                <span class="cat-card-title">{{ $c['name'] }}</span>
                                <span class="cat-card-desc">{{ \Illuminate\Support\Str::limit($c['description'] ?: 'Fresh ' . $c['name'] . ' delivered to your door.', 90) }}</span>
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
                    <a @spa href="{{ route('shop') }}" class="btn btn-ghost">Explore the shop</a>
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
                    <a @spa href="{{ route('shop.offers') }}" class="btn btn-ghost">All offers</a>
                </div>
            </div>
        </section>
    @endif

    <section class="section" style="background: var(--muted);">
        <div class="container text-center">
            <p class="section-label">Ready when you are</p>
            <h2 class="section-title" style="max-width: 520px; margin-left: auto; margin-right: auto;">Good food starts with better ingredients.</h2>
            <p class="section-desc" style="margin-bottom: 1.5rem;">Browse the full market or jump straight into our offers.</p>
            <div class="flex items-center gap-2" style="justify-content: center; flex-wrap: wrap;">
                <a @spa href="{{ route('shop') }}" class="btn btn-dark">Start shopping</a>
                <a @spa href="{{ route('shop.offers') }}" class="btn btn-ghost">Offers</a>
            </div>
        </div>
    </section>

    @if ($testimonialsJson->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">Reviews</p>
                    <h2 class="section-title">What our shoppers say</h2>
                    <p class="section-desc">Real words from the neighbourhood.</p>
                </div>
                <div class="tmn-slider" data-tmn-slider aria-roledescription="carousel" aria-label="Testimonials">
                    @foreach ($testimonialsJson as $i => $t)
                        <figure class="tmn-slide {{ $i === 0 ? 'active' : '' }}">
                            <div class="tmn-stars" aria-label="Rated 5 out of 5">
                                @for ($s = 0; $s < 5; $s++)<x-icon name="star" />@endfor
                            </div>
                            <blockquote>{{ $t['review'] }}</blockquote>
                            <figcaption>
                                @if ($t['image'])<img src="{{ $t['image'] }}" alt="{{ $t['name'] }}" loading="lazy">@endif
                                <div class="tmn-who">
                                    <strong>{{ $t['name'] }}</strong>
                                    @if ($t['designation'])<span>{{ $t['designation'] }}</span>@endif
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                    @if ($testimonialsJson->count() > 1)
                        <button type="button" class="tmn-arrow prev" aria-label="Previous review"><x-icon name="chevron-left" /></button>
                        <button type="button" class="tmn-arrow next" aria-label="Next review"><x-icon name="chevron-right" /></button>
                        <div class="tmn-dots" role="tablist">
                            @foreach ($testimonialsJson as $i => $t)
                                <button type="button" class="tmn-dot {{ $i === 0 ? 'active' : '' }}" aria-label="Go to review {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($galleryJson->isNotEmpty())
        <section class="section" style="background: var(--muted);">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">From the market</p>
                    <h2 class="section-title">Fresh in pictures</h2>
                    <p class="section-desc">A peek at the produce and the people behind it.</p>
                </div>
                <div class="cat-grid">
                    @foreach ($galleryJson as $g)
                        <div class="cat-card" data-gallery-item data-full="{{ $g['src'] }}" data-caption="{{ $g['caption'] ?? '' }}" role="button" tabindex="0" aria-label="View larger: {{ $g['caption'] ?? 'Gallery image' }}">
                            <img src="{{ $g['src'] }}" alt="{{ $g['caption'] ?? 'Gallery image' }}" loading="lazy">
                            @if ($g['caption'])
                                <div class="cat-card-overlay">
                                    <span class="cat-card-title" style="font-size:1rem;">{{ $g['caption'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($faqsJson->isNotEmpty())
        <section class="section">
            <div class="container" style="max-width:680px;">
                <div class="section-header">
                    <p class="section-label">Help</p>
                    <h2 class="section-title">Frequently asked questions</h2>
                </div>
                @include('frontend.partials.faq-list', ['faqsJson' => $faqsJson])
            </div>
        </section>
    @endif

    @if ($company->google_map || $company->address1 || $company->phone1 || $company->opening_time)
        <section class="section">
            <div class="container">
                <div class="section-header">
                    <p class="section-label">Visit us</p>
                    <h2 class="section-title">Find the market</h2>
                </div>
                <div class="store-grid">
                    <div class="store-card">
                        <div class="store-head">
                            <span class="store-eyebrow">Visit the store</span>
                            <strong>{{ $company->company_name ?? 'Evergreen Foods' }}</strong>
                        </div>
                        @if ($company->address1)
                            <div class="store-row">
                                <x-icon name="map-pin" />
                                <div>
                                    <strong>Store address</strong>
                                    {{ $company->address1 }}
                                    @if ($company->address2)<br>{{ $company->address2 }}@endif
                                    @if ($company->address3)<br>{{ $company->address3 }}@endif
                                </div>
                            </div>
                        @endif
                        @if ($company->phone1)
                            <div class="store-row">
                                <x-icon name="phone" />
                                <div>
                                    <strong>Call us</strong>
                                    <a href="tel:{{ preg_replace('/\s+/', '', $company->phone1) }}">{{ $company->phone1 }}</a>
                                </div>
                            </div>
                        @endif
                        @if ($company->opening_time)
                            <div class="store-row">
                                <x-icon name="clock" />
                                <div>
                                    <strong>Opening hours</strong>
                                    {{ $company->opening_time }}
                                </div>
                            </div>
                        @endif
                        @php
                            $waStore = preg_replace('/\D/', '', (string) ($company->whatsapp ?? ''));
                            $dirUrl = $company->address1 ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($company->address1 . ' ' . ($company->address2 ?? '') . ' ' . ($company->address3 ?? '')) : null;
                        @endphp
                        <div class="store-ctas">
                            @if ($dirUrl)
                                <a href="{{ $dirUrl }}" target="_blank" rel="noopener" class="store-btn is-solid"><x-icon name="map-pin" /> Get directions</a>
                            @endif
                            @if ($company->phone1)
                                <a href="tel:{{ preg_replace('/\s+/', '', $company->phone1) }}" class="store-btn"><x-icon name="phone" /> Call store</a>
                            @endif
                        </div>
                    </div>
                    @if ($company->google_map)
                        <div class="map-embed">{!! $company->google_map !!}</div>
                    @endif
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
