@extends('frontend.layout')
@section('title', 'Architectural Collections | OriginSpaces Modular UK')
@section('content')


  <!-- Catalogue -->
  <main class="py-12 md:py-16 px-4 sm:px-6 lg:px-10">
    <div class="max-w-7xl mx-auto">

      <!-- Title + search -->
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8">
        <div>
          <h1 class="font-serif text-3xl sm:text-5xl text-[#1a1d24] font-semibold tracking-tight">Architectural Collections</h1>
          <p class="mt-3 text-[#374151] max-w-2xl text-sm md:text-[15px] font-semibold leading-relaxed">Explore our comprehensive edit of monolithic stone suites, crafted sanitaryware, tactile hardware, and atmospheric lighting.</p>
        </div>
        <div class="relative w-full lg:w-72 shrink-0">
          <x-icon name="search" class="w-4 h-4 text-[#9ca3af] absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            id="collection-search-input"
            oninput="handleCollectionSearch(this.value)"
            placeholder="Search model, stone, finish..."
            class="w-full bg-white border border-[#e5e2da] focus:border-[#9a7b4f] text-xs text-[#1a1d24] pl-10 pr-4 py-3 rounded-lg shadow-xs focus:outline-none transition-colors placeholder:text-[#9ca3af]"
          />
        </div>
      </div>

      <!-- Category tabs + refine -->
      <div class="flex items-center justify-between gap-4 border-y border-[#e5e2da] py-3 mb-3">
        <div class="flex items-center gap-5 sm:gap-7 overflow-x-auto no-scrollbar text-[12px] font-mono font-semibold uppercase tracking-wider" id="collection-tabs">
          <button onclick="filterCollection('All', this)" class="collection-tab shrink-0 pb-1 transition-all {{ $activeCategory === 'All' ? 'text-[#1a1d24] border-b-2 border-[#9a7b4f]' : 'text-[#6b7280] border-b-2 border-transparent hover:text-[#1a1d24]' }}">All</button>
          @forelse($categories as $tabCat)
            <button onclick="filterCollection({{ json_encode($tabCat->name) }}, this)" class="collection-tab shrink-0 pb-1 transition-all {{ $activeCategory === $tabCat->name ? 'text-[#1a1d24] border-b-2 border-[#9a7b4f]' : 'text-[#6b7280] border-b-2 border-transparent hover:text-[#1a1d24]' }}">{{ $tabCat->name }}</button>
          @empty
            <button onclick="filterCollection('Kitchen', this)" class="collection-tab shrink-0 pb-1 text-[#6b7280] border-b-2 border-transparent hover:text-[#1a1d24] transition-all">Kitchen</button>
            <button onclick="filterCollection('Expandable Homes', this)" class="collection-tab shrink-0 pb-1 text-[#6b7280] border-b-2 border-transparent hover:text-[#1a1d24] transition-all">Expandable Homes</button>
          @endforelse
        </div>
      </div>

      <!-- Count -->
      <div class="text-xs text-[#6b7280] mb-8">Displaying <strong id="collection-count" class="text-[#1a1d24] font-semibold">{{ count($productsJson) }}</strong> pieces</div>

      <!-- Cards (server-rendered; JS only filters) -->
      <div id="collection-grid" class="space-y-8">
        @forelse($productsJson as $i => $p)
          <div class="collection-card grid grid-cols-1 lg:grid-cols-12 items-stretch bg-white rounded-2xl border border-[#e5e2da] overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300" data-cat="{{ $p['category'] }}" data-search="{{ strtolower($p['name'] . ' ' . $p['modelCode'] . ' ' . ($p['tagline'] ?? '') . ' ' . implode(' ', $p['materials'] ?? [])) }}">
            <div class="lg:col-span-5 p-6 sm:p-8 flex flex-col justify-center space-y-3 order-2 {{ $i % 2 === 1 ? 'lg:order-2' : 'lg:order-1' }}">
              <span class="text-[11px] font-mono uppercase tracking-[0.2em] text-[#9a7b4f] font-semibold">{{ $p['category'] }}</span>
              <h3 class="font-serif text-2xl sm:text-3xl text-[#1a1d24] font-semibold leading-tight">{{ $p['name'] }}</h3>
              <p class="text-sm text-[#374151] leading-relaxed">{{ $p['tagline'] }}</p>
              <div class="grid grid-cols-2 gap-4 pt-3 border-t border-[#f0ede6] text-xs">
                <div><span class="block text-[10px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold">Dimensions</span><span class="font-semibold text-[#1a1d24]">{{ $p['dimensions'] ?: '—' }}</span></div>
                <div><span class="block text-[10px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold">Lead Time</span><span class="font-semibold text-[#1a1d24]">{{ $p['leadTime'] ?: '—' }}</span></div>
              </div>
              <div class="pt-1 flex flex-wrap items-center gap-4">
                <span class="font-mono text-[#9a7b4f] font-bold text-sm">{{ $p['price'] }}</span>
                <a @spa href="/product/{{ $p['slug'] }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all">Full Details <x-icon name="arrow-right" class="w-3.5 h-3.5" /></a>
                <button type="button" onclick='openEnquiryModal(@json($p['name'] . ' — Spec Pack'), {{ $p['id'] }}, "")' class="text-[11px] font-mono uppercase tracking-wider text-[#6b7280] hover:text-[#9a7b4f] font-semibold transition-colors">Request This Spec &rarr;</button>
              </div>
              <div class="collection-specs hidden pt-4 border-t border-[#f0ede6] space-y-3">
                <div>
                  <span class="block text-[10px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold mb-1.5">Primary Materials</span>
                  <div class="flex flex-wrap gap-1.5">@foreach($p['materials'] ?? [] as $m)<span class="text-[11px] px-2.5 py-1 rounded-full bg-[#FAF9F5] border border-[#e5e2da] text-[#374151] font-medium">{{ $m }}</span>@endforeach</div>
                </div>
                <ul class="space-y-1.5">@foreach($p['specs'] ?? [] as $s)<li class="flex gap-2 text-xs text-[#374151]"><x-icon name="check" class="w-3.5 h-3.5 text-[#9a7b4f] shrink-0 mt-0.5" /><span>{{ $s }}</span></li>@endforeach</ul>
                <p class="text-[11px] text-[#6b7280]">{{ $p['warranty'] ?? '' }}</p>
              </div>
            </div>
            <div class="lg:col-span-7 relative min-h-[260px] order-1 {{ $i % 2 === 1 ? 'lg:order-1' : 'lg:order-2' }}">
              <img src="{{ $p['heroImage'] }}" alt="{{ $p['name'] }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover" />
              <span class="absolute top-4 left-4 px-2.5 py-1 bg-white/90 backdrop-blur text-[10px] font-mono rounded shadow">{{ $p['modelCode'] }}</span>
              <button onclick="toggleSaved(this)" title="Save piece" class="absolute top-4 right-4 w-9 h-9 rounded-lg bg-white border border-[#e5e2da] flex items-center justify-center transition-all shadow">
                <x-icon name="bookmark" class="w-4 h-4 text-[#1a1d24]" />
              </button>
            </div>
          </div>
        @empty
          <div class="bg-white rounded-2xl border border-[#e5e2da] p-12 text-center text-sm text-[#6b7280]">No pieces yet — check back soon.</div>
        @endforelse
        <div id="collection-empty" class="hidden bg-white rounded-2xl border border-[#e5e2da] p-12 text-center text-sm text-[#6b7280]">No pieces match your search. Try a different keyword or category.</div>
      </div>

    </div>
  </main>

  <!-- Footer -->
  
