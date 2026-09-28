@extends('frontend.layout')
@section('title', 'Contact | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto text-center max-w-3xl">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Contact the Studio</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight leading-[1.1]">Talk to Us About<br />Your Project.</h1>
      <p class="mt-5 text-sm sm:text-base text-[#374151] leading-relaxed max-w-2xl mx-auto">Quotes, CAD packs, compliance documents and callbacks — the studio replies within one working day.</p>
    </div>
  </section>

  <!-- Contact Grid -->
  <section class="py-20 md:py-28 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Info -->
      <div class="lg:col-span-5 space-y-5">
        <div class="bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-7 shadow-xs">
          <div class="flex items-center gap-2 text-[11px] font-mono uppercase tracking-widest text-[#9a7b4f] font-semibold mb-4">
            <x-icon name="phone" class="w-4 h-4" />
            <span>Studio Line</span>
          </div>
          <p class="font-serif text-2xl text-[#1a1d24] font-semibold">{{ $company->phone1 ?: '+44 (0)20 7946 0880' }}</p>
          <p class="text-xs text-[#6b7280] mt-1">{{ $company->opening_time ?: 'Mon–Sat, 10:00–18:00' }}</p>
          @if($company->email1)<p class="text-sm text-[#374151] mt-2"><a href="mailto:{{ $company->email1 }}" class="text-[#9a7b4f] font-semibold hover:underline">{{ $company->email1 }}</a></p>@endif
        </div>
        <div class="bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-7 shadow-xs">
          <div class="flex items-center gap-2 text-[11px] font-mono uppercase tracking-widest text-[#9a7b4f] font-semibold mb-4">
            <x-icon name="map-pin" class="w-4 h-4" />
            <span>Showrooms</span>
          </div>
          <ul class="space-y-3 text-sm text-[#374151]">
            <li><strong class="text-[#1a1d24] block">Head Office</strong>{{ $company->address1 ?: 'Mayfair Flagship, 14 Berkeley Square, W1J 6BQ' }}</li>
            @if($company->address2)<li><strong class="text-[#1a1d24] block">Showroom</strong>{{ $company->address2 }}</li>@endif
            @if($company->address3)<li><strong class="text-[#1a1d24] block">Warehouse</strong>{{ $company->address3 }}</li>@endif
            @if(!$company->address2 && !$company->address3)
            <li><strong class="text-[#1a1d24] block">Edinburgh</strong>28 Dundas St, EH3 6JN</li>
            <li><strong class="text-[#1a1d24] block">Chipping Campden</strong>GL55 6AT</li>
            @endif
          </ul>
        </div>
        <div class="bg-[#181b20] text-white rounded-2xl p-6 sm:p-7">
          <h3 class="font-serif text-xl font-semibold">Prefer to configure first?</h3>
          <p class="text-xs text-[#9ca3af] mt-1.5 mb-4">Build your exact footprint and interior, then send it straight to the studio.</p>
          <a @spa href="{{ route('custom-build') }}" class="block w-full py-3 rounded-xl bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs font-semibold uppercase tracking-wider transition-all text-center">Open Factory Configurator</a>
        </div>
      </div>
      <!-- Form -->
      <div class="lg:col-span-7">
        <form onsubmit="return handleContactSubmit(event)" class="bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-9 shadow-xs space-y-4">
          <div id="contact-form-fields" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Full Name *</label>
                <input type="text" id="contact-name" required placeholder="Jane Smith" class="w-full border border-[#e5e2da] rounded-lg p-3 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Email *</label>
                <input type="email" id="contact-email" required placeholder="jane@example.co.uk" class="w-full border border-[#e5e2da] rounded-lg p-3 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none" />
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Topic</label>
                <select id="contact-topic" class="w-full border border-[#e5e2da] rounded-lg p-3 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none bg-white">
                  <option>Factory quote &amp; CAD pack</option>
                  <option>Compliance documents</option>
                  <option>Callback request</option>
                  <option>Showroom visit</option>
                  <option>Something else</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Site Postcode</label>
                <input type="text" id="contact-postcode" placeholder="e.g. GL54 3AA" class="w-full border border-[#e5e2da] rounded-lg p-3 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Project Details *</label>
              <textarea id="contact-message" rows="5" required placeholder="Plot size, intended use, timelines — anything that helps us quote accurately." class="w-full border border-[#e5e2da] rounded-lg p-3 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none"></textarea>
            </div>
            <button type="submit" class="w-full py-3.5 rounded-xl bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all">Send Enquiry</button>
            <p class="text-[11px] text-center text-[#6b7280]">No spam, no obligation — studio reply within one working day.</p>
          </div>
          <div id="contact-form-success" class="hidden text-center py-10">
            <span class="w-14 h-14 rounded-full bg-[#9a7b4f]/10 flex items-center justify-center mx-auto mb-4">
              <x-icon name="check" class="w-6 h-6 text-[#9a7b4f]" />
            </span>
            <h3 class="font-serif text-2xl text-[#1a1d24] font-semibold">Enquiry received.</h3>
            <p class="text-sm text-[#374151] mt-2">Thank you — the studio will reply within one working day.</p>
          </div>
        </form>
      </div>
    </div>
  </section>

  @if($company->google_map)
  <!-- Showroom Map -->
  <section class="py-16 md:py-20 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center gap-2 text-[11px] font-mono uppercase tracking-widest text-[#9a7b4f] font-semibold mb-4">
        <x-icon name="map-pin" class="w-4 h-4" />
        <span>Find the Studio</span>
      </div>
      <div class="contact-map rounded-2xl border border-[#e5e2da] overflow-hidden shadow-xs bg-white">
        <div class="w-full h-[380px] md:h-[440px]">{!! $company->google_map !!}</div>
      </div>
    </div>
  </section>
  <style>.contact-map iframe{width:100%!important;height:100%!important;border:0!important}</style>
  @endif

@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */
    function handleContactSubmit(e) {
      e.preventDefault();
      var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
      var form = e.target;
      var btn = form.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      fetch("{{ route('contact.store') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({
          name: document.getElementById('contact-name').value,
          email: document.getElementById('contact-email').value,
          topic: document.getElementById('contact-topic').value,
          postcode: document.getElementById('contact-postcode').value,
          message: document.getElementById('contact-message').value,
        }),
      }).then(function (r) {
        if (btn) btn.disabled = false;
        if (!r.ok) {
          return r.json().catch(function(){ return {}; }).then(function (d) {
            var msg = d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Please check the form.');
            if (window.showToast) showToast('Something went wrong', msg);
          });
        }
        document.getElementById('contact-form-fields').classList.add('hidden');
        document.getElementById('contact-form-success').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }).catch(function () {
        if (btn) btn.disabled = false;
        if (window.showToast) showToast('Something went wrong', 'Please try again.');
      });
      return false;
    }
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
