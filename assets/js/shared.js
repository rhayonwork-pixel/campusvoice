/* Notifications page + account settings (all roles). */
(function () {
  'use strict';
  const E = CV.esc;
  const role = document.body.className.match(/role-(\w+)/)[1];

  /* notifications page */
  const nt = CV.$('#notifTable');
  if (nt) {
    const tbl = CV.table({
      el: nt, url: 'api/notifications.php', cols: 3, per: 12,
      params: () => ({ action: 'list' }),
      empty: { title: 'No notifications', text: 'You are all caught up.' },
      row: (n) => '<tr class="clickable' + (n.is_read == 1 ? '' : ' unread-row') + '" data-id="' + n.notification_id + '" data-concern="' + (n.concern_id || '') + '">' +
        '<td><span class="dot-u ' + (n.is_read == 1 ? '' : 'on') + '" title="' + (n.is_read == 1 ? 'Read' : 'Unread') + '"></span></td>' +
        '<td><div class="n-title">' + E(n.title) + (n.is_urgent == 1 ? '<span class="n-urgent">Urgent</span>' : '') + '</div><div class="cell-sub">' + E(n.message) + '</div></td>' +
        '<td class="cell-sub" style="white-space:nowrap">' + CV.ago(n.created_at) + '</td></tr>'
    });
    tbl.first();
    nt.addEventListener('click', async (e) => {
      const tr = e.target.closest('tr[data-id]'); if (!tr) return;
      await CV.post('api/notifications.php', { action: 'read', id: tr.dataset.id }).catch(() => {});
      if (tr.dataset.concern) location.href = CV.url(role + '/concern.php?id=' + tr.dataset.concern); else { tbl.reload(true); CV.refreshBell(); }
    });
    CV.$('#readAll').addEventListener('click', async () => { await CV.post('api/notifications.php', { action: 'read_all' }); tbl.reload(true); CV.refreshBell(); CV.toast('All notifications marked as read.'); });
  }

  /* appearance: light / dark / system */
  const seg = CV.$('#themeSeg');
  if (seg && window.cvTheme) {
    const show = () => CV.$$('button', seg).forEach((b) => b.setAttribute('aria-checked', String(b.dataset.themeOpt === cvTheme.get())));
    show();
    seg.addEventListener('click', (e) => { const b = e.target.closest('button[data-theme-opt]'); if (!b) return; cvTheme.set(b.dataset.themeOpt); show(); CV.toast('Theme set to ' + b.dataset.themeOpt + '.'); });
    document.addEventListener('cv:theme', show);
  }

  /* settings */
  const url = 'api/account.php';
  const mismatch = (f) => (f.password.value !== f.password_confirm.value ? 'New passwords do not match.' : '');
  CV.bindForm(CV.$('#profileForm'), { url, action: 'profile', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Profile saved.') });
  CV.bindForm(CV.$('#pwForm'), { url, action: 'password', busy: 'Updating…', delay: 900, validate: mismatch });
  CV.bindForm(CV.$('#availForm'), { url, action: 'availability', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Availability updated.') });
  CV.bindForm(CV.$('#prefForm'), { url, action: 'prefs', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Preferences saved.') });

  const av = CV.$('#avatarIn');
  if (av) av.addEventListener('change', async () => {
    if (!av.files.length) return;
    const fd = new FormData(); fd.append('action', 'avatar'); fd.append('avatar', av.files[0]);
    try { await CV.post(url, fd); location.reload(); } catch (e) { CV.toast(e.message, 'error'); }
  });
  const del = CV.$('#avatarDel');
  if (del) del.addEventListener('click', async () => { try { await CV.post(url, { action: 'avatar_remove' }); location.reload(); } catch (e) { CV.toast(e.message, 'error'); } });

  /* settings sections: one pane at a time, remembered in the URL hash (#profile, #security, #linked, ...) */
  const shell = CV.$('#settingsShell');
  if (shell) {
    const links = CV.$$('.set-link', shell), panes = CV.$$('.set-pane', shell);
    const ids = links.map((a) => a.dataset.sec);
    const show = (id, focus) => {
      if (!ids.includes(id)) id = shell.dataset.first;
      links.forEach((a) => { const on = a.dataset.sec === id; a.classList.toggle('on', on); a.setAttribute('aria-selected', String(on)); if (on && a.scrollIntoView && window.innerWidth < 900) a.scrollIntoView({ block: 'nearest', inline: 'center' }); });
      panes.forEach((p) => p.classList.toggle('on', p.dataset.sec === id));
      if (focus) { const h = CV.$('#sec-' + id + ' h2'); if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); } }
    };
    links.forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); history.replaceState(null, '', '#' + a.dataset.sec); show(a.dataset.sec, true); }));
    window.addEventListener('hashchange', () => show(location.hash.slice(1)));
    show(location.hash.slice(1) || shell.dataset.first);
  }

  const gu = CV.$('#googleUnlink');
  if (gu) gu.addEventListener('click', async () => {
    if (!confirm('Unlink your Google account? You can link it again any time.')) return;
    gu.disabled = true;
    try { const r = await CV.post(url, { action: 'google_unlink' }); CV.toast(r.message); setTimeout(() => location.reload(), 700); }
    catch (e) { CV.toast(e.message, 'error'); gu.disabled = false; }
  });
})();
