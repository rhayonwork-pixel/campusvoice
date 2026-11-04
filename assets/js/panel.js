/* Panel shell: collapsible sidebar, mobile drawer, notification bell, logout, polling. */
(function () {
  'use strict';
  const shell = CV.$('#shell');
  const KEY = 'cv_sidebar_collapsed';
  const store = { get() { try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; } }, set(v) { try { localStorage.setItem(KEY, v ? '1' : '0'); } catch (e) { /* private mode */ } } };

  // sidebar collapse (desktop) / drawer (mobile)
  const toggle = CV.$('#sbToggle');
  const tablet = matchMedia('(min-width: 901px) and (max-width: 1199px)');
  const mobile = matchMedia('(max-width: 900px)');
  const label = () => {
    const open = tablet.matches ? shell.classList.contains('peek') : !shell.classList.contains('collapsed');
    toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? 'Collapse sidebar' : 'Expand sidebar');
  };
  // Three modes: desktop (remembered expand/collapse), tablet (icon rail that opens over content), mobile (drawer).
  function sync() {
    shell.classList.remove('open', 'peek');
    if (mobile.matches) shell.classList.remove('collapsed');
    else if (tablet.matches) shell.classList.add('collapsed');
    else shell.classList.toggle('collapsed', store.get());
    label();
  }
  sync();
  tablet.addEventListener('change', sync); mobile.addEventListener('change', sync);
  toggle.addEventListener('click', () => {
    if (tablet.matches) shell.classList.toggle('peek');
    else { const c = !shell.classList.contains('collapsed'); shell.classList.toggle('collapsed', c); store.set(c); }
    label();
  });
  CV.$('#menuBtn').addEventListener('click', () => shell.classList.add('open'));
  const closeNav = () => { shell.classList.remove('open', 'peek'); label(); };
  CV.$('#scrim').addEventListener('click', closeNav);
  CV.$$('.sb-nav a').forEach((a) => a.addEventListener('click', closeNav));
  // sidebar groups (Users → Staff & Admins / Students). In the icon-only rail there is no room to unfold, so jump to the first page.
  CV.$$('.nav-parent').forEach((b) => b.addEventListener('click', () => {
    if (shell.classList.contains('collapsed') && !shell.classList.contains('peek') && !mobile.matches) { location.href = b.dataset.first; return; }
    const g = b.closest('.nav-grp'), open = !g.classList.contains('open');
    g.classList.toggle('open', open); b.setAttribute('aria-expanded', String(open));
  }));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeNav(); });

  // logout via AJAX
  CV.$('#logoutBtn').addEventListener('click', async () => {
    try { const r = await CV.post('api/auth.php', { action: 'logout' }); location.href = r.redirect; } catch (e) { location.href = CV.url('login.php'); }
  });

  // notification bell
  const bellBtn = CV.$('#bellBtn'), pop = CV.$('#bellPop'), dot = CV.$('#bellDot'), navCount = CV.$('#navCount'), list = CV.$('#bpList');
  const role = document.body.className.match(/role-(\w+)/)[1];
  const setCount = (n) => {
    dot.textContent = n > 99 ? '99+' : n; dot.hidden = !n;
    if (navCount) { navCount.textContent = n > 99 ? '99+' : n; navCount.hidden = !n; }
  };
  async function count() { try { const r = await CV.get('api/notifications.php', { action: 'count' }); setCount(r.unread); } catch (e) { /* ignore */ } }
  const itemHtml = (n) => '<div class="bp-item ' + (n.is_read ? '' : 'unread') + '" data-id="' + n.notification_id + '" data-concern="' + (n.concern_id || '') + '">'
    + '<b>' + CV.esc(n.title) + (n.is_urgent == 1 ? '<span class="bp-urgent">Urgent</span>' : '') + '</b><span>' + CV.esc(n.message) + '</span><small>' + CV.ago(n.created_at) + '</small></div>';
  async function loadPop() {
    try {
      const r = await CV.get('api/notifications.php', { action: 'list', per: 8, page: 1 });
      list.innerHTML = r.rows.length ? r.rows.map(itemHtml).join('') : '<div class="bp-empty">You\'re all caught up.</div>';
      setCount(r.unread);
    } catch (e) { list.innerHTML = '<div class="bp-empty">Could not load notifications.</div>'; }
  }
  bellBtn.addEventListener('click', (e) => { e.stopPropagation(); const open = pop.hidden; pop.hidden = !open; bellBtn.setAttribute('aria-expanded', String(open)); if (open) loadPop(); });
  document.addEventListener('click', (e) => { if (!pop.hidden && !pop.contains(e.target)) { pop.hidden = true; bellBtn.setAttribute('aria-expanded', 'false'); } });
  CV.$('#markAll').addEventListener('click', async () => { await CV.post('api/notifications.php', { action: 'read_all' }); loadPop(); });
  list.addEventListener('click', async (e) => {
    const it = e.target.closest('.bp-item'); if (!it) return;
    await CV.post('api/notifications.php', { action: 'read', id: it.dataset.id }).catch(() => {});
    if (it.dataset.concern) location.href = CV.url(role + '/concern.php?id=' + it.dataset.concern);
    else loadPop();
  });
  window.CV.refreshBell = count;
  count();
  setInterval(() => { if (!document.hidden) count(); }, 30000);
})();
