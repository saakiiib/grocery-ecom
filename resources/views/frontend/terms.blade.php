@extends('frontend.layout')
@section('title', 'Terms of Supply | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-3xl mx-auto text-center">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Legal</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight">Terms of Supply.</h1>
      <p class="mt-4 text-sm text-[#374151]">Last updated: September 2026. The basis on which we quote, build and deliver.</p>
    </div>
  </section>

  <!-- Content -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    @if($company->terms_and_conditions)
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-10 shadow-xs text-sm text-[#374151] leading-relaxed mb-8">
      {!! $company->terms_and_conditions !!}
    </div>
    @endif
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-10 shadow-xs space-y-8 text-sm text-[#374151] leading-relaxed">
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">1. Quotes &amp; CAD Drawings</h2>
        <p>Guide prices shown are factory ex-works and exclude VAT and UK haulage unless stated. Custom CAD drawings are issued free of charge and carry no obligation; production begins only after your written sign-off of the final drawing set.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">2. Deposits &amp; Staged Payments</h2>
        <p>Orders follow a staged schedule: deposit to commence production, a production-milestone payment, a pre-dispatch payment following successful PDI, and the final balance on delivery. No hidden charges are added after sign-off.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">3. Production &amp; Delivery</h2>
        <p>Factory build typically takes 25–30 days, followed by sea freight, UK customs clearance, central-warehouse PDI and Hiab delivery to your prepared base. Timelines given are good-faith estimates; we notify you promptly of any delay.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">4. Site Requirements</h2>
        <p>You are responsible for site access (minimum 3m clear width unless agreed otherwise), a level prepared base of ground screws or pad piers to our setting-out drawing, and utility connection points. Failed deliveries due to unprepared sites may incur re-delivery charges.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">5. Warranties</h2>
        <p>Structural frames carry a 25-year warranty; joinery, envelope and mechanisms carry a 10-year warranty, subject to normal use and the supplied care guides. Consumables, intentional damage and unapproved modifications are excluded.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">6. Cancellation</h2>
        <p>You may cancel free of charge before CAD sign-off. After production commences, stage payments made are non-refundable as materials and factory slots are committed. Contact the <a @spa href="{{ route('contact') }}" class="text-[#9a7b4f] font-semibold hover:underline">studio</a> to discuss any change.</p>
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
