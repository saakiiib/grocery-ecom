@extends('frontend.layout')
@section('title', 'Privacy Policy | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-3xl mx-auto text-center">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Legal</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight">Privacy Policy.</h1>
      <p class="mt-4 text-sm text-[#374151]">Last updated: September 2026. How we collect, use and protect your data under UK GDPR.</p>
    </div>
  </section>

  <!-- Content -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    @if($company->privacy_policy)
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-10 shadow-xs text-sm text-[#374151] leading-relaxed mb-8">
      {!! $company->privacy_policy !!}
    </div>
    @endif
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-10 shadow-xs space-y-8 text-sm text-[#374151] leading-relaxed">
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">1. Data We Collect</h2>
        <p>When you submit an enquiry, request a quote, CAD pack or callback, we collect your name, email address, phone number where provided, site postcode and project details. We also receive basic technical information such as pages visited, for site improvement only.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">2. How We Use It</h2>
        <p>Your details are used solely to respond to your enquiry, prepare quotations and CAD drawings, arrange callbacks and showroom visits, and issue requested specification documents. We do not sell your data, and we never share it with third parties for marketing.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">3. Lawful Basis &amp; Retention</h2>
        <p>Processing is based on your consent and our legitimate interest in responding to business enquiries. Enquiry records are retained for up to 24 months, after which they are securely deleted. You may request earlier deletion at any time.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">4. Your Rights</h2>
        <p>Under UK GDPR you may request access, correction, restriction or deletion of your personal data, and you may withdraw consent at any time. Contact the studio via the <a @spa href="{{ route('contact') }}" class="text-[#9a7b4f] font-semibold hover:underline">contact page</a> to exercise any right; we respond within one month.</p>
      </div>
      <div>
        <h2 class="font-serif text-2xl text-[#1a1d24] font-semibold mb-2">5. Cookies</h2>
        <p>This site uses no advertising or tracking cookies. Essential technical storage (such as remembering UI preferences in your own browser) never leaves your device.</p>
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
