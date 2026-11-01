/* Admin: users & staff, campus data (categories / locations), system settings. */
(function () {
  'use strict';
  const E = CV.esc, ic = CV.ui.ic;

  /* ---------- users & staff ---------- */
  const ut = CV.$('#userTable');
  if (ut) {
    const U = 'api/admin_users.php';
    const store = {};
    const tbl = CV.table({
      el: ut, url: U, cols: 7, per: 12,
      params: () => ({ action: 'users', q: CV.$('#fq').value.trim(), role: CV.$('#frole').value, status: CV.$('#fstatus').value }),
      empty: { title: 'No users found', text: 'Try a different search or filter.' },
      row: (r) => {
        store[r.user_id] = r;
        const dept = r.role === 'student' ? [r.course, r.year_level ? 'Y' + r.year_level : '', r.section].filter(Boolean).join(' · ') : (r.department_name || '—');
        // ID first, then name (students shown as Surname, Firstname, MI.)
        return '<tr><td class="col-id">' + E(r.school_id) + '</td><td><div class="cell-title">' + E(r.display_name) + '</div><div class="cell-sub">' + E(r.position || '') + '</div></td>' +
          '<td><span class="role-tag ' + E(r.role) + '">' + E(r.role) + '</span></td><td>' + E(dept) + '</td><td>' + E(r.email) + '</td><td><span class="acct ' + E(r.account_status) + '">' + E(r.account_status) + '</span></td>' +
          '<td class="nw"><div class="row-actions"><button class="btn btn-ghost btn-sm" data-edit="' + r.user_id + '" title="Edit" aria-label="Edit">' + ic('edit') + '</button>' +
          '<button class="btn btn-ghost btn-sm" data-reset="' + r.user_id + '" title="Issue reset code" aria-label="Issue reset code">' + ic('key') + '</button>' +
          '<button class="btn btn-ghost btn-sm" data-toggle="' + r.user_id + '" title="' + (r.account_status === 'active' ? 'Deactivate' : 'Activate') + '" aria-label="Toggle active">' + ic(r.account_status === 'active' ? 'lock' : 'checkCircle') + '</button>' +
          '<button class="btn btn-ghost btn-sm" data-del="' + r.user_id + '" title="Delete" aria-label="Delete">' + ic('trash') + '</button></div></td></tr>';
      }
    });
    tbl.first();
    ['#frole', '#fstatus'].forEach((s) => CV.$(s).addEventListener('change', () => tbl.first()));
    CV.$('#fq').addEventListener('input', CV.debounce(() => tbl.first(), 350));

    const form = CV.$('#userForm'), role = CV.$('#umRole');
    const toggleRole = () => { CV.$('#umStudentRow').hidden = role.value !== 'student'; CV.$('#umStaffRow').hidden = role.value === 'student'; };
    role.addEventListener('change', toggleRole);
    CV.get(U, { action: 'departments' }).then((r) => { CV.$('#umDept').innerHTML = '<option value="">None</option>' + r.rows.map((d) => '<option value="' + d.department_id + '">' + E(d.department_name) + '</option>').join(''); });

    function openUser(r) {
      form.reset(); CV.$('.form-msg', form).className = 'form-msg';
      CV.$('#umTitle').textContent = r ? 'Edit user' : 'Add user';
      CV.$('#umId').value = r ? r.user_id : 0;
      CV.$('#umForceWrap').hidden = !r; CV.$('#umNote').hidden = !!r;
      if (r) {
        role.value = r.role; CV.$('#umSid').value = r.school_id; CV.$('#umFirst').value = r.first_name; CV.$('#umMi').value = r.middle_initial || ''; CV.$('#umLast').value = r.last_name;
        CV.$('#umEmail').value = r.email; CV.$('#umContact').value = r.contact_number || ''; CV.$('#umDept').value = r.department_id || ''; CV.$('#umPos').value = r.position || '';
        CV.$('#umCourse').value = r.course || ''; CV.$('#umYear').value = r.year_level || 1; CV.$('#umSec').value = r.section || '';
      }
      toggleRole(); CV.modal.open('userModal');
    }
    CV.$('#addUser').addEventListener('click', () => openUser(null));
    CV.bindForm(form, { url: U, action: 'user_save', busy: 'Saving…', noRedirect: true,
      success: (res) => {
        CV.modal.close('userModal'); tbl.reload(true);
        if (res.temp_password) secret('Temporary password', 'Account created for ' + res.email + '. They must change it at first sign-in.', res.temp_password);
        else CV.toast(res.message);
      } });

    function secret(title, text, value) { CV.$('#secTitle').textContent = title; CV.$('#secText').textContent = text; CV.$('#secValue').textContent = value; CV.modal.open('secretModal'); }

    ut.addEventListener('click', async (e) => {
      const b = e.target.closest('button[data-edit],button[data-reset],button[data-toggle],button[data-del]'); if (!b) return;
      const id = b.dataset.edit || b.dataset.reset || b.dataset.toggle || b.dataset.del, r = store[id];
      try {
        if (b.dataset.edit) return openUser(r);
        if (b.dataset.reset) { const x = await CV.post(U, { action: 'reset_code', user_id: id }); return secret('Password reset code', 'Give this code to ' + x.email + '. It works for ' + x.minutes + ' minutes on the Reset password page.', x.code); }
        if (b.dataset.toggle) { const x = await CV.post(U, { action: 'user_status', user_id: id, status: r.account_status === 'active' ? 'inactive' : 'active' }); CV.toast(x.message); return tbl.reload(true); }
        if (b.dataset.del) { if (!confirm('Delete ' + r.display_name + '? This cannot be undone.')) return; const x = await CV.post(U, { action: 'user_delete', user_id: id }); CV.toast(x.message); tbl.reload(true); }
      } catch (err) { CV.toast(err.message, 'error'); }
    });
  }

  /* ---------- campus data ---------- */
  const campus = CV.$('#campus');
  if (campus) {
    const U = 'api/admin_users.php';
    const sw = (t) => { CV.$$('#tabs button').forEach((b) => b.classList.toggle('on', b.dataset.tab === t)); CV.$('#paneCat').classList.toggle('on', t === 'cat'); CV.$('#paneLoc').classList.toggle('on', t === 'loc'); };
    CV.$$('#tabs button').forEach((b) => b.addEventListener('click', () => sw(b.dataset.tab)));
    let cats = {}, locs = {};
    const status = (s) => '<span class="acct ' + (s === 'active' ? 'active' : 'inactive') + '">' + E(s) + '</span>';

    async function loadCats() {
      const r = await CV.get(U, { action: 'categories' }); cats = {};
      CV.$('tbody', CV.$('#catTable')).innerHTML = r.rows.map((c) => { cats[c.category_id] = c;
        return '<tr><td><div class="cell-title">' + E(c.category_name) + '</div><div class="cell-sub">' + E(c.description || '') + '</div></td><td>' + E(c.department_name || '—') + '</td><td class="nw">' + c.concerns + '</td><td>' + status(c.status) + '</td>' +
          '<td class="nw"><div class="row-actions"><button class="btn btn-ghost btn-sm" data-cedit="' + c.category_id + '" aria-label="Edit">' + ic('edit') + '</button><button class="btn btn-ghost btn-sm" data-ctoggle="' + c.category_id + '" aria-label="Toggle">' + ic(c.status === 'active' ? 'eyeOff' : 'eye') + '</button></div></td></tr>'; }).join('');
    }
    async function loadLocs() {
      const r = await CV.get(U, { action: 'locations' }); locs = {};
      CV.$('tbody', CV.$('#locTable')).innerHTML = r.rows.map((l) => { locs[l.location_id] = l;
        return '<tr><td class="cell-title">' + E(l.building) + '</td><td>' + E(l.floor) + '</td><td>' + E(l.area) + '</td><td class="nw">' + l.concerns + '</td><td>' + status(l.status) + '</td>' +
          '<td class="nw"><div class="row-actions"><button class="btn btn-ghost btn-sm" data-ledit="' + l.location_id + '" aria-label="Edit">' + ic('edit') + '</button><button class="btn btn-ghost btn-sm" data-ltoggle="' + l.location_id + '" aria-label="Toggle">' + ic(l.status === 'active' ? 'eyeOff' : 'eye') + '</button></div></td></tr>'; }).join('');
    }
    CV.get(U, { action: 'departments' }).then((r) => { CV.$('#catDept').innerHTML = '<option value="">None</option>' + r.rows.map((d) => '<option value="' + d.department_id + '">' + E(d.department_name) + '</option>').join(''); });
    const openCat = (c) => { CV.$('#catForm').reset(); CV.$('#catTitle').textContent = c ? 'Edit category' : 'Add category'; CV.$('#catId').value = c ? c.category_id : 0;
      if (c) { CV.$('#catName').value = c.category_name; CV.$('#catDesc').value = c.description || ''; CV.$('#catDept').value = c.default_department_id || ''; } CV.modal.open('catModal'); };
    const openLoc = (l) => { CV.$('#locForm').reset(); CV.$('#locTitle').textContent = l ? 'Edit location' : 'Add location'; CV.$('#locId').value = l ? l.location_id : 0;
      if (l) { CV.$('#locB').value = l.building; CV.$('#locF').value = l.floor; CV.$('#locA').value = l.area; } CV.modal.open('locModal'); };
    CV.$('#addCat').addEventListener('click', () => openCat(null)); CV.$('#addLoc').addEventListener('click', () => openLoc(null));
    CV.bindForm(CV.$('#catForm'), { url: U, action: 'category_save', busy: 'Saving…', noRedirect: true, success: (r) => { CV.toast(r.message); CV.modal.close('catModal'); loadCats(); } });
    CV.bindForm(CV.$('#locForm'), { url: U, action: 'location_save', busy: 'Saving…', noRedirect: true, success: (r) => { CV.toast(r.message); CV.modal.close('locModal'); loadLocs(); } });
    campus.addEventListener('click', async (e) => {
      const b = e.target.closest('button[data-cedit],button[data-ctoggle],button[data-ledit],button[data-ltoggle]'); if (!b) return;
      try {
        if (b.dataset.cedit) openCat(cats[b.dataset.cedit]);
        else if (b.dataset.ledit) openLoc(locs[b.dataset.ledit]);
        else if (b.dataset.ctoggle) { const x = await CV.post(U, { action: 'category_toggle', category_id: b.dataset.ctoggle }); CV.toast(x.message); loadCats(); }
        else { const x = await CV.post(U, { action: 'location_toggle', location_id: b.dataset.ltoggle }); CV.toast(x.message); loadLocs(); }
      } catch (err) { CV.toast(err.message, 'error'); }
    });
    loadCats().catch((e) => CV.toast(e.message, 'error')); loadLocs().catch((e) => CV.toast(e.message, 'error'));
  }

  /* ---------- system settings ---------- */
  const sys = CV.$('#systemSettings');
  if (sys) {
    const S = 'api/admin_system.php';
    async function load() {
      const r = await CV.get(S, { action: 'settings' });
      Object.keys(r.settings).forEach((k) => { const el = CV.$('#' + k); if (!el) return; if (el.type === 'checkbox') el.checked = r.settings[k] === '1'; else el.value = r.settings[k]; });
      r.hours.forEach((h) => { const f = CV.$('#hoursForm'); f['active_' + h.day_of_week].checked = +h.active === 1; f['open_' + h.day_of_week].value = h.open_time || ''; f['close_' + h.day_of_week].value = h.close_time || ''; });
    }
    CV.bindForm(CV.$('#sysForm'), { url: S, action: 'settings_save', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Settings saved.') });
    const tm = CV.$('#testMail');
    if (tm) tm.addEventListener('click', async () => {
      const msg = CV.$('#mailMsg'); const label = tm.innerHTML;
      tm.disabled = true; tm.innerHTML = '<span class="spin"></span> Sending…'; msg.className = 'form-msg';
      try { const r = await CV.post(S, { action: 'test_email' }); msg.textContent = r.message; msg.className = 'form-msg show ok'; }
      catch (e) { msg.textContent = e.message; msg.className = 'form-msg show error'; }
      finally { tm.disabled = false; tm.innerHTML = label; }
    });
    CV.bindForm(CV.$('#hoursForm'), { url: S, action: 'hours_save', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Working hours saved.') });
    load().catch((e) => CV.toast(e.message, 'error'));
  }
})();
