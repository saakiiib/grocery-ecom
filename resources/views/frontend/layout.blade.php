<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

{{-- $company shared globally via AppServiceProvider (cached) --}}

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <link rel="icon" href="{{ $company->fav_icon ? asset('uploads/company/' . $company->fav_icon) : asset('resources/frontend-raw/assets/logo.png') }}" sizes="48x48">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('resources/frontend-raw/css/site.css') }}?v={{ filemtime(public_path('resources/frontend-raw/css/site.css')) }}">
    @yield('style')
</head>

<body>

@include('frontend.header')

<div @spaContent>
    @yield('content')
</div>

@include('frontend.footer')

<script src="{{ asset('resources/frontend-raw/js/icons.js') }}?v={{ filemtime(public_path('resources/frontend-raw/js/icons.js')) }}"></script>

@spaEngine

@yield('script')

<div class="share-float" id="shareFloat">
  <div class="share-panel" id="sharePanel">
    <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" title="Messenger">
      <span class="share-label">Messenger</span>
      <x-icon name="message-circle" />
    </a>
    <a class="share-btn" href="https://wa.me/{{ $company->whatsapp ?? '' }}?text={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" title="WhatsApp">
      <span class="share-label">WhatsApp</span>
      <x-icon name="phone" />
    </a>
    <a class="share-btn" href="mailto:{{ $company->email1 ?? '' }}?subject={{ urlencode(request()->url()) }}" title="Email">
      <span class="share-label">Email</span>
      <x-icon name="mail" />
    </a>
    <a class="share-btn" href="tel:{{ $company->phone1 ?? '' }}" title="Call">
      <span class="share-label">Call</span>
      <x-icon name="phone" />
    </a>
  </div>
  <button class="share-toggle" id="shareToggle" title="Contact Us" aria-label="Contact Us">
    <x-icon name="phone" />
  </button>
</div>
<style>
.share-float{position:fixed;bottom:90px;right:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-end;gap:10px}
.share-panel{display:flex;flex-direction:column;gap:8px;opacity:0;visibility:hidden;transform:translateX(20px);transition:all .3s ease;width:fit-content;align-items:flex-end}
.share-float.open .share-panel{opacity:1;visibility:visible;transform:translateX(0)}
.share-btn{display:inline-flex;align-items:center;gap:12px;background:#132238;color:#fff;padding:10px 16px;border-radius:30px;text-decoration:none;font-size:.85rem;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.2);transition:all .2s;white-space:nowrap;width:fit-content}
.share-btn:hover{background:#9a7b4f;transform:translateX(-4px)}
.share-btn i{width:24px;text-align:center}
.share-btn .share-label{color:#fff}
.share-toggle{width:56px;height:56px;border-radius:50%;background:#9a7b4f;border:none;color:#fff;cursor:pointer;box-shadow:0 4px 16px rgba(154,123,79,.4);transition:all .25s;display:flex;align-items:center;justify-content:center}
.share-toggle:hover{transform:scale(1.1)}
.back-to-top{position:fixed;bottom:24px;right:24px;z-index:10000;width:56px;height:56px;border-radius:50%;background:#9a7b4f;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 16px rgba(154,123,79,.4);opacity:0;visibility:hidden;transform:translateY(-20px);transition:all .25s}
.back-to-top.show{opacity:1;visibility:visible;transform:translateY(0)}
.back-to-top:hover{background:#86683f;transform:scale(1.1)}
/* Popup layering (plain CSS so it never depends on generated utilities) */
#lightbox-modal{z-index:70}
#enquiry-modal{z-index:60}
#sample-modal{z-index:60}
</style>

<div class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0, behavior:'smooth'})">
  <x-icon name="arrow-up" />
</div>
<script>
(function(){
  var toggle = document.getElementById('shareToggle');
  var float = document.getElementById('shareFloat');
  if (toggle && float) {
    toggle.addEventListener('click', function(){ float.classList.toggle('open'); });
  }
  var btn = document.getElementById('backToTop');
  if (btn) {
    window.addEventListener('scroll', function(){
      if (window.scrollY > 400) { btn.classList.add('show'); } else { btn.classList.remove('show'); }
    }, { passive: true });
  }
  document.addEventListener('spa:loaded', function(){ if (window.lucide) lucide.createIcons(); });
})();
</script>

</body>
</html>
