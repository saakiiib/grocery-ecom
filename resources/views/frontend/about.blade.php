@extends('frontend.layout')
@section('title', 'About | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto text-center max-w-3xl">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— About OriginSpaces</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight leading-[1.1]">Direct UK Import,<br />Factory Precision.</h1>
      <p class="mt-5 text-sm sm:text-base text-[#374151] leading-relaxed max-w-2xl mx-auto">Precision-engineered factory buildings with full UK compliance, turnkey finishes and nationwide delivery. Build-to-order CAD, video PDI, customs handled, UK warehouse fulfilment, helical screw piles.</p>
    </div>
  </section>

  @if($company->about_us)
  <section class="py-14 md:py-20 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-3xl mx-auto text-sm sm:text-[15px] text-[#374151] leading-relaxed space-y-4">{!! $company->about_us !!}</div>
  </section>
  @endif

  <!-- Philosophy -->
  <section class="py-20 md:py-28 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div>
        <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— The OriginSpaces Philosophy</span>
        <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl text-[#1a1d24] font-semibold leading-[1.15]">Designed for <em class="italic text-[#9a7b4f]">Modern Living.</em><br />Engineered for Generations.</h2>
        <p class="mt-5 text-sm sm:text-[15px] text-[#374151] leading-relaxed">We reject transient decorative trends in favour of timeless architectural permanence. Every surface, tap, handle, and joinery module is conceived as an enduring component of your interior envelope — grounded in the geological weight of natural marble, the warmth of quarter-sawn British oak, and the calibrated precision of solid forged brass.</p>
        <blockquote class="mt-6 border-l-2 border-[#9a7b4f] pl-4 text-sm text-[#374151] italic leading-relaxed">“An interior should calm the senses before demanding admiration. True luxury is not ornamentation — it is the quiet harmony of materials that age with dignified grace.”</blockquote>
        <p class="mt-2 text-[11px] font-mono uppercase tracking-wider text-[#9a7b4f] font-semibold">Julian Vance · Head of Architecture &amp; Design, OriginSpaces London</p>
      </div>
      <div class="relative w-full max-w-[520px] ml-auto">
        <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1000&q=85" alt="Modern residence at dusk" class="w-full rounded-2xl border border-[#e5e2da] shadow-lg object-cover aspect-[4/3]" />
        <div class="absolute -bottom-5 left-5 bg-white rounded-xl border border-[#e5e2da] shadow-xl px-4 py-3 flex items-center gap-3">
          <span class="w-9 h-9 rounded-full bg-[#9a7b4f]/10 flex items-center justify-center shrink-0">
            <x-icon name="shield-check" class="w-4 h-4 text-[#9a7b4f]" />
          </span>
          <span>
            <span class="block text-[10px] font-mono uppercase tracking-widest text-[#1a1d24] font-bold">UK 25-Year Guarantee</span>
            <span class="block text-[11px] text-[#6b7280]">Exhaustive trade testing &amp; WRAS certification</span>
          </span>
        </div>
      </div>
    </div>
  </section>

  <!-- Tenets -->
  <section class="py-20 md:py-28 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-3">How We Work</span>
        <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl text-[#1a1d24] font-semibold tracking-tight">Three Tenets, Every Build</h2>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-7 rounded-2xl border border-[#e5e2da] shadow-xs">
          <span class="text-[10px] font-mono uppercase tracking-widest text-[#9a7b4f] font-bold block mb-2">Tenet 01</span>
          <h3 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">Geological Weight</h3>
          <p class="text-sm text-[#374151] leading-relaxed">Monolithic stone, honest mass, materials chosen for decades — not seasons.</p>
        </div>
        <div class="bg-white p-7 rounded-2xl border border-[#e5e2da] shadow-xs">
          <span class="text-[10px] font-mono uppercase tracking-widest text-[#9a7b4f] font-bold block mb-2">Tenet 02</span>
          <h3 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">Engineering Discipline</h3>
          <p class="text-sm text-[#374151] leading-relaxed">Robotic factory tolerance to &plusmn;2mm, 50-point PDI before any unit ships to site.</p>
        </div>
        <div class="bg-white p-7 rounded-2xl border border-[#e5e2da] shadow-xs">
          <span class="text-[10px] font-mono uppercase tracking-widest text-[#9a7b4f] font-bold block mb-2">Tenet 03</span>
          <h3 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">Living Patina</h3>
          <p class="text-sm text-[#374151] leading-relaxed">Unlacquered brass, smoked oak and honed stone that age with dignified grace.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Compliance strip -->
  <section class="py-20 md:py-28 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
      <div class="lg:col-span-7">
        <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-3">Compliance at a Glance</span>
        <h2 class="font-serif text-3xl sm:text-4xl text-[#1a1d24] font-semibold tracking-tight mb-4">Built for British Standards</h2>
        <ul class="space-y-2.5 text-sm text-[#374151]">
          <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 text-[#9a7b4f] shrink-0 mt-0.5" /> BS 7671 electrics, 230V plug-and-play</li>
          <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 text-[#9a7b4f] shrink-0 mt-0.5" /> UK Building Regulations / Part L (U &le; 0.18)</li>
          <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 text-[#9a7b4f] shrink-0 mt-0.5" /> Permitted Development / Caravan Sites Act 1968</li>
          <li class="flex gap-2"><x-icon name="check" class="w-4 h-4 text-[#9a7b4f] shrink-0 mt-0.5" /> 50-point PDI, 25-year structural guarantee</li>
        </ul>
      </div>
      <div class="lg:col-span-5">
        <div class="bg-[#181b20] text-white rounded-2xl p-7 sm:p-8 text-center">
          <h3 class="font-serif text-2xl font-semibold">See It Before You Commit</h3>
          <p class="text-sm text-[#c9c4b7] mt-2 mb-6">Configure your exact footprint, insulation and interior — engineered build-to-order, delivered from our UK warehouse.</p>
          <div class="flex flex-col gap-2.5">
            <a @spa href="{{ route('custom-build') }}" class="w-full py-3 rounded-xl bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs font-semibold uppercase tracking-wider transition-all text-center">Custom Build</a>
            <a @spa href="{{ route('contact') }}" class="w-full py-3 rounded-xl border border-white/20 hover:border-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all text-center">Talk to the Studio</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  
@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
