@extends('frontend.layout')
@section('title', 'Page Not Found | OriginSpaces')

@section('content')
<section class="py-24 md:py-32 px-4 sm:px-6 lg:px-10">
  <div class="max-w-2xl mx-auto text-center">
    <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— 404</span>
    <h1 class="font-serif text-5xl sm:text-6xl text-[#1a1d24] font-semibold tracking-tight">Page Not Found.</h1>
    <p class="mt-4 text-sm text-[#374151]">The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
    <div class="mt-8 flex flex-col sm:flex-row justify-center gap-2.5">
      <a @spa href="{{ route('home') }}" class="px-6 py-3 rounded-xl bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all">Go Home</a>
      <a @spa href="{{ route('collections') }}" class="px-6 py-3 rounded-xl border border-[#e5e2da] bg-white hover:border-[#9a7b4f] text-[#1a1d24] text-xs font-semibold uppercase tracking-wider transition-all">Browse Collections</a>
    </div>
  </div>
</section>
@endsection

@section('script')
<script>if (window.lucide) lucide.createIcons();</script>
@endsection
