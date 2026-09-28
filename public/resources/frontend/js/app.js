/* Vai Vai Electronics — core JS
   Loads shared partials, preloader, cart badge, lang toggle,
   NiceSelect, AOS, header shrink, counters, countdown, FAQ. */

(function () {
  const ready = (fn) => (document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn));

  // -------- Load header/footer partials --------
  const loadPartial = async (selector, url) => {
    const el = document.querySelector(selector);
    if (!el) return;
    try {
      const res = await fetch(url);
      el.innerHTML = await res.text();
    } catch (e) { console.warn('partial failed', url, e); }
  };

  window.VV = window.VV || {};
  VV.setActive = (page) => {
    document.querySelectorAll('.nav-links a').forEach(a => {
      if (a.dataset.page === page) a.classList.add('active');
    });
  };

  ready(async () => {
    // Preloader
    if (!document.getElementById('loader')) {
      const l = document.createElement('div'); l.id = 'loader';
      l.innerHTML = '<div class="loader-inner"><div class="loader-ring"></div></div>';
      document.body.append(l);
    }

    // Base path for partials — relative to current html file
    const base = document.body.dataset.base || './';
    // Header/footer are inlined at build time — no fetch needed.

    initHeader(base);
    initHeaderShrink();
    initLangToggle();
    initShareFloat();
    initNiceSelect();
    initAOS();
    initCounters();
    initCountdown();
    initFAQ();
    initQty();
    initMegaMenu();

    // Hide preloader
    setTimeout(() => document.getElementById('loader')?.classList.add('hide'), 500);

    // Mark active nav
    const page = document.body.dataset.page;
    if (page) VV.setActive(page);
  });

  function initHeader(base) {
    // rewrite relative hrefs in partials to work from subfolders
    document.querySelectorAll('[data-href]').forEach(a => {
      a.setAttribute('href', base + a.dataset.href);
    });
    document.querySelectorAll('[data-src]').forEach(i => {
      i.setAttribute('src', base + i.dataset.src);
    });
  }

  function initHeaderShrink() {
    const h = document.querySelector('.site-header');
    if (!h) return;
    window.addEventListener('scroll', () => {
      h.classList.toggle('shrink', window.scrollY > 40);
    });
  }

  function initLangToggle() {
    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-lang]');
      if (!b) return;
      document.querySelectorAll('[data-lang]').forEach(btn => btn.classList.remove('active'));
      b.classList.add('active');
    });
  }

  function initShareFloat() {
    const float = document.getElementById('shareFloat');
    const toggle = document.getElementById('shareToggle');
    if (!float || !toggle) return;

    const shareUrl = encodeURIComponent(window.location.href);

    float.querySelectorAll('[data-share-url]').forEach(link => {
      link.href = link.href.split('?u=')[0] + '?u=' + shareUrl;
    });

    toggle.addEventListener('click', () => {
      float.classList.toggle('open');
      toggle.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!float.contains(e.target)) {
        float.classList.remove('open');
        toggle.classList.remove('open');
      }
    });
  }

  document.addEventListener('spa:loaded', initShareFloat);

  function initNiceSelect() {
    if (typeof NiceSelect === 'undefined') return;
    document.querySelectorAll('select.nice').forEach(el => { try { NiceSelect.bind(el, { searchable: false }); } catch (e) {} });
  }

  function initAOS() {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 700, once: true, easing: 'ease-out' });
  }

  function initCounters() {
    const els = document.querySelectorAll('.counter');
    if (!els.length) return;
    const run = (el) => {
      const t = +el.dataset.target; let c = 0; const step = Math.max(1, t / 60);
      const iv = setInterval(() => { c += step; if (c >= t) { c = t; clearInterval(iv); } el.textContent = Math.floor(c).toLocaleString(); }, 20);
    };
    const io = new IntersectionObserver((ents) => ents.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } }), { threshold: .5 });
    els.forEach(el => io.observe(el));
  }

  function initCountdown() {
    const wrap = document.getElementById('countdown'); if (!wrap) return;
    let h = 8, m = 45, s = 12;
    setInterval(() => {
      s--; if (s < 0) { s = 59; m--; } if (m < 0) { m = 59; h--; } if (h < 0) { h = 8; m = 45; s = 12; }
      const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = String(v).padStart(2, '0'); };
      set('cd-h', h); set('cd-m', m); set('cd-s', s);
    }, 1000);
  }

  function initFAQ() {
    document.querySelectorAll('.faq-item').forEach(item => {
      item.addEventListener('click', () => {
        const ans = item.querySelector('.faq-a'); const icon = item.querySelector('.faq-q i');
        const open = !ans.classList.contains('d-none');
        document.querySelectorAll('.faq-a').forEach(a => a.classList.add('d-none'));
        document.querySelectorAll('.faq-q i').forEach(i => i.style.transform = 'rotate(0deg)');
        if (!open) { ans.classList.remove('d-none'); if (icon) icon.style.transform = 'rotate(45deg)'; }
      });
    });
  }

  function initQty() {
    document.addEventListener('click', (e) => {
      const b = e.target.closest('.qty-box button'); if (!b) return;
      const input = b.parentElement.querySelector('input');
      let v = parseInt(input.value || '1', 10);
      if (b.dataset.dir === '+') v++; else if (v > 1) v--;
      input.value = v;
    });
  }

  function initMegaMenu() {
    document.addEventListener('click', (e) => {
      const t = e.target.closest('.mega-wrap > .cat-btn');
      if (t) {
        e.preventDefault();
        t.parentElement.classList.toggle('open');
      } else if (!e.target.closest('.mega-wrap')) {
        document.querySelectorAll('.mega-wrap.open').forEach(w => w.classList.remove('open'));
      }
    });
  }
})();
