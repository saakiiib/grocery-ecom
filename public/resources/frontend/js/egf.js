/* Evergreen Foods storefront — vanilla JS, SPA re-runnable.
   Rules: var only (shared global scope), no DOMContentLoaded (init runs
   directly + on spa:loaded). Document-level listeners bind exactly once. */

(function () {
  'use strict';

  function routes() {
    return window.EGF_ROUTES || { shop: '/collections', checkout: '/checkout', product: '/product' };
  }

  function placeholderImg() {
    return (window.EGF_ASSETS && window.EGF_ASSETS.placeholder) || '';
  }

  function formatPrice(n) {
    return '£' + Number(n).toFixed(2);
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

  function addToBag(variantId, qty) {
    var r = routes();
    bagPost(r.bagAdd, { variant_id: parseInt(variantId, 10), qty: qty || 1 })
      .then(function (data) {
        refreshBagUI(data);
        showToast(data.message || 'Added to your bag');
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
      if (mainImg && v.image) mainImg.src = v.image;
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
        addToBag(btn.dataset.variantId || btn.dataset.id, qty);
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
        '<a data-spa href="' + link + '"><img src="' + img + '" alt="' + item.name + '" loading="lazy"></a>' +
        '<div><div class="cart-item-name">' + item.name + '</div>' +
        (item.pack ? '<div class="cart-item-meta">' + item.pack + '</div>' : '') +
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
    var t = data ? data.subtotal || 0 : 0;
    if (sub) sub.textContent = formatPrice(t);
    if (tot) tot.textContent = formatPrice(t);
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

    var next = slider.querySelector('.hero-arrow.next');
    var prev = slider.querySelector('.hero-arrow.prev');
    if (next) next.addEventListener('click', function () { go(index + 1); });
    if (prev) prev.addEventListener('click', function () { go(index - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });

    slider.addEventListener('mouseenter', function () { clearInterval(timer); });
    slider.addEventListener('mouseleave', function () { restart(); });
    if (!window._egfSliderVisBound) {
      window._egfSliderVisBound = true;
      document.addEventListener('visibilitychange', function () {
        document.querySelectorAll('[data-hero-slider]').forEach(function () { /* timers are per-slider; nothing global */ });
      });
    }

    var startX = null;
    slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; clearInterval(timer); }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 45) { go(index + (dx < 0 ? 1 : -1)); } else { restart(); }
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

  /* ---------------- Search overlay (server catalog) ---------------- */
  var searchEl = null;

  function catalog() {
    return window.EGF_CATALOG || [];
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
      '<img src="' + p.img + '" alt="' + p.name + '" loading="lazy"></a>' +
      '<div class="product-body"><span class="product-meta">' + p.cat + '</span>' +
      '<a data-spa href="' + r.product + '/' + p.slug + '" class="product-name">' + p.name + '</a>' +
      '<div class="product-price-row"><span class="product-price">' + formatPrice(p.price) + '</span>' +
      '<span class="product-meta">' + (p.pack || '') + '</span></div>' +
      '<div class="product-actions"><button type="button" class="btn btn-dark btn-sm" data-add-to-bag ' +
      'data-variant-id="' + p.variant_id + '" data-name="' + p.name + '" data-price="' + p.price + '" ' +
      'data-pack="' + (p.pack || '') + '" data-image="' + p.img + '">Add to bag</button></div>' +
      '</div></article>';
  }

  function renderResults(q) {
    if (!searchEl) return;
    var results = searchEl.querySelector('[data-search-results]');
    var count = searchEl.querySelector('[data-search-count]');
    var term = (q || '').trim().toLowerCase();
    var list;
    if (!term) {
      list = catalog().slice(0, 6);
      count.textContent = 'Popular right now';
    } else {
      list = catalog().filter(function (p) {
        return (p.name + ' ' + p.cat + ' ' + (p.tags || '')).toLowerCase().indexOf(term) > -1;
      });
      count.textContent = list.length + ' result' + (list.length === 1 ? '' : 's') + ' for "' + q.trim() + '"';
    }
    if (!list.length) {
      results.innerHTML = '<div class="search-empty"><h3>Nothing matched "' + q.trim() + '"</h3>' +
        '<p>Try a different word, or <a href="' + routes().shop + '">browse all groceries</a>.</p></div>';
      return;
    }
    results.innerHTML = list.map(cardHTML).join('');
    bindSearchButtons(results);
  }

  function bindSearchButtons(scope) {
    scope.querySelectorAll('[data-add-to-bag]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        addToBag(btn.dataset.variantId, 1);
      });
    });
  }

  function openSearch() {
    var el = buildSearch();
    document.body.classList.add('search-open');
    el.classList.add('open');
    setTimeout(function () { el.querySelector('#egf-search-input').focus(); }, 120);
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
      input.addEventListener('focus', function () {
        openSearch();
        var target = searchEl.querySelector('#egf-search-input');
        if (this.value) { target.value = this.value; renderResults(this.value); }
        this.blur();
      });
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

  /* ---------------- Boot (runs on first load + every SPA nav) ---------------- */
  function boot() {
    if (searchEl && document.body.contains(searchEl)) searchEl.remove();
    searchEl = null;
    loadBag();    initMobileNav();
    initCardPackSelects();
    initDetailPicker();
    initQtyControls();
    initAddToBag();
    renderBagPage();
    initHeroSlider();
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
