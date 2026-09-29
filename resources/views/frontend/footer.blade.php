@php
    $logo = $company->company_logo
        ? asset('uploads/company/' . $company->company_logo)
        : asset('frontend-raw/assets/images/logo.png');
    $brand = $company->company_name ?? 'Evergreen Foods';
    $footCats = \App\Models\Category::where('status', true)->orderBy('sort_order')->take(3)->get(['name', 'slug']);
@endphp
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a @spa href="{{ route('home') }}" class="logo">
                    <img class="logo-img" src="{{ $logo }}" alt="{{ $brand }}">
                    <span class="logo-text">
                        <strong>{{ $brand }}</strong>
                        <small>Fresh living, every day</small>
                    </span>
                </a>
                <p>Thoughtfully sourced groceries delivered to your door. Fresh picks, everyday essentials, and a simpler way to shop.</p>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a @spa href="{{ route('collections') }}">All groceries</a>
                @foreach ($footCats as $c)
                    <a @spa href="{{ route('collections', ['category' => $c->slug]) }}">{{ $c->name }}</a>
                @endforeach
                <a @spa href="{{ route('offers') }}">Weekly offers</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a @spa href="{{ route('about') }}">Our story</a>
                <a @spa href="{{ route('contact') }}">Contact us</a>
                <a @spa href="{{ route('faq') }}">FAQ</a>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <a @spa href="{{ route('account') }}">My account</a>
                <a @spa href="{{ route('bag') }}">Your bag</a>
                <a @spa href="{{ route('checkout') }}">Checkout</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ $brand }}. All rights reserved.</span>
            <span>Prices in GBP · Free delivery over £50</span>
        </div>
    </div>
</footer>
