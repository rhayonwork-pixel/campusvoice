/* Rendering helpers shared by student / staff / admin concern screens. */
(function () {
  'use strict';
  const ic = (n, cls) => '<svg class="ic ' + (cls || '') + '"><use href="#i-' + n + '"/></svg>';
  const E = CV.esc;

  CV.ui = {
    ic,
    stars(n) { let s = ''; for (let i = 1; i <= 5; i++) s += ic('star', i <= n ? '' : 'off'); return '<span class="stars" aria-label="' + n + ' out of 5">' + s + '</span>'; },

    timeline(rows) {
      if (!rows.length) return '<div class="empty">No updates yet.</div>';
      return '<ol class="timeline">' + rows.map((t) =>
        '<li><b>' + E(t.update_type) + (t.status ? ' ' + CV.badge.st(t.status) : '') + '</b>' +
        (t.response ? '<p>' + E(t.response) + '</p>' : '') +
        '<small>' + E(t.actor || 'System') + ' · ' + CV.fmtDate(t.created_at, true) + '</small></li>').join('') + '</ol>';
    },

    photos(list, label) {
      if (!list.length) return '';
      const tag = { before: ['Before', 'before'], after: ['Proof of fix', 'after'] };
      return '<section class="gallery" data-gallery><div class="gal-head"><b>' + ic('image') + ' Photos</b><span>' + list.length + '</span></div>' +
        '<div class="gal-grid' + (list.length === 1 ? ' one' : '') + '">' + list.map((p, i) => {
          const t = tag[p.photo_type];
          const cap = (t ? t[0] : (label || 'Photo')) + ' · ' + (i + 1) + ' of ' + list.length;
          return '<button type="button" class="gal-item" data-lightbox="' + E(p.src) + '" data-caption="' + E(cap) + '" aria-label="View ' + E(cap) + '">' +
            '<img src="' + E(p.src) + '" alt="" loading="lazy">' + (t ? '<span class="gal-tag ' + t[1] + '">' + t[0] + '</span>' : '') +
            '<span class="gal-zoom">' + ic('search') + '</span></button>';
        }).join('') + '</div></section>';
    },

    due(c) {
      if (!c.due_at) return '—';
      const over = +c.overdue === 1;
      return CV.fmtDate(c.due_at, true) + (over ? ' <span class="badge badge-over">Overdue</span>' : '');
    },

    anon(c) { return +c.is_anonymous === 1 ? ' <span class="badge badge-anon">Anonymous</span>' : ''; }
  };
})();
