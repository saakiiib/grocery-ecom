@extends('frontend.layout')
@section('title', 'Product Details | OriginSpaces Modular UK')
@section('content')


  <!-- Breadcrumb -->
  <div class="bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-10 py-3.5 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2 text-xs text-[#6b7280]">
        <a @spa href="{{ route('collections') }}" class="font-mono uppercase text-[#9a7b4f] font-semibold tracking-wider hover:underline flex items-center gap-1">
          <x-icon name="arrow-left" class="w-3.5 h-3.5" />
          <span>Catalogue View</span>
        </a>
        <span>&bull;</span>
        <span id="crumb-category" class="text-[#374151] font-medium">Kitchen</span>
        <span>&bull;</span>
        <span id="crumb-name" class="text-[#1a1d24] font-semibold">—</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs text-[#6b7280] font-mono">Model Ref: <strong id="crumb-model" class="text-[#1a1d24]">—</strong></span>
        <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#F0EBE1] text-[#786348] text-xs font-mono font-medium border border-[#DDD5C5]">
          <x-icon name="shield-check" class="w-3.5 h-3.5 text-[#9a7b4f]" />
          CE &amp; UK Part L Certified
        </span>
      </div>
    </div>
  </div>

  <!-- Main Split -->
  <main class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
    <div class="flex flex-col lg:flex-row items-start gap-8">

      <!-- LEFT: Configurator -->
      <aside class="w-full lg:w-[36%] xl:w-[34%] flex flex-col gap-5 lg:sticky lg:top-24">
        <div class="bg-white border border-[#e5e2da] rounded-2xl p-5 shadow-xs space-y-4">
          <div>
            <div class="flex items-center justify-between gap-2 mb-1.5">
              <span class="text-[11px] font-mono uppercase tracking-[0.18em] text-[#9a7b4f] font-bold">Turnkey Architectural Specification</span>
              <span id="spec-leadtime" class="text-[11px] font-mono px-2 py-0.5 bg-[#F4F1EA] text-[#6b7280] rounded whitespace-nowrap">—</span>
            </div>
            <h1 id="spec-title" class="font-serif text-2xl sm:text-3xl text-[#1a1d24] font-semibold tracking-tight leading-tight">—</h1>
            <p id="spec-tagline" class="text-sm text-[#374151] mt-1.5 font-semibold leading-relaxed">—</p>
          </div>

          <!-- 1. Model -->
          <div class="pt-3 border-t border-[#EAE7DF] space-y-2">
            <label class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold flex items-center justify-between">
              <span>1. Current Model</span>
            </label>
            <div id="spec-model-list"></div>
          </div>

          <!-- 2. Configuration -->
          <div class="pt-3 border-t border-[#EAE7DF] space-y-2">
            <label class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold">2. Configuration / Footprint</label>
            <div class="grid grid-cols-2 gap-2" id="spec-config-list"></div>
          </div>

          <!-- 3. Finish -->
          <div class="pt-3 border-t border-[#EAE7DF] space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold">3. Exterior / Primary Finish</label>
              <span id="spec-finish-label" class="text-[11px] font-mono font-semibold text-[#9a7b4f]">—</span>
            </div>
            <div class="grid grid-cols-2 gap-2" id="spec-finish-list"></div>
          </div>

          <!-- 4. Glazing -->
          <div class="pt-3 border-t border-[#EAE7DF] space-y-2">
            <label class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold">4. Glazing &amp; Metal Accents</label>
            <div class="space-y-2" id="spec-glazing-list"></div>
          </div>

          <!-- 5. Upgrades -->
          <div class="pt-3 border-t border-[#EAE7DF] space-y-2">
            <label class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold">5. Optional Technology &amp; Upgrades</label>
            <div class="space-y-2" id="spec-upgrade-list"></div>
          </div>

          <!-- 6. Tech specs -->
          <div class="pt-3 border-t border-[#EAE7DF]">
            <button onclick="toggleSpecAccordion('spec-tech')" class="w-full flex items-center justify-between py-0.5 text-xs font-mono uppercase tracking-[0.15em] text-[#1a1d24] font-bold">
              <span class="flex items-center gap-2">
                <x-icon name="paperclip" class="w-3.5 h-3.5 text-[#9a7b4f]" />
                <span>6. Technical Specifications</span>
              </span>
              <x-icon name="chevron-down" id="spec-tech-chevron" class="w-4 h-4 text-[#9a7b4f] transition-transform" />
            </button>
            <div id="spec-tech" class="hidden mt-2.5 bg-[#FAF8F5] rounded-xl border border-[#e5e2da] px-4 py-2 text-xs text-[#374151]"></div>
          </div>

          <!-- 7. Downloads -->
          <div class="pt-3 border-t border-[#EAE7DF]">
            <button onclick="toggleSpecAccordion('spec-docs')" class="w-full flex items-center justify-between py-0.5 text-xs font-mono uppercase tracking-[0.15em] text-[#1a1d24] font-bold">
              <span class="flex items-center gap-2">
                <x-icon name="book-open" class="w-3.5 h-3.5 text-[#9a7b4f]" />
                <span>7. Downloads &amp; Manuals</span>
              </span>
              <x-icon name="chevron-down" id="spec-docs-chevron" class="w-4 h-4 text-[#9a7b4f] transition-transform" />
            </button>
            <div id="spec-docs" class="hidden mt-2.5 space-y-2"></div>
          </div>

          <!-- 8. Pricing -->
          <div class="pt-5 border-t border-[#EAE7DF] space-y-4">
            <div class="bg-[#FAF8F5] p-4 rounded-xl border border-[#DDD7C9]">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-[11px] font-mono uppercase text-[#786348] font-bold block">Estimated Specifier Total</span>
                  <span id="spec-total" class="text-2xl font-serif text-[#1a1d24] font-bold">—</span>
                </div>
                <span class="text-xs text-right text-[#6b7280]">Excl. VAT<br />UK Delivery</span>
              </div>
              <div id="spec-breakdown" class="mt-3 pt-3 border-t border-[#EAE7DF] space-y-1 text-[11px] font-mono text-[#6b7280]"></div>
            </div>
            <button type="button" id="spec-cta" onclick='openEnquiryModal(@json($product->name . ' — Spec Pack'), {{ $product->id }}, "")' class="w-full py-3.5 px-6 bg-[#181B20] hover:bg-[#9A7B4F] text-white text-sm font-semibold tracking-wider uppercase transition-all duration-200 rounded-xl shadow-md flex items-center justify-center gap-2">
              <span>Request Information &amp; Spec Pack</span>
              <x-icon name="arrow-right" class="w-4 h-4" />
            </button>
            <p class="text-[11px] text-center text-[#6b7280]">Includes personalised drawing set &amp; site feasibility review.</p>
          </div>
        </div>
      </aside>

      <!-- RIGHT: Viewer -->
      <section class="w-full lg:w-[64%] xl:w-[66%] flex flex-col gap-5 min-w-0 mt-8 scroll-mt-28">
        <!-- Media tabs -->
        <div class="bg-white border border-[#e5e2da] rounded-xl p-2 shadow-xs flex items-center justify-between gap-2 overflow-x-auto">
          <div class="flex items-center gap-1 sm:gap-2">
            <button id="tab-btn-3d" onclick="switchMediaTab('3d')" class="px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 bg-transparent text-[#4b5563] hover:text-[#1a1d24] hover:bg-[#F4F1EA]">
              <x-icon name="box" class="w-4 h-4 text-gray-400" />
              <span class="hidden min-[420px]:inline">3D Interactive</span>
            </button>
            <button id="tab-btn-video" onclick="switchMediaTab('video')" class="px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 bg-[#181B20] text-white shadow">
              <x-icon name="video" class="w-4 h-4 text-[#c5a880]" />
              <span class="hidden min-[420px]:inline">Video</span>
            </button>
            <button id="tab-btn-gallery" onclick="switchMediaTab('gallery')" class="px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 bg-transparent text-[#4b5563] hover:text-[#1a1d24] hover:bg-[#F4F1EA]">
              <x-icon name="image" class="w-4 h-4 text-gray-400" />
              <span id="gallery-tab-label" class="hidden min-[420px]:inline">Gallery</span>
            </button>
            <button id="tab-btn-floor" onclick="switchMediaTab('floor')" class="px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 bg-transparent text-[#4b5563] hover:text-[#1a1d24] hover:bg-[#F4F1EA]">
              <x-icon name="layout" class="w-4 h-4 text-gray-400" />
              <span class="hidden min-[420px]:inline">Floor Plan</span>
            </button>
          </div>
          <div class="hidden md:block text-xs text-[#786348] font-mono pr-2 shrink-0">Selected: <strong id="viewer-finish-name" class="text-[#1a1d24]">—</strong></div>
        </div>

        <!-- Video stage -->
        <div id="media-video" class="bg-white border border-[#e5e2da] rounded-2xl overflow-hidden shadow-xs">
          <div class="relative w-full bg-black aspect-video">
            @if(!empty($videoEmbed))
            <iframe id="spec-video-frame" src="{{ $videoEmbed }}" title="Product video" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @else
            <video id="spec-video" class="w-full h-full object-cover" playsinline preload="metadata" controls muted loop autoplay></video>
            <button id="spec-unmute-overlay" onclick="unmuteSpecVideo()" class="absolute top-4 left-4 z-20 flex items-center gap-2 bg-white/95 hover:bg-[#9a7b4f] text-[#1a1d24] hover:text-white border border-[#e5e2da] px-3.5 py-2 font-mono text-[11px] uppercase tracking-wider transition-all shadow-md rounded">
              <x-icon name="volume-2" class="w-4 h-4" />
              <span>Click for sound</span>
            </button>
            @endif
          </div>
          <div class="p-5 sm:p-6">
            <h2 id="video-title" class="font-serif text-xl sm:text-2xl text-[#1a1d24] font-semibold">—</h2>
            <p id="video-desc" class="text-[13px] text-[#374151] mt-1">—</p>
            @if(empty($videoEmbed))
            <div class="mt-4" id="video-chapters-wrap">
              <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#9a7b4f] font-bold block mb-2">Key Chapters — Jump to Phase</span>
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-2" id="video-chapters"></div>
            </div>
            @endif
          </div>
        </div>

        <!-- Gallery stage -->
        <div id="media-gallery" class="hidden bg-white border border-[#e5e2da] rounded-2xl overflow-hidden shadow-xs p-4 sm:p-6">
          <div class="relative rounded-xl overflow-hidden border border-[#e5e2da]" id="gallery-fullscreen-wrap">
            <img id="gallery-main" src="" alt="Product gallery view" class="w-full h-auto block" style="width:100%;height:auto;display:block" />
            <span id="gallery-cat-chip" class="absolute top-3 left-3 px-2.5 py-1 rounded bg-[#181b20]/80 backdrop-blur text-white text-[10px] font-mono uppercase tracking-widest">—</span>
            <div class="absolute top-3 right-3 flex items-center gap-2">
              <span id="gallery-counter" class="px-2.5 py-1 rounded bg-[#181b20]/80 backdrop-blur text-white text-[10px] font-mono">1 / 1</span>
              <button onclick="expandGallery()" title="Fullscreen" class="w-8 h-8 rounded-lg bg-white/95 hover:bg-[#9a7b4f] hover:text-white text-[#1a1d24] flex items-center justify-center transition-all shadow">
                <x-icon name="maximize-2" class="w-4 h-4" />
              </button>
            </div>
            <button onclick="toggleHotspots()" id="hotspot-toggle" class="absolute bottom-3 right-3 px-3 py-1.5 rounded-lg bg-[#181b20]/80 backdrop-blur text-white text-[11px] font-mono uppercase tracking-wider">Hotspots: On</button>
            <div id="gallery-pins" class="absolute inset-0"></div>
            <div id="gallery-caption" class="absolute bottom-0 left-0 right-0 px-4 pt-8 pb-3 bg-gradient-to-t from-black/70 to-transparent text-white text-xs"></div>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-2" id="gallery-plates"></div>
          <div class="mt-3 grid grid-cols-3 sm:grid-cols-4 gap-2" id="gallery-thumbs"></div>
        </div>

        <!-- Floor plan stage -->
        <div id="media-floor" class="hidden bg-white border border-[#e5e2da] rounded-2xl overflow-hidden shadow-xs p-4 sm:p-6">
          <div class="relative rounded-xl overflow-hidden border border-[#2d3139] bg-[#14171c]">
            <span class="absolute top-3 left-3 px-2.5 py-1 rounded bg-white/10 backdrop-blur text-white text-[10px] font-mono uppercase tracking-widest border border-white/15">Ref: <span id="floor-ref">—</span></span>
            <span class="absolute top-3 right-3 px-2.5 py-1 rounded bg-white/10 backdrop-blur text-white text-[10px] font-mono uppercase tracking-widest border border-white/15">Units: Metric (m²)</span>
            <svg id="floor-svg" viewBox="0 0 640 360" class="w-full h-auto block"></svg>
          </div>
          <div class="mt-4">
            <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#9a7b4f] font-bold block mb-1">Active Zone Breakdown</span>
            <h4 id="floor-zone-name" class="font-serif text-xl text-[#1a1d24] font-semibold">—</h4>
            <p id="floor-zone-desc" class="text-xs text-[#374151] mt-1">—</p>
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2" id="floor-stats"></div>
          </div>
        </div>

        <!-- 3D stage -->
        <div id="media-3d" class="hidden bg-white border border-[#e5e2da] rounded-2xl overflow-hidden shadow-xs p-4 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="font-serif text-xl text-[#1a1d24] font-semibold">Interactive Massing Model</h3>
              <p class="text-xs text-[#6b7280]">Drag to orbit &middot; scroll to zoom &middot; explode to inspect layers</p>
            </div>
            <div class="flex items-center gap-3 text-xs flex-wrap">
              <label class="flex items-center gap-1.5 text-[#374151] font-medium">
                <input type="checkbox" id="model3d-spin" checked onchange="toggleSpin()" class="accent-[#9a7b4f]" /> Auto-rotate
              </label>
              <label class="flex items-center gap-1.5 text-[#374151] font-medium" id="model3d-unfold-wrap">
                Unfold <input type="range" id="model3d-unfold" min="0" max="100" value="100" oninput="unfoldModel(this.value)" class="w-24 accent-[#9a7b4f]" />
              </label>
              <label class="flex items-center gap-1.5 text-[#374151] font-medium" id="model3d-explode-wrap">
                Explode <input type="range" id="model3d-explode" min="0" max="70" value="0" oninput="explodeModel(this.value)" class="w-24 accent-[#9a7b4f]" />
              </label>
            </div>
          </div>
          <div id="model3d-stage" class="model3d-scene relative w-full h-[380px] sm:h-[440px] rounded-xl bg-gradient-to-b from-[#FAF9F5] to-[#EDEAE2] border border-[#e5e2da] overflow-hidden">
            <div class="absolute bottom-3 left-4 text-[10px] font-mono uppercase tracking-widest text-[#6b7280] z-10">Drag to orbit &middot; Scroll to zoom</div>
            <div class="absolute bottom-3 right-4 text-[10px] font-mono uppercase tracking-widest text-[#9a7b4f] font-semibold z-10" id="model3d-label">—</div>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-white border border-[#e5e2da] rounded-2xl p-6 sm:p-8 shadow-xs">
          <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-3">Architectural Heritage &amp; Monocoque Engineering</span>
          <h2 id="narrative-title" class="font-serif text-2xl sm:text-3xl text-[#1a1d24] font-semibold mb-3">—</h2>
          <div id="narrative-body" class="text-sm text-[#374151] leading-relaxed">—</div>
          <ul id="narrative-specs" class="mt-4 space-y-2"></ul>
        </div>

        <!-- Spec tables -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="bg-white border border-[#e5e2da] rounded-2xl p-6 shadow-xs">
            <h3 class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold mb-4">Envelope &amp; Dimensions</h3>
            <div id="spec-table-envelope" class="space-y-2.5 text-xs"></div>
          </div>
          <div class="bg-white border border-[#e5e2da] rounded-2xl p-6 shadow-xs">
            <h3 class="text-xs font-mono uppercase tracking-wider text-[#1a1d24] font-bold mb-4">Systems &amp; Assurance</h3>
            <div id="spec-table-systems" class="space-y-2.5 text-xs"></div>
          </div>
        </div>

        <!-- Logistics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="logistics-row"></div>

        <!-- Related pieces -->
        @if(count($relatedJson))
        <div class="mt-4">
          <div class="flex items-end justify-between mb-5">
            <h3 class="font-serif text-2xl sm:text-3xl text-[#1a1d24] font-semibold">Continue Specifying</h3>
            <a @spa href="{{ route('collections') }}" class="text-[11px] font-mono uppercase tracking-wider text-[#6b7280] hover:text-[#9a7b4f] font-semibold">View All &rarr;</a>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($relatedJson as $rel)
              <a @spa href="/product/{{ $rel['slug'] }}" class="group bg-white rounded-2xl border border-[#e5e2da] overflow-hidden hover:shadow-xl transition-all">
                <div class="relative overflow-hidden">
                  <img src="{{ $rel['heroImage'] }}" alt="{{ $rel['name'] }}" loading="lazy" class="w-full aspect-[4/3] object-cover transition-transform duration-700 group-hover:scale-105" />
                  <span class="absolute top-3 left-3 px-2.5 py-1 bg-white/90 backdrop-blur text-[10px] font-mono rounded shadow">{{ $rel['modelCode'] }}</span>
                </div>
                <div class="p-4">
                  <span class="text-[10px] font-mono uppercase tracking-[0.2em] text-[#9a7b4f] font-semibold">{{ $rel['category'] }}</span>
                  <h4 class="font-serif text-lg text-[#1a1d24] font-semibold leading-snug mt-1">{{ $rel['name'] }}</h4>
                  <span class="font-mono text-[#9a7b4f] font-bold text-xs mt-2 block">{{ $rel['price'] }}</span>
                </div>
              </a>
            @endforeach
          </div>
        </div>
        @endif
      </section>
    </div>
  </main>

@endsection
@section('style')
<script defer src="https://unpkg.com/three@0.147.0/build/three.min.js"></script>
<script defer src="https://unpkg.com/three@0.147.0/examples/js/controls/OrbitControls.js"></script>
<script defer src="https://unpkg.com/three@0.147.0/examples/js/loaders/GLTFLoader.js"></script>
<style>
/* Gallery stage stays above the sticky site header (z-40) but below popups
   (enquiry modal z-50, lightbox z-70) so they can cover it */
#media-gallery{position:relative;z-index:45}
/* Fullscreen gallery: fixed stage, whole photo fitted */
#media-gallery:fullscreen{background:#0b0d10;border:none;border-radius:0;padding:1rem;overflow-y:auto}
#media-gallery:fullscreen #gallery-fullscreen-wrap{height:calc(100vh - 230px);min-height:320px}
#media-gallery:fullscreen #gallery-main{height:100% !important;width:auto !important;max-width:100%;margin:0 auto;object-fit:contain;background:#000}
</style>
@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */

    var PRODUCTS = []; // single product injected below

    var CATEGORY_VIDEO = {}; // video comes server-side

    var CONFIG_OPTIONS = @json($optionsJson["config"]);
    var FINISH_OPTIONS = @json($optionsJson["finish"]);
    var GLAZING_OPTIONS = @json($optionsJson["glazing"]);
    var UPGRADE_OPTIONS = @json($optionsJson["upgrade"]);

    var currentProduct = null;
    var selConfig = (CONFIG_OPTIONS.find(o => o.default) || CONFIG_OPTIONS[0] || {}).id || null;
    var selFinish = (FINISH_OPTIONS.find(o => o.default) || FINISH_OPTIONS[0] || {}).id || null;
    var selGlazing = (GLAZING_OPTIONS.find(o => o.default) || GLAZING_OPTIONS[0] || {}).id || null;
    var selUpgrades = [];
    var activeMediaTab = 'video';
    var galleryIdx = 0;
    var hotspotsOn = true;

    function parseBasePrice(p) {
      const m = String(p.price || '').replace(/[^0-9]/g, '');
      return m ? parseInt(m, 10) : 0;
    }
    function gbp(n) {
      return '£' + n.toLocaleString('en-GB');
    }

    function initProduct() {
      currentProduct = @json($productJson);
      if (!currentProduct) return;
      renderAll();
      // Hide media tabs with no content (server-driven).
      if (!currentProduct.video) {
        var vb = document.getElementById('tab-btn-video');
        var vm = document.getElementById('media-video');
        if (vb) vb.style.display = 'none';
        if (vm) vm.classList.add('hidden');
        if (activeMediaTab === 'video') switchMediaTab('gallery');
      }
      if (!currentProduct.show3d) {
        var b3 = document.getElementById('tab-btn-3d');
        var m3 = document.getElementById('media-3d');
        if (b3) b3.style.display = 'none';
        if (m3) m3.classList.add('hidden');
        if (activeMediaTab === '3d') switchMediaTab('gallery');
      }
    }

    function pillBtn(active) {
      return active
        ? 'border-[#9a7b4f] bg-[#FAF8F5] ring-1 ring-[#9a7b4f]'
        : 'border-[#e5e2da] bg-white hover:border-[#C4BEB1]';
    }

    function renderAll() {
      renderHeader();
      renderModels();
      renderOptions();
      renderTech();
      renderDocs();
      renderPricing();
      renderMedia();
      renderNarrative();
      renderTables();
      renderLogistics();
      renderFloor();
      buildCube();
      if (window.lucide) lucide.createIcons();
    }

    function renderHeader() {
      const p = currentProduct;
      document.getElementById('crumb-category').textContent = p.category;
      document.getElementById('crumb-name').textContent = p.name;
      document.getElementById('crumb-model').textContent = p.modelCode;
      document.getElementById('spec-leadtime').textContent = p.leadTime || '—';
      document.getElementById('spec-title').textContent = p.name;
      document.getElementById('spec-tagline').textContent = p.tagline;
      document.title = p.name + ' | OriginSpaces Modular UK';
    }

    function renderModels() {
      const p = currentProduct;
      document.getElementById('spec-model-list').innerHTML = `
        <div class="p-2.5 rounded-lg border bg-[#FAF8F5] border-[#9a7b4f] ring-1 ring-[#9a7b4f] shadow-sm flex items-center gap-2.5">
          <img src="${p.heroImage.replace('w=1200', 'w=200')}" alt="${p.name}" loading="lazy" class="w-14 h-11 object-cover rounded border border-[#e5e2da] shrink-0" />
          <div class="min-w-0">
            <div class="text-[13px] font-semibold text-[#1a1d24] leading-snug">${p.name}</div>
            <div class="text-[11px] text-[#6b7280] font-mono">${p.modelCode} &middot; ${p.price || ''}</div>
          </div>
        </div>`;
    }

    function renderOptions() {
      document.getElementById('spec-config-list').innerHTML = CONFIG_OPTIONS.map(o => `
        <button onclick="setDetailOpt('config','${o.id}')" class="p-2.5 text-left rounded-lg border text-xs transition-all ${selConfig === o.id ? pillBtn(true) : pillBtn(false)}">
          <div class="font-semibold text-[#1a1d24]">${o.name}</div>
          <div class="text-[11px] ${o.price ? 'text-[#9a7b4f] font-bold' : 'text-[#6b7280]'}">${o.price ? '+' + gbp(o.price) : o.sub}</div>
        </button>`).join('');
      const f = FINISH_OPTIONS.find(x => x.id === selFinish);
      document.getElementById('spec-finish-label').textContent = f ? f.name.toUpperCase() : '—';
      document.getElementById('viewer-finish-name').textContent = f ? f.name : '—';
      document.getElementById('spec-finish-list').innerHTML = FINISH_OPTIONS.map(o => `
        <button onclick="setDetailOpt('finish','${o.id}')" class="p-2.5 rounded-lg border flex items-center gap-2 text-left transition-all ${selFinish === o.id ? pillBtn(true) : pillBtn(false)}">
          <span class="w-5 h-5 rounded-full border border-black/20 shrink-0" style="background-color:${o.swatch};"></span>
          <span class="min-w-0">
            <span class="block text-xs font-semibold text-[#1a1d24] truncate">${o.name}</span>
            <span class="block text-[10px] ${o.price ? 'text-[#9a7b4f] font-bold' : 'text-[#6b7280]'}">${o.price ? '+' + gbp(o.price) : o.sub}</span>
          </span>
        </button>`).join('');
      document.getElementById('spec-glazing-list').innerHTML = GLAZING_OPTIONS.map(o => {
        const on = selGlazing === o.id;
        return `
        <button onclick="setDetailOpt('glazing','${o.id}')" class="w-full p-2.5 px-3 text-left rounded-lg border flex items-center justify-between gap-2 transition-all ${on ? pillBtn(true) : pillBtn(false)}">
          <span>
            <span class="block text-xs font-semibold text-[#1a1d24]">${o.name}</span>
            <span class="block text-[11px] text-[#6b7280]">${o.sub}</span>
          </span>
          <span class="text-xs font-mono text-[#9a7b4f] font-bold shrink-0">${o.price ? '+' + gbp(o.price) : 'Included'}</span>
        </button>`;
      }).join('');
      document.getElementById('spec-upgrade-list').innerHTML = UPGRADE_OPTIONS.map(o => {
        const on = selUpgrades.includes(o.id);
        return `
        <button onclick="toggleDetailUpgrade('${o.id}')" class="w-full p-2.5 px-3 text-left rounded-lg border flex items-center justify-between gap-2 transition-all ${on ? pillBtn(true) : pillBtn(false)}">
          <span>
            <span class="block text-xs font-semibold text-[#1a1d24]">${o.name}</span>
            <span class="block text-[11px] text-[#6b7280]">${o.sub}</span>
          </span>
          <span class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-mono text-[#9a7b4f] font-bold">${o.price ? '+' + gbp(o.price) : 'Included'}</span>
            <span class="w-5 h-5 rounded-md border ${on ? 'bg-[#9a7b4f] border-[#9a7b4f] text-white' : 'border-[#C4BEB1] text-transparent'} flex items-center justify-center text-xs">✓</span>
          </span>
        </button>`;
      }).join('');
    }

    function setDetailOpt(group, id) {
      if (group === 'config') selConfig = id;
      if (group === 'finish') selFinish = id;
      if (group === 'glazing') selGlazing = id;
      renderOptions();
      renderPricing();
      if (window.lucide) lucide.createIcons();
    }

    function toggleDetailUpgrade(id) {
      selUpgrades = selUpgrades.includes(id) ? selUpgrades.filter(x => x !== id) : [...selUpgrades, id];
      renderOptions();
      renderPricing();
      if (window.lucide) lucide.createIcons();
    }

    function currentTotal() {
      const base = parseBasePrice(currentProduct);
      const c = CONFIG_OPTIONS.find(x => x.id === selConfig);
      const f = FINISH_OPTIONS.find(x => x.id === selFinish);
      const g = GLAZING_OPTIONS.find(x => x.id === selGlazing);
      const u = UPGRADE_OPTIONS.filter(x => selUpgrades.includes(x.id)).reduce((s, x) => s + x.price, 0);
      return { base, total: base + (c ? c.price : 0) + (f ? f.price : 0) + (g ? g.price : 0) + u };
    }

    function renderPricing() {
      const t = currentTotal();
      document.getElementById('spec-total').textContent = gbp(t.total);
      const rows = [];
      const c = CONFIG_OPTIONS.find(x => x.id === selConfig);
      const f = FINISH_OPTIONS.find(x => x.id === selFinish);
      const g = GLAZING_OPTIONS.find(x => x.id === selGlazing);
      rows.push(['Base — ' + currentProduct.modelCode, t.base]);
      if (c && c.price) rows.push([c.name, c.price]);
      if (f && f.price) rows.push([f.name, f.price]);
      if (g && g.price) rows.push([g.name, g.price]);
      UPGRADE_OPTIONS.filter(x => selUpgrades.includes(x.id)).forEach(x => rows.push([x.name, x.price]));
      document.getElementById('spec-breakdown').innerHTML = rows.map(([n, v]) =>
        `<div class="flex justify-between gap-2"><span class="truncate">${n}</span><span class="font-semibold text-[#1a1d24] shrink-0">${gbp(v)}</span></div>`
      ).join('');
    }

    function toggleSpecAccordion(id) {
      const el = document.getElementById(id);
      const chev = document.getElementById(id + '-chevron');
      if (!el) return;
      el.classList.toggle('hidden');
      if (chev) chev.classList.toggle('rotate-180');
    }

    function renderTech() {
      const p = currentProduct;
      const rows = [
        ['Model Reference', p.modelCode, false],
        ['Expanded Dimensions', p.dimensions || '—', false],
        ['Primary Materials', (p.materials || []).slice(0, 2).join(', ') || '—', false],
      ].concat((p.tech || []).map(t => [t.label, t.value, !!t.highlight])).concat([
        ['Lead Time', p.leadTime || '—', false],
        ['Warranty Standard', p.warranty || '10-Year UK cover', false]
      ]);
      document.getElementById('spec-tech').innerHTML = rows.map(([k, v, gold]) =>
        `<div class="flex justify-between gap-3 py-1.5 border-b border-[#EAE6DC] last:border-0"><span class="text-[#6b7280] shrink-0">${k}:</span><span class="font-mono font-bold text-right ${gold ? 'text-[#9a7b4f]' : 'text-[#1a1d24]'}">${v}</span></div>`
      ).join('');
    }

    function renderDocs() {
      const p = currentProduct;
      const docs = (p.docs || []).map(d => ({ t: d.title, s: 'Issued by studio', url: d.url }));
      if (!docs.length) {
        document.getElementById('spec-docs').innerHTML = `
        <div class="p-3 bg-[#FAF8F5] border border-[#e5e2da] rounded-lg flex items-center justify-between gap-3">
          <div class="text-xs text-[#6b7280]">Full drawing set issued with every enquiry — browse the library.</div>
          <a @spa href="{{ route('downloads') }}" class="text-xs text-[#374151] hover:text-[#9a7b4f] font-medium transition-colors shrink-0">Open Library</a>
        </div>`;
        return;
      }
      document.getElementById('spec-docs').innerHTML = docs.map(d => `
        <div class="p-3 bg-[#FAF8F5] border border-[#e5e2da] rounded-lg flex items-center justify-between gap-3">
          <div class="flex items-center gap-2.5 min-w-0">
            <x-icon name="book-open" class="w-4 h-4 text-[#9a7b4f] shrink-0" />
            <div class="min-w-0">
              <div class="font-serif text-[15px] font-semibold text-[#1a1d24] truncate">${d.t}</div>
              <div class="text-[11px] text-[#6b7280]">${d.s}</div>
            </div>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <a @spa href="{{ route('downloads') }}" class="text-xs text-[#374151] hover:text-[#9a7b4f] font-medium transition-colors">Read</a>
            <a href="${d.url}" target="_blank" class="text-[#9a7b4f] hover:text-[#866940] transition-colors">
              <x-icon name="download" class="w-4 h-4" />
            </a>
          </div>
        </div>`).join('');
    }

    function downloadSpecFile(kind) {
      const p = currentProduct;
      const body = ['OriginSpaces Specification Request', '', 'Product: ' + p.name, 'Model: ' + p.modelCode, 'Document: ' + (kind === 'lookbook' ? 'Architectural Lookbook & CAD' : 'Technical Specification Sheet'), 'Configured total: ' + gbp(currentTotal().total) + ' (excl. VAT)', '', 'The studio will issue this file within one working day.'].join('\n');
      const blob = new Blob([body], { type: 'text/plain' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = p.modelCode.replace(/[^A-Za-z0-9]+/g, '_') + '_' + kind + '.txt';
      document.body.appendChild(a);
      a.click();
      setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500);
    }

    function switchMediaTab(tab) {
      activeMediaTab = tab;
      /* The 3D stage is hidden at init so the canvas boots at fallback width —
         re-fit once the tab is visible. */
      if (tab === '3d') setTimeout(fitStage, 30);
      ['video', 'gallery', 'floor', '3d'].forEach(k => {
        document.getElementById('media-' + k).classList.toggle('hidden', k !== tab);
        const b = document.getElementById('tab-btn-' + k);
        b.className = 'px-3 sm:px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 ' +
          (k === tab ? 'bg-[#181B20] text-white shadow' : 'bg-transparent text-[#4b5563] hover:text-[#1a1d24] hover:bg-[#F4F1EA]');
      });
      const v = document.getElementById('spec-video');
      if (v) {
        if (tab === 'video') { v.play().catch(() => {}); }
        else v.pause();
      }
      if (window.lucide) lucide.createIcons();
    }

    function unmuteSpecVideo() {
      const v = document.getElementById('spec-video');
      if (!v) return;
      v.muted = false;
      v.play().catch(() => {});
      const o = document.getElementById('spec-unmute-overlay');
      if (o) o.style.display = 'none';
    }

    function detailGallery() {
      const p = currentProduct;
      const plates = [{ img: p.heroImage, cap: p.name + ' — Hero View', cat: p.category }];
      (p.gallery || []).forEach(g => plates.push({ img: g.src, cap: g.caption || p.name, cat: p.category }));
      return plates;
    }

    var galleryPlate = 'all';
    var gallerySel = 0;

    function galleryPlates() {
      const g = detailGallery();
      const cats = [...new Set(g.map(x => x.cat).filter(Boolean))];
      return [{ id: 'all', n: 'All', c: g.length }].concat(cats.map(c => ({ id: c, n: c, c: g.filter(x => x.cat === c).length })));
    }

    function renderGalleryMain() {
      const g = detailGallery();
      const visible = g.filter(x => galleryPlate === 'all' || x.cat === galleryPlate);
      let item = g[gallerySel];
      if (!item || (galleryPlate !== 'all' && item.cat !== galleryPlate)) {
        item = visible[0] || g[0];
        gallerySel = g.indexOf(item);
      }
      const main = document.getElementById('gallery-main');
      main.src = item.img;
      main.alt = item.cap;
      main.onclick = expandGallery;
      main.classList.add('cursor-zoom-in');
      document.getElementById('gallery-cat-chip').textContent = (item.cat || currentProduct.category).toUpperCase();
      document.getElementById('gallery-counter').textContent = (visible.indexOf(item) + 1) + ' / ' + visible.length;
      document.getElementById('gallery-caption').textContent = item.cap + ' — Click image to expand';
      document.getElementById('gallery-pins').innerHTML = hotspotsOn ? `
        <button onclick="openSpecPin('Material detail')" class="absolute z-10" style="top:34%;left:30%">
          <span class="relative flex h-4 w-4"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#9a7b4f] opacity-75"></span><span class="relative inline-flex rounded-full h-4 w-4 bg-[#9a7b4f] border-2 border-white shadow"></span></span>
        </button>
        <button onclick="openSpecPin('Junction detail')" class="absolute z-10" style="top:58%;left:64%">
          <span class="relative flex h-4 w-4"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#9a7b4f] opacity-75"></span><span class="relative inline-flex rounded-full h-4 w-4 bg-[#9a7b4f] border-2 border-white shadow"></span></span>
        </button>` : '';
      document.getElementById('gallery-plates').innerHTML =
        '<span class="text-[10px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold mr-1">Curated Plates:</span>' +
        galleryPlates().map(p => `
          <button onclick="setGalleryPlate('${p.id.replace(/'/g, "\\'")}')" class="px-3 py-1.5 rounded-lg text-[11px] font-mono uppercase tracking-wider font-semibold border transition-all ${galleryPlate === p.id ? 'bg-[#181b20] text-white border-[#181b20]' : 'bg-white text-[#6b7280] border-[#e5e2da] hover:border-[#9a7b4f] hover:text-[#1a1d24]'}">${p.n} (${p.c})</button>`
        ).join('');
      document.getElementById('gallery-thumbs').innerHTML = visible.map((t) => {
        const gi = g.indexOf(t);
        return `
        <button onclick="setGalleryIdx(${gi})" class="rounded-lg overflow-hidden border-2 transition-all ${gi === gallerySel ? 'border-[#9a7b4f] ring-1 ring-[#9a7b4f]' : 'border-[#e5e2da] hover:border-[#C4BEB1]'}">
          <img src="${t.img.replace('w=1200', 'w=300')}" alt="${t.cap}" loading="lazy" class="w-full aspect-[16/10] object-cover" />
        </button>`;
      }).join('');
      const t = document.getElementById('hotspot-toggle');
      if (t) t.textContent = 'Hotspots: ' + (hotspotsOn ? 'On' : 'Off');
    }

    function setGalleryPlate(id) {
      galleryPlate = id;
      renderGalleryMain();
    }
    function renderMedia() {
      const p = currentProduct;
      const video = document.getElementById('spec-video');
      if (video && video.tagName === 'VIDEO') {
        if (p.video && !p.videoEmbed) video.src = p.video;
        video.poster = p.heroImage;
      }
      gallerySel = 0;
      galleryPlate = 'all';
      document.getElementById('video-title').textContent = p.name + ': Turnkey Architectural Demonstration';
      document.getElementById('video-desc').textContent = p.tagline;
      document.getElementById('gallery-tab-label').textContent = 'Gallery (' + detailGallery().length + ')';
      const chapters = [
        { t: '00:00', n: 'Overview & Siting' },
        { t: '00:45', n: 'Stone Mitres & Alignment' },
        { t: '01:30', n: 'Concealed Downdraft Extraction' },
        { t: '02:10', n: 'Internal Joinery & Walnut Inserts' }
      ];
      const chaptersEl = document.getElementById('video-chapters');
      if (chaptersEl) chaptersEl.innerHTML = chapters.map((c, i) =>
        `<button onclick="seekVideo(${i})" class="p-2.5 text-left rounded-lg border border-[#e5e2da] bg-white hover:border-[#9a7b4f] text-xs transition-all"><span class="block font-mono text-[10px] text-[#9a7b4f] font-bold">${c.t}</span><span class="font-serif font-semibold text-[#1a1d24] text-sm">${c.n}</span></button>`
      ).join('');
      renderGalleryMain();
    }

    function seekVideo(i) {
      const v = document.getElementById('spec-video');
      if (!v || !v.duration || isNaN(v.duration)) return;
      v.currentTime = Math.min(v.duration - 0.5, (v.duration / 4) * i);
      v.play().catch(() => {});
    }

    function expandGallery() {
      // Centered popup viewer (shared lightbox): whole photo, arrows, caption, counter.
      const g = detailGallery();
      detailLbIdx = Math.min(Math.max(0, gallerySel), Math.max(0, g.length - 1));
      showDetailLb();
      document.getElementById('lightbox-modal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    var detailLbIdx = 0;
    function showDetailLb() {
      const g = detailGallery();
      const item = g[detailLbIdx] || g[0];
      if (!item) return;
      document.getElementById('lightbox-img').src = item.img;
      document.getElementById('lightbox-caption').textContent = item.cap;
      document.getElementById('lightbox-counter').textContent = (detailLbIdx + 1) + ' / ' + g.length;
    }
    window.lightboxNav = function (dir) {
      const modal = document.getElementById('lightbox-modal');
      if (!modal || modal.classList.contains('hidden')) return;
      const g = detailGallery();
      if (!g.length) return;
      detailLbIdx = (detailLbIdx + dir + g.length) % g.length;
      showDetailLb();
    };
    window.closeLightbox = function () {
      document.getElementById('lightbox-modal').classList.add('hidden');
      document.body.style.overflow = '';
    };
    document.addEventListener('keydown', function (e) {
      const modal = document.getElementById('lightbox-modal');
      if (!modal || modal.classList.contains('hidden')) return;
      if (e.key === 'Escape') window.closeLightbox();
      if (e.key === 'ArrowRight') window.lightboxNav(1);
      if (e.key === 'ArrowLeft') window.lightboxNav(-1);
    });

    function setGalleryIdx(i) {
      gallerySel = i;
      renderGalleryMain();
    }

    function toggleHotspots() {
      hotspotsOn = !hotspotsOn;
      renderGalleryMain();
    }

    function openSpecPin(label) {
      const box = document.getElementById('gallery-caption');
      if (box) box.textContent = currentProduct.name + ' — ' + label + ' (see Technical Specifications)';
    }

    function renderNarrative() {
      const p = currentProduct;
      document.getElementById('narrative-title').textContent = p.name + ', Specified End to End';
      const body = document.getElementById('narrative-body');
      if (p.description) {
        body.innerHTML = p.description;
      } else {
        body.textContent = p.tagline + ' Supplied with ' + (p.materials || []).join(', ').toLowerCase() + '. ' + (p.specs && p.specs[0] ? p.specs[0] : '');
      }
      const specsEl = document.getElementById('narrative-specs');
      const bullets = p.specs || [];
      if (specsEl) {
        specsEl.innerHTML = bullets.map(s =>
          `<li class="flex items-start gap-2.5 text-sm text-[#374151] leading-relaxed"><span class="mt-[7px] w-1.5 h-1.5 rounded-full bg-[#9a7b4f] shrink-0"></span><span>${s}</span></li>`
        ).join('');
        specsEl.style.display = bullets.length ? '' : 'none';
      }
    }

    function specRow(k, v) {
      return `<div class="flex justify-between gap-3 py-1.5 border-b border-[#F0EDE4] last:border-0"><span class="text-[#6b7280] shrink-0">${k}</span><span class="font-semibold text-[#1a1d24] text-right">${v}</span></div>`;
    }

    function renderTables() {
      const p = currentProduct;
      document.getElementById('spec-table-envelope').innerHTML =
        specRow('Dimensions', p.dimensions || '—') +
        specRow('Primary Materials', (p.materials || []).slice(0, 2).join(', ') || '—') +
        specRow('Lead Time', p.leadTime || '—') +
        specRow('Model Ref', p.modelCode);
      document.getElementById('spec-table-systems').innerHTML =
        specRow('Electrics', 'BS 7671 · 230V') +
        specRow('Plumbing', 'Pre-plumbed · UK fittings') +
        specRow('HVAC', 'Heat-pump ready') +
        specRow('Warranty', p.warranty || '10-Year UK cover');
    }

    function renderLogistics() {
      document.getElementById('logistics-row').innerHTML = [
        ['factory', 'Factory Build', 'Precision build in 25–30 days with video PDI.'],
        ['truck', 'Warehouse & Haulage', 'UK depot intake, 50-point check, Hiab to site.'],
        ['key', 'Unfold & Handover', 'Hydraulic unfolding and levelling under 1 hour.']
      ].map(([icon, t, d]) => `
        <div class="bg-white border border-[#e5e2da] rounded-2xl p-5 shadow-xs">
          <i data-lucide="${icon}" class="w-5 h-5 text-[#9a7b4f] mb-2.5"></i>
          <h4 class="text-sm font-semibold text-[#1a1d24] mb-1">${t}</h4>
          <p class="text-xs text-[#374151] leading-relaxed">${d}</p>
        </div>`).join('');
    }

    /* Floor plan plates */
    /* Floor plan plates (geometry slots are static; zone content is admin-driven) */
    var ZONE_SLOTS = [
      { x: 55, y: 45, w: 150, h: 140 }, { x: 215, y: 45, w: 230, h: 270 },
      { x: 455, y: 45, w: 130, h: 150 }, { x: 55, y: 195, w: 150, h: 120 },
      { x: 455, y: 205, w: 130, h: 110 },
    ];
    var FLOOR_ZONES = @json($zonesJson);
    FLOOR_ZONES.forEach((z, i) => Object.assign(z, ZONE_SLOTS[i % ZONE_SLOTS.length]));
    var activeZone = (FLOOR_ZONES[0] || {}).id || null;

    function selectZone(id) {
      activeZone = id;
      renderFloor();
    }

    function renderFloor() {
      const p = currentProduct;
      document.getElementById('floor-ref').textContent = p.modelCode;
      const svg = document.getElementById('floor-svg');
      let s = '<defs><pattern id="fpgrid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="#262c38" stroke-width="1"/></pattern></defs>';
      s += '<rect x="0" y="0" width="640" height="360" fill="#14171c"/>';
      s += '<rect x="0" y="0" width="640" height="360" fill="url(#fpgrid)"/>';
      s += '<rect x="40" y="30" width="560" height="300" fill="none" stroke="#3a4150" stroke-width="1.5"/>';
      s += '<text x="48" y="24" fill="#8b93a1" font-size="9" font-family="monospace">EXPANDED WIDTH 6,300 · DEPTH (M)</text>';
      FLOOR_ZONES.forEach(z => {
        const on = z.id === activeZone;
        s += `<g onclick="selectZone('${z.id}')" style="cursor:pointer">`;
        s += `<rect x="${z.x}" y="${z.y}" width="${z.w}" height="${z.h}" fill="${on ? '#2a2f3a' : '#1d2129'}" stroke="${on ? '#c5a880' : '#3a4150'}" stroke-width="${on ? 2 : 1}"/>`;
        s += `<text x="${z.x + 10}" y="${z.y + 22}" fill="#e5e7eb" font-size="10" font-weight="bold" font-family="monospace">${(z.name || '').toUpperCase()}</text>`;
        s += `<text x="${z.x + 10}" y="${z.y + 38}" fill="#8b93a1" font-size="9" font-family="monospace">${z.dims || ''}</text>`;
        s += '</g>';
      });
      svg.innerHTML = s;
      const z = FLOOR_ZONES.find(x => x.id === activeZone) || FLOOR_ZONES[0];
      if (!z) return;
      document.getElementById('floor-zone-name').textContent = z.name || '—';
      document.getElementById('floor-zone-desc').textContent = z.desc || '';
      const stats = [
        ['Expanded', p.dimensions || '—'],
        ['Lead Time', p.leadTime || '—'],
        ['Model Ref', p.modelCode],
        ['Warranty', (p.warranty || '10-Year cover').split(',')[0]]
      ];
      document.getElementById('floor-stats').innerHTML = stats.map(([k, v]) =>
        `<div class="bg-[#FAF9F5] border border-[#e5e2da] rounded-lg p-3"><span class="block text-[9px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold">${k}</span><span class="block text-[13px] font-semibold text-[#1a1d24] mt-0.5 leading-snug">${v}</span></div>`
      ).join('');
    }

    /* 3D massing viewer (Three.js) */
    var renderer3d = null, scene3d = null, camera3d = null, controls3d = null, villa3d = null;
    var unfoldT = 1, explodeT = 0, spinOn = true, raf3d = false;
    /* Three.js CDN scripts in the style section only run on full page loads — the
       SPA engine never executes script-src tags on navigation. Load on demand so the
       viewer also boots when arriving via SPA, reusing one cached promise. */
    var THREE_URLS = [
      'https://unpkg.com/three@0.147.0/build/three.min.js',
      'https://unpkg.com/three@0.147.0/examples/js/controls/OrbitControls.js',
      'https://unpkg.com/three@0.147.0/examples/js/loaders/GLTFLoader.js'
    ];
    function threeReady() {
      return (typeof THREE !== 'undefined') && THREE.OrbitControls && THREE.GLTFLoader;
    }
    function loadScriptOnce(src) {
      var done = document.querySelector('script[data-dyn3d="' + src + '"]');
      if (done) return;
      var el = document.createElement('script');
      el.src = src;
      el.async = false;
      el.setAttribute('data-dyn3d', src);
      el.onerror = function () { if (el.parentNode) el.parentNode.removeChild(el); };
      document.head.appendChild(el);
    }
    function ensureThree() {
      if (window.__threePromise) return window.__threePromise;
      window.__threePromise = new Promise(function (resolve) {
        var tries = 0;
        THREE_URLS.forEach(function (u) { loadScriptOnce(u); });
        (function wait() {
          if (threeReady()) { resolve(true); return; }
          tries++;
          if (tries < 80) { setTimeout(wait, 250); return; }
          window.__threePromise = null;
          resolve(false);
        })();
      });
      return window.__threePromise;
    }
    function buildCube() {
      const stage = document.getElementById('model3d-stage');
      const label = document.getElementById('model3d-label');
      if (!stage) return;
      window.__cubeTries = 0;
      /* SPA re-navigation re-executes this script with fresh vars — stop the
         previous page's RAF loop and free its GL context first. */
      window.__loop3d = (window.__loop3d || 0) + 1;
      try {
        if (window.__controls3d && window.__controls3d.dispose) window.__controls3d.dispose();
        if (window.__renderer3d) {
          if (window.__renderer3d.dispose) window.__renderer3d.dispose();
          if (window.__renderer3d.domElement && window.__renderer3d.domElement.parentNode) window.__renderer3d.domElement.parentNode.removeChild(window.__renderer3d.domElement);
        }
      } catch (e) {}
      window.__controls3d = null;
      window.__renderer3d = null;
      if (!threeReady()) {
        if (label) label.textContent = 'Loading 3D…';
        ensureThree().then(function (ok) {
          if (ok) { buildCube(); return; }
          var late = document.getElementById('model3d-label');
          if (late) late.textContent = '3D unavailable offline';
        });
        return;
      }
      stage.querySelectorAll('canvas').forEach(function (c) { c.remove(); });
      const W = stage.clientWidth || 800, H = stage.clientHeight || 440;
      renderer3d = new THREE.WebGLRenderer({ antialias: true, alpha: true });
      renderer3d.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      renderer3d.setSize(W, H);
      stage.insertBefore(renderer3d.domElement, stage.firstChild);
      scene3d = new THREE.Scene();
      camera3d = new THREE.PerspectiveCamera(42, W / H, 0.1, 200);
      camera3d.position.set(9.5, 7, 11.5);
      controls3d = new THREE.OrbitControls(camera3d, renderer3d.domElement);
      controls3d.enableDamping = true;
      controls3d.dampingFactor = 0.08;
      controls3d.maxPolarAngle = Math.PI / 2 - 0.05;
      controls3d.minDistance = 6;
      controls3d.maxDistance = 32;
      controls3d.autoRotate = spinOn;
      controls3d.autoRotateSpeed = 0.9;
      window.__renderer3d = renderer3d;
      window.__controls3d = controls3d;
      scene3d.add(new THREE.HemisphereLight(0xfff6e8, 0x8a8474, 0.95));
      const sun = new THREE.DirectionalLight(0xffffff, 0.75);
      sun.position.set(8, 14, 6);
      scene3d.add(sun);
      const ground = new THREE.Mesh(new THREE.PlaneGeometry(70, 70), new THREE.MeshStandardMaterial({ color: 0xe7e2d6, roughness: 1 }));
      ground.rotation.x = -Math.PI / 2;
      scene3d.add(ground);
      const grid = new THREE.GridHelper(34, 34, 0xc8c3b7, 0xd8d4c7);
      grid.position.y = 0.02;
      scene3d.add(grid);
      /* Uploaded .glb/.gltf model wins over the procedural massing demo */
      if (currentProduct && currentProduct.model3d && typeof THREE.GLTFLoader !== 'undefined') {
        var uw = document.getElementById('model3d-unfold-wrap'); if (uw) uw.style.display = 'none';
        var ew = document.getElementById('model3d-explode-wrap'); if (ew) ew.style.display = 'none';
        try {
          new THREE.GLTFLoader().load(currentProduct.model3d, function (gltf) {
            const obj = gltf.scene;
            const box = new THREE.Box3().setFromObject(obj);
            const size = box.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z) || 1;
            const s = 7 / maxDim;
            obj.scale.setScalar(s);
            const c = box.getCenter(new THREE.Vector3());
            obj.position.sub(c.clone().multiplyScalar(s));
            obj.position.y += (size.y * s) / 2;
            scene3d.add(obj);
            villa3d = null;
            if (label) label.textContent = (currentProduct ? currentProduct.modelCode + ' · ' : '') + '3D model';
          }, undefined, function () {
            if (label) label.textContent = '3D model failed to load';
          });
        } catch (e) { /* fall through to procedural demo */ }
        startLoop3d();
        return;
      }
      const wallMat = new THREE.MeshStandardMaterial({ color: 0xf6f3ec, roughness: 0.9 });
      const roofMat = new THREE.MeshStandardMaterial({ color: 0xded9cb, roughness: 0.85 });
      const darkMat = new THREE.MeshStandardMaterial({ color: 0x2b2f36, roughness: 0.7 });
      const glassMat = new THREE.MeshStandardMaterial({ color: 0x3d5a68, emissive: 0x9fc3d4, emissiveIntensity: 0.55, roughness: 0.3 });
      const edgeMat = new THREE.LineBasicMaterial({ color: 0x9a7b4f });
      function edgedBox(w, h, d, mat) {
        const g = new THREE.Group();
        const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
        g.add(m);
        g.add(new THREE.LineSegments(new THREE.EdgesGeometry(m.geometry), edgeMat));
        return g;
      }
      const villa = new THREE.Group();
      const base = edgedBox(7.0, 0.25, 7.0, darkMat); base.position.y = 0.125; villa.add(base);
      const floor = edgedBox(6.4, 0.15, 6.4, roofMat); floor.position.y = 0.32; villa.add(floor);
      const core = edgedBox(2.4, 2.8, 6.3, wallMat); core.position.y = 1.8; villa.add(core);
      function wing(side) {
        const g = new THREE.Group();
        const body = edgedBox(1.95, 2.6, 6.1, wallMat); body.position.set(side * 0.975, 1.7, 0); g.add(body);
        const win = new THREE.Mesh(new THREE.PlaneGeometry(1.1, 0.9), glassMat);
        win.position.set(side * 1.955, 1.8, 0); win.rotation.y = side * Math.PI / 2; g.add(win);
        const win2 = win.clone(); win2.position.z = 1.8; g.add(win2);
        const win3 = win.clone(); win3.position.z = -1.8; g.add(win3);
        return g;
      }
      const wingL = wing(-1), wingR = wing(1);
      villa.add(wingL); villa.add(wingR);
      const roof = edgedBox(1, 0.18, 6.5, roofMat); roof.position.y = 3.3; villa.add(roof);
      const coreGlass = new THREE.Mesh(new THREE.PlaneGeometry(1.6, 1.1), glassMat);
      coreGlass.position.set(0, 1.9, 3.16); villa.add(coreGlass);
      scene3d.add(villa);
      villa3d = { villa: villa, wingL: wingL, wingR: wingR, roof: roof, floor: floor, base: base, roofY: 3.3, floorY: 0.32, baseY: 0.125 };
      unfoldT = 1; explodeT = 0;
      const us = document.getElementById('model3d-unfold'); if (us) us.value = 100;
      const es = document.getElementById('model3d-explode'); if (es) es.value = 0;
      applyVilla();
      startLoop3d();
    }
    function startLoop3d() {
      if (!raf3d) {
        raf3d = true;
        var gen = window.__loop3d || 0;
        (function loop() {
          if (gen !== window.__loop3d) return;
          requestAnimationFrame(loop);
          if (controls3d && activeMediaTab === '3d') controls3d.update();
          if (renderer3d && scene3d && camera3d && activeMediaTab === '3d') renderer3d.render(scene3d, camera3d);
        })();
      }
      if (!window.__fitBound) {
        window.__fitBound = true;
        window.addEventListener('resize', fitStage);
      }
    }
    function fitStage() {
      const stage = document.getElementById('model3d-stage');
      if (!stage || !renderer3d || !camera3d) return;
      const W = stage.clientWidth, H = stage.clientHeight;
      renderer3d.setSize(W, H);
      camera3d.aspect = W / H;
      camera3d.updateProjectionMatrix();
    }
    function applyVilla() {
      if (!villa3d) return;
      const t = unfoldT, e = explodeT;
      villa3d.wingL.position.x = -1.2 * t;
      villa3d.wingR.position.x = 1.2 * t;
      villa3d.roof.scale.x = 2.4 + (6.6 - 2.4) * t;
      villa3d.roof.position.y = villa3d.roofY + e * 1.6;
      villa3d.floor.position.y = villa3d.floorY - e * 0.8;
      villa3d.base.position.y = villa3d.baseY - e * 1.4;
      const label = document.getElementById('model3d-label');
      const p = currentProduct ? currentProduct.modelCode : '';
      if (label) label.textContent = (p ? p + ' · ' : '') + Math.round(t * 100) + '% unfolded';
    }
    function toggleSpin() {
      const c = document.getElementById('model3d-spin');
      spinOn = c ? c.checked : !spinOn;
      if (controls3d) controls3d.autoRotate = spinOn;
    }
    function explodeModel(v) {
      explodeT = (parseInt(v, 10) || 0) / 100;
      applyVilla();
    }
    function unfoldModel(v) {
      unfoldT = (parseInt(v, 10) || 0) / 100;
      applyVilla();
    }

    initProduct();
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
