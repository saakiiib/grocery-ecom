@php
  $navProducts = App\Models\Product::where('status', 1)->orderByDesc('is_featured')->orderBy('sort_order')->take(5)->get(['id', 'name', 'slug', 'model_code', 'base_price', 'hero_image']);
  $logo = $company->company_logo ? asset('uploads/company/' . $company->company_logo) : asset('resources/frontend-raw/assets/logo.png');
@endphp
<header class="sticky top-0 z-40 bg-white border-b border-[#e5e2da] transition-all">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 h-20 flex items-center justify-between">
    <a @spa href="{{ route('home') }}" class="flex items-center shrink-0">
      <img src="{{ $logo }}" alt="{{ $company->company_name ?: 'OriginSpaces' }}" class="h-10 w-auto object-contain" />
    </a>

    <nav class="hidden lg:flex items-center gap-7 text-[17px] font-semibold text-[#374151]">
      <a @spa href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-[#1a1d24]' : '' }} hover:text-[#1a1d24] transition-colors">Home</a>
      <a @spa href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-[#1a1d24]' : '' }} hover:text-[#1a1d24] transition-colors">About</a>
      <div class="relative group">
        <a @spa href="{{ route('collections') }}" class="flex items-center gap-1 hover:text-[#1a1d24] transition-colors py-6 {{ request()->routeIs('collections', 'product.show') ? 'text-[#1a1d24]' : '' }}">
          <span>Collection</span>
          <x-icon name="chevron-down" class="w-3.5 h-3.5" />
        </a>
        <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 absolute left-1/2 -translate-x-1/2 top-full w-[860px] max-w-[90vw] bg-white rounded-2xl border border-[#e5e2da] shadow-2xl p-8 z-50">
          <div class="flex items-start justify-between pb-5 border-b border-[#e5e2da] mb-6">
            <div>
              <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-1">Featured Products</span>
              <span class="font-serif text-2xl text-[#1a1d24]">Explore Signature Pieces</span>
            </div>
            <a @spa href="{{ route('collections') }}" class="text-[11px] font-mono uppercase tracking-widest text-[#6b7280] hover:text-[#9a7b4f] font-semibold flex items-center gap-1 shrink-0 mt-2">
              <span>View all products</span>
              <x-icon name="arrow-up-right" class="w-3.5 h-3.5" />
            </a>
          </div>
          <div class="grid grid-cols-5 gap-5">
            @forelse($navProducts as $navProduct)
              <a @spa href="{{ route('product.show', $navProduct->slug) }}" class="text-left group/card block">
                <img src="{{ $navProduct->hero_image && str_starts_with($navProduct->hero_image, 'http') ? $navProduct->hero_image : ($navProduct->hero_image ? url($navProduct->hero_image) : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80') }}" alt="{{ $navProduct->name }}" loading="lazy" class="w-full aspect-[4/5] object-cover rounded-lg mb-2.5" />
                <span class="text-[13px] font-semibold text-[#1a1d24] block leading-snug">{{ $navProduct->name }}</span>
                <span class="text-[11px] font-mono text-[#6b7280]">{{ $navProduct->base_price ? 'From £'.number_format($navProduct->base_price, 0) : $navProduct->model_code }}</span>
              </a>
            @empty
              <a @spa href="{{ route('collections') }}" class="text-left group/card block">
                <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=400&q=80" alt="Collections" class="w-full aspect-[4/5] object-cover rounded-lg mb-2.5" />
                <span class="text-[13px] font-semibold text-[#1a1d24] block leading-snug">All Products</span>
                <span class="text-[11px] font-mono text-[#6b7280]">Browse</span>
              </a>
            @endforelse
          </div>
        </div>
      </div>
      <a @spa href="{{ route('downloads') }}" class="{{ request()->routeIs('downloads') ? 'text-[#1a1d24]' : '' }} hover:text-[#1a1d24] transition-colors">Downloads</a>
      <a @spa href="{{ route('gallery') }}" class="{{ request()->routeIs('gallery') ? 'text-[#1a1d24]' : '' }} hover:text-[#1a1d24] transition-colors">Gallery</a>
      <a @spa href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-[#1a1d24]' : '' }} hover:text-[#1a1d24] transition-colors">Contact</a>
    </nav>

    <div class="hidden sm:flex items-center gap-2.5">
      <a @spa href="{{ route('custom-build') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all shadow-xs">
        <span>Custom Build</span>
        <x-icon name="arrow-right" class="w-3.5 h-3.5" />
      </a>
      <a @spa href="{{ route('collections') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs font-semibold uppercase tracking-wider transition-all shadow-xs">
        <x-icon name="sliders-horizontal" class="w-3.5 h-3.5" />
        <span>Explore Products</span>
      </a>
    </div>

    <button onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-lg text-[#1a1d24] hover:bg-[#e5e2da]/60">
      <x-icon name="menu" id="menu-icon-open" class="w-6 h-6" />
      <x-icon name="x" id="menu-icon-close" class="w-6 h-6 hidden" />
    </button>
  </div>

  <div id="mobile-menu" class="hidden md:hidden bg-white border-b border-[#e5e2da] px-6 py-5 space-y-4">
    <a @spa href="{{ route('home') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#1a1d24]">Home</a>
    <a @spa href="{{ route('about') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#1a1d24]">About</a>
    <a @spa href="{{ route('collections') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#9a7b4f]">Collection</a>
    <a @spa href="{{ route('downloads') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#1a1d24]">Downloads</a>
    <a @spa href="{{ route('gallery') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#1a1d24]">Gallery</a>
    <a @spa href="{{ route('contact') }}" onclick="toggleMobileMenu()" class="block text-sm font-semibold text-[#1a1d24]">Contact</a>
    <div class="pt-4 border-t border-[#e5e2da] flex flex-col gap-2">
      <a @spa href="{{ route('custom-build') }}" onclick="toggleMobileMenu()" class="w-full py-2.5 rounded-lg bg-[#181b20] text-white text-xs font-semibold uppercase tracking-wider text-center">Custom Build</a>
      <a @spa href="{{ route('collections') }}" onclick="toggleMobileMenu()" class="w-full py-2.5 rounded-lg bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider text-center">Explore Products</a>
    </div>
  </div>
</header>
<script>
function toggleMobileMenu() {
  var menu = document.getElementById('mobile-menu');
  var iconOpen = document.getElementById('menu-icon-open');
  var iconClose = document.getElementById('menu-icon-close');
  if (!menu) return;
  if (menu.classList.contains('hidden')) {
    menu.classList.remove('hidden');
    if (iconOpen) iconOpen.classList.add('hidden');
    if (iconClose) iconClose.classList.remove('hidden');
  } else {
    menu.classList.add('hidden');
    if (iconOpen) iconOpen.classList.remove('hidden');
    if (iconClose) iconClose.classList.add('hidden');
  }
}
</script>
