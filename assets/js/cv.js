/* CampusVoice shared client helpers: AJAX, toast, modal, lightbox, tables, reveal. No inline scripts (CSP). */
(function () {
  'use strict';
  const meta = (n) => (document.querySelector('meta[name="' + n + '"]') || {}).content || '';
  const CV = (window.CV = {});
  CV.base = meta('cv-base');
  CV.csrf = meta('cv-csrf');
  CV.url = (p) => CV.base + '/' + String(p).replace(/^\//, '');

  CV.esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  CV.$ = (s, r) => (r || document).querySelector(s);
  CV.$$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  /* ---------- AJAX ---------- */
  async function request(url, options) {
    let res;
    try { res = await fetch(url, Object.assign({ credentials: 'same-origin', headers: { 'X-CSRF-Token': CV.csrf, 'X-Requested-With': 'fetch' } }, options)); }
    catch (e) { throw new Error('Network problem. Please check your connection.'); }
    let json = null;
    try { json = await res.json(); } catch (e) { /* non-JSON */ }
    if (res.status === 401 && json && json.login) { location.href = json.login; throw new Error(json.error || 'Signed out'); }
    if (!json) throw new Error('Unexpected server response.');
    if (!json.ok) { const err = new Error(json.error || 'Something went wrong.'); err.data = json; throw err; }
    return json;
  }
  CV.post = (url, data) => {
    let body;
    if (data instanceof FormData) body = data;
    else { body = new FormData(); Object.keys(data || {}).forEach((k) => body.append(k, data[k])); }
    return request(CV.url(url), { method: 'POST', body });
  };
  CV.get = (url, params) => {
    const qs = params ? '?' + new URLSearchParams(Object.entries(params).filter(([, v]) => v !== '' && v != null)).toString() : '';
    return request(CV.url(url) + qs, { method: 'GET' });
  };

  /* ---------- Toast ---------- */
  CV.toast = (msg, type) => {
    let box = CV.$('.toasts');
    if (!box) { box = document.createElement('div'); box.className = 'toasts'; box.setAttribute('role', 'status'); box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = 'toast ' + (type || 'ok');
    const g = document.createElement('b'); g.className = 'tg'; g.textContent = (type || 'ok') === 'error' ? '!' : '✓';
    const s = document.createElement('span'); s.textContent = msg;
    t.append(g, s);
    box.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 320); }, 3800);
  };

  /* ---------- Modal ---------- */
  CV.modal = {
    open(id) { const m = document.getElementById(id); if (!m) return; m.classList.add('open'); document.body.style.overflow = 'hidden'; const f = m.querySelector('input,select,textarea'); if (f) setTimeout(() => f.focus(), 60); m.dispatchEvent(new CustomEvent('cv:open')); },
    close(m) { if (typeof m === 'string') m = document.getElementById(m); if (!m) return; m.classList.remove('open'); if (!CV.$('.modal.open')) document.body.style.overflow = ''; }
  };
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-modal]');
    if (opener) { e.preventDefault(); CV.modal.open(opener.getAttribute('data-open-modal')); return; }
    if (e.target.closest('[data-close-modal]')) { CV.modal.close(e.target.closest('.modal')); return; }
    if (e.target.classList && e.target.classList.contains('modal')) CV.modal.close(e.target);
  });

  /* ---------- Photo viewer (opens on the same page; arrows, counter, thumbnails, swipe) ---------- */
  const LB = { items: [], i: 0 };
  function ensureLightbox() {
    let lb = CV.$('.lightbox');
    if (lb) return lb;
    lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-label', 'Photo viewer');
    lb.innerHTML = '<div class="lb-top"><span class="lb-count"></span><span class="lb-grow"></span><a class="lb-save" download title="Save photo">Save</a><button type="button" class="lb-close" aria-label="Close photo">&times;</button></div>' +
      '<button type="button" class="lb-nav prev" aria-label="Previous photo">&#8249;</button>' +
      '<figure class="lb-stage"><img alt=""><figcaption class="lb-cap"></figcaption></figure>' +
      '<button type="button" class="lb-nav next" aria-label="Next photo">&#8250;</button>' +
      '<div class="lb-strip"></div>';
    document.body.appendChild(lb);
    lb.addEventListener('click', (e) => { if (!e.target.closest('img,.lb-nav,.lb-top,.lb-strip,.lb-cap')) closeLightbox(); });
    lb.querySelector('.lb-close').addEventListener('click', closeLightbox);
    lb.querySelector('.prev').addEventListener('click', () => lbShow(LB.i - 1));
    lb.querySelector('.next').addEventListener('click', () => lbShow(LB.i + 1));
    lb.querySelector('.lb-strip').addEventListener('click', (e) => { const t = e.target.closest('[data-i]'); if (t) lbShow(+t.dataset.i); });
    let x0 = null;                                              // swipe left / right on touch screens
    lb.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', (e) => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 50) lbShow(LB.i + (dx < 0 ? 1 : -1)); });
    return lb;
  }
  function lbShow(n) {
    const lb = CV.$('.lightbox'); if (!lb || !LB.items.length) return;
    LB.i = (n + LB.items.length) % LB.items.length;
    const it = LB.items[LB.i], img = lb.querySelector('.lb-stage img');
    img.classList.remove('in'); img.src = it.src; img.alt = it.caption || 'Concern photo';
    requestAnimationFrame(() => img.classList.add('in'));
    lb.querySelector('.lb-cap').textContent = it.caption || '';
    lb.querySelector('.lb-count').textContent = LB.items.length > 1 ? (LB.i + 1) + ' / ' + LB.items.length : '';
    lb.querySelector('.lb-save').href = it.src;
    lb.classList.toggle('single', LB.items.length < 2);
    CV.$$('.lb-strip [data-i]', lb).forEach((t) => t.classList.toggle('on', +t.dataset.i === LB.i));
  }
  function closeLightbox() { const lb = CV.$('.lightbox'); if (lb) { lb.classList.remove('open'); if (!CV.$('.modal.open')) document.body.style.overflow = ''; } }
  /** items: [{src, caption}] or a single src string; start = index to open. */
  CV.lightbox = (items, start, caption) => {
    const lb = ensureLightbox();
    LB.items = typeof items === 'string' ? [{ src: items, caption: start && typeof start === 'string' ? start : caption }] : items;
    if (typeof start !== 'number') start = 0;
    lb.querySelector('.lb-strip').innerHTML = LB.items.length > 1 ? LB.items.map((it, i) => '<button type="button" data-i="' + i + '" aria-label="Photo ' + (i + 1) + '"><img src="' + CV.esc(it.src) + '" alt=""></button>').join('') : '';
    lb.classList.add('open'); document.body.style.overflow = 'hidden';
    lbShow(start);
    lb.querySelector('.lb-close').focus();
  };
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-lightbox]');
    if (!t) return;
    e.preventDefault();
    const group = t.closest('[data-gallery]');
    if (group) {
      const all = CV.$$('[data-lightbox]', group);
      return CV.lightbox(all.map((n) => ({ src: n.getAttribute('data-lightbox'), caption: n.getAttribute('data-caption') || '' })), all.indexOf(t));
    }
    CV.lightbox(t.getAttribute('data-lightbox') || t.getAttribute('href') || t.src, t.getAttribute('data-caption') || t.getAttribute('alt') || '');
  });
  document.addEventListener('keydown', (e) => {
    const open = CV.$('.lightbox.open');
    if (open && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) { e.preventDefault(); return lbShow(LB.i + (e.key === 'ArrowRight' ? 1 : -1)); }
    if (e.key !== 'Escape') return;
    if (open) closeLightbox();
    else CV.$$('.modal.open').forEach((m) => CV.modal.close(m));
  });

  /* ---------- Forms via AJAX ---------- */
  CV.bindForm = (form, opts) => {
    if (!form) return;
    const msg = form.querySelector('.form-msg') || (opts && opts.msg);
    const show = (text, type) => { if (!msg) return CV.toast(text, type === 'ok' ? 'ok' : 'error'); msg.textContent = text; msg.className = 'form-msg show ' + type; };
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (opts.validate) { const bad = opts.validate(form); if (bad) return show(bad, 'error'); }
      const btn = form.querySelector('button[type=submit]');
      const label = btn ? btn.innerHTML : '';
      if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spin"></span> ' + (opts.busy || 'Please wait…'); }
      if (msg) msg.className = 'form-msg';
      try {
        const fd = new FormData(form);
        if (opts.action) fd.set('action', opts.action);
        const res = await CV.post(opts.url, fd);
        if (opts.success) opts.success(res, form, show); else if (res.message) show(res.message, 'ok');
        if (res.redirect && !opts.noRedirect) setTimeout(() => (location.href = res.redirect), opts.delay || 0);
      } catch (err) { show(err.message, 'error'); if (opts.fail) opts.fail(err); }
      finally { if (btn && !(opts.keepDisabled)) { btn.disabled = false; btn.innerHTML = label; } }
    });
  };

  /* password show/hide + strength meter */
  CV.$$('[data-reveal]').forEach((btn) => btn.addEventListener('click', () => {
    const input = document.getElementById(btn.getAttribute('data-reveal'));
    const show = input.type === 'password'; input.type = show ? 'text' : 'password'; btn.textContent = show ? 'HIDE' : 'SHOW';
  }));
  CV.$$('[data-strength]').forEach((input) => {
    const bar = document.getElementById(input.getAttribute('data-strength'));
    if (!bar) return;
    input.addEventListener('input', () => {
      const v = input.value; let s = 0;
      if (v.length >= 8) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
      bar.firstElementChild.style.width = (v ? Math.max(s, 1) * 25 : 0) + '%';
      bar.firstElementChild.style.background = ['#b42318', '#b42318', '#d9a441', '#2f9e6e', '#0f7b4f'][s];
    });
  });

  /* ---------- Paginated AJAX table ---------- */
  /* cfg: { el, url, params(), row(r), cols, empty, per, onData(json) } */
  CV.table = (cfg) => {
    const host = typeof cfg.el === 'string' ? CV.$(cfg.el) : cfg.el;
    const tbody = host.querySelector('tbody');
    const pager = host.querySelector('.pager');
    let page = 1, token = 0;
    const skeleton = () => {
      tbody.innerHTML = Array.from({ length: 5 }, () => '<tr class="tbl-skel">' + Array.from({ length: cfg.cols }, () => '<td><div class="skel"></div></td>').join('') + '</tr>').join('');
    };
    async function load(p, quiet) {
      page = p || page; const mine = ++token;
      if (!quiet) skeleton();
      try {
        const json = await CV.get(cfg.url, Object.assign({ page, per: cfg.per || 10 }, cfg.params ? cfg.params() : {}));
        if (mine !== token) return;
        tbody.innerHTML = json.rows.length
          ? json.rows.map(cfg.row).join('')
          : '<tr><td colspan="' + cfg.cols + '"><div class="empty"><b>' + CV.esc((cfg.empty || {}).title || 'Nothing here yet') + '</b>' + CV.esc((cfg.empty || {}).text || '') + '</div></td></tr>';
        renderPager(json);
        if (cfg.onData) cfg.onData(json);
      } catch (err) {
        if (mine !== token) return;
        tbody.innerHTML = '<tr><td colspan="' + cfg.cols + '"><div class="empty"><b>Could not load data</b>' + CV.esc(err.message) + '</div></td></tr>';
      }
    }
    function renderPager(j) {
      if (!pager) return;
      const pages = Math.max(1, Math.ceil(j.total / j.per));
      const from = j.total ? (j.page - 1) * j.per + 1 : 0, to = Math.min(j.total, j.page * j.per);
      const btns = [];
      const add = (n, label, dis, on) => btns.push('<button type="button" data-p="' + n + '"' + (dis ? ' disabled' : '') + (on ? ' class="on"' : '') + '>' + label + '</button>');
      add(j.page - 1, '‹', j.page <= 1);
      let start = Math.max(1, j.page - 2), end = Math.min(pages, start + 4); start = Math.max(1, end - 4);
      for (let i = start; i <= end; i++) add(i, i, false, i === j.page);
      add(j.page + 1, '›', j.page >= pages);
      pager.innerHTML = '<span>Showing ' + from + '–' + to + ' of ' + j.total + '</span><div class="pages">' + btns.join('') + '</div>';
      pager.querySelectorAll('button[data-p]').forEach((b) => b.addEventListener('click', () => load(+b.dataset.p)));
    }
    return { reload: (quiet) => load(page, quiet), first: () => load(1), page: () => page };
  };

  CV.debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms || 300); }; };

  CV.badge = {
    sev: (s) => '<span class="badge sev-' + CV.esc(s) + '">' + CV.esc(s) + '</span>',
    st: (s) => '<span class="badge st-' + CV.esc(String(s).replace(/ /g, '-')) + '">' + CV.esc(s) + '</span>'
  };

  CV.ago = (iso) => {
    const d = new Date(iso.replace(' ', 'T')); const s = Math.round((Date.now() - d) / 1000);
    if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s / 60) + ' min ago';
    if (s < 86400) return Math.floor(s / 3600) + ' hr ago'; if (s < 604800) return Math.floor(s / 86400) + ' d ago';
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
  };
  CV.fmtDate = (iso, withTime) => {
    if (!iso) return '—'; const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) + (withTime ? ', ' + d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '');
  };

  /* ---------- Reveal on scroll + count-up ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    const els = CV.$$('.reveal-up');
    if ('IntersectionObserver' in window && els.length) {
      const io = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { threshold: 0.12 });
      els.forEach((el) => io.observe(el));
    } else els.forEach((el) => el.classList.add('in'));

    const counters = CV.$$('[data-count]');
    if (counters.length && 'IntersectionObserver' in window) {
      const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
      const co = new IntersectionObserver((entries) => entries.forEach((en) => {
        if (!en.isIntersecting) return; co.unobserve(en.target);
        const el = en.target, end = +el.dataset.count, suffix = el.dataset.suffix || '';
        if (reduce || end === 0) { el.textContent = end + suffix; return; }
        const t0 = performance.now(), dur = 1400;
        const tick = (t) => { const p = Math.min(1, (t - t0) / dur); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suffix; if (p < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
      }), { threshold: 0.4 });
      counters.forEach((c) => co.observe(c));
    }
  });
})();
