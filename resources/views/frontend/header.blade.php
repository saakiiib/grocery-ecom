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
            <span>Free delivery on orders over £{{ number_format(\App\Models\Setting::money('delivery_free_over', 50.00), 2) }}</span>
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
            @if ($logo)<img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}" width="2172" height="724">@endif
            <span class="logo-text">
                <strong>{{ $brand }}</strong>
                <small>Fresh living, every day</small>
            </span>
        </a>
        <nav class="nav-desktop" aria-label="Main">
            <a @spa href="{{ route('home') }}" class="{{ $isActive('home') }}">Home</a>
            <a @spa href="{{ route('about') }}" class="{{ $isActive('about') }}">About Us</a>
            <a @spa href="{{ route('shop') }}" class="{{ $isActive('shop') && ! request()->boolean('only_offers') && ! request()->routeIs('shop.offers') ? 'active' : '' }}">Shop</a>
            <a @spa href="{{ route('shop.offers') }}" class="{{ request()->routeIs('shop.offers') || ($isActive('shop') && request()->boolean('only_offers')) ? 'active' : '' }}">Offers</a>
            {{-- Recipes hidden (kept, not deleted) --}}
            <a @spa href="{{ route('gallery') }}" class="{{ $isActive('gallery') }}">Gallery</a>
            <a @spa href="{{ route('contact') }}" class="{{ $isActive('contact') }}">Contact Us</a>
        </nav>
        <div class="header-actions">
            <button type="button" class="icon-btn" data-search-open aria-label="Search" title="Search">
                <x-icon name="search" />
            </button>
            <a href="{{ auth()->check() && auth()->user()->user_type == 1 ? route('admin.dashboard') : route('account') }}" class="icon-btn" aria-label="My account" title="{{ auth()->check() ? (auth()->user()->user_type == 1 ? 'Admin dashboard' : auth()->user()->name) : 'My account' }}">
                @auth
                    <span class="avatar-initial">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                @else
                    <x-icon name="user" />
                @endauth
            </a>
            <a href="{{ route('favourites') }}" class="icon-btn" aria-label="Favourites" title="Favourites">
                <x-icon name="heart" />
            </a>
            <a @spa href="{{ route('bag') }}" class="cart-btn" aria-label="Shopping bag">
                <x-icon name="shopping-bag" />
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
                @if ($logo)<img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}" width="2172" height="724">@endif
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
            <a href="{{ route('favourites') }}">Favourites</a>
        </nav>
    </div>
</div>
