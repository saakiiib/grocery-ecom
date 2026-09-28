@extends('frontend.layout')
@section('title', 'Downloads | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto text-center max-w-3xl">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Downloads</span>
      <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-[#1a1d24] font-semibold tracking-tight leading-[1.1]">Spec Resources &amp; Monographs.</h1>
      <p class="mt-5 text-sm sm:text-base text-[#374151] leading-relaxed max-w-2xl mx-auto">Catalogues, installation and MEP manuals, Revit and BIM families, material care guides.</p>
    </div>
  </section>

  <!-- Library -->
  <section class="py-16 md:py-24 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5] border-b border-[#e5e2da]">
    <div class="max-w-5xl mx-auto">
      <div class="bg-white rounded-xl border border-[#e5e2da] p-4 sm:p-5 shadow-xs mb-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="relative w-full sm:max-w-xs">
            <x-icon name="search" class="w-4 h-4 text-[#9ca3af] absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              id="file-search-input"
              oninput="handleFileSearch(this.value)"
              placeholder="Search file name or product..."
              class="w-full text-xs pl-10 pr-4 py-2.5 rounded-lg border border-[#e5e2da] focus:border-[#9a7b4f] focus:outline-none bg-[#FAF9F5] placeholder:text-[#9ca3af]"
            />
          </div>
          <span class="text-xs text-[#6b7280] whitespace-nowrap">Found <strong id="file-count" class="text-[#1a1d24] font-semibold">{{ count($filesJson) }}</strong> technical files</span>
        </div>
        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-[#f0ede6]">
          <span class="text-[10px] font-mono uppercase tracking-widest text-[#6b7280] font-semibold mr-1">Format:</span>
          <button onclick="filterFiles('all', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#9a7b4f]/10 border-[#9a7b4f]/50 text-[#1a1d24]">All</button>
          <button onclick="filterFiles('PDF Spec', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#FAF9F5] border-[#e5e2da] text-[#6b7280] hover:border-[#9a7b4f] hover:text-[#1a1d24]">PDF Spec</button>
          <button onclick="filterFiles('CAD Drawing', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#FAF9F5] border-[#e5e2da] text-[#6b7280] hover:border-[#9a7b4f] hover:text-[#1a1d24]">CAD Drawing</button>
          <button onclick="filterFiles('BIM/Revit', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#FAF9F5] border-[#e5e2da] text-[#6b7280] hover:border-[#9a7b4f] hover:text-[#1a1d24]">BIM/Revit</button>
          <button onclick="filterFiles('Installation Manual', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#FAF9F5] border-[#e5e2da] text-[#6b7280] hover:border-[#9a7b4f] hover:text-[#1a1d24]">Installation Manual</button>
          <button onclick="filterFiles('Care Guide', this)" class="file-format-pill px-3.5 py-1.5 rounded-lg text-[11px] font-mono font-semibold border transition-all bg-[#FAF9F5] border-[#e5e2da] text-[#6b7280] hover:border-[#9a7b4f] hover:text-[#1a1d24]">Care Guide</button>
        </div>
      </div>
      <div id="file-list" class="bg-white rounded-xl border border-[#e5e2da] shadow-xs overflow-hidden divide-y divide-[#f0ede6]">
        @forelse($filesJson as $f)
          <div class="file-row p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4" data-format="{{ $f['format'] }}" data-search="{{ strtolower($f['title'] . ' ' . ($f['ref'] ?? '') . ' ' . $f['suite']) }}">
            <span class="w-11 h-11 rounded-lg bg-[#9a7b4f]/10 flex items-center justify-center shrink-0">
              <x-icon name="file-text" class="w-5 h-5 text-[#9a7b4f]" />
            </span>
            <div class="flex-1 min-w-0">
              <div class="text-[10px] font-mono uppercase tracking-[0.14em] text-[#9a7b4f] font-semibold">{{ $f['ref'] }} &middot; {{ $f['suite'] }}</div>
              <h3 class="font-serif text-xl text-[#1a1d24] font-semibold mt-0.5">{{ $f['title'] }}</h3>
              <div class="text-[11px] font-mono text-[#6b7280] mt-1">Format: {{ $f['format'] }} &middot; Size: {{ $f['size'] }} &middot; Rev: {{ $f['rev'] }}</div>
            </div>
            @if($f['url'])
              <a href="{{ $f['url'] }}" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-[#e5e2da] bg-white hover:border-[#9a7b4f] hover:text-[#9a7b4f] text-[11px] font-mono uppercase tracking-wider font-semibold text-[#374151] transition-all">
                <x-icon name="download" class="w-3.5 h-3.5" /> Download File
              </a>
            @else
              <a @spa href="{{ route('contact') }}" class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-[#e5e2da] bg-white hover:border-[#9a7b4f] hover:text-[#9a7b4f] text-[11px] font-mono uppercase tracking-wider font-semibold text-[#374151] transition-all">
                <x-icon name="download" class="w-3.5 h-3.5" /> Request File
              </a>
            @endif
          </div>
        @empty
          <div class="p-10 text-center text-sm text-[#6b7280]">No technical files yet — ask the studio directly.</div>
        @endforelse
        <div id="file-empty" class="hidden p-10 text-center text-sm text-[#6b7280]">No files match your search. Try a different keyword or format.</div>
      </div>
      <p class="text-xs text-[#6b7280] text-center mt-6">Need a file you cannot find? <a @spa href="{{ route('contact') }}" class="text-[#9a7b4f] font-semibold hover:underline">Ask the studio</a> — issued within one working day.</p>
    </div>
  </section>

  <!-- Footer -->
  
@endsection
@section('script')
<script>
    /* toggleMobileMenu lives in header partial */

    /* files server-rendered by Blade; JS only filters visible rows */
    var activeFileFormat = 'all';
    var fileSearch = '';

    function filterFiles(format, btn) {
      activeFileFormat = format;
      document.querySelectorAll('.file-format-pill').forEach(p => {
        p.classList.remove('bg-[#9a7b4f]/10', 'border-[#9a7b4f]/50', 'text-[#1a1d24]');
        p.classList.add('bg-[#FAF9F5]', 'border-[#e5e2da]', 'text-[#6b7280]');
      });
      if (btn) {
        btn.classList.remove('bg-[#FAF9F5]', 'border-[#e5e2da]', 'text-[#6b7280]');
        btn.classList.add('bg-[#9a7b4f]/10', 'border-[#9a7b4f]/50', 'text-[#1a1d24]');
      }
      renderFiles();
    }

    function handleFileSearch(val) {
      fileSearch = (val || '').toLowerCase().trim();
      renderFiles();
    }

                function renderFiles() {
      // Rows are server-rendered; only toggle visibility.
      var rows = document.querySelectorAll('#file-list .file-row');
      var shown = 0;
      rows.forEach(function (row) {
        var okFormat = (activeFileFormat === 'all' || row.getAttribute('data-format') === activeFileFormat);
        var hay = (row.getAttribute('data-search') || '');
        var okSearch = (!fileSearch || hay.indexOf(fileSearch) !== -1);
        var show = okFormat && okSearch;
        row.classList.toggle('hidden', !show);
        if (show) shown++;
      });
      var count = document.getElementById('file-count');
      if (count) count.textContent = shown;
      var empty = document.getElementById('file-empty');
      if (empty) empty.classList.toggle('hidden', shown !== 0);
    }

                function downloadFile(idx) {
      // Legacy request-file helper (rows without a real file link to contact instead).
      if (window.showToast) showToast('Request noted', 'Please use the contact page and the studio will issue the file.');
    }

    renderFiles();
    if (window.lucide) lucide.createIcons();
  </script>
@endsection
