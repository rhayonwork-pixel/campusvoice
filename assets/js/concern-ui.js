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
      return '<div class="photos">' + list.map((p, i) =>
        '<img src="' + E(p.src) + '" alt="' + E(label || 'Concern photo') + ' ' + (i + 1) + '" data-lightbox="' + E(p.src) + '" data-caption="' + E((label || 'Concern photo') + ' ' + (i + 1) + ' of ' + list.length) + '" loading="lazy">').join('') + '</div>';
    },

    due(c) {
      if (!c.due_at) return '—';
      const over = +c.overdue === 1;
      return CV.fmtDate(c.due_at, true) + (over ? ' <span class="badge badge-over">Overdue</span>' : '');
    },

    anon(c) { return +c.is_anonymous === 1 ? ' <span class="badge badge-anon">Anonymous</span>' : ''; }
  };
})();
