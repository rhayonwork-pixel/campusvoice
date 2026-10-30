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
      row: (n) => '<tr class="clickable" data-id="' + n.notification_id + '" data-concern="' + (n.concern_id || '') + '" style="' + (n.is_read == 1 ? '' : 'background:#fdfaf2') + '">' +
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

  if (new URLSearchParams(location.search).get('force')) { const c = CV.$('#pwCard'); if (c) c.scrollIntoView({ block: 'center' }); }
})();
