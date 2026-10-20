@php
    $siteBrand = $siteBrand ?? trim((string) ($company->company_name ?? ''));
    if ($siteBrand === '' || strcasecmp($siteBrand, 'Evergreen') === 0) {
        $siteBrand = 'Alam Mini Market';
    }

    // Exactly one <title> per page. Controllers that know the page's SEO title
    // share $seoTitle (including any admin-entered title); otherwise the view's
    // @section('title') is used and the brand is appended.
    $sectionTitle = trim((string) \Illuminate\Support\Facades\View::yieldContent('title'));
    $seoTitle = trim((string) ($seoTitle ?? ''));
    if ($seoTitle === '') {
        $seoTitle = $sectionTitle !== '' ? $sectionTitle.' — '.$siteBrand : $siteBrand;
    }
    OpenGraph::setTitle($seoTitle);
    Twitter::setTitle($seoTitle);

    // Tracking/verification IDs live in Company Details (admin → Company).
    $googleSiteVerification = trim((string) ($company->google_site_verification ?? ''));
    $googleAnalyticsId = trim((string) ($company->google_analytics_id ?? ''));
    $googleTagManagerId = trim((string) ($company->google_tag_manager_id ?? ''));
    $facebookPixelId = trim((string) ($company->facebook_pixel_id ?? ''));
    $hasTracking = $googleAnalyticsId !== '' || $googleTagManagerId !== '' || $facebookPixelId !== '';
@endphp
<!DOCTYPE html>
<html lang="en-GB">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="{{ $siteBrand }}">
    <meta name="theme-color" content="#1A2E22">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="geo.placename" content="{{ config('seo.business.locality') }}">
    @if ($googleSiteVerification !== '')
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif
    @if ($googleTagManagerId !== '' || $googleAnalyticsId !== '')
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="dns-prefetch" href="https://www.google-analytics.com">
    @endif
    @if ($facebookPixelId !== '')
        <link rel="preconnect" href="https://connect.facebook.net">
    @endif
    <meta property="og:site_name" content="{{ $siteBrand }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_GB">
    <title>{{ $seoTitle }}</title>
    {!! SEOMeta::generate() !!}
    {!! OpenGraph::generate() !!}
    {!! Twitter::generate() !!}
    @include('frontend.partials.structured-data')
    @include('frontend.partials.tracking')
    <link rel="icon" href="{{ $company->fav_icon ? asset('uploads/company/' . $company->fav_icon) : asset('favicon.ico') }}">
    @if ($company->fav_icon)
        <link rel="apple-touch-icon" href="{{ asset('uploads/company/' . $company->fav_icon) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('resources/frontend/css/style.css') }}?v={{ filemtime(public_path('resources/frontend/css/style.css')) }}">
    @yield('style')
</head>

<body>

@include('frontend.header')

<div @spaContent>
    @yield('content')
</div>

@include('frontend.footer')

@include('frontend.partials.floating-contact')

@include('frontend.partials.floating-bag')

@include('frontend.partials.cookie-banner')

<script>
    window.EGF_ROUTES = {
        shop: "{{ route('shop') }}",
        bag: "{{ route('bag') }}",
        bagData: "{{ route('bag.data') }}",
        bagAdd: "{{ route('bag.add') }}",
        bagUpdate: "{{ route('bag.update') }}",
        bagRemove: "{{ route('bag.remove') }}",
        checkout: "{{ route('checkout') }}",
        favToggle: "{{ route('favourites.toggle') }}",
        reviewStore: "{{ route('reviews.store') }}",
        login: "{{ route('login') }}",
        catalog: "{{ route('search.catalog') }}",
        newsletter: "{{ route('newsletter.subscribe') }}",
        notify: "{{ route('notify.store') }}",
        product: "{{ url('/product') }}"
    };
    window.EGF_ASSETS = { placeholder: "{{ asset('placeholder.webp') }}" };
    window.EGF_CATALOG = [];
</script>
<script src="{{ asset('resources/frontend/js/egf.js') }}?v={{ filemtime(public_path('resources/frontend/js/egf.js')) }}"></script>

@spaEngine

@yield('script')

</body>
</html>
