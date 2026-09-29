/* Evergreen Foods — Vanilla JS core */

(function () {
  'use strict';

  // ----- Cart (localStorage) -----
  const CART_KEY = 'egf_cart';

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    } catch {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartUI();
  }

  function addToCart(product) {
    const cart = getCart();
    const existing = cart.find(
      (i) => i.id === product.id && i.pack === product.pack
    );
    if (existing) {
      existing.qty += product.qty || 1;
    } else {
      cart.push({ ...product, qty: product.qty || 1 });
    }
    saveCart(cart);
    showToast(`${product.name} added to bag`);
  }

  function removeFromCart(id, pack) {
    let cart = getCart().filter((i) => !(i.id === id && i.pack === pack));
    saveCart(cart);
  }

  function updateQty(id, pack, qty) {
    const cart = getCart();
    const item = cart.find((i) => i.id === id && i.pack === pack);
    if (item) {
      item.qty = Math.max(1, qty);
      saveCart(cart);
    }
  }

  function cartCount() {
    return getCart().reduce((s, i) => s + i.qty, 0);
  }

  function cartTotal() {
    return getCart().reduce((s, i) => s + i.price * i.qty, 0);
  }

  function formatPrice(n) {
    return '£' + Number(n).toFixed(2);
  }

  function updateCartUI() {
    const countEls = document.querySelectorAll('[data-cart-count]');
    const totalEls = document.querySelectorAll('[data-cart-total]');
    const count = cartCount();
    const total = cartTotal();
    countEls.forEach((el) => {
      el.textContent = count;
    });
    totalEls.forEach((el) => {
      el.textContent = formatPrice(total);
    });
  }

  // ----- Toast -----
  function showToast(msg) {
    let t = document.querySelector('.toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2600);
  }

  // ----- Mobile nav -----
  function initMobileNav() {
    const btn = document.querySelector('[data-mobile-menu]');
    const nav = document.querySelector('.mobile-nav');
    const closeBtns = document.querySelectorAll('[data-close-menu]');
    if (!btn || !nav) return;

    btn.addEventListener('click', () => nav.classList.add('open'));
    closeBtns.forEach((b) =>
      b.addEventListener('click', () => nav.classList.remove('open'))
    );
    nav.querySelector('.mobile-nav-overlay')?.addEventListener('click', () =>
      nav.classList.remove('open')
    );
  }

  // ----- Pack selectors on product cards / detail -----
  function initPackSelectors() {
    document.querySelectorAll('.pack-opt').forEach((btn) => {
      btn.addEventListener('click', function () {
        const group = this.parentElement;
        group.querySelectorAll('.pack-opt').forEach((b) => b.classList.remove('active'));
        this.classList.add('active');
        const price = this.dataset.price;
        const old = this.dataset.old;
        const priceEl = document.querySelector('[data-current-price]');
        const oldEl = document.querySelector('[data-old-price]');
        if (priceEl && price) priceEl.textContent = formatPrice(price);
        if (oldEl) {
          if (old) {
            oldEl.textContent = formatPrice(old);
            oldEl.style.display = '';
          } else {
            oldEl.style.display = 'none';
          }
        }
      });
    });
  }

  // ----- Qty controls -----
  function initQtyControls() {
    document.querySelectorAll('.qty-control').forEach((ctrl) => {
      const minus = ctrl.querySelector('[data-qty-minus]');
      const plus = ctrl.querySelector('[data-qty-plus]');
      const display = ctrl.querySelector('[data-qty-value]');
      if (!minus || !plus || !display) return;
      minus.addEventListener('click', () => {
        let v = parseInt(display.textContent, 10) || 1;
        if (v > 1) display.textContent = v - 1;
      });
      plus.addEventListener('click', () => {
        let v = parseInt(display.textContent, 10) || 1;
        display.textContent = v + 1;
      });
    });
  }

  // ----- Add to bag buttons -----
  function initAddToBag() {
    document.querySelectorAll('[data-add-to-bag]').forEach((btn) => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const image = this.dataset.image || '';
        let pack = this.dataset.pack || 'default';
        let qty = 1;

        // From detail page pack options
        const activePack = document.querySelector('.pack-opt.active');
        if (activePack) {
          pack = activePack.dataset.pack || pack;
          const p = parseFloat(activePack.dataset.price);
          if (!isNaN(p)) this.dataset.price = p;
        }
        const qtyEl = document.querySelector('[data-qty-value]');
        if (qtyEl) qty = parseInt(qtyEl.textContent, 10) || 1;

        addToCart({
          id,
          name,
          price: parseFloat(this.dataset.price) || price,
          pack,
          image,
          qty,
        });
      });
    });
  }

  // ----- Cart page render -----
  function renderCartPage() {
    const container = document.querySelector('[data-cart-items]');
    if (!container) return;
    const cart = getCart();
    if (cart.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <h2>Your bag is empty</h2>
          <p>Browse the market and add some fresh finds.</p>
          <a href="shop.html" class="btn btn-dark">Shop groceries</a>
        </div>`;
      return;
    }
    container.innerHTML = cart
      .map(
        (item) => `
      <div class="cart-item" data-id="${item.id}" data-pack="${item.pack}">
        <img src="${item.image || 'assets/images/tomatoes.jpg'}" alt="${item.name}">
        <div>
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-meta">${item.pack}</div>
          <div class="cart-item-price">${formatPrice(item.price)}</div>
          <div class="qty-control mt-1" style="display:inline-flex">
            <button type="button" data-cart-minus aria-label="Decrease">−</button>
            <span data-cart-qty>${item.qty}</span>
            <button type="button" data-cart-plus aria-label="Increase">+</button>
          </div>
        </div>
        <div>
          <button type="button" class="icon-btn" data-cart-remove aria-label="Remove" title="Remove">
            <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
      </div>`
      )
      .join('');

    // Bind events
    container.querySelectorAll('[data-cart-remove]').forEach((btn) => {
      btn.addEventListener('click', function () {
        const row = this.closest('.cart-item');
        removeFromCart(row.dataset.id, row.dataset.pack);
        renderCartPage();
        updateCartSummary();
      });
    });
    container.querySelectorAll('[data-cart-minus]').forEach((btn) => {
      btn.addEventListener('click', function () {
        const row = this.closest('.cart-item');
        const span = row.querySelector('[data-cart-qty]');
        let q = parseInt(span.textContent, 10) - 1;
        if (q < 1) q = 1;
        updateQty(row.dataset.id, row.dataset.pack, q);
        span.textContent = q;
        updateCartSummary();
      });
    });
    container.querySelectorAll('[data-cart-plus]').forEach((btn) => {
      btn.addEventListener('click', function () {
        const row = this.closest('.cart-item');
        const span = row.querySelector('[data-cart-qty]');
        let q = parseInt(span.textContent, 10) + 1;
        updateQty(row.dataset.id, row.dataset.pack, q);
        span.textContent = q;
        updateCartSummary();
      });
    });
    updateCartSummary();
  }

  function updateCartSummary() {
    const sub = document.querySelector('[data-summary-subtotal]');
    const tot = document.querySelector('[data-summary-total]');
    const t = cartTotal();
    if (sub) sub.textContent = formatPrice(t);
    if (tot) tot.textContent = formatPrice(t);
  }

  // ----- Init -----
  document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    initMobileNav();
    initPackSelectors();
    initQtyControls();
    initAddToBag();
    renderCartPage();
  });

  // Expose for inline use if needed
  window.EGF = { addToCart, getCart, formatPrice, showToast };
})();

/* ============================================================
   FINAL POLISH — hero slider + functional search
   ============================================================ */
(function () {
  'use strict';

  var BASE = location.pathname.indexOf('/pages/') > -1 ? '../' : '';
  var PAGES = BASE + 'pages/';

  var PRODUCTS = [
    { id: 'vine-tomatoes', name: 'Vine-Ripened Tomatoes', cat: 'Vegetables', tags: 'tomato salad locally grown', price: 1.89, pack: '500 g', img: 'assets/images/tomatoes.jpg' },
    { id: 'spinach', name: 'Baby Leaf Spinach', cat: 'Vegetables', tags: 'greens leaves salad', price: 1.35, pack: '200 g', img: 'assets/images/spinach.jpg' },
    { id: 'carrots', name: 'Sweet Bunched Carrots', cat: 'Vegetables', tags: 'root veg orange', price: 1.10, pack: '1 bunch', img: 'assets/images/carrots.jpg' },
    { id: 'sweet-bananas', name: 'Sweet Bananas', cat: 'Fruit', tags: 'banana farm fresh', price: 0.79, pack: '4 pcs', img: 'assets/images/bananas.jpg' },
    { id: 'fresh-strawberries', name: 'Fresh Strawberries', cat: 'Fruit', tags: 'berries seasonal', price: 1.99, pack: '250 g', img: 'assets/images/strawberries.jpg' },
    { id: 'oranges', name: 'Juicing Oranges', cat: 'Fruit', tags: 'citrus juice', price: 2.25, pack: '6 pcs', img: 'assets/images/oranges.jpg' },
    { id: 'ripe-avocados', name: 'Ripe Avocados', cat: 'Fruit', tags: 'avocado ready to eat', price: 2.49, pack: '2 pcs', img: 'assets/images/avocados.jpg' },
    { id: 'sourdough', name: 'Artisan Sourdough', cat: 'Bakery', tags: 'bread loaf baked', price: 3.25, pack: '1 loaf', img: 'assets/images/bread.jpg' },
    { id: 'milk', name: 'Fresh Whole Milk', cat: 'Dairy & Eggs', tags: 'milk dairy', price: 1.45, pack: '1 L', img: 'assets/images/milk.jpg' },
    { id: 'eggs', name: 'Free-Range Eggs', cat: 'Dairy & Eggs', tags: 'egg breakfast', price: 2.15, pack: '6 eggs', img: 'assets/images/eggs.jpg' },
    { id: 'cheddar', name: 'Aged Cheddar Cheese', cat: 'Dairy & Eggs', tags: 'cheese mature', price: 3.75, pack: '200 g', img: 'assets/images/cheese.jpg' },
    { id: 'olive-oil', name: 'Extra Virgin Olive Oil', cat: 'Pantry', tags: 'oil cooking olive', price: 6.99, pack: '500 ml', img: 'assets/images/olive.jpg' }
  ];

  /* ---------------- Hero slider ---------------- */
  function initHeroSlider() {
    var slider = document.querySelector('[data-hero-slider]');
    if (!slider) return;
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

    slider.querySelector('.hero-arrow.next')?.addEventListener('click', function () { go(index + 1); });
    slider.querySelector('.hero-arrow.prev')?.addEventListener('click', function () { go(index - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); });

    slider.addEventListener('mouseenter', function () { clearInterval(timer); slider.classList.add('paused'); });
    slider.addEventListener('mouseleave', function () { slider.classList.remove('paused'); restart(); });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { clearInterval(timer); } else { restart(); }
    });

    // Touch swipe
    var startX = null;
    slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; clearInterval(timer); }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 45) { go(index + (dx < 0 ? 1 : -1)); } else { restart(); }
      startX = null;
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') go(index + 1);
      if (e.key === 'ArrowLeft') go(index - 1);
    });

    go(0);
  }

  /* ---------------- Search overlay ---------------- */
  var searchEl = null;

  function buildSearch() {
    if (searchEl) return searchEl;
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
          '<input type="search" id="egf-search-input" placeholder="Search for tomatoes, bread, cheese…" aria-label="Search products" autocomplete="off">' +
          '<button type="button" class="search-close" data-search-close>Close</button>' +
        '</div>' +
        '<div class="search-hint">Popular: ' +
          ['Vegetables', 'Fruit', 'Bakery', 'Cheese', 'Olive oil'].map(function (t) {
            return '<button type="button" class="search-chip" data-search-term="' + t + '">' + t + '</button>';
          }).join('') +
        '</div>' +
        '<p class="search-count" data-search-count></p>' +
        '<div class="search-results" data-search-results></div>' +
      '</div></div>';
    document.body.appendChild(wrap);
    searchEl = wrap;

    var input = wrap.querySelector('#egf-search-input');
    wrap.querySelectorAll('[data-search-close]').forEach(function (b) {
      b.addEventListener('click', closeSearch);
    });
    wrap.querySelectorAll('[data-search-term]').forEach(function (b) {
      b.addEventListener('click', function () {
        input.value = this.dataset.searchTerm;
        render(input.value);
        input.focus();
      });
    });
    input.addEventListener('input', function () { render(this.value); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSearch();
    });
    render('');
    return wrap;
  }

  function cardHTML(p) {
    return '' +
      '<article class="product-card">' +
        '<a href="' + PAGES + 'product.html?id=' + p.id + '" class="product-img-wrap">' +
          '<img src="' + BASE + p.img + '" alt="' + p.name + '" loading="lazy">' +
        '</a>' +
        '<div class="product-body">' +
          '<span class="product-meta">' + p.cat + '</span>' +
          '<a href="' + PAGES + 'product.html?id=' + p.id + '" class="product-name">' + p.name + '</a>' +
          '<div class="product-price-row"><span class="product-price">£' + p.price.toFixed(2) + '</span>' +
          '<span class="product-meta">' + p.pack + '</span></div>' +
          '<div class="product-actions"><button type="button" class="btn btn-dark btn-sm" data-add-to-bag ' +
            'data-id="' + p.id + '" data-name="' + p.name + '" data-price="' + p.price + '" ' +
            'data-pack="' + p.pack + '" data-image="' + BASE + p.img + '">Add to bag</button></div>' +
        '</div>' +
      '</article>';
  }

  function render(q) {
    var results = searchEl.querySelector('[data-search-results]');
    var count = searchEl.querySelector('[data-search-count]');
    var term = (q || '').trim().toLowerCase();
    var list;
    if (!term) {
      list = PRODUCTS.slice(0, 6);
      count.textContent = 'Popular right now';
    } else {
      list = PRODUCTS.filter(function (p) {
        return (p.name + ' ' + p.cat + ' ' + p.tags).toLowerCase().indexOf(term) > -1;
      });
      count.textContent = list.length + ' result' + (list.length === 1 ? '' : 's') + ' for “' + q.trim() + '”';
    }
    if (!list.length) {
      count.textContent = 'No results';
      results.innerHTML = '';
      results.insertAdjacentHTML('beforebegin', '');
      results.innerHTML = '<div class="search-empty"><h3>Nothing matched “' + q.trim() + '”</h3>' +
        '<p>Try a different word, or <a href="' + PAGES + 'shop.html">browse all groceries</a>.</p></div>';
      return;
    }
    results.innerHTML = list.map(cardHTML).join('');
    bindResultButtons(results);
  }

  function bindResultButtons(scope) {
    scope.querySelectorAll('[data-add-to-bag]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        window.EGF.addToCart({
          id: this.dataset.id,
          name: this.dataset.name,
          price: parseFloat(this.dataset.price),
          pack: this.dataset.pack,
          image: this.dataset.image,
          qty: 1
        });
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
    document.querySelectorAll('[aria-label="Search"], [data-search-open]').forEach(function (el) {
      if (el.tagName === 'INPUT' || el.tagName === 'SELECT') return;
      el.addEventListener('click', function (e) {
        e.preventDefault();
        openSearch();
      });
    });
    // Inline shop search field routes into the overlay
    document.querySelectorAll('.shop-search input').forEach(function (input) {
      input.addEventListener('focus', function () {
        openSearch();
        var target = searchEl.querySelector('#egf-search-input');
        if (this.value) { target.value = this.value; render(this.value); }
        this.blur();
      });
    });
  }

  /* ---------------- Sticky header ---------------- */
  function initStickyHeader() {
    var header = document.querySelector('.site-header');
    if (!header) return;
    var onScroll = function () {
      header.classList.toggle('scrolled', window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------------- Reveal on scroll ---------------- */
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

  document.addEventListener('DOMContentLoaded', function () {
    initHeroSlider();
    initSearchTriggers();
    initStickyHeader();
    initReveal();
  });
})();
