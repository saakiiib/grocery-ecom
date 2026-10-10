@php
    $logo = $company->company_logo
        ? asset('uploads/company/' . $company->company_logo)
        : null;
    $brand = $siteBrand ?? trim((string) ($company->company_name ?? '')) ?: 'Alam Mini Market';
    $isActive = fn (...$routes) => request()->routeIs(...$routes) ? 'active' : '';
@endphp
@include('frontend.partials.announcement-bar')

<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-info">
            <span class="halal-badge"><x-icon name="shield-check" /> 100% Halal</span>
            <span class="sep">·</span>
            <span>Fresh to your door</span>
            <span class="sep">·</span>
            <span>Free delivery on orders over £50</span>
            @if ($company->opening_time)
                <span class="sep">·</span>
                <span>{{ $company->opening_time }}</span>
            @endif
        </div>
        @php
            $safeAppUrl = function ($url, $host) {
                if (! $url) {
                    return null;
                }
                $parts = parse_url((string) $url) ?: [];
                $actualHost = strtolower($parts['host'] ?? '');
                $path = strtolower(trim($parts['path'] ?? '', '/'));
                if (($parts['scheme'] ?? '') !== 'https' || $actualHost !== $host || $path === '' || $path === 'test') {
                    return null;
                }
                return $url;
            };
            $appStore = $safeAppUrl($company->google_appstore_link, 'apps.apple.com');
            $playStore = $safeAppUrl($company->google_play_link, 'play.google.com');
        @endphp
        @if ($appStore || $playStore)
            <div class="topbar-app">
                <span class="topbar-app-label">Get our app</span>
                @if ($appStore)<a href="{{ $appStore }}" target="_blank" rel="noopener" class="app-btn is-mini" aria-label="Download on the App Store"><x-icon name="apple" /><span>App Store</span></a>@endif
                @if ($playStore)<a href="{{ $playStore }}" target="_blank" rel="noopener" class="app-btn is-mini" aria-label="Get it on Google Play"><x-icon name="play" /><span>Google Play</span></a>@endif
            </div>
        @endif
    </div>
</div>

<header class="site-header">
    <div class="container header-inner">
        <a @spa href="{{ route('home') }}" class="logo">
            @if ($logo)<img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}">@endif
            <span class="logo-text">
                <strong>{{ $brand }}</strong>
                <small>Fresh living, every day</small>
            </span>
        </a>
        <nav class="nav-desktop" aria-label="Main">
            <a @spa href="{{ route('home') }}" class="{{ $isActive('home') }}">Home</a>
            <a @spa href="{{ route('about') }}" class="{{ $isActive('about') }}">About Us</a>
            <a @spa href="{{ route('shop') }}" class="{{ $isActive('shop') && ! request()->boolean('only_offers') ? 'active' : '' }}">Shop</a>
            <a @spa href="{{ route('shop.offers') }}" class="{{ $isActive('shop') && request()->boolean('only_offers') ? 'active' : '' }}">Offers</a>
            {{-- Recipes hidden (kept, not deleted) --}}
            <a @spa href="{{ route('gallery') }}" class="{{ $isActive('gallery') }}">Gallery</a>
            <a @spa href="{{ route('contact') }}" class="{{ $isActive('contact') }}">Contact Us</a>
        </nav>
        <div class="header-actions">
            <button type="button" class="icon-btn" data-search-open aria-label="Search" title="Search">
                <x-icon name="search" />
            </button>
            <a @spa href="{{ route('account') }}" class="icon-btn" aria-label="My account" title="{{ auth()->check() ? auth()->user()->name : 'My account' }}">
                @auth
                    <span class="avatar-initial">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                @else
                    <x-icon name="user" />
                @endauth
            </a>
            <a @spa href="{{ route('favourites') }}" class="icon-btn" aria-label="Favourites" title="Favourites">
                <x-icon name="heart" />
            </a>
            <a @spa href="{{ route('bag') }}" class="cart-btn" aria-label="Shopping bag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span data-bag-count>{{ \App\Http\Controllers\BagController::count() }}</span>
            </a>
            <button type="button" class="icon-btn mobile-menu-btn" data-mobile-menu aria-label="Menu">
                <x-icon name="menu" />
            </button>
        </div>
    </div>
</header>

<div class="mobile-nav" aria-hidden="true">
    <div class="mobile-nav-overlay" data-close-menu></div>
    <div class="mobile-nav-panel">
        <div class="mobile-nav-header">
            <a @spa href="{{ route('home') }}" class="logo">
                @if ($logo)<img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}">@endif
                <span class="logo-text"><strong>{{ $brand }}</strong></span>
            </a>
            <button type="button" class="icon-btn" data-close-menu aria-label="Close">
                <x-icon name="x" />
            </button>
        </div>
        <nav class="mobile-nav-links">
            <a @spa href="{{ route('home') }}">Home</a>
            <a @spa href="{{ route('about') }}">About Us</a>
            <a @spa href="{{ route('shop') }}">Shop</a>
            <a @spa href="{{ route('shop.offers') }}">Offers</a>
            {{-- Recipes hidden (kept, not deleted) --}}
            <a @spa href="{{ route('gallery') }}">Gallery</a>
            <a @spa href="{{ route('contact') }}">Contact Us</a>
            <a @spa href="{{ route('favourites') }}">Favourites</a>
        </nav>
    </div>
</div>
