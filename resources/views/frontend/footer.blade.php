@php
  $footCats = App\Models\Category::where('status', 1)->orderBy('sort_order')->take(5)->get(['name', 'slug']);
  $footLogo = $company->footer_logo ? asset('uploads/company/' . $company->footer_logo) : ($company->company_logo ? asset('uploads/company/' . $company->company_logo) : asset('resources/frontend-raw/assets/logo.png'));
@endphp
<footer id="showrooms" class="bg-[#181b20] text-white pt-12 pb-6 px-4 sm:px-6 lg:px-10 border-t border-[#2d3139]">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 pb-12 border-b border-white/10">
      <div class="space-y-4 sm:col-span-2 lg:col-span-1">
        <img src="{{ $footLogo }}" alt="Origin Spaces" class="h-11 w-auto object-contain bg-white rounded-lg px-3 py-1.5" />
        <p class="text-xs text-[#9ca3af] leading-relaxed">
          {{ $company->footer_content ?: 'Expandable modular homes manufactured build-to-order and fulfilled directly from our UK Central Distribution Warehouse.' }}
        </p>
        <div class="text-[11px] font-mono text-[#9a7b4f]">Direct Warehouse Dispatch &bull; Nationwide UK Delivery</div>
        @if($company->facebook || $company->instagram || $company->linkedin || $company->youtube || $company->twitter)
        <div class="flex items-center gap-2.5 pt-1">
          @if($company->facebook)<a href="{{ $company->facebook }}" target="_blank" rel="noopener" aria-label="Facebook" class="w-8 h-8 rounded-full border border-white/15 flex items-center justify-center text-[#c9c4b7] hover:text-white hover:border-[#9a7b4f] transition-all"><x-icon name="arrow-up-right" class="w-3.5 h-3.5" /></a>@endif
          @if($company->instagram)<a href="{{ $company->instagram }}" target="_blank" rel="noopener" aria-label="Instagram" class="w-8 h-8 rounded-full border border-white/15 flex items-center justify-center text-[#c9c4b7] hover:text-white hover:border-[#9a7b4f] transition-all"><x-icon name="image" class="w-3.5 h-3.5" /></a>@endif
          @if($company->linkedin)<a href="{{ $company->linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn" class="w-8 h-8 rounded-full border border-white/15 flex items-center justify-center text-[#c9c4b7] hover:text-white hover:border-[#9a7b4f] transition-all"><x-icon name="briefcase" class="w-3.5 h-3.5" /></a>@endif
          @if($company->youtube)<a href="{{ $company->youtube }}" target="_blank" rel="noopener" aria-label="YouTube" class="w-8 h-8 rounded-full border border-white/15 flex items-center justify-center text-[#c9c4b7] hover:text-white hover:border-[#9a7b4f] transition-all"><x-icon name="youtube" class="w-3.5 h-3.5" /></a>@endif
          @if($company->twitter)<a href="{{ $company->twitter }}" target="_blank" rel="noopener" aria-label="X" class="w-8 h-8 rounded-full border border-white/15 flex items-center justify-center text-[#c9c4b7] hover:text-white hover:border-[#9a7b4f] transition-all"><x-icon name="zap" class="w-3.5 h-3.5" /></a>@endif
        </div>
        @endif
      </div>

      <div>
        <span class="text-xs uppercase font-mono tracking-widest text-[#9a7b4f] font-semibold block mb-4">Architecture</span>
        <ul class="space-y-2 text-xs text-[#c9c4b7]">
          <li><a @spa href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a></li>
          <li><a @spa href="{{ route('about') }}" class="hover:text-white transition-colors">About</a></li>
          <li><a @spa href="{{ route('downloads') }}" class="hover:text-white transition-colors">Downloads</a></li>
          <li><a @spa href="{{ route('home') }}#faq-section" class="hover:text-white transition-colors">FAQ</a></li>
          <li><a @spa href="{{ route('gallery') }}" class="hover:text-white transition-colors">Gallery</a></li>
          <li><a @spa href="{{ route('contact') }}" class="hover:text-white transition-colors">Contact</a></li>
        </ul>
      </div>

      <div>
        <span class="text-xs uppercase font-mono tracking-widest text-[#9a7b4f] font-semibold block mb-4">Collections</span>
        <ul class="space-y-2 text-xs text-[#c9c4b7]">
          @forelse($footCats as $fc)
            <li><a @spa href="{{ route('collections', ['category' => $fc->slug]) }}" class="hover:text-white transition-colors">{{ $fc->name }}</a></li>
          @empty
            <li><a @spa href="{{ route('collections') }}" class="hover:text-white transition-colors">The Architectural Kitchen</a></li>
            <li><a @spa href="{{ route('collections') }}" class="hover:text-white transition-colors">Sanctuary &amp; Bath</a></li>
            <li><a @spa href="{{ route('collections') }}" class="hover:text-white transition-colors">Sculptural Illumination</a></li>
            <li><a @spa href="{{ route('collections') }}" class="hover:text-white transition-colors">Architectural Joinery</a></li>
          @endforelse
          <li class="pt-1"><a @spa href="{{ route('collections') }}" class="text-[#9a7b4f] hover:underline font-mono font-semibold block">Dedicated Collection Page &rarr;</a></li>
        </ul>
      </div>

      <div>
        <span class="text-xs uppercase font-mono tracking-widest text-[#9a7b4f] font-semibold block mb-4">UK Applications</span>
        <ul class="space-y-2 text-xs text-[#c9c4b7]">
          <li><a @spa href="{{ route('home') }}#applications-section" class="hover:text-white transition-colors">Garden Annexes (Caravan Act)</a></li>
          <li><a @spa href="{{ route('home') }}#applications-section" class="hover:text-white transition-colors">Acoustic Garden Offices</a></li>
          <li><a @spa href="{{ route('home') }}#applications-section" class="hover:text-white transition-colors">Commercial Coffee Pods</a></li>
          <li><a @spa href="{{ route('home') }}#applications-section" class="hover:text-white transition-colors">Luxury Glamping Retreats</a></li>
        </ul>
      </div>

      <div>
        <span class="text-xs uppercase font-mono tracking-widest text-[#9a7b4f] font-semibold block mb-4">Showrooms &amp; Contact</span>
        <ul class="space-y-2 text-xs text-[#c9c4b7]">
          <li><span class="text-white">{{ $company->address1 ?: 'Mayfair Flagship, 14 Berkeley Square, W1J 6BQ' }}</span></li>
          @if($company->address2)<li><span class="text-white">{{ $company->address2 }}</span></li>@endif
          @if($company->address3)<li><span class="text-white">{{ $company->address3 }}</span></li>@endif
          <li><span class="text-white">Tel {{ $company->phone1 ?: '+44 (0)20 7946 0880' }}, {{ $company->opening_time ?: 'Mon-Sat 10-18' }}</span></li>
          @if($company->email1)<li><a href="mailto:{{ $company->email1 }}" class="hover:text-white transition-colors">{{ $company->email1 }}</a></li>@endif
          @if($company->whatsapp)<li><a href="https://wa.me/{{ $company->whatsapp }}" target="_blank" rel="noopener" class="hover:text-white transition-colors">WhatsApp the studio</a></li>@endif
          <li class="pt-2">
            <button onclick="openEnquiryModal('Direct Callback')" class="text-xs text-[#9a7b4f] hover:underline font-semibold flex items-center gap-1">
              <span>Request Callback / CAD Pack</span>
              <x-icon name="arrow-right" class="w-3 h-3" />
            </button>
          </li>
        </ul>
      </div>
    </div>

    <div class="pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] text-[#6b7280] gap-4">
      <span>&copy; {{ date('Y') }} {{ $company->company_name ?: 'OriginSpaces Modular UK Ltd.' }} {{ $company->copyright ?: 'All rights reserved. Registered in England & Wales.' }}</span>
      <div class="flex items-center gap-4">
        <a @spa href="{{ route('privacy') }}" class="hover:text-[#9ca3af]">Privacy Policy</a>
        <span>&bull;</span>
        <a @spa href="{{ route('terms') }}" class="hover:text-[#9ca3af]">Terms of Supply</a>
      </div>
    </div>
  </div>
