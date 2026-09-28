@extends('frontend.layout')
@section('title', 'Gallery | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto text-center max-w-3xl">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Gallery</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight leading-[1.1]">Expanded Living, Documented.</h1>
      <p class="mt-5 text-sm sm:text-base text-[#374151] leading-relaxed max-w-2xl mx-auto">Lifestyle, expanded and cutaway views for every chassis. Pick a category, click any image to expand in 4K, then step through with the arrows.</p>
    </div>
  </section>

  <!-- Gallery -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-wrap items-center justify-center gap-2 mb-10">
        <button onclick="filterGallery('all', this)" class="gallery-pill px-5 py-2.5 rounded-full bg-[#181b20] text-white text-[13px] font-semibold border border-[#181b20] transition-all">All Work ({{ count($galleryJson) }})</button>
        @forelse($galleryCatsJson as $gSlug => $gLabel)
          <button onclick="filterGallery({{ json_encode($gSlug) }}, this)" class="gallery-pill px-5 py-2.5 rounded-full bg-white border border-[#e5e2da] text-[#6b7280] text-[13px] font-semibold hover:text-[#9a7b4f] hover:border-[#9a7b4f] transition-all">{{ $gLabel }} ({{ collect($galleryJson)->where('cat', $gSlug)->count() }})</button>
        @empty
          <button onclick="filterGallery('exterior', this)" class="gallery-pill px-5 py-2.5 rounded-full bg-white border border-[#e5e2da] text-[#6b7280] text-[13px] font-semibold hover:text-[#9a7b4f] hover:border-[#9a7b4f] transition-all">Exteriors</button>
          <button onclick="filterGallery('interior', this)" class="gallery-pill px-5 py-2.5 rounded-full bg-white border border-[#e5e2da] text-[#6b7280] text-[13px] font-semibold hover:text-[#9a7b4f] hover:border-[#9a7b4f] transition-all">Interiors</button>
        @endforelse
      </div>
      <div id="gallery-grid" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($galleryJson as $gi => $g)
          <button onclick="openGallery({{ $gi }})" data-cat="{{ $g['cat'] }}" class="gallery-item group relative rounded-xl overflow-hidden border border-[#e5e2da] bg-white text-left">
            <img src="{{ $g['src'] }}" alt="{{ $g['caption'] }}" loading="lazy" class="w-full aspect-[4/3] object-cover transition-transform duration-700 group-hover:scale-105" />
            <span class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></span>
            <span class="absolute bottom-2 left-2 text-[10px] font-mono uppercase bg-[#181b20]/80 text-white px-2 py-1 rounded-full">{{ $g['catLabel'] }}</span>
            <span class="absolute bottom-2 right-2 w-8 h-8 rounded-full bg-white/90 text-[#1a1d24] items-center justify-center hidden group-hover:flex">
              <x-icon name="maximize-2" class="w-3.5 h-3.5" />
            </span>
          </button>
        @empty
          <p class="col-span-full text-center text-sm text-[#6b7280] py-10">Gallery is being curated — check back soon.</p>
        @endforelse
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="py-16 md:py-20 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-4xl mx-auto bg-[#181b20] text-white rounded-2xl p-8 sm:p-10 text-center">
      <h2 class="font-serif text-3xl sm:text-4xl font-semibold">Like What You See?</h2>
      <p class="text-sm text-[#c9c4b7] mt-2 mb-6">Configure the same chassis, finishes and interiors for your own plot.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-2.5">
        <a @spa href="{{ route('custom-build') }}" class="px-6 py-3 rounded-xl bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs font-semibold uppercase tracking-wider transition-all">Custom Build</a>
        <a @spa href="{{ route('contact') }}" class="px-6 py-3 rounded-xl border border-white/20 hover:border-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all">Talk to the Studio</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  
@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */

    var GALLERY = @json($galleryJson);
    var activeGalleryCat = 'all';
    var galleryView = [];
    var galleryIdx = 0;

    function galleryThumb(g, size) {
      if (g.src) return g.src;
      return 'https://images.unsplash.com/' + g.id + '?auto=format&fit=crop&w=' + size + '&q=80';
    }

    function filterGallery(cat, btn) {
      activeGalleryCat = cat;
      document.querySelectorAll('.gallery-pill').forEach(function (p) {
        p.classList.remove('bg-[#181b20]', 'text-white', 'border-[#181b20]');
        p.classList.add('bg-white', 'text-[#6b7280]', 'border', 'border-[#e5e2da]', 'hover:text-[#9a7b4f]', 'hover:border-[#9a7b4f]');
      });
      if (btn) {
        btn.classList.remove('bg-white', 'text-[#6b7280]', 'border', 'border-[#e5e2da]', 'hover:text-[#9a7b4f]', 'hover:border-[#9a7b4f]');
        btn.classList.add('bg-[#181b20]', 'text-white', 'border', 'border-[#181b20]');
      }
      renderGallery();
    }

    function renderGallery() {
      // Grid is server-rendered; only toggle visibility.
      document.querySelectorAll('#gallery-grid .gallery-item').forEach(function (el) {
        var show = (activeGalleryCat === 'all' || el.getAttribute('data-cat') === activeGalleryCat);
        el.classList.toggle('hidden', !show);
      });
    }

    function openGallery(globalIdx) {
      galleryView = GALLERY.map((g, i) => ({ ...g, index: i })).filter(g => activeGalleryCat === 'all' || g.cat === activeGalleryCat);
      galleryIdx = Math.max(0, galleryView.findIndex(g => g.index === globalIdx));
      showGalleryItem();
      document.getElementById('lightbox-modal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function showGalleryItem() {
      const g = galleryView[galleryIdx];
      if (!g) return;
      document.getElementById('lightbox-img').src = galleryThumb(g, 1600);
      document.getElementById('lightbox-caption').textContent = g.caption;
      document.getElementById('lightbox-counter').textContent = (galleryIdx + 1) + ' / ' + galleryView.length;
    }

    function lightboxNav(dir) {
      if (!galleryView.length) return;
      galleryIdx = (galleryIdx + dir + galleryView.length) % galleryView.length;
      showGalleryItem();
    }

    function closeLightbox() {
      document.getElementById('lightbox-modal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
      const modal = document.getElementById('lightbox-modal');
      if (!modal || modal.classList.contains('hidden')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowRight') lightboxNav(1);
      if (e.key === 'ArrowLeft') lightboxNav(-1);
    });

    renderGallery();
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
