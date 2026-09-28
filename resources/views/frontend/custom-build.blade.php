@extends('frontend.layout')
@section('title', 'Custom Build | OriginSpaces Modular UK')
@section('content')


  <!-- Page Hero -->
  <section class="py-14 md:py-20 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-7xl mx-auto text-center max-w-3xl">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Custom Factory Order</span>
      <h1 class="font-serif text-4xl sm:text-5xl text-[#1a1d24] font-semibold tracking-tight">Build It Your Way.</h1>
      <p class="mt-4 text-sm text-[#374151] max-w-xl mx-auto">Four steps, one live specification, direct from our UK warehouse to your site.</p>
    </div>
  </section>

  <!-- Custom Factory Order Configurator Section - position 3 after applications -->
  <section id="custom-build-section" class="py-20 md:py-28 px-4 sm:px-6 lg:px-10 bg-[#f4f2ef] border-b border-[#e5e2da] relative">
    <div class="max-w-7xl mx-auto">
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#9a7b4f]/30 text-[#9a7b4f] text-xs font-mono font-semibold uppercase tracking-[0.2em] mb-4">
          <x-icon name="wrench" class="w-4 h-4" />
          <span>Direct Chinese Factory Production</span>
        </div>

        <h2 class="font-serif text-3xl sm:text-4xl md:text-5xl text-[#1a1d24] font-semibold tracking-tight mb-4">
          Custom Factory Order Configurator
        </h2>

        <p class="text-[#374151] text-base sm:text-lg leading-relaxed">
          Configure your exact footprint, layout, UK climate insulation, and turnkey interior. Your custom requirements are engineered build-to-order at our Chinese factory atelier, then fulfilled and dispatched directly to your site from our UK Central Distribution Warehouse.
        </p>
      </div>

      <!-- Main Configurator Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left 8 Cols: 4 Steps -->
        <div class="lg:col-span-8 bg-white border border-[#e5e2da] rounded-2xl p-6 sm:p-8 shadow-sm">
          <!-- Step Tabs -->
          <div class="flex items-center justify-between border-b border-[#eae7df] pb-4 mb-8 overflow-x-auto gap-2">
            <button onclick="goToConfigStep(1)" id="config-step-tab-1" class="step-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] sm:text-sm font-semibold transition-all whitespace-nowrap bg-[#181b20] text-white">
              <span>1. UK Application</span>
            </button>
            <button onclick="goToConfigStep(2)" id="config-step-tab-2" class="step-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] sm:text-sm font-semibold transition-all whitespace-nowrap text-[#6b7280] hover:text-[#9a7b4f]">
              <span>2. Footprint &amp; Chassis</span>
            </button>
            <button onclick="goToConfigStep(3)" id="config-step-tab-3" class="step-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] sm:text-sm font-semibold transition-all whitespace-nowrap text-[#6b7280] hover:text-[#9a7b4f]">
              <span>3. Insulation &amp; Facade</span>
            </button>
            <button onclick="goToConfigStep(4)" id="config-step-tab-4" class="step-tab-btn flex items-center gap-2 px-3.5 py-2 rounded-lg text-[13px] sm:text-sm font-semibold transition-all whitespace-nowrap text-[#6b7280] hover:text-[#9a7b4f]">
              <span>4. Equipment &amp; UK Delivery</span>
            </button>
          </div>

          <!-- Step 1 Pane -->
          <div id="config-step-pane-1" class="space-y-6">
            <div class="flex items-center justify-between">
              <h3 class="font-serif text-xl sm:text-2xl text-[#1a1d24] font-semibold">Select Intended UK Application</h3>
              <span class="text-xs font-mono text-[#9a7b4f] uppercase font-semibold">Step 1 of 4</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="config-usecase-options">
              <!-- Dynamically populated / styled via JS -->
            </div>
            <div class="pt-4 flex justify-end">
              <button onclick="goToConfigStep(2)" class="inline-flex items-center gap-2 bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs sm:text-sm uppercase tracking-wider font-semibold px-6 py-3.5 rounded-lg transition-all">
                <span>Next: Footprint &amp; Chassis</span>
                <x-icon name="arrow-right" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Step 2 Pane -->
          <div id="config-step-pane-2" class="space-y-6 hidden">
            <div class="flex items-center justify-between">
              <h3 class="font-serif text-xl sm:text-2xl text-[#1a1d24] font-semibold">Chassis Length &amp; Bedroom Partitioning</h3>
              <span class="text-xs font-mono text-[#9a7b4f] uppercase font-semibold">Step 2 of 4</span>
            </div>
            <div class="space-y-3" id="config-size-options">
              <!-- Dynamically populated via JS -->
            </div>
            <div class="pt-4 flex justify-between items-center">
              <button onclick="goToConfigStep(1)" class="text-xs sm:text-sm font-semibold text-[#6b7280] hover:text-[#1a1d24]">&larr; Back</button>
              <button onclick="goToConfigStep(3)" class="inline-flex items-center gap-2 bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs sm:text-sm uppercase tracking-wider font-semibold px-6 py-3.5 rounded-lg transition-all">
                <span>Next: Insulation &amp; Facade</span>
                <x-icon name="arrow-right" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Step 3 Pane -->
          <div id="config-step-pane-3" class="space-y-6 hidden">
            <div class="flex items-center justify-between">
              <h3 class="font-serif text-xl sm:text-2xl text-[#1a1d24] font-semibold">UK Climate Insulation &amp; Cladding</h3>
              <span class="text-xs font-mono text-[#9a7b4f] uppercase font-semibold">Step 3 of 4</span>
            </div>
            <div class="space-y-5">
              <div>
                <label class="block text-xs uppercase font-mono tracking-wider text-[#6b7280] font-semibold mb-2.5">Thermal Envelope Specification</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="config-insulation-options"></div>
              </div>
              <div>
                <label class="block text-xs uppercase font-mono tracking-wider text-[#6b7280] font-semibold mb-2.5">Exterior Facade Finish</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="config-facade-options"></div>
              </div>
            </div>
            <div class="pt-4 flex justify-between items-center">
              <button onclick="goToConfigStep(2)" class="text-xs sm:text-sm font-semibold text-[#6b7280] hover:text-[#1a1d24]">&larr; Back</button>
              <button onclick="goToConfigStep(4)" class="inline-flex items-center gap-2 bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs sm:text-sm uppercase tracking-wider font-semibold px-6 py-3.5 rounded-lg transition-all">
                <span>Next: Equipment &amp; UK Delivery</span>
                <x-icon name="arrow-right" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Step 4 Pane -->
          <div id="config-step-pane-4" class="space-y-6 hidden">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-serif text-xl sm:text-2xl text-[#1a1d24] font-semibold">Pre-Fitted Turnkey Equipment &amp; UK Warehouse Delivery</h3>
                <p class="text-xs text-[#6b7280] mt-1">Direct fulfillment: Dispatched from our central UK warehouse with crane offload or customer collection.</p>
              </div>
              <span class="text-xs font-mono text-[#9a7b4f] uppercase font-semibold shrink-0 ml-3">Step 4 of 4</span>
            </div>

            <!-- Addon Checkboxes -->
            <div>
              <label class="block text-xs uppercase font-mono tracking-wider text-[#6b7280] font-semibold mb-2.5">Factory Pre-Installed Turnkey Packages</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="config-addons-options"></div>
            </div>

            <!-- Warehouse Dispatch Selection -->
            <div class="pt-2">
              <div class="flex items-center justify-between mb-2.5">
                <label class="block text-xs uppercase font-mono tracking-wider text-[#6b7280] font-semibold">UK Warehouse Dispatch &amp; On-Site Delivery Method</label>
                <span class="text-[11px] font-mono text-[#9a7b4f] font-semibold flex items-center gap-1">
                  <x-icon name="truck" class="w-3.5 h-3.5" />
                  Central UK Warehouse Dispatch
                </span>
              </div>
              <div class="space-y-3" id="config-delivery-options"></div>

              <!-- Postcode Input -->
              <div class="mt-4 p-4 bg-[#FAF9F5] border border-[#E5E2DA] rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                  <x-icon name="truck" class="w-5 h-5 text-[#9a7b4f] shrink-0" />
                  <div>
                    <span class="text-xs font-semibold text-[#1a1d24] block">UK Delivery Postcode or County (Optional)</span>
                    <span class="text-[11px] text-[#6b7280] block">Allows our logistics dispatch team to pre-calculate haulage route and crane access</span>
                  </div>
                </div>
                <input 
                  type="text" 
                  id="input-delivery-postcode" 
                  oninput="updateConfigPostcode(this.value)" 
                  placeholder="e.g. GL54 3AA, Cotswolds..." 
                  class="bg-white border border-[#dcd8cd] focus:border-[#9a7b4f] rounded-lg px-3.5 py-2 text-xs text-[#1a1d24] w-full sm:w-60 focus:outline-none"
                />
              </div>

              <!-- Warehouse PDI Guarantee Banner -->
              <div class="mt-3 p-3 bg-white border border-[#e5e2da] rounded-xl flex items-start gap-2.5">
                <x-icon name="shield-check" class="w-4 h-4 text-[#9a7b4f] shrink-0 mt-0.5" />
                <p class="text-[11px] text-[#6b7280] leading-relaxed">
                  <strong class="text-[#1a1d24]">Direct UK Warehouse Logistics:</strong> All custom orders are imported under bonded transit to our UK central warehouse, where they undergo rigorous 50-point PDI (Pre-Delivery Inspection) before flatbed dispatch to your plot.
                </p>
              </div>
            </div>

            <div class="pt-4 flex justify-between items-center">
              <button onclick="goToConfigStep(3)" class="text-xs sm:text-sm font-semibold text-[#6b7280] hover:text-[#1a1d24]">&larr; Back</button>
              <button onclick="submitConfiguratorEnquiry()" class="inline-flex items-center gap-2 bg-[#9a7b4f] hover:bg-[#866940] text-white text-xs sm:text-sm uppercase tracking-wider font-semibold px-7 py-3.5 rounded-lg transition-all shadow-md">
                <span>Submit Factory Custom Spec</span>
                <x-icon name="arrow-right" class="w-4 h-4" />
              </button>
            </div>
          </div>

        </div>

        <!-- Right 4 Cols: Sticky Live Production Bill -->
        <div class="lg:col-span-4 bg-[#181b20] text-white rounded-2xl p-6 sm:p-7 border border-[#2d3139] shadow-xl sticky top-28">
          <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-5">
            <div>
              <span class="text-[10px] uppercase font-mono tracking-[0.25em] text-[#9a7b4f] font-semibold block">Live Production Bill</span>
              <h4 class="font-serif text-lg font-semibold text-white">Factory Build Spec</h4>
            </div>
            <div class="px-2.5 py-1 rounded bg-[#9a7b4f]/20 border border-[#9a7b4f]/40 text-[#9a7b4f] text-[11px] font-mono font-bold">
              BTO &bull; Build to Order
            </div>
          </div>

          <!-- Spec List Summary -->
          <div class="space-y-3.5 text-xs">
            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Use-Case:</span>
              <span id="bill-usecase" class="font-semibold text-white text-right">Garden Annex</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Chassis Model:</span>
              <span id="bill-chassis" class="font-semibold text-white text-right">30ft Sanctuary</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Floor Area:</span>
              <span id="bill-area" class="font-mono text-[#9a7b4f] font-bold text-right">56 m&sup2;</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Insulation:</span>
              <span id="bill-insulation" class="font-semibold text-white text-right">100mm PIR (Part L)</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Turnkey Addons:</span>
              <span id="bill-addons" class="font-semibold text-[#9a7b4f] text-right">3 Modules Pre-Fitted</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">Fulfillment Hub:</span>
              <span class="font-semibold text-[#9a7b4f] text-right text-[11px]">UK Central Warehouse</span>
            </div>

            <div class="flex justify-between items-start pb-2.5 border-b border-white/5">
              <span class="text-[#9ca3af]">UK Dispatch:</span>
              <span id="bill-dispatch" class="font-semibold text-white text-right text-[11px] max-w-[190px]">Direct UK Site Delivery (Hiab)</span>
            </div>

            <div id="bill-postcode-row" class="flex justify-between items-start pb-2.5 border-b border-white/5 hidden">
              <span class="text-[#9ca3af]">Site Location:</span>
              <span id="bill-postcode" class="font-mono text-[#9a7b4f] text-right uppercase text-[11px]">GL54 3AA</span>
            </div>
          </div>

          <!-- Production & Warehouse Schedule -->
          <div class="my-5 p-3.5 rounded-xl bg-white/5 border border-white/10 space-y-2">
            <div class="flex items-center gap-2 text-xs text-[#9a7b4f] font-mono font-semibold">
              <x-icon name="calendar" class="w-3.5 h-3.5" />
              <span>Production &amp; Warehouse Schedule:</span>
            </div>
            <div class="text-[11px] text-[#9ca3af] space-y-1 font-mono">
              <div>&bull; Precision Factory Build: 25 &ndash; 30 Days</div>
              <div>&bull; UK Warehouse Intake &amp; 50-Point PDI Check</div>
              <div>&bull; Final Direct Haulage from UK Warehouse</div>
              <div>&bull; On-Site Hydraulic Unfolding: &lt; 1 Hour</div>
            </div>
          </div>

          <!-- Base Guide Price -->
          <div class="pt-4 border-t border-white/10 mb-5">
            <span class="text-[11px] text-[#9ca3af] block font-mono">Factory Ex-Works / FOB Guide</span>
            <div class="flex items-baseline gap-2 mt-1">
              <span id="bill-price" class="font-serif text-2xl sm:text-3xl font-bold text-white">&pound;36,400</span>
              <span class="text-xs text-[#9ca3af]">+ UK haulage &amp; VAT</span>
            </div>
          </div>

          <button onclick="submitConfiguratorEnquiry()" class="w-full bg-[#9a7b4f] hover:bg-[#866940] text-white font-semibold text-xs sm:text-sm uppercase tracking-wider py-4 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
            <span>Request Factory CAD &amp; Quote</span>
            <x-icon name="arrow-right" class="w-4 h-4" />
          </button>

          <span class="text-[10px] text-[#6b7280] text-center block mt-3">
            No deposit required to receive custom factory CAD drawings and feasibility review.
          </span>
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
<script>
    var CONFIG_USE_CASES = [
      { id: 'annex', name: 'Residential Garden Annex (Caravan Act)', tag: 'Caravan Act Permitted' },
      { id: 'cafe', name: 'Commercial Coffee Shop / Food Kiosk', tag: 'Flip-Up Serving Hatch' },
      { id: 'office', name: 'Acoustic Garden Workspace', tag: 'Soundproof & Cat6' },
      { id: 'glamping', name: 'Luxury Holiday Glamping Suite', tag: '4-Season Part L' },
      { id: 'retail', name: 'Retail Pop-Up / Brand Pavilion', tag: 'Full Glass Front' }
    ];

    var CONFIG_SIZES = [
      { id: '20ft-compact', name: '20ft Compact Studio', area: '37 m²', dims: '5.9m × 6.3m', basePrice: '£24,800' },
      { id: '20ft-bifold', name: '20ft Bi-Fold Extended', area: '42 m²', dims: '5.9m × 7.2m', basePrice: '£27,200' },
      { id: '30ft-bifold', name: '30ft Sanctuary Bi-Fold', area: '56 m²', dims: '9.0m × 6.3m', basePrice: '£36,400' },
      { id: '40ft-trifold', name: '40ft Tri-Fold Residence', area: '74 m²', dims: '11.8m × 6.3m', basePrice: '£45,900' }
    ];

    var CONFIG_INSULATION = [
      { id: 'uk-part-l-100mm', name: '100mm PIR Core (UK Part L Building Regs)', rating: 'U ≤ 0.18 W/m²K &bull; All-Season UK Heat Retaining' },
      { id: 'rockwool-noncombustible', name: '100mm Rockwool (A1 Non-Combustible Fire Rated)', rating: 'BS 476 Part 7 Fire Rated &bull; High Acoustic Dampening' }
    ];

    var CONFIG_FACADES = [
      { id: 'charcoal-composite', name: 'Charcoal Slatted Composite', desc: 'Anthracite contemporary look' },
      { id: 'natural-cedar', name: 'Natural Western Red Cedar', desc: 'Architectural luxury timber' },
      { id: 'textured-sandstone', name: 'Textured Sandstone Panel', desc: 'Mineral aesthetic' }
    ];

    var CONFIG_ADDONS = [
      { id: 'ensuite-bathroom', label: 'Factory Pre-Fitted Ensuite with Shower, Vanity & Dual-Flush WC' },
      { id: 'fitted-kitchenette', label: 'Turnkey Kitchenette / Bar Counter with Sink & Induction' },
      { id: 'uk-consumer-unit', label: 'BS 7671 UK Consumer Unit with RCBO Circuit Breakers' },
      { id: 'inverter-heatpump', label: 'Split-System Heat Pump AC (Whisper-Quiet Heating & Cooling)' },
      { id: 'solar-prep', label: 'Pre-Wired Roof Solar PV Conduits & Inverter Ready' },
      { id: 'acoustic-lining', label: 'Extra Acoustic Rockwool Internal Soundproofing' }
    ];

    var CONFIG_DELIVERY = [
      {
        id: 'warehouse-hiab',
        name: 'Direct UK Site Delivery with Hiab Crane Offload',
        badge: 'Most Popular',
        desc: 'Dispatched directly from our UK central warehouse to your property or plot. Our rigid flatbed truck with on-board hydraulic Hiab crane offloads and positions the unit onto your prepared foundation pads or ground screws.',
        dispatch: 'Dispatched from UK Central Warehouse'
      },
      {
        id: 'turnkey-installation',
        name: 'Full White-Glove Warehouse Delivery & Turnkey Unfolding',
        badge: 'Zero-Effort',
        desc: 'Complete turnkey service from UK warehouse to handover: flatbed delivery, crane offloading, hydraulic unfolding of side wings, mechanical seal lock-in, and full electrical & plumbing connection check by our technician team.',
        dispatch: 'UK Warehouse + On-Site Crew'
      },
      {
        id: 'depot-collection',
        name: 'Client / Haulier Collection from UK Warehouse Depot',
        badge: 'Ex-Warehouse',
        desc: 'Arrange your own haulage or collection directly from our central UK logistics hub. Overhead gantry crane loading onto your transport vehicle is provided free of charge by our depot crew.',
        dispatch: 'Collect at UK Logistics Depot'
      }
    ];

    var configState = {
      step: 1,
      useCase: 'annex',
      modelSize: '30ft-bifold',
      insulation: 'uk-part-l-100mm',
      facade: 'charcoal-composite',
      addons: ['ensuite-bathroom', 'uk-consumer-unit', 'fitted-kitchenette'],
      delivery: 'warehouse-hiab',
      postcode: ''
    };

    // Page init runs at the end of this script (see bottom): it must execute
    // on hard load AND on every SPA injection, where DOMContentLoaded never fires.










    var activeFileFormat = 'all';
    var fileSearch = '';









    function goToConfigStep(stepNumber) {
      configState.step = stepNumber;
      for (let i = 1; i <= 4; i++) {
        const pane = document.getElementById('config-step-pane-' + i);
        const tab = document.getElementById('config-step-tab-' + i);
        if (pane && tab) {
          if (i === stepNumber) {
            pane.classList.remove('hidden');
            tab.classList.remove('text-[#6b7280]', 'hover:text-[#9a7b4f]');
            tab.classList.add('bg-[#181b20]', 'text-white');
          } else {
            pane.classList.add('hidden');
            tab.classList.remove('bg-[#181b20]', 'text-white');
            tab.classList.add('text-[#6b7280]', 'hover:text-[#9a7b4f]');
          }
        }
      }
    }

    function renderConfiguratorOptions() {
      const ucContainer = document.getElementById('config-usecase-options');
      if (ucContainer) {
        ucContainer.innerHTML = CONFIG_USE_CASES.map(uc => `
          <button 
            type="button"
            onclick="setConfigUseCase('${uc.id}')"
            class="p-4 rounded-xl border text-left transition-all flex items-start justify-between ${
              configState.useCase === uc.id 
                ? 'border-[#9a7b4f] bg-[#FAF9F5] ring-1 ring-[#9a7b4f]/40 font-semibold' 
                : 'border-[#e5e2da] bg-white hover:border-[#c8c3b7]'
            }"
          >
            <div>
              <span class="text-xs text-[#1a1d24] block">${uc.name}</span>
              <span class="text-[10px] font-mono text-[#9a7b4f] font-semibold">${uc.tag}</span>
            </div>
            <div class="w-4 h-4 rounded-full border mt-0.5 flex items-center justify-center ${
              configState.useCase === uc.id ? 'bg-[#9a7b4f] border-[#9a7b4f] text-white' : 'border-[#d1cece]'
            }">
              ${configState.useCase === uc.id ? '&bull;' : ''}
            </div>
          </button>
        `).join('');
      }

      const sizeContainer = document.getElementById('config-size-options');
      if (sizeContainer) {
        sizeContainer.innerHTML = CONFIG_SIZES.map(sz => `
          <button
            type="button"
            onclick="setConfigSize('${sz.id}')"
            class="w-full p-4 rounded-xl border text-left transition-all flex items-center justify-between ${
              configState.modelSize === sz.id
                ? 'border-[#9a7b4f] bg-[#FAF9F5] ring-1 ring-[#9a7b4f]/40'
                : 'border-[#e5e2da] bg-white hover:border-[#c8c3b7]'
            }"
          >
            <div>
              <div class="flex items-center gap-2">
                <strong class="text-xs sm:text-sm text-[#1a1d24]">${sz.name}</strong>
                <span class="text-[10px] font-mono text-[#9a7b4f] font-semibold bg-[#FAF9F5] px-2 py-0.5 rounded border border-[#9a7b4f]/30">${sz.area}</span>
              </div>
              <span class="text-[11px] text-[#6b7280] block mt-0.5">Footprint: ${sz.dims} &bull; Hydraulic Unfolding</span>
            </div>
            <div class="text-right">
              <span class="font-serif text-lg font-bold text-[#1a1d24]">${sz.basePrice}</span>
              <span class="text-[10px] text-[#6b7280] block font-mono">Factory Base</span>
            </div>
          </button>
        `).join('');
      }

      const insContainer = document.getElementById('config-insulation-options');
      if (insContainer) {
        insContainer.innerHTML = CONFIG_INSULATION.map(ins => `
          <button
            type="button"
            onclick="setConfigInsulation('${ins.id}')"
            class="p-3.5 rounded-xl border text-left transition-all ${
              configState.insulation === ins.id
                ? 'border-[#9a7b4f] bg-[#FAF9F5] font-semibold text-[#1a1d24]'
                : 'border-[#e5e2da] bg-white text-[#6b7280]'
            }"
          >
            <span class="text-xs text-[#1a1d24] block">${ins.name}</span>
            <span class="text-[11px] text-[#6b7280] block mt-1 leading-snug">${ins.rating}</span>
          </button>
        `).join('');
      }

      const facContainer = document.getElementById('config-facade-options');
      if (facContainer) {
        facContainer.innerHTML = CONFIG_FACADES.map(fc => `
          <button
            type="button"
            onclick="setConfigFacade('${fc.id}')"
            class="p-3.5 rounded-xl border text-left transition-all ${
              configState.facade === fc.id
                ? 'border-[#9a7b4f] bg-[#FAF9F5] font-semibold text-[#1a1d24]'
                : 'border-[#e5e2da] bg-white text-[#6b7280]'
            }"
          >
            <span class="text-xs text-[#1a1d24] block">${fc.name}</span>
            <span class="text-[11px] text-[#6b7280] block mt-1">${fc.desc}</span>
          </button>
        `).join('');
      }

      const addonsContainer = document.getElementById('config-addons-options');
      if (addonsContainer) {
        addonsContainer.innerHTML = CONFIG_ADDONS.map(ad => {
          const isChecked = configState.addons.includes(ad.id);
          return `
            <button
              type="button"
              onclick="toggleConfigAddon('${ad.id}')"
              class="p-3 rounded-xl border text-left transition-all flex items-start gap-2.5 ${
                isChecked
                  ? 'border-[#9a7b4f] bg-[#FAF9F5] text-[#1a1d24]'
                  : 'border-[#e5e2da] bg-white text-[#6b7280]'
              }"
            >
              <div class="w-4 h-4 rounded border mt-0.5 flex items-center justify-center shrink-0 ${
                isChecked ? 'bg-[#9a7b4f] border-[#9a7b4f] text-white' : 'border-[#d1cece]'
              }">
                ${isChecked ? '&check;' : ''}
              </div>
              <span class="text-xs leading-relaxed font-semibold">${ad.label}</span>
            </button>
          `;
        }).join('');
      }

      const deliveryContainer = document.getElementById('config-delivery-options');
      if (deliveryContainer) {
        deliveryContainer.innerHTML = CONFIG_DELIVERY.map(opt => `
          <button
            type="button"
            onclick="setConfigDelivery('${opt.id}')"
            class="w-full p-4 rounded-xl border text-left transition-all ${
              configState.delivery === opt.id
                ? 'border-[#9a7b4f] bg-[#FAF9F5] ring-1 ring-[#9a7b4f]/40 text-[#1a1d24]'
                : 'border-[#e5e2da] bg-white text-[#374151]'
            }"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-bold text-[#1a1d24]">${opt.name}</span>
                  <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded-full font-semibold ${
                    configState.delivery === opt.id ? 'bg-[#9a7b4f] text-white' : 'bg-[#f0ede6] text-[#6b7280]'
                  }">${opt.badge}</span>
                </div>
                <p class="text-[11px] text-[#6b7280] leading-relaxed">${opt.desc}</p>
                <span class="text-[10px] font-mono text-[#9a7b4f] font-semibold block">&bull; ${opt.dispatch}</span>
              </div>
              <div class="w-4 h-4 rounded-full border mt-1 shrink-0 flex items-center justify-center ${
                configState.delivery === opt.id ? 'bg-[#9a7b4f] border-[#9a7b4f] text-white' : 'border-[#d1cece]'
              }">
                ${configState.delivery === opt.id ? '&bull;' : ''}
              </div>
            </div>
          </button>
        `).join('');
      }
    }

    function setConfigUseCase(id) {
      configState.useCase = id;
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function setConfigSize(id) {
      configState.modelSize = id;
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function setConfigInsulation(id) {
      configState.insulation = id;
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function setConfigFacade(id) {
      configState.facade = id;
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function toggleConfigAddon(id) {
      if (configState.addons.includes(id)) {
        configState.addons = configState.addons.filter(a => a !== id);
      } else {
        configState.addons.push(id);
      }
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function setConfigDelivery(id) {
      configState.delivery = id;
      renderConfiguratorOptions();
      updateLiveBill();
    }

    function updateConfigPostcode(val) {
      configState.postcode = val;
      updateLiveBill();
    }

    function updateLiveBill() {
      const uc = CONFIG_USE_CASES.find(u => u.id === configState.useCase) || CONFIG_USE_CASES[0];
      const sz = CONFIG_SIZES.find(s => s.id === configState.modelSize) || CONFIG_SIZES[2];
      const del = CONFIG_DELIVERY.find(d => d.id === configState.delivery) || CONFIG_DELIVERY[0];

      document.getElementById('bill-usecase').textContent = uc.name.split(' (')[0];
      document.getElementById('bill-chassis').textContent = sz.name;
      document.getElementById('bill-area').textContent = sz.area;
      document.getElementById('bill-insulation').textContent = configState.insulation === 'uk-part-l-100mm' ? '100mm PIR (Part L)' : '100mm Rockwool (A1)';
      document.getElementById('bill-addons').textContent = `${configState.addons.length} Modules Pre-Fitted`;
      document.getElementById('bill-dispatch').textContent = del.name;
      document.getElementById('bill-price').textContent = sz.basePrice;

      const postcodeRow = document.getElementById('bill-postcode-row');
      const postcodeVal = document.getElementById('bill-postcode');
      if (configState.postcode && configState.postcode.trim() !== '') {
        postcodeRow.classList.remove('hidden');
        postcodeVal.textContent = configState.postcode.toUpperCase();
      } else {
        postcodeRow.classList.add('hidden');
      }
    }

    function submitConfiguratorEnquiry() {
      const uc = CONFIG_USE_CASES.find(u => u.id === configState.useCase) || CONFIG_USE_CASES[0];
      const sz = CONFIG_SIZES.find(s => s.id === configState.modelSize) || CONFIG_SIZES[2];
      const del = CONFIG_DELIVERY.find(d => d.id === configState.delivery) || CONFIG_DELIVERY[0];

      const summaryText = `Model: ${sz.name} (${sz.area}) | Use-Case: ${uc.name} | Insulation: ${configState.insulation} | Facade: ${configState.facade} | Fulfillment: Dispatched from UK Central Warehouse (${del.name}) | Base Guide: ${sz.basePrice}`;

      openEnquiryModal(`Custom Factory Order: ${uc.name}`, summaryText, configState.postcode);
    }

    // Bridge to the shared footer modal (real POST to enquiries inbox).
    function openEnquiryModal(title, summary, postcode) {
      if (postcode) {
        var pf = document.getElementById('enquiry-postcode');
        if (pf && !pf.value) pf.value = postcode;
      }
      var src = document.getElementById('enquiry-source');
      if (src) src.value = 'custom-build';
      window.__footerOpenEnquiry(title ? `Enquiry: ${title}` : 'Request Factory Quote & CAD Pack', null, summary || '');
    }

    // Page init: runs on hard load (script sits at body end, DOM ready) and on
    // every SPA injection. DOMContentLoaded would never fire after SPA navigation.
    renderConfiguratorOptions();
    updateLiveBill();
    if (window.lucide) {
      lucide.createIcons();
    }


  </script>
@endsection