</footer>

<!-- Modal: Enquiry / Factory Quote -->
<div id="enquiry-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#181b20]/75 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-[#e5e2da] relative max-h-[90vh] overflow-y-auto">
    <button onclick="closeEnquiryModal()" class="absolute top-4 right-4 text-[#6b7280] hover:text-[#1a1d24] p-1.5 rounded-lg hover:bg-[#f4f2ef]">
      <x-icon name="x" class="w-5 h-5" />
    </button>
    <div class="mb-6">
      <span class="text-[10px] uppercase font-mono tracking-widest text-[#9a7b4f] font-semibold block">OriginSpaces UK Direct</span>
      <h3 id="modal-enquiry-title" class="font-serif text-2xl text-[#1a1d24] font-semibold">Request Factory Quote &amp; CAD Pack</h3>
      <p class="text-xs text-[#6b7280] mt-1">Dispatched and fulfilled directly from our UK central warehouse.</p>
    </div>
    <div id="modal-spec-summary" class="mb-5 p-3.5 bg-[#FAF9F5] border border-[#e5e2da] rounded-xl text-xs space-y-1 hidden">
      <span class="font-semibold text-[#1a1d24] block text-[11px] font-mono uppercase text-[#9a7b4f]">Selected Configuration:</span>
      <div id="modal-spec-details" class="text-[#374151] text-[11px] leading-relaxed"></div>
    </div>
    <form id="enquiry-form" onsubmit="return handleEnquirySubmit(event)" class="space-y-4">
      <input type="hidden" id="enquiry-product-id" value="" />
      <input type="hidden" id="enquiry-config" value="" />
      <input type="hidden" id="enquiry-source" value="modal" />
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Full Name *</label>
        <input type="text" id="enquiry-name" required placeholder="e.g. Eleanor Vance" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Email Address *</label>
          <input type="email" id="enquiry-email" required placeholder="e.g. eleanor@example.co.uk" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Phone Number *</label>
          <input type="tel" id="enquiry-phone" required placeholder="e.g. 07700 900123" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">UK Delivery Postcode / Town</label>
        <input type="text" id="enquiry-postcode" placeholder="e.g. OX14 4SR or Manchester" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Delivery &amp; Installation Preference</label>
        <select id="enquiry-topic" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none bg-white">
          <option>Direct UK Site Delivery with Hiab Crane Offload</option>
          <option>Full White-Glove Warehouse Delivery &amp; Turnkey Unfolding</option>
          <option>Client / Haulier Collection from UK Warehouse Depot</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Project Notes / Ground Conditions (Optional)</label>
        <textarea id="enquiry-message" rows="2" placeholder="e.g. Garden installation on grass, access via 3m side driveway..." class="w-full text-xs px-3.5 py-2 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none"></textarea>
      </div>
      <button type="submit" id="enquiry-submit-btn" class="w-full py-3.5 rounded-xl bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs font-semibold uppercase tracking-wider transition-all shadow-md mt-2">Submit Enquiry to UK Logistics Team</button>
      <span class="text-[10px] text-[#6b7280] text-center block">Your details are protected under GDPR. We respond with complete CAD blueprints within 24 business hours.</span>
    </form>
  </div>
