@php
    $logo = $company->company_logo
        ? asset('uploads/company/' . $company->company_logo)
        : null;
    $brand = $siteBrand ?? trim((string) ($company->company_name ?? '')) ?: 'Alam Mini Market';
    $footerCopy = trim((string) ($company->footer_content ?? ''));
    if ($footerCopy === '' || preg_match('/^(enim|lorem ipsum|test content)/i', trim(strip_tags($footerCopy)))) {
        $footerCopy = 'Fresh groceries, meat and everyday essentials, ready for delivery or collection.';
    }
    $safeSocialUrl = function ($url, $host) {
        if (! $url) {
            return null;
        }
        $parts = parse_url((string) $url) ?: [];
        $actualHost = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || ($actualHost !== $host && ! str_ends_with($actualHost, '.'.$host))) {
            return null;
        }
        return $url;
    };
    $safeAppUrl = function ($url, $host) use ($safeSocialUrl) {
        $safe = $safeSocialUrl($url, $host);
        $pathValue = parse_url((string) $url, PHP_URL_PATH);
        $path = strtolower(trim(is_string($pathValue) ? $pathValue : '', '/'));
        if (! $safe || $path === '' || $path === 'test') {
            return null;
        }
        return $safe;
    };
    $footCats = \App\Models\Category::where('status', true)->whereNull('parent_id')->orderBy('sort_order')->take(5)->get(['name', 'slug']);
    $socials = [
        ['facebook', $safeSocialUrl($company->facebook, 'facebook.com'), 'Facebook'],
        ['instagram', $safeSocialUrl($company->instagram, 'instagram.com'), 'Instagram'],
        ['twitter', $safeSocialUrl($company->twitter, 'x.com') ?? $safeSocialUrl($company->twitter, 'twitter.com'), 'X'],
        ['linkedin', $safeSocialUrl($company->linkedin, 'linkedin.com'), 'LinkedIn'],
        ['youtube', $safeSocialUrl($company->youtube, 'youtube.com'), 'YouTube'],
        ['music', $safeSocialUrl($company->tiktok, 'tiktok.com'), 'TikTok'],
        ['message-circle', $company->whatsapp ? $safeSocialUrl('https://wa.me/' . preg_replace('/\D/', '', $company->whatsapp), 'wa.me') : null, 'WhatsApp'],
    ];
    $hasSocials = collect($socials)->contains(fn ($s) => ! empty($s[1]));
    $appStore = $safeAppUrl($company->google_appstore_link, 'apps.apple.com');
    $playStore = $safeAppUrl($company->google_play_link, 'play.google.com');
    $stripeOn = \App\Http\Controllers\CheckoutController::stripeConfigured();
    $paypalOn = \App\Http\Controllers\CheckoutController::paypalConfigured();
@endphp
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a @spa href="{{ route('home') }}" class="logo">
                    @if ($logo)<img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}">@endif
                    <span class="logo-text">
                        <strong>{{ $brand }}</strong>
                        <small>Fresh living, every day</small>
                    </span>
                </a>
                <p>{{ trim(strip_tags($footerCopy)) }}</p>
                @if ($hasSocials)
                    <div class="social-row">
                        @foreach ($socials as [$icon, $url, $label])
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}"><x-icon name="{{ $icon }}" /></a>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if ($appStore || $playStore)
                    <div class="app-badges">
                        <p>Get our app</p>
                        <div>
                            @if ($appStore)<a href="{{ $appStore }}" target="_blank" rel="noopener" class="app-btn"><x-icon name="apple" /> App Store</a>@endif
                            @if ($playStore)<a href="{{ $playStore }}" target="_blank" rel="noopener" class="app-btn"><x-icon name="play" /> Google Play</a>@endif
                        </div>
                    </div>
                @endif
                <div class="footer-payments" aria-label="Payment options">
                    <span class="footer-payments-label"><x-icon name="shield-check" /> {{ $stripeOn || $paypalOn ? 'Secure online payments' : 'Payment options' }}</span>
                    <div class="footer-payment-marks">
                        @if ($stripeOn)<span class="footer-payment-mark"><strong>Card</strong><small>via Stripe</small></span>@endif
                        @if ($paypalOn)<span class="footer-payment-mark footer-payment-paypal"><strong>PayPal</strong></span>@endif
                        <span class="footer-payment-mark"><strong>Cash</strong><small>delivery / pickup</small></span>
                    </div>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a @spa href="{{ route('shop') }}">Shop all</a>
                @foreach ($footCats as $c)
                    <a @spa href="{{ route('shop.category', ['category' => $c->slug]) }}">{{ $c->name }}</a>
                @endforeach
                <a @spa href="{{ route('shop.offers') }}">Offers</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a @spa href="{{ route('about') }}">About Us</a>
                <a @spa href="{{ route('contact') }}">Contact Us</a>
                <a @spa href="{{ route('faq') }}">FAQ</a>
                <a @spa href="{{ route('privacy') }}">Privacy Policy</a>
                <a @spa href="{{ route('terms') }}">Terms of Supply</a>
                <a @spa href="{{ route('refund') }}">Refund Policy</a>
            </div>
            <div class="footer-col">
                <h4>Orders</h4>
                <a @spa href="{{ route('track') }}">Track order</a>
                <a @spa href="{{ route('loyalty') }}">Loyalty points</a>
                <a @spa href="{{ route('delivery') }}">Delivery info</a>
            </div>
        </div>
        <div class="newsletter-strip" data-newsletter>
            <div>
                <strong>Fresh deals in your inbox</strong>
                <p>One short email a week — offers, new arrivals, no spam.</p>
            </div>
            <form data-newsletter-form novalidate>
                <input type="email" name="email" required maxlength="255" placeholder="you@example.com" aria-label="Email address">
                <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
            </form>
            <p class="newsletter-msg" data-newsletter-msg style="display:none;"></p>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ $brand }}. All rights reserved.</span>
            <span><x-icon name="shield-check" /> 100% Halal promise · Prices in GBP · Free delivery over £50</span>
            <span class="footer-credit">Design &amp; Developed by <a href="https://mentosoftware.co.uk/" target="_blank" rel="noopener">MentoSoftware</a></span>
        </div>
    </div>
</footer>
