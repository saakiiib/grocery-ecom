/* Evergreen Foods storefront — vanilla JS, SPA re-runnable.
   Rules: var only (shared global scope), no DOMContentLoaded (init runs
   directly + on spa:loaded). Document-level listeners bind exactly once. */

(function () {
  'use strict';

  function routes() {
    return window.EGF_ROUTES || { shop: '/shop', checkout: '/checkout', product: '/product' };
  }

  function placeholderImg() {
    return (window.EGF_ASSETS && window.EGF_ASSETS.placeholder) || '';
  }

  function formatPrice(n) {
    return '£' + Number(n).toFixed(2);
  }

  /* Escape admin/shopper text before innerHTML (names, packs, promo labels, queries). */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------------- Bag (server session — prices always come from the DB) ---------------- */
  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function bagPost(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      body: JSON.stringify(data || {})
    }).then(function (res) {
      return res.json().then(function (json) {
        if (!res.ok) throw json;
        return json;
      });
    });
  }

  function refreshBagUI(data) {
    if (!data) return;
    document.querySelectorAll('[data-bag-count]').forEach(function (el) { el.textContent = data.count || 0; });
    document.querySelectorAll('[data-bag-total]').forEach(function (el) { el.textContent = formatPrice(data.subtotal || 0); });
  }

  function loadBag() {
    var r = routes();
    if (!r.bagData) return;
    fetch(r.bagData, { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (data) { refreshBagUI(data); renderBagPage(data); })
      .catch(function () { /* bag badge keeps its server-rendered count */ });
  }

  function flashAdded(btn) {
    if (!btn || btn.disabled) return;
    if (btn._addedOriginal === undefined) btn._addedOriginal = btn.textContent;
    btn.textContent = '✓ Added';
    btn.classList.add('is-added');
    clearTimeout(btn._addedTimer);
    btn._addedTimer = setTimeout(function () {
      btn.textContent = btn._addedOriginal;
      btn.classList.remove('is-added');
    }, 1800);
  }

  function addToBag(variantId, qty, btn) {
    var r = routes();
    bagPost(r.bagAdd, { variant_id: parseInt(variantId, 10), qty: qty || 1 })
      .then(function (data) {
        refreshBagUI(data);
        showToast(data.message || 'Added to your bag');
        flashAdded(btn);
        if (document.querySelector('[data-bag-items]')) renderBagPage(data);
      })
      .catch(function (err) { showToast((err && err.message) || 'Sorry, that item is unavailable.'); });
  }

  /* ---------------- Toast ---------------- */
  function showToast(msg) {
    var t = document.querySelector('.egf-toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'egf-toast toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(function () { t.classList.remove('show'); }, 2600);
  }

  /* ---------------- Mobile nav ---------------- */
  function initMobileNav() {
    var btn = document.querySelector('[data-mobile-menu]');
    var nav = document.querySelector('.mobile-nav');
    if (!btn || !nav || nav._egfBound) return;
    nav._egfBound = true;
    btn.addEventListener('click', function () { nav.classList.add('open'); });
    nav.querySelectorAll('[data-close-menu]').forEach(function (b) {
      b.addEventListener('click', function () { nav.classList.remove('open'); });
    });
    var overlay = nav.querySelector('.mobile-nav-overlay');
    if (overlay) overlay.addEventListener('click', function () { nav.classList.remove('open'); });
  }

  /* ---------------- Card pack selects (home / shop / offers / search) ---------------- */
  function initCardPackSelects() {
    document.querySelectorAll('.product-card[data-card]').forEach(function (card) {
      if (card._egfBound) return;
      card._egfBound = true;
      var select = card.querySelector('.product-pack select');
      var priceEl = card.querySelector('[data-card-price]');
      var oldEl = card.querySelector('[data-card-old]');
      var btn = card.querySelector('[data-add-to-bag]');
      if (!select || !btn) return;
      select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        if (priceEl) priceEl.textContent = formatPrice(opt.dataset.price || 0);
        if (oldEl) {
          if (opt.dataset.old) { oldEl.textContent = formatPrice(opt.dataset.old); oldEl.style.display = ''; }
          else { oldEl.style.display = 'none'; }
        }
        btn.dataset.variantId = opt.value;
        btn.dataset.price = opt.dataset.price || 0;
        btn.dataset.pack = opt.dataset.pack || '';
        btn.dataset.image = opt.dataset.image || btn.dataset.image || '';
        var out = opt.dataset.stock === '0';
        btn.disabled = out;
        btn.textContent = out ? 'Out of stock' : 'Add to bag';
      });
    });
  }

  /* ---------------- Detail-page variant picker ---------------- */
  function initDetailPicker() {
    var root = document.querySelector('[data-variant-picker]');
    if (!root || root._egfBound) return;
    root._egfBound = true;
    var variants = [];
    var groups = [];
    try {
      var vEl = root.dataset.variantsId ? document.getElementById(root.dataset.variantsId) : null;
      var gEl = root.dataset.groupsId ? document.getElementById(root.dataset.groupsId) : null;
      variants = JSON.parse(vEl ? vEl.textContent : (root.dataset.variants || '[]'));
      groups = JSON.parse(gEl ? gEl.textContent : (root.dataset.groups || '[]'));
    } catch (e) { variants = []; groups = []; }

    var priceEl = document.querySelector('[data-current-price]');
    var oldEl = document.querySelector('[data-old-price]');
    var saveEl = document.querySelector('[data-save-badge]');
    var packNote = document.querySelector('[data-pack-note]');
    var skuNote = document.querySelector('[data-sku-note]');
    var expiresNote = document.querySelector('[data-expires-note]');
    var mainImg = document.querySelector('[data-main-image]');
    var addBtn = document.querySelector('[data-detail-add]');
    var stockNote = document.querySelector('[data-stock-note]');

    function selected() {
      var sel = {};
      groups.forEach(function (g) {
        var scope = root.querySelector('[data-group="' + g.slug + '"]');
        if (!scope) return;
        var active = scope.querySelector('.pack-opt.active');
        if (active) { sel[g.slug] = active.dataset.value; return; }
        var dropdown = scope.querySelector('select');
        if (dropdown && dropdown.value) sel[g.slug] = dropdown.value;
      });
      return sel;
    }

    function findVariant(sel) {
      for (var i = 0; i < variants.length; i++) {
        var v = variants[i];
        var ok = true;
        for (var k in sel) {
          if ((v.values[k] || null) !== sel[k]) { ok = false; break; }
        }
        if (ok) return v;
      }
      return null;
    }

    function paint(v) {
      if (!v) return;
      if (priceEl) priceEl.textContent = formatPrice(v.selling);
      if (oldEl) {
        if (v.old) { oldEl.textContent = formatPrice(v.old); oldEl.style.display = ''; }
        else { oldEl.style.display = 'none'; }
      }
      if (saveEl) {
        if (v.save_pct) { saveEl.textContent = 'Save ' + v.save_pct + '%'; saveEl.style.display = ''; }
        else { saveEl.style.display = 'none'; }
      }
      if (packNote) packNote.textContent = (v.pack || '') + ' · Price includes all taxes';
      if (skuNote) {
        if (v.sku) { skuNote.textContent = 'SKU: ' + v.sku; skuNote.style.display = ''; }
        else { skuNote.style.display = 'none'; }
      }
      if (expiresNote) {
        if (v.expires) { expiresNote.textContent = 'Best before: ' + v.expires; expiresNote.style.display = ''; }
        else { expiresNote.style.display = 'none'; }
      }
      if (mainImg && v.image) mainImg.src = v.image;
      var listVariant = document.querySelector('[data-list-variant]');
      var listSave = document.querySelector('[data-list-save]');
      if (listVariant) listVariant.value = v.id;
      if (listSave && listSave.getAttribute('action') === '#') {
        var listSel = listSave.querySelector('[data-list-select]');
        if (listSel && listSel.value) listSave.setAttribute('action', '/account/lists/' + listSel.value + '/items');
      }
      if (addBtn) {
        addBtn.dataset.variantId = v.id;
        addBtn.dataset.price = v.selling;
        addBtn.dataset.pack = v.pack || '';
        if (v.image) addBtn.dataset.image = v.image;
        addBtn.disabled = !v.in_stock;
        addBtn.textContent = v.in_stock ? '+ Add to bag' : 'Out of stock';
      }
      if (stockNote) stockNote.style.display = v.in_stock ? 'none' : '';
    }

    root.querySelectorAll('.pack-opt').forEach(function (b) {
      b.addEventListener('click', function () {
        var scope = b.closest('[data-group]');
        scope.querySelectorAll('.pack-opt').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
        paint(findVariant(selected()));
      });
    });
    root.querySelectorAll('select[data-group-select]').forEach(function (s) {
      s.addEventListener('change', function () { paint(findVariant(selected())); });
    });

    var listSaveOnce = document.querySelector('[data-list-save] [data-list-select]');
    var listSaveForm = document.querySelector('[data-list-save]');
    if (listSaveOnce && listSaveForm && !listSaveForm._egfBound) {
      listSaveForm._egfBound = true;
      listSaveOnce.addEventListener('change', function () {
        listSaveForm.setAttribute('action', '/account/lists/' + listSaveOnce.value + '/items');
      });
    }

    paint(findVariant(selected()) || variants.filter(function (v) { return v.is_default; })[0] || variants[0]);
  }

  /* ---------------- Qty controls ---------------- */
  function initQtyControls() {
    document.querySelectorAll('.qty-control').forEach(function (ctrl) {
      if (ctrl._egfBound) return;
      ctrl._egfBound = true;
      var minus = ctrl.querySelector('[data-qty-minus]');
      var plus = ctrl.querySelector('[data-qty-plus]');
      var display = ctrl.querySelector('[data-qty-value]');
      if (!minus || !plus || !display) return;
      minus.addEventListener('click', function () {
        var v = parseInt(display.textContent, 10) || 1;
        if (v > 1) display.textContent = v - 1;
      });
      plus.addEventListener('click', function () {
        var v = parseInt(display.textContent, 10) || 1;
        display.textContent = v + 1;
      });
    });
  }

  /* ---------------- Add to bag (server session) ---------------- */
  function initAddToBag() {
    document.querySelectorAll('[data-add-to-bag]').forEach(function (btn) {
      if (btn._egfBound) return;
      btn._egfBound = true;
      btn.addEventListener('click', function () {
        var qty = 1;
        var qtyEl = document.querySelector('[data-qty-value]');
        if (qtyEl) qty = parseInt(qtyEl.textContent, 10) || 1;
        addToBag(btn.dataset.variantId || btn.dataset.id, qty, btn);
      });
    });
  }

  /* ---------------- Bag page (server-rendered, JS keeps it live) ---------------- */
  function renderBagPage(data) {
    var container = document.querySelector('[data-bag-items]');
    if (!container) return;
    if (!data) { loadBag(); return; }
    var r = routes();
    refreshBagUI(data);
    if (!data.lines || data.lines.length === 0) {
      container.innerHTML =
        '<div class="empty-state"><h2>Your bag is empty</h2>' +
        '<p>Browse the market and add some fresh finds.</p>' +
        '<a href="' + r.shop + '" class="btn btn-dark">Shop groceries</a></div>';
      updateBagSummary(data);
      return;
    }
    container.innerHTML = data.lines.map(function (item) {
      var img = item.image || placeholderImg();
      var link = item.slug ? r.product + '/' + item.slug : r.shop;
      var row = '<div class="cart-item" data-key="' + item.variant_id + '">' +
        '<a data-spa href="' + link + '"><img src="' + img + '" alt="' + esc(item.name) + '" loading="lazy"></a>' +
        '<div><div class="cart-item-name">' + esc(item.name) + '</div>' +
        (item.pack ? '<div class="cart-item-meta">' + esc(item.pack) + '</div>' : '') +
        (item.promo_label ? '<div><span class="promo-tag">' + esc(item.promo_label) + (item.free_qty ? ' · ' + item.free_qty + ' free' : '') + '</span></div>' : '') +
        (item.available
          ? '<div class="cart-item-price">' + formatPrice(item.price) + '</div>'
          : '<div class="cart-item-meta" style="color:#B91C1C">No longer available</div>') +
        '<div class="qty-control mt-1" style="display:inline-flex">' +
        '<button type="button" data-bag-minus aria-label="Decrease">−</button>' +
        '<span data-bag-qty>' + item.qty + '</span>' +
        '<button type="button" data-bag-plus aria-label="Increase">+</button></div></div>' +
        '<div><div class="cart-item-price">' + formatPrice(item.line_total) + '</div>' +
        '<button type="button" class="icon-btn" data-bag-remove aria-label="Remove" title="Remove">' +
        '<svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div></div>';

      return row;
    }).join('');

    container.querySelectorAll('[data-bag-remove]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        bagPost(r.bagRemove, { variant_id: btn.closest('.cart-item').dataset.key })
          .then(function (d) { renderBagPage(d); })
          .catch(function (err) { showToast((err && err.message) || 'Could not remove that item.'); });
      });
    });
    container.querySelectorAll('[data-bag-minus]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('.cart-item');
        var span = row.querySelector('[data-bag-qty]');
        var q = Math.max(0, (parseInt(span.textContent, 10) || 1) - 1);
        bagPost(r.bagUpdate, { variant_id: row.dataset.key, qty: q })
          .then(function (d) { renderBagPage(d); })
          .catch(function (err) { showToast((err && err.message) || 'Could not update that item.'); });
      });
    });
    container.querySelectorAll('[data-bag-plus]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = btn.closest('.cart-item');
        var span = row.querySelector('[data-bag-qty]');
        var q = (parseInt(span.textContent, 10) || 0) + 1;
        bagPost(r.bagUpdate, { variant_id: row.dataset.key, qty: q })
          .then(function (d) { renderBagPage(d); })
          .catch(function (err) { showToast((err && err.message) || 'Could not update that item.'); });
      });
    });
    updateBagSummary(data);
  }

  function updateBagSummary(data) {
    var sub = document.querySelector('[data-summary-subtotal]');
    var tot = document.querySelector('[data-summary-total]');
    var bogoRow = document.querySelector('[data-summary-bogo]');
    var bogoAmt = document.querySelector('[data-summary-bogo-amount]');
    var bundleRow = document.querySelector('[data-summary-bundle]');
    var bundleAmt = document.querySelector('[data-summary-bundle-amount]');
    var t = data ? data.subtotal || 0 : 0;
    var save = data ? data.bogo_discount || 0 : 0;
    var bsave = data ? data.bundle_discount || 0 : 0;
    if (sub) sub.textContent = formatPrice(t);
    if (tot) tot.textContent = formatPrice(t);
    if (bogoRow) bogoRow.style.display = save > 0 ? '' : 'none';
    if (bogoAmt) bogoAmt.textContent = '−' + formatPrice(save);
    if (bundleRow) bundleRow.style.display = bsave > 0 ? '' : 'none';
    if (bundleAmt) bundleAmt.textContent = '−' + formatPrice(bsave);
    updateDeliveryBar(data, t);
  }

  function updateDeliveryBar(data, subtotal) {
    var bar = document.querySelector('[data-delivery-bar]');
    if (!bar) return;
    var min = data && data.min_order ? parseFloat(data.min_order) : parseFloat(bar.getAttribute('data-min')) || 15;
    var free = data && data.free_over ? parseFloat(data.free_over) : parseFloat(bar.getAttribute('data-free')) || 50;
    var t = parseFloat(subtotal) || 0;
    var pct = free > 0 ? Math.min(100, Math.max(0, t / free * 100)) : 0;
    var fill = bar.querySelector('[data-delivery-fill]');
    var msg = bar.querySelector('[data-delivery-msg]');
    if (fill) fill.style.width = pct.toFixed(1) + '%';
    bar.classList.toggle('is-free', free > 0 && t >= free);
    if (msg) {
      if (free > 0 && t >= free) msg.textContent = "You've unlocked FREE delivery";
      else if (t >= min) msg.textContent = 'Add ' + formatPrice(free - t) + ' more for FREE delivery';
      else msg.textContent = 'Minimum order ' + formatPrice(min) + ' — add ' + formatPrice(min - t) + ' more';
    }
  }

  /* ---------------- Hero slider ---------------- */
  function initHeroSlider() {
    var slider = document.querySelector('[data-hero-slider]');
    if (!slider || slider._egfBound) return;
    slider._egfBound = true;
    var slides = [].slice.call(slider.querySelectorAll('.hero-slide'));
    var dots = [].slice.call(slider.querySelectorAll('.hero-dot'));
    if (slides.length < 2) return;
    var index = 0;
    var timer = null;
    var DURATION = 6000;

    function go(next) {
      index = (next + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('active', i === index); });
      dots.forEach(function (d, i) {
        var on = i === index;
        d.classList.toggle('active', on);
        d.setAttribute('aria-current', on ? 'true' : 'false');
      });
      restart();
    }
    function restart() {
      clearInterval(timer);
      timer = setInterval(function () { go(index + 1); }, DURATION);
    }
    function resume() {
      slider.classList.remove('paused');
      var d = slider.querySelector('.hero-dot.active');
      if (d) { d.classList.remove('active'); void d.offsetWidth; d.classList.add('active'); }
      restart();
    }

    var next = slider.querySelector('.hero-arrow.next');
    var prev = slider.querySelector('.hero-arrow.prev');
    if (next) next.addEventListener('click', function () { go(index + 1); });
    if (prev) prev.addEventListener('click', function () { go(index - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });

    slider.addEventListener('mouseenter', function () {
      slider.classList.add('paused');
      clearInterval(timer);
      timer = null;
    });
    slider.addEventListener('mouseleave', function () {
      resume();
    });
    slider._egfResume = resume;
    slider._egfPause = function () {
      slider.classList.add('paused');
      clearInterval(timer);
      timer = null;
    };
    if (!window._egfSliderVisBound) {
      window._egfSliderVisBound = true;
        document.addEventListener('visibilitychange', function () {
        document.querySelectorAll('[data-hero-slider]').forEach(function (n) {
          if (document.hidden) { if (n._egfPause) n._egfPause(); }
          else if (n._egfResume) { n._egfResume(); }
        });
      });
    }

    var startX = null;
    slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; clearInterval(timer); timer = null; slider.classList.add('paused'); }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      slider.classList.remove('paused');
      if (Math.abs(dx) > 45) { go(index + (dx < 0 ? 1 : -1)); } else { resume(); }
      startX = null;
    });

    if (!window._egfKeysBound) {
      window._egfKeysBound = true;
      document.addEventListener('keydown', function (e) {
        var sliderNow = document.querySelector('[data-hero-slider]');
        if (!sliderNow) return;
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          var slidesNow = sliderNow.querySelectorAll('.hero-slide');
          var activeIdx = 0;
          slidesNow.forEach(function (s, i) { if (s.classList.contains('active')) activeIdx = i; });
          var nextIdx = (activeIdx + (e.key === 'ArrowRight' ? 1 : slidesNow.length - 1)) % slidesNow.length;
          slidesNow.forEach(function (s, i) { s.classList.toggle('active', i === nextIdx); });
          sliderNow.querySelectorAll('.hero-dot').forEach(function (d, i) { d.classList.toggle('active', i === nextIdx); });
        }
        if (e.key === 'Escape') closeSearch();
      });
    }

    go(0);
  }

  /* ---------------- Testimonials slider ---------------- */
  function initTmnSlider() {
    var slider = document.querySelector('[data-tmn-slider]');
    if (!slider || slider._egfBound) return;
    slider._egfBound = true;
    var slides = [].slice.call(slider.querySelectorAll('.tmn-slide'));
    var dots = [].slice.call(slider.querySelectorAll('.tmn-dot'));
    if (slides.length < 2) return;
    var index = 0;
    var timer = null;
    var DURATION = 5000;

    function go(next) {
      index = (next + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('active', i === index); });
      dots.forEach(function (d, i) { d.classList.toggle('active', i === index); });
      restart();
    }
    function restart() {
      clearInterval(timer);
      timer = setInterval(function () { go(index + 1); }, DURATION);
    }
    function pause() { clearInterval(timer); timer = null; }

    var next = slider.querySelector('.tmn-arrow.next');
    var prev = slider.querySelector('.tmn-arrow.prev');
    if (next) next.addEventListener('click', function () { go(index + 1); });
    if (prev) prev.addEventListener('click', function () { go(index - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });
    slider.addEventListener('mouseenter', pause);
    slider.addEventListener('mouseleave', restart);
    slider._egfPause = pause;
    slider._egfResume = restart;
    if (!window._egfTmnVisBound) {
      window._egfTmnVisBound = true;
      document.addEventListener('visibilitychange', function () {
        document.querySelectorAll('[data-tmn-slider]').forEach(function (n) {
          if (document.hidden) { if (n._egfPause) n._egfPause(); }
          else if (n._egfResume) { n._egfResume(); }
        });
      });
    }
    var startX = null;
    slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; pause(); }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 45) { go(index + (dx < 0 ? 1 : -1)); } else { restart(); }
      startX = null;
    });

    go(0);
  }

  /* ---------------- FAQ category tabs ---------------- */
  function initFaqTabs() {
    var bars = document.querySelectorAll('[data-faq-tabs]');
    for (var i = 0; i < bars.length; i++) {
      (function (bar) {
        if (bar._egfBound) return;
        bar._egfBound = true;
        var tabs = bar.querySelectorAll('[data-faq-tab]');
        var scope = bar.parentElement;
        for (var j = 0; j < tabs.length; j++) {
          tabs[j].addEventListener('click', function () {
            var idx = this.getAttribute('data-faq-tab');
            for (var k = 0; k < tabs.length; k++) {
              tabs[k].classList.toggle('active', tabs[k] === this);
            }
            var panels = scope.querySelectorAll('[data-faq-panel]');
            for (var m = 0; m < panels.length; m++) {
              (function (panel) {
                var show = panel.getAttribute('data-faq-panel') === idx;
                panel.classList.toggle('is-hidden', !show);
                if (show) {
                  var first = panel.querySelector('details.faq-item');
                  if (first) first.open = true;
                }
              })(panels[m]);
            }
          });
        }
      })(bars[i]);
    }
  }

  /* ---------------- Shop filters collapse (mobile) ---------------- */
  function initShopFiltersToggle() {
    var btn = document.querySelector('[data-shop-filters-toggle]');
    var panel = document.querySelector('[data-shop-filters]');
    if (!btn || !panel || btn._egfBound) return;
    btn._egfBound = true;
    btn.addEventListener('click', function () {
      var open = panel.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* ---------------- Search overlay (server catalog) ---------------- */
  var searchEl = null;

  function catalog() {
    return window.EGF_CATALOG || [];
  }

  var catLoading = false;
  var catQueue = [];

  function ensureCatalog(done) {
    if (window.EGF_CATALOG && window.EGF_CATALOG.length) { done(); return; }
    catQueue.push(done);
    if (catLoading) return;
    catLoading = true;
    var url = (routes().catalog || '/search-catalog');
    fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (res) { return res.json(); }).then(function (json) {
      window.EGF_CATALOG = json || [];
    }).catch(function () {
      window.EGF_CATALOG = [];
    }).then(function () {
      catLoading = false;
      var q = catQueue;
      catQueue = [];
      q.forEach(function (fn) { fn(); });
    });
  }

  function buildSearch() {
    if (searchEl && document.body.contains(searchEl)) return searchEl;
    var wrap = document.createElement('div');
    wrap.className = 'search-overlay';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'Search products');
    wrap.innerHTML =
      '<div class="search-backdrop" data-search-close></div>' +
      '<div class="search-panel"><div class="container">' +
      '<div class="search-field">' +
      '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>' +
      '<input type="search" id="egf-search-input" placeholder="Search for lamb, rice, salt…" aria-label="Search products" autocomplete="off">' +
      '<button type="button" class="search-close" data-search-close>Close</button></div>' +
      '<div class="search-hint">Popular: ' +
      ['Meat', 'Rice', 'Pantry', 'Offers'].map(function (t) {
        return '<button type="button" class="search-chip" data-search-term="' + t + '">' + t + '</button>';
      }).join('') +
      '</div><p class="search-count" data-search-count></p>' +
      '<div class="search-results" data-search-results></div>' +
      '</div></div>';
    document.body.appendChild(wrap);
    searchEl = wrap;

    wrap.querySelectorAll('[data-search-close]').forEach(function (b) {
      b.addEventListener('click', closeSearch);
    });
    wrap.querySelectorAll('[data-search-term]').forEach(function (b) {
      b.addEventListener('click', function () {
        var input = wrap.querySelector('#egf-search-input');
        input.value = b.dataset.searchTerm;
        renderResults(input.value);
        input.focus();
      });
    });
    wrap.querySelector('#egf-search-input').addEventListener('input', function () { renderResults(this.value); });
    renderResults('');
    return wrap;
  }

  function cardHTML(p) {
    var r = routes();
    /* data-spa here is the sanctioned JS-runtime exception (Blade @spa can't compile JS-built markup). */
    return '<article class="product-card" data-card>' +
      '<a data-spa href="' + r.product + '/' + p.slug + '" class="product-img-wrap">' +
      '<img src="' + p.img + '" data-full="' + (p.full || '') + '" alt="' + esc(p.name) + '" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=this.dataset.full||this.src;"></a>' +
      '<div class="product-body"><span class="product-meta">' + esc(p.cat) + '</span>' +
      '<a data-spa href="' + r.product + '/' + p.slug + '" class="product-name">' + esc(p.name) + '</a>' +
      '<div class="product-price-row"><span class="product-price">' + formatPrice(p.price) + '</span></div>' +
      (p.pack ? '<div><span class="product-meta">' + esc(p.pack) + '</span></div>' : '') +
      '</div></article>';
  }

  function renderResults(q) {
    if (!searchEl) return;
    var results = searchEl.querySelector('[data-search-results]');
    var count = searchEl.querySelector('[data-search-count]');
    var term = (q || '').trim().toLowerCase();
    var list;
    if (!term) {
      list = catalog().slice(0, 5);
      count.textContent = 'Popular right now';
    } else {
      list = catalog().filter(function (p) {
        return (p.name + ' ' + p.cat + ' ' + (p.tags || '')).toLowerCase().indexOf(term) > -1;
      });
      count.textContent = list.length + ' result' + (list.length === 1 ? '' : 's') + ' for "' + q.trim() + '"';
    }
    if (!list.length) {
      results.innerHTML = '<div class="search-empty"><h3>Nothing matched "' + esc(q.trim()) + '"</h3>' +
        '<p>Try a different word, or <a href="' + routes().shop + '">browse all groceries</a>.</p></div>';
      return;
    }
    var capped = term ? list.slice(0, 5) : list;
    var html = capped.map(cardHTML).join('');
    if (term && list.length > capped.length) {
      html += '<div class="search-more"><a data-spa href="' + routes().shop + '?q=' + encodeURIComponent(q.trim()) + '">See all ' + list.length + ' results</a></div>';
    }
    results.innerHTML = html;
    bindSearchLinks(results);
  }

  function bindSearchLinks(scope) {
    scope.querySelectorAll('a[data-spa]').forEach(function (a) {
      a.addEventListener('click', function () { closeSearch(); });
    });
  }

  function openSearch() {
    ensureCatalog(function () {
      var el = buildSearch();
      document.body.classList.add('search-open');
      el.classList.add('open');
      setTimeout(function () { el.querySelector('#egf-search-input').focus(); }, 120);
    });
  }

  function closeSearch() {
    if (!searchEl) return;
    searchEl.classList.remove('open');
    document.body.classList.remove('search-open');
  }

  function initSearchTriggers() {
    document.querySelectorAll('[data-search-open]').forEach(function (el) {
      if (el._egfBound || el.tagName === 'INPUT' || el.tagName === 'SELECT') return;
      el._egfBound = true;
      el.addEventListener('click', function (e) { e.preventDefault(); openSearch(); });
    });
    document.querySelectorAll('.shop-search input').forEach(function (input) {
      if (input._egfBound) return;
      input._egfBound = true;
      // The shop page filters its own grid — never hijack it into the global overlay.
      if (input.closest && input.closest('form.shop-tools')) return;
      input.addEventListener('focus', function () {
        openSearch();
        var target = searchEl.querySelector('#egf-search-input');
        if (this.value) { target.value = this.value; renderResults(this.value); }
        this.blur();
      });
    });
  }

  /* ---------------- Pretty selects (progressive enhancement, SPA-safe) ---------------- */
  var egfPrettyOpen = null;

  function closePrettySelect() {
    if (!egfPrettyOpen) return;
    egfPrettyOpen.classList.remove('open');
    var b = egfPrettyOpen.querySelector('.egf-select-btn');
    if (b) b.setAttribute('aria-expanded', 'false');
    egfPrettyOpen = null;
  }

  function prettyLabel(sel) {
    var opt = sel.options[sel.selectedIndex];
    return opt ? opt.textContent : '';
  }

  function initPrettySelects() {
    document.querySelectorAll('select').forEach(function (sel) {
      if (sel._egfPretty || sel.hasAttribute('data-native')) return;
      sel._egfPretty = true;

      var wrap = document.createElement('div');
      wrap.className = 'egf-select' + (sel.className ? ' ' + sel.className : '');
      sel.className = 'egf-select-native';
      sel.parentNode.insertBefore(wrap, sel);
      wrap.appendChild(sel);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'egf-select-btn';
      btn.setAttribute('aria-haspopup', 'listbox');
      btn.setAttribute('aria-expanded', 'false');
      if (sel.getAttribute('aria-label')) btn.setAttribute('aria-label', sel.getAttribute('aria-label'));
      var label = document.createElement('span');
      label.textContent = prettyLabel(sel);
      btn.appendChild(label);
      wrap.appendChild(btn);

      var list = document.createElement('div');
      list.className = 'egf-select-list';
      list.setAttribute('role', 'listbox');
      Array.prototype.forEach.call(sel.options, function (opt, i) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'egf-select-opt' + (i === sel.selectedIndex ? ' selected' : '') + (opt.disabled ? ' disabled' : '');
        item.setAttribute('role', 'option');
        item.textContent = opt.textContent;
        if (!opt.disabled) {
          item.addEventListener('click', function () {
            sel.selectedIndex = i;
            label.textContent = opt.textContent;
            list.querySelectorAll('.egf-select-opt').forEach(function (x) { x.classList.remove('selected'); });
            item.classList.add('selected');
            closePrettySelect();
            var ev;
            try { ev = new Event('change', { bubbles: true }); }
            catch (e) { ev = document.createEvent('Event'); ev.initEvent('change', true, true); }
            sel.dispatchEvent(ev);
          });
        }
        list.appendChild(item);
      });
      wrap.appendChild(list);

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var was = wrap.classList.contains('open');
        closePrettySelect();
        if (!was) {
          wrap.classList.add('open');
          btn.setAttribute('aria-expanded', 'true');
          egfPrettyOpen = wrap;
        }
      });

      sel.addEventListener('change', function () {
        label.textContent = prettyLabel(sel);
        list.querySelectorAll('.egf-select-opt').forEach(function (x, idx) {
          x.classList.toggle('selected', idx === sel.selectedIndex);
        });
      });
    });

    if (!window._egfPrettyGlobal) {
      window._egfPrettyGlobal = true;
      document.addEventListener('click', function () { closePrettySelect(); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePrettySelect();
      });
    }
  }

  /* ---------------- Shop filters without reload (SPA) ---------------- */
  function shopFilterUrl(form) {
    var params = new URLSearchParams();
    var cat = form.querySelector('input[name="category"]');
    var q = form.querySelector('input[name="q"]');
    var min = form.querySelector('input[name="min_price"]');
    var max = form.querySelector('input[name="max_price"]');
    var sort = form.querySelector('select[name="sort"]');
    if (cat && cat.value) params.set('category', cat.value);
    if (q && q.value.trim()) params.set('q', q.value.trim());
    var offers = form.querySelector('input[name="only_offers"]');
    if (offers && offers.value) params.set('only_offers', offers.value);
    if (min && min.value !== '' && Number(min.value) >= 0) params.set('min_price', min.value);
    if (max && max.value !== '' && Number(max.value) >= 0) params.set('max_price', max.value);
    if (sort && sort.value && sort.value !== 'featured') params.set('sort', sort.value);
    var base = (form.action || window.location.pathname).split('?')[0];
    var qs = params.toString();
    return base + (qs ? '?' + qs : '');
  }

  function initShopFilters() {
    var form = document.querySelector('form.shop-tools');
    if (form && !form._egfShopBound) {
      form._egfShopBound = true;
      form.addEventListener('submit', function (e) {
        if (!window.spaNavigate) return;
        e.preventDefault();
        window.spaNavigate(shopFilterUrl(form), { push: true, scroll: false });
      });
      var sort = form.querySelector('select[name="sort"]');
      if (sort && !sort._egfShopBound) {
        sort._egfShopBound = true;
        sort.addEventListener('change', function () {
          if (!window.spaNavigate) { form.submit(); return; }
          window.spaNavigate(shopFilterUrl(form), { push: true, scroll: false });
        });
      }
    }
    if (!window._egfShopPillsBound) {
      window._egfShopPillsBound = true;
      document.addEventListener('click', function (e) {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest ? e.target.closest('.cat-pills a[href], .child-chips a[href], a[data-load-more]') : null;
        if (!a || !window.spaNavigate) return;
        e.preventDefault();
        window.spaNavigate(a.href, { push: true, scroll: false });
      });
    }
  }

  /* ---------------- Shop price slider (dual range, capped to shop bounds) ---------------- */
  function initPriceSlider() {
    document.querySelectorAll('[data-price-slider]').forEach(function (root) {
      if (root._egfBound) return;
      root._egfBound = true;
      var floor = parseFloat(root.dataset.floor) || 0;
      var ceil = parseFloat(root.dataset.ceil) || 0;
      if (!(ceil > floor)) return;
      var lo = root.querySelector('[data-price-lo]');
      var hi = root.querySelector('[data-price-hi]');
      var minH = root.querySelector('[data-price-min]');
      var maxH = root.querySelector('[data-price-max]');
      var fill = root.querySelector('[data-price-fill]');
      var range = root.querySelector('[data-price-range]');
      if (!lo || !hi || !minH || !maxH) return;
      var form = root.closest ? root.closest('form') : null;

      function vals() {
        var a = parseFloat(lo.value);
        var b = parseFloat(hi.value);
        return [Math.min(a, b), Math.max(a, b)];
      }
      function paint() {
        var v = vals();
        var span = ceil - floor;
        if (fill) {
          fill.style.left = ((v[0] - floor) / span * 100) + '%';
          fill.style.right = (100 - (v[1] - floor) / span * 100) + '%';
        }
        if (range) range.textContent = '£' + v[0] + ' – £' + v[1];
      }
      lo.addEventListener('input', paint);
      hi.addEventListener('input', paint);
      function commit() {
        var v = vals();
        lo.value = v[0];
        hi.value = v[1];
        minH.value = v[0];
        maxH.value = v[1];
        paint();
        if (!form) return;
        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
      }
      lo.addEventListener('change', commit);
      hi.addEventListener('change', commit);
      paint();
    });
  }

  /* ---------------- Gallery lightbox (click to enlarge, step through) ---------------- */
  function initGalleryLightbox() {
    if (window._egfGalleryBound) return;
    window._egfGalleryBound = true;

    function items() {
      return [].slice.call(document.querySelectorAll('[data-gallery-item]'));
    }
    function openAt(start) {
      var list = items();
      if (!list.length) return;
      var index = Math.max(0, Math.min(start, list.length - 1));

      var overlay = document.createElement('div');
      overlay.className = 'lb-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', 'Image viewer');
      overlay.innerHTML =
        '<div class="lb-backdrop" data-lb-close></div>' +
        '<figure class="lb-figure">' +
        '<img class="lb-img" alt="">' +
        '<figcaption class="lb-cap"><span data-lb-text></span><span data-lb-count></span></figcaption>' +
        '</figure>' +
        '<button type="button" class="lb-btn lb-close" data-lb-close aria-label="Close">✕</button>' +
        '<button type="button" class="lb-btn lb-prev" aria-label="Previous image">‹</button>' +
        '<button type="button" class="lb-btn lb-next" aria-label="Next image">›</button>';
      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';

      var img = overlay.querySelector('.lb-img');
      var text = overlay.querySelector('[data-lb-text]');
      var count = overlay.querySelector('[data-lb-count]');

      function show(i) {
        index = (i + list.length) % list.length;
        var el = list[index];
        img.src = el.getAttribute('data-full') || '';
        img.alt = el.getAttribute('data-caption') || 'Gallery image';
        text.textContent = el.getAttribute('data-caption') || '';
        text.style.display = text.textContent ? '' : 'none';
        count.textContent = (index + 1) + ' / ' + list.length;
      }
      function close() {
        overlay.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKey);
      }
      function onKey(e) {
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowRight') show(index + 1);
        else if (e.key === 'ArrowLeft') show(index - 1);
      }

      overlay.querySelector('.lb-prev').addEventListener('click', function (e) { e.stopPropagation(); show(index - 1); });
      overlay.querySelector('.lb-next').addEventListener('click', function (e) { e.stopPropagation(); show(index + 1); });
      overlay.querySelectorAll('[data-lb-close]').forEach(function (b) {
        b.addEventListener('click', function () { close(); });
      });
      document.addEventListener('keydown', onKey);

      var touchX = null;
      overlay.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
      overlay.addEventListener('touchend', function (e) {
        if (touchX === null) return;
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 45) show(index + (dx < 0 ? 1 : -1));
        touchX = null;
      });

      show(index);
      var closeBtn = overlay.querySelector('.lb-close');
      if (closeBtn) closeBtn.focus();
    }

    document.addEventListener('click', function (e) {
      var item = e.target.closest ? e.target.closest('[data-gallery-item]') : null;
      if (!item) return;
      e.preventDefault();
      openAt(items().indexOf(item));
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var item = e.target && e.target.closest ? e.target.closest('[data-gallery-item]') : null;
      if (!item) return;
      e.preventDefault();
      openAt(items().indexOf(item));
    });
  }

  /* ---------------- Cookie consent (localStorage, no dependencies) ---------------- */
  function initCookieBanner() {
    var bar = document.querySelector('[data-cookie-banner]');
    if (!bar || bar._egfBound) return;
    bar._egfBound = true;
    var choice = null;
    try { choice = window.localStorage.getItem('egf-consent'); } catch (e) { choice = null; }
    if (choice) return;
    bar.hidden = false;
    function decide(value) {
      try { window.localStorage.setItem('egf-consent', value); } catch (e) {}
      bar.hidden = true;
    }
    var accept = bar.querySelector('[data-cookie-accept]');
    var decline = bar.querySelector('[data-cookie-decline]');
    if (accept) accept.addEventListener('click', function () { decide('accepted'); });
    if (decline) decline.addEventListener('click', function () { decide('declined'); });
  }

  /* ---------------- Favourites (heart toggle, shoppers only) ---------------- */
  function initFavToggles() {
    if (window._egfFavBound) return;
    window._egfFavBound = true;
    document.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('[data-fav-toggle]') : null;
      if (!btn || !routes().favToggle) return;
      e.preventDefault();
      var pid = btn.dataset.productId;
      fetch(routes().favToggle, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ product_id: parseInt(pid, 10) })
      }).then(function (res) {
        if (res.status === 401) {
          window.location.href = routes().login || '/login';
          return null;
        }
        return res.json().then(function (json) {
          if (!res.ok) throw json;
          return json;
        });
      }).then(function (data) {
        if (!data) return;
        document.querySelectorAll('[data-fav-toggle][data-product-id="' + pid + '"]').forEach(function (b) {
          b.classList.toggle('active', !!data.favourited);
          b.setAttribute('aria-pressed', data.favourited ? 'true' : 'false');
        });
        showToast(data.message || (data.favourited ? 'Saved to favourites.' : 'Removed from favourites.'));
      }).catch(function (err) { showToast((err && err.message) || 'Please sign in to save favourites.'); });
    });
  }

  /* ---------------- Sticky header + reveal ---------------- */
  function initStickyHeader() {
    var header = document.querySelector('.site-header');
    if (!header || header._egfBound) return;
    header._egfBound = true;
    var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 20); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function initReveal() {
    if (!('IntersectionObserver' in window)) return;
    var items = document.querySelectorAll('.section .product-card, .cat-card, .trust-item');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.style.opacity = 1; en.target.style.transform = 'none'; io.unobserve(en.target); }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px' });
    items.forEach(function (el) {
      el.style.opacity = 0;
      el.style.transform = 'translateY(18px)';
      el.style.transition = 'opacity .6s ease, transform .6s ease';
      io.observe(el);
    });
  }

  /* ---------------- Announcement bar + welcome promo modal ---------------- */
  function todayKey() {
    var d = new Date();
    return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
  }

  function initAnnouncementBar() {
    var bar = document.querySelector('[data-announce-bar]');
    if (!bar || bar._egfBound) return;
    bar._egfBound = true;
    var hidden = null;
    try { hidden = window.localStorage.getItem('egf-announcement'); } catch (e) { hidden = null; }
    if (hidden === (bar.getAttribute('data-announce-text') || '')) bar.remove();
    else {
      var close = bar.querySelector('[data-announce-close]');
      if (close) close.addEventListener('click', function () {
        try { window.localStorage.setItem('egf-announcement', bar.getAttribute('data-announce-text') || 'dismissed'); } catch (e) {}
        bar.remove();
      });
    }
  }

  function initPromoModal() {
    var modal = document.querySelector('[data-promo-modal]');
    if (!modal || modal._egfBound) return;
    modal._egfBound = true;
    var quiet = modal.querySelector('[data-promo-quiet]');
    function close(days) {
      modal.hidden = true;
      try {
        if (quiet && quiet.checked) window.localStorage.setItem('egf-promo-quiet', '1');
        window.localStorage.setItem('egf-promo-day', days || todayKey());
      } catch (e) {}
    }
    modal.querySelectorAll('[data-promo-close]').forEach(function (btn) {
      btn.addEventListener('click', function () { close(); });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hidden) close();
    });
    var copy = modal.querySelector('[data-promo-copy]');
    if (copy) copy.addEventListener('click', function () {
      var code = modal.querySelector('[data-promo-code]');
      var text = code ? code.textContent : '';
      function done() { showToast('Code copied: ' + text); }
      if (navigator.clipboard && text) navigator.clipboard.writeText(text).then(done, done);
      else done();
    });
    var last = null;
    var muted = null;
    try {
      last = window.localStorage.getItem('egf-promo-day');
      muted = window.localStorage.getItem('egf-promo-quiet');
    } catch (e) { last = null; muted = null; }
    if (muted === '1' || last === todayKey()) return;
    setTimeout(function () { modal.hidden = false; }, 1500);
  }

  /* ---------------- Deal-of-the-day countdown (1s tick, SPA re-runnable) ---------------- */
  function paintDeal(el) {
    var ends = new Date(el.getAttribute('data-deal-countdown')).getTime();
    var diff = ends - Date.now();
    if (isNaN(diff) || diff <= 0) {
      var day = el.closest ? el.closest('[data-deal-day]') : null;
      if (day) day.remove();
      if (el._dealTimer) clearInterval(el._dealTimer);
      return;
    }
    var s = Math.floor(diff / 1000);
    var d = Math.floor(s / 86400);
    var h = Math.floor((s % 86400) / 3600);
    var m = Math.floor((s % 3600) / 60);
    var sec = s % 60;
    function set(sel, val) {
      var n = el.querySelector(sel);
      if (n) n.textContent = val;
    }
    set('[data-deal-d]', d);
    set('[data-deal-h]', h);
    set('[data-deal-m]', m);
    set('[data-deal-s]', sec);
  }

  function initDealCountdown() {
    var el = document.querySelector('[data-deal-countdown]');
    if (!el || el._dealBound) return;
    el._dealBound = true;
    paintDeal(el);
    el._dealTimer = setInterval(function () { paintDeal(el); }, 1000);
  }

  /* ---------------- Newsletter signup (footer, SPA re-runnable) ---------------- */
  function initNewsletter() {
    var box = document.querySelector('[data-newsletter]');
    if (!box || box._egfBound) return;
    box._egfBound = true;
    var form = box.querySelector('[data-newsletter-form]');
    var msg = box.querySelector('[data-newsletter-msg]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input[name="email"]');
      var email = input ? input.value.trim() : '';
      if (!email || email.indexOf('@') < 0) {
        if (msg) { msg.style.display = ''; msg.style.color = '#B91C1C'; msg.textContent = 'Enter a valid email address.'; }
        return;
      }
      bagPost(routes().newsletter || '/newsletter', { email: email, source: 'footer' })
        .then(function (data) {
          if (msg) { msg.style.display = ''; msg.style.color = '#1A2E22'; msg.textContent = data.message || 'Subscribed.'; }
          if (input) input.value = '';
        })
        .catch(function (err) {
          if (msg) { msg.style.display = ''; msg.style.color = '#B91C1C'; msg.textContent = (err && err.message) || 'Could not subscribe — try again.'; }
        });
    });
  }

  /* ---------------- Notify-me (back in stock / price drop, SPA re-runnable) ---------------- */
  function initNotifyMe() {
    document.querySelectorAll('[data-notify]').forEach(function (box) {
      if (box._egfBound) return;
      box._egfBound = true;
      var toggle = box.querySelector('[data-notify-toggle]');
      var form = box.querySelector('[data-notify-form]');
      var msg = box.querySelector('[data-notify-msg]');
      if (toggle && form) toggle.addEventListener('click', function () {
        form.style.display = form.style.display === 'none' ? '' : 'none';
      });
      if (!form) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="email"]');
        var email = input ? input.value.trim() : '';
        if (!email || email.indexOf('@') < 0) {
          if (msg) { msg.style.display = ''; msg.style.color = '#B91C1C'; msg.textContent = 'Enter a valid email address.'; }
          return;
        }
        bagPost(routes().notify || '/notify', {
          email: email,
          product_id: parseInt(box.getAttribute('data-product'), 10),
          product_variant_id: parseInt(box.getAttribute('data-variant'), 10) || null,
          type: box.getAttribute('data-type')
        }).then(function (data) {
          if (msg) { msg.style.display = ''; msg.style.color = '#1A2E22'; msg.textContent = data.message || 'Watching.'; }
          if (input) input.value = '';
        }).catch(function (err) {
          if (msg) { msg.style.display = ''; msg.style.color = '#B91C1C'; msg.textContent = (err && err.message) || 'Could not save — try again.'; }
        });
      });
    });
  }

  /* ---------------- Social proof toast (recent orders, max 3 per view) ---------------- */
  function initSocialProof() {
    var box = document.querySelector('[data-proof]');
    if (!box || box._egfBound) return;
    box._egfBound = true;
    var dataEl = box.querySelector('[data-proof-data]');
    var items = [];
    try { items = JSON.parse(dataEl ? dataEl.textContent : '[]'); } catch (e) { items = []; }
    if (!items.length) return;
    var link = box.querySelector('[data-proof-link]');
    var text = box.querySelector('[data-proof-text]');
    var idx = 0;
    var shown = 0;
    function show() {
      if (shown >= 3 || idx >= items.length) return;
      var it = items[idx++];
      shown++;
      if (link) link.setAttribute('href', it.url);
      if (text) text.textContent = it.name + (it.city ? ' from ' + it.city : '') + ' bought ' + it.item + ' · ' + it.ago;
      box.hidden = false;
      setTimeout(function () {
        box.hidden = true;
        setTimeout(show, 9000);
      }, 6000);
    }
    setTimeout(show, 8000);
    var close = box.querySelector('[data-proof-close]');
    if (close) close.addEventListener('click', function () {
      box.remove();
      idx = items.length;
    });
  }

  /* ---------------- Boot (runs on first load + every SPA nav) ---------------- */
  function boot() {
    if (searchEl && document.body.contains(searchEl)) searchEl.remove();
    searchEl = null;
    loadBag();    initMobileNav();
    initCardPackSelects();
    initDetailPicker();
    initQtyControls();
    initAddToBag();
    initPrettySelects();
    initShopFilters();
    initPriceSlider();
    initGalleryLightbox();
    initCookieBanner();
    initAnnouncementBar();
    initPromoModal();
    initDealCountdown();
    initNewsletter();
    initNotifyMe();
    initSocialProof();
    initFavToggles();
    renderBagPage();
    initHeroSlider();
    initTmnSlider();
    initFaqTabs();
    initShopFiltersToggle();
    initSearchTriggers();
    initStickyHeader();
    initReveal();
  }

  document.addEventListener('spa:loaded', boot);
  boot();

  window.EGF = {
    addToBag: addToBag, loadBag: loadBag, formatPrice: formatPrice,
    showToast: showToast, boot: boot, openSearch: openSearch
  };
})();
