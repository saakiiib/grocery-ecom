<!DOCTYPE html>
<html lang="en-GB">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="{{ $company->company_name ?? '' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('title', config('app.name'))</title>
    {!! SEOMeta::generate() !!}
    {!! OpenGraph::generate() !!}
    {!! Twitter::generate() !!}
    @if ($company->google_analytics_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $company->google_analytics_id }}"></script>
        <script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', '{{ $company->google_analytics_id }}');</script>
    @endif
    <link rel="icon" href="{{ $company->fav_icon ? asset('uploads/company/' . $company->fav_icon) : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('resources/frontend/css/egf.css') }}?v={{ filemtime(public_path('resources/frontend/css/egf.css')) }}">
    @yield('style')
</head>

<body>

@include('frontend.header')

<div @spaContent>
    @yield('content')
</div>

@include('frontend.footer')

@php
    $egfCatalog = \App\Models\Product::with(['category:id,name', 'variants' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
        ->where('status', true)
        ->orderBy('sort_order')->orderByDesc('id')
        ->get()
        ->map(function ($p) {
            $v = $p->variants->firstWhere('is_default', true) ?? $p->variants->first();
            if (! $v) return null;
            return [
                'slug' => $p->slug,
                'name' => $p->name,
                'cat' => $p->category?->name ?? '',
                'tags' => trim($p->name . ' ' . ($p->category?->name ?? '') . ' ' . ($v->sku ?? '')),
                'price' => $v->sellingPrice(),
                'pack' => $v->combinationLabel() ?? '',
                'img' => $v->image ? url($v->image) : ($p->hero_image ? url($p->hero_image) : url('placeholder.webp')),
                'variant_id' => $v->id,
            ];
        })->filter()->values();
@endphp
<script>
    window.EGF_ROUTES = {
        shop: "{{ route('collections') }}",
        bag: "{{ route('bag') }}",
        bagData: "{{ route('bag.data') }}",
        bagAdd: "{{ route('bag.add') }}",
        bagUpdate: "{{ route('bag.update') }}",
        bagRemove: "{{ route('bag.remove') }}",
        checkout: "{{ route('checkout') }}",
        product: "{{ url('/product') }}"
    };
    window.EGF_ASSETS = { placeholder: "{{ asset('placeholder.webp') }}" };
    window.EGF_CATALOG = @json($egfCatalog);
</script>
<script src="{{ asset('resources/frontend/js/egf.js') }}?v={{ filemtime(public_path('resources/frontend/js/egf.js')) }}"></script>

@spaEngine

@yield('script')

</body>
</html>