</div>

<!-- Modal: Material Sample Box -->
<div id="sample-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#181b20]/75 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-[#e5e2da] relative">
    <button onclick="closeSampleModal()" class="absolute top-4 right-4 text-[#6b7280] hover:text-[#1a1d24] p-1.5 rounded-lg hover:bg-[#f4f2ef]">
      <x-icon name="x" class="w-5 h-5" />
    </button>
    <div class="text-center mb-6">
      <div class="w-12 h-12 rounded-xl bg-[#FAF9F5] border border-[#9a7b4f]/30 text-[#9a7b4f] flex items-center justify-center mx-auto mb-3">
        <x-icon name="package" class="w-6 h-6" />
      </div>
      <h3 class="font-serif text-2xl text-[#1a1d24] font-semibold">Order Material Sample Box</h3>
      <p class="text-xs text-[#6b7280] mt-1">Receive genuine timber composite swatches, 100mm PIR core cutaway, and double-glazing seal profiles at your UK address.</p>
    </div>
    <form onsubmit="return handleSampleSubmit(event)" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Full Name *</label>
        <input type="text" id="sample-name" required placeholder="e.g. Marcus Hughes" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">UK Postal Address *</label>
        <input type="text" id="sample-address" required placeholder="e.g. 14 Highfield Lane, St Albans, AL1 4DW" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#1a1d24] mb-1">Phone / Mobile for Courier *</label>
        <input type="tel" id="sample-phone" required placeholder="e.g. 07700 900456" class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-[#dcd8cd] focus:border-[#9a7b4f] focus:outline-none" />
      </div>
      <button type="submit" class="w-full py-3.5 rounded-xl bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all shadow-md">Dispatch Free Sample Box (Royal Mail 24)</button>
    </form>
  </div>
</div>

<!-- Global Lightbox -->
<div id="lightbox-modal" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md hidden" onclick="if(window.closeLightbox)closeLightbox()">
  <div class="relative max-w-5xl w-full" onclick="event.stopPropagation()">
    <button onclick="if(window.closeLightbox)closeLightbox()" class="absolute -top-12 right-0 text-white/80 hover:text-white p-2">
      <x-icon name="x" class="w-6 h-6" />
    </button>
    <img id="lightbox-img" src="" alt="Enlarged Architectural Detail" class="w-full max-h-[76vh] object-contain rounded-xl shadow-2xl" style="width:100%;max-height:76vh;object-fit:contain;display:block;background:transparent" />
    <button onclick="if(window.lightboxNav)lightboxNav(-1)" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-[#9a7b4f] text-white border border-white/20 flex items-center justify-center backdrop-blur-md transition-all">
      <x-icon name="chevron-left" class="w-5 h-5" />
    </button>
    <button onclick="if(window.lightboxNav)lightboxNav(1)" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/10 hover:bg-[#9a7b4f] text-white border border-white/20 flex items-center justify-center backdrop-blur-md transition-all">
      <x-icon name="chevron-right" class="w-5 h-5" />
    </button>
    <div class="mt-3 flex items-center justify-between text-xs">
      <span id="lightbox-caption" class="text-white/85 font-medium"></span>
      <span id="lightbox-counter" class="text-white/50 font-mono"></span>
    </div>
  </div>
</div>

<!-- Global Toast -->
<div id="toast-notification" class="fixed bottom-6 right-6 z-[60] bg-[#181b20] text-white px-5 py-3.5 rounded-xl shadow-2xl border border-[#9a7b4f]/40 flex items-center gap-3 transform translate-y-24 opacity-0 transition-all duration-300">
  <div class="w-7 h-7 rounded-full bg-[#9a7b4f] flex items-center justify-center text-white shrink-0">
    <x-icon name="check" class="w-4 h-4" />
  </div>
  <div class="text-xs">
    <span id="toast-title" class="font-semibold block text-white">Submitted Successfully</span>
    <span id="toast-message" class="text-[#9ca3af] block">Our team will be in touch shortly.</span>
  </div>
</div>

<script>
(function () {
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  window.showToast = function (title, message) {
    var t = document.getElementById('toast-notification');
    if (!t) return;
    document.getElementById('toast-title').textContent = title || 'Submitted Successfully';
    document.getElementById('toast-message').textContent = message || 'Our team will be in touch shortly.';
    t.classList.remove('translate-y-24', 'opacity-0');
    if (window.lucide) lucide.createIcons();
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(function(){ t.classList.add('translate-y-24', 'opacity-0'); }, 4500);
  };

  window.openEnquiryModal = function (title, productId, configSummary) {
    function setVal(id, v) { var el = document.getElementById(id); if (el) el.value = v; }
    function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
    if (title) setText('modal-enquiry-title', title);
    setVal('enquiry-product-id', productId || '');
    setVal('enquiry-config', configSummary || '');
    setVal('enquiry-source', productId ? 'details' : 'modal');
    var box = document.getElementById('modal-spec-summary');
    if (box) {
      if (configSummary) {
        box.classList.remove('hidden');
        setText('modal-spec-details', configSummary);
      } else {
        box.classList.add('hidden');
      }
    }
    var modal = document.getElementById('enquiry-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
  };
  window.closeEnquiryModal = function () {
    document.getElementById('enquiry-modal').classList.add('hidden');
    document.body.style.overflow = '';
  };
  window.__footerOpenEnquiry = window.openEnquiryModal;
  window.openSampleModal = function () {
    document.getElementById('sample-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
  };
  window.closeSampleModal = function () {
    document.getElementById('sample-modal').classList.add('hidden');
    document.body.style.overflow = '';
  };

  function postEnquiry(payload, onOk) {
    fetch("{{ route('enquiries.store') }}", {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
    .then(function (res) {
      if (!res.ok) {
        var msg = res.d.message || (res.d.errors ? Object.values(res.d.errors)[0][0] : 'Please check the form.');
        showToast('Something went wrong', msg);
        return;
      }
      onOk();
    }).catch(function () { showToast('Something went wrong', 'Please try again.'); });
  }

  window.handleEnquirySubmit = function (e) {
    e.preventDefault();
    var btn = document.getElementById('enquiry-submit-btn');
    if (btn) btn.disabled = true;
    postEnquiry({
      name: document.getElementById('enquiry-name').value,
      email: document.getElementById('enquiry-email').value,
      phone: document.getElementById('enquiry-phone').value,
      postcode: document.getElementById('enquiry-postcode').value,
      topic: document.getElementById('enquiry-topic').value,
      message: document.getElementById('enquiry-message').value,
      product_id: document.getElementById('enquiry-product-id').value || null,
      config_summary: document.getElementById('enquiry-config').value || null,
      source_page: document.getElementById('enquiry-source').value,
    }, function () {
      if (btn) btn.disabled = false;
      document.getElementById('enquiry-form').reset();
      closeEnquiryModal();
      showToast('Enquiry received', 'Thank you — the studio will reply within one working day.');
    });
    return false;
  };

  window.handleSampleSubmit = function (e) {
    e.preventDefault();
    postEnquiry({
      name: document.getElementById('sample-name').value,
      phone: document.getElementById('sample-phone').value,
      topic: 'Material Sample Box Request',
      message: 'Sample box delivery address: ' + document.getElementById('sample-address').value,
      source_page: 'sample',
    }, function () {
      e.target.reset();
      closeSampleModal();
      showToast('Sample box requested', 'Dispatched via Royal Mail 24.');
    });
    return false;
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeEnquiryModal(); closeSampleModal(); }
  });
})();
</script>