@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */

        /* products server-rendered by Blade; JS only filters visible cards */

    var activeCollectionCat = @json($activeCategory);
    var collectionSearch = '';

    function filterCollection(cat, btn) {
      activeCollectionCat = cat;
      document.querySelectorAll('.collection-tab').forEach(b => {
        b.classList.remove('text-[#1a1d24]', 'border-[#9a7b4f]');
        b.classList.add('text-[#6b7280]', 'border-transparent');
      });
      if (btn) {
        btn.classList.remove('text-[#6b7280]', 'border-transparent');
        btn.classList.add('text-[#1a1d24]', 'border-[#9a7b4f]');
      }
      renderCollections();
    }

    function handleCollectionSearch(val) {
      collectionSearch = (val || '').toLowerCase().trim();
      renderCollections();
    }

    function toggleSaved(btn) {
      const on = btn.classList.toggle('bg-[#9a7b4f]');
      btn.classList.toggle('border-[#9a7b4f]', on);
      const icon = btn.querySelector('svg');
      if (icon) {
        icon.classList.toggle('text-white', on);
        icon.classList.toggle('text-[#1a1d24]', !on);
      }
    }

                        function renderCollections() {
      var cards = document.querySelectorAll('#collection-grid .collection-card');
      var shown = 0;
      cards.forEach(function (card) {
        var okCat = (activeCollectionCat === 'All' || card.getAttribute('data-cat') === activeCollectionCat);
        var hay = (card.getAttribute('data-search') || '');
        var okSearch = (!collectionSearch || hay.indexOf(collectionSearch) !== -1);
        var show = okCat && okSearch;
        card.classList.toggle('hidden', !show);
        if (show) shown++;
      });
      var count = document.getElementById('collection-count');
      if (count) count.textContent = shown;
      var empty = document.getElementById('collection-empty');
      if (empty) empty.classList.toggle('hidden', shown !== 0);
    }

    renderCollections();
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
