@php
    $logo = $company->company_logo
        ? asset('uploads/company/' . $company->company_logo)
        : null;
    $brand = $company->company_name ?? 'Evergreen Foods';
    $footCats = \App\Models\Category::where('status', true)->whereNull('parent_id')->orderBy('sort_order')->take(5)->get(['name', 'slug']);
    $socials = [
        ['facebook', $company->facebook, 'Facebook'],
        ['instagram', $company->instagram, 'Instagram'],
        ['twitter', $company->twitter, 'Twitter'],
        ['linkedin', $company->linkedin, 'LinkedIn'],
        ['youtube', $company->youtube, 'YouTube'],
        ['music', $company->tiktok, 'TikTok'],
        ['message-circle', $company->whatsapp ? 'https://wa.me/' . preg_replace('/\D/', '', $company->whatsapp) : null, 'WhatsApp'],
    ];
    $hasSocials = collect($socials)->contains(fn ($s) => ! empty($s[1]));
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
                {!! $company->footer_content ?? '<p>Thoughtfully sourced groceries delivered to your door. Fresh picks, everyday essentials, and a simpler way to shop.</p>' !!}
                @if ($hasSocials)
                    <div class="social-row">
                        @foreach ($socials as [$icon, $url, $label])
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}"><x-icon name="{{ $icon }}" /></a>
                            @endif
                        @endforeach
                    </div>
                @endif
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
        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ $brand }}. All rights reserved.</span>
            <span>Prices in GBP · Free delivery over £50</span>
        </div>
    </div>
</footer>
