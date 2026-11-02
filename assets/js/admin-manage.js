/* Admin: users & staff, campus data (categories / locations), system settings. */
(function () {
  'use strict';
  const E = CV.esc, ic = CV.ui.ic;

  /* ---------- users & staff ---------- */
  const ut = CV.$('#userTable');
  if (ut) {
    const U = 'api/admin_users.php';
    const store = {};
    // email + Google link status in one cell
    const contact = (r) => (r.email ? '<div class="cell-sub strong">' + E(r.email) + '</div>' : '<div class="cell-sub">No email</div>') +
      (r.google_email ? '<span class="g-tag on" title="Linked ' + E(CV.fmtDate(r.google_linked_at)) + '">' + ic('link') + ' ' + E(r.google_email) + '</span>'
                      : '<span class="g-tag">' + ic('unlink') + ' Google not linked</span>') +
      (+r.must_change_password ? '<span class="g-tag warn">First sign-in pending</span>' : '');
    const tbl = CV.table({
      el: ut, url: U, cols: 7, per: 12,
      params: () => ({ action: 'users', q: CV.$('#fq').value.trim(), role: CV.$('#frole').value, status: CV.$('#fstatus').value }),
      empty: { title: 'No users found', text: 'Try a different search or filter.' },
      row: (r) => {
        store[r.user_id] = r;
        const dept = r.role === 'student' ? [r.course, r.year_level ? 'Y' + r.year_level : '', r.section].filter(Boolean).join(' · ') : (r.department_name || '—');
        // ID first, then name (students shown as Surname, Firstname, MI.)
        return '<tr><td class="col-id">' + E(r.school_id) + '</td><td><div class="cell-title">' + E(r.display_name) + '</div><div class="cell-sub">' + E(r.position || '') + '</div></td>' +
          '<td><span class="role-tag ' + E(r.role) + '">' + E(r.role) + '</span></td><td>' + E(dept) + '</td><td>' + contact(r) + '</td><td><span class="acct ' + E(r.account_status) + '">' + E(r.account_status) + '</span></td>' +
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
    const toggleRole = () => {
      const st = role.value === 'student', isNew = CV.$('#umId').value === '0';
      CV.$('#umStudentRow').hidden = !st; CV.$('#umStaffRow').hidden = st;
      CV.$('#umEmailOpt').hidden = !st; CV.$('#umPwMode').hidden = !(st && isNew);
    };
    role.addEventListener('change', toggleRole);
    CV.get(U, { action: 'departments' }).then((r) => { CV.$('#umDept').innerHTML = '<option value="">None</option>' + r.rows.map((d) => '<option value="' + d.department_id + '">' + E(d.department_name) + '</option>').join(''); });

    function openUser(r) {
      form.reset(); CV.$('.form-msg', form).className = 'form-msg';
      CV.$('#umTitle').textContent = r ? 'Edit user' : 'Add user';
      CV.$('#umId').value = r ? r.user_id : 0;
      CV.$('#umForceWrap').hidden = !r; CV.$('#umNote').hidden = !!r;
      if (r) {
        role.value = r.role; CV.$('#umSid').value = r.school_id; CV.$('#umFirst').value = r.first_name; CV.$('#umMi').value = r.middle_initial || ''; CV.$('#umLast').value = r.last_name;
        CV.$('#umEmail').value = r.email || ''; CV.$('#umContact').value = r.contact_number || ''; CV.$('#umDept').value = r.department_id || ''; CV.$('#umPos').value = r.position || '';
        CV.$('#umCourse').value = r.course || ''; CV.$('#umYear').value = r.year_level || 1; CV.$('#umSec').value = r.section || '';
      }
      toggleRole(); CV.modal.open('userModal');
    }
    CV.$('#addUser').addEventListener('click', () => openUser(null));
    CV.bindForm(form, { url: U, action: 'user_save', busy: 'Saving…', noRedirect: true,
      success: (res) => {
        CV.modal.close('userModal'); tbl.reload(true);
        if (res.temp_password) secret('First password', 'Account created. Username: ' + res.username + '. They must change this password at first sign-in.', res.temp_password);
        else CV.toast(res.message);
      } });

    /* ---------- import students from a masterlist ---------- */
    const im = { rows: [], created: [], mode: 'pattern' };
    const imSteps = CV.$$('#imSteps li');
    const imStep = (n) => {
      imSteps.forEach((li, i) => { li.classList.toggle('on', i === n); li.classList.toggle('done', i < n); });
      ['#imUpload', '#imCommit', '#imDone'].forEach((sel, i) => CV.$(sel).classList.toggle('on', i === n));
    };
    const fileIn = CV.$('#imFile'), drop = CV.$('#imDrop');
    const pickName = () => { const f = fileIn.files[0]; CV.$('#imFileName').textContent = f ? f.name + ' · ' + Math.ceil(f.size / 1024) + ' KB' : ''; drop.classList.toggle('has', !!f); };
    fileIn.addEventListener('change', pickName);
    ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', (e) => { if (e.dataTransfer.files.length) { fileIn.files = e.dataTransfer.files; pickName(); } });

    // the college follows the course (shown, never asked)
    const collegeOf = (text) => {
      const norm = (x) => String(x).toUpperCase().replace(/[^A-Z0-9]/g, '');
      const t = norm(text); if (!t) return '';
      const groups = window.CV_COURSES || [];
      for (const g of groups) if (g.programs.some((p) => norm(p) === t || (t.length > 6 && norm(p).includes(t)))) return g.college;
      for (const g of groups) if (g.programs.some((p) => { const m = p.match(/\(([A-Z]+)\)/); return m && m[1].length >= 3 && t.includes(m[1]); })) return g.college;
      return '';
    };
    const showCollege = () => {
      const c = collegeOf(CV.$('#imCourse').value);
      CV.$('#imCollegeName').textContent = c || 'follows the course';
      CV.$('#imCollege').classList.toggle('set', !!c);
    };
    CV.$('#imCourse').addEventListener('input', showCollege);
    CV.$('#imCourse').addEventListener('change', showCollege);
    CV.$('#importBtn').addEventListener('click', () => {
      CV.$('#imUpload').reset(); pickName(); CV.$('.form-msg', CV.$('#imUpload')).className = 'form-msg';
      imStep(0); CV.modal.open('importModal');
    });

    const renderPreview = () => {
      const mid = CV.$('#imMid').checked;
      CV.$('tbody', CV.$('#imTable')).innerHTML = im.rows.map((r, idx) => {
        const n = mid ? r.split_on : r.split_off;
        const name = n[0] + ', ' + n[1] + (n[2] ? ' ' + n[2] + '.' : '');
        const extra = [r.course, r.year_level ? 'Y' + r.year_level : '', r.section].filter(Boolean).join(' · ');
        return '<tr class="' + (r.problem ? 'bad' : '') + '"><td class="muted">' + (idx + 1) + '</td><td class="col-id">' + E(r.school_id || '—') + '</td>' +
          '<td><div class="cell-title">' + E(name) + '</div>' + (extra ? '<div class="cell-sub">' + E(extra) + '</div>' : '') + '</td>' +
          '<td>' + (r.problem ? '<span class="chk bad">' + ic('alert') + ' ' + E(r.problem) + '</span>' : '<span class="chk ok">' + ic('check') + ' Ready</span>') + '</td></tr>';
      }).join('');
    };
    CV.$('#imMid').addEventListener('change', renderPreview);

    CV.bindForm(CV.$('#imUpload'), {
      url: U, action: 'import_preview', busy: 'Reading the file…', noRedirect: true,
      validate: () => (fileIn.files[0] ? '' : 'Choose the masterlist file first.'),
      success: (res) => {
        im.rows = res.rows;
        CV.$('#imToken').value = res.token;
        const bad = res.rows.length - res.ready;
        CV.$('#imSummary').innerHTML = (res.title ? '<span class="im-title">' + ic('file') + ' ' + E(res.title) + '</span>' : '') +
          '<span class="pill ok">' + res.ready + ' ready</span>' + (bad ? '<span class="pill bad">' + bad + ' will be skipped</span>' : '') +
          '<span class="muted">' + res.rows.length + ' rows found</span>';
        CV.$('#imMidWrap').hidden = !res.has_full_name;
        CV.$('#imGo').disabled = !res.ready;
        CV.$('#imGo').innerHTML = ic('users') + ' Create ' + res.ready + ' account' + (res.ready === 1 ? '' : 's');
        CV.$('.form-msg', CV.$('#imCommit')).className = 'form-msg';
        // pre-fill course / year / section read from the list's title or file name
        const d = res.detected || {};
        const found = [];
        CV.$('#imCourse').value = d.course || ''; CV.$('#imSec').value = d.section || '';
        if (d.year_level) CV.$('#imYear').value = d.year_level;
        if (d.code) found.push(d.code); if (d.year_level) found.push('Year ' + d.year_level); if (d.section) found.push('Section ' + d.section);
        const box = CV.$('#imDetect');
        box.hidden = !found.length;
        if (found.length) CV.$('#imDetectText').innerHTML = 'Read <b>' + E(found.join(' · ')) + '</b> from “' + E(res.title || res.filename) + '”. Check it, then change anything that is wrong.';
        showCollege();
        renderPreview(); imStep(1);
      }
    });
    CV.$('#imBack').addEventListener('click', () => { CV.$('#imUpload').reset(); pickName(); imStep(0); });

    CV.bindForm(CV.$('#imCommit'), {
      url: U, action: 'import_commit', busy: 'Creating accounts…', noRedirect: true,
      validate: (f) => {
        const needBatch = im.rows.some((r) => !r.problem && (!r.course || !r.year_level || !r.section));
        return needBatch && (!f.course.value.trim() || !f.section.value.trim()) ? 'Enter the course and section for this class.' : '';
      },
      success: (res) => {
        im.created = res.created; im.mode = res.mode;
        CV.$('#imDoneTitle').textContent = res.message;
        CV.$('#imDoneText').textContent = res.mode === 'pattern'
          ? 'Each student signs in with their student ID and the password {ID}@' + (CV.$('#imDone').dataset.domain || '') + '. They will be asked to change it.'
          : 'Random first passwords were made. Download or print the list now; it cannot be shown again.';
        imStep(2); tbl.reload(true);
      }
    });

    const csvCell = (v) => '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"';
    CV.$('#imCsv').addEventListener('click', () => {
      const lines = [['Username', 'Name', 'Course', 'Year', 'Section', 'First password']].concat(
        im.created.map((c) => [c.username, c.name, c.course, c.year_level, c.section, c.password]));
      const blob = new Blob(['﻿' + lines.map((l) => l.map(csvCell).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
      a.download = 'campusvoice-accounts-' + new Date().toISOString().slice(0, 10) + '.csv'; a.click();
      setTimeout(() => URL.revokeObjectURL(a.href), 2000);
    });
    CV.$('#imXls').addEventListener('click', () => {
      // the list is posted back to the server only to be turned into a designed Excel file; nothing is stored
      const f = document.createElement('form'); f.method = 'POST'; f.action = CV.url('api/admin_users.php'); f.style.display = 'none';
      const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
      add('action', 'import_xlsx'); add('_csrf', (document.querySelector('meta[name=cv-csrf]') || {}).content || ''); add('data', JSON.stringify(im.created));
      document.body.appendChild(f); f.submit(); setTimeout(() => f.remove(), 2000);
    });
    CV.$('#imSlips').addEventListener('click', () => {
      const w = window.open('', '_blank');
      if (!w) return CV.toast('Allow pop-ups to print the slips.', 'error');
      const site = location.origin + location.pathname.replace(/admin\/[^/]*$/, 'login.php');
      const slip = (c) => '<div class="slip"><b>CampusVoice sign-in</b><span>' + E(c.name) + '</span><span>' + E([c.course, 'Y' + c.year_level, c.section].join(' · ')) + '</span>' +
        '<dl><dt>Username</dt><dd>' + E(c.username) + '</dd><dt>First password</dt><dd>' + E(c.password) + '</dd></dl><small>' + E(site) + ' · change your password after signing in</small></div>';
      w.document.write('<!doctype html><meta charset="utf-8"><title>Sign-in slips</title><style>' +
        'body{font:13px system-ui,sans-serif;margin:12mm;color:#111}.grid{display:grid;grid-template-columns:1fr 1fr;gap:6mm}' +
        '.slip{border:1px dashed #888;border-radius:6px;padding:10px 12px;break-inside:avoid;display:flex;flex-direction:column;gap:2px}' +
        '.slip b{color:#0f2a5e;font-size:14px}dl{display:grid;grid-template-columns:auto 1fr;gap:2px 10px;margin:6px 0}dt{color:#555}dd{margin:0;font-family:ui-monospace,Consolas,monospace;font-weight:700}small{color:#666}' +
        '</style><div class="grid">' + im.created.map(slip).join('') + '</div><script>onload=()=>print()<\/script>');
      w.document.close();
    });

    function secret(title, text, value) { CV.$('#secTitle').textContent = title; CV.$('#secText').textContent = text; CV.$('#secValue').textContent = value; CV.modal.open('secretModal'); }

    ut.addEventListener('click', async (e) => {
      const b = e.target.closest('button[data-edit],button[data-reset],button[data-toggle],button[data-del]'); if (!b) return;
      const id = b.dataset.edit || b.dataset.reset || b.dataset.toggle || b.dataset.del, r = store[id];
      try {
        if (b.dataset.edit) return openUser(r);
        if (b.dataset.reset) { const x = await CV.post(U, { action: 'reset_code', user_id: id }); return secret('Password reset code', 'Give this code to ' + x.username + '. It works for ' + x.minutes + ' minutes on the Reset password page (Forgot password → Enter it here).', x.code); }
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
    let cats = {}, locs = {}, staff = [];
    const status = (s) => '<span class="acct ' + (s === 'active' ? 'active' : 'inactive') + '">' + E(s) + '</span>';

    async function loadCats() {
      const r = await CV.get(U, { action: 'categories' }); cats = {}; staff = r.staff;
      const people = (c) => c.handlers.length ? '<div class="chips">' + c.handlers.map((h) => '<span class="chip' + (h.availability === 'on_leave' ? ' off' : '') + '">' + E(h.name) + '</span>').join('') + '</div>'
        : '<span class="cell-sub">' + ic('inbox') + ' Admin queue</span>';
      CV.$('tbody', CV.$('#catTable')).innerHTML = r.rows.map((c) => { cats[c.category_id] = c;
        return '<tr><td><div class="cell-title">' + E(c.category_name) + (+c.needs_location ? ' <span class="auto-tag">' + ic('pin') + ' location</span>' : '') + '</div><div class="cell-sub">' + E(c.description || '') + '</div></td>' +
          '<td>' + people(c) + '</td><td>' + E(c.department_name || '—') + '</td><td class="nw">' + c.concerns + '</td><td>' + status(c.status) + '</td>' +
          '<td class="nw"><div class="row-actions"><button class="btn btn-ghost btn-sm" data-cedit="' + c.category_id + '" aria-label="Edit">' + ic('edit') + '</button><button class="btn btn-ghost btn-sm" data-ctoggle="' + c.category_id + '" aria-label="Toggle">' + ic(c.status === 'active' ? 'eyeOff' : 'eye') + '</button></div></td></tr>'; }).join('');
    }
    async function loadLocs() {
      const r = await CV.get(U, { action: 'locations' }); locs = {};
      CV.$('tbody', CV.$('#locTable')).innerHTML = r.rows.map((l) => { locs[l.location_id] = l;
        return '<tr><td class="cell-title">' + E(l.building) + '</td><td>' + E(l.floor) + '</td><td>' + E(l.area) + '</td><td class="nw">' + l.concerns + '</td><td>' + status(l.status) + '</td>' +
          '<td class="nw"><div class="row-actions"><button class="btn btn-ghost btn-sm" data-ledit="' + l.location_id + '" aria-label="Edit">' + ic('edit') + '</button><button class="btn btn-ghost btn-sm" data-ltoggle="' + l.location_id + '" aria-label="Toggle">' + ic(l.status === 'active' ? 'eyeOff' : 'eye') + '</button></div></td></tr>'; }).join('');
    }
    CV.get(U, { action: 'departments' }).then((r) => { CV.$('#catDept').innerHTML = '<option value="">None</option>' + r.rows.map((d) => '<option value="' + d.department_id + '">' + E(d.department_name) + '</option>').join(''); });
    const openCat = (c) => { CV.$('#catForm').reset(); CV.$('#catTitle').textContent = c ? 'Edit concern type' : 'Add concern type'; CV.$('#catId').value = c ? c.category_id : 0;
      const mine = c ? c.handlers.map((h) => +h.user_id) : [];
      CV.$('#catHandlers').innerHTML = staff.length ? staff.map((s) => '<label class="handler"><input type="checkbox" name="handlers[]" value="' + s.user_id + '"' + (mine.includes(+s.user_id) ? ' checked' : '') + '>' +
        '<span><b>' + E(s.name) + '</b><small>' + E(s.department_name || 'No office') + (s.availability === 'on_leave' ? ' · on leave' : '') + '</small></span></label>').join('')
        : '<p class="cell-sub">No active staff yet. Add staff in Users &amp; Staff.</p>';
      if (c) { CV.$('#catName').value = c.category_name; CV.$('#catDesc').value = c.description || ''; CV.$('#catDept').value = c.default_department_id || '';
        CV.$('#catNext').value = c.next_steps || ''; CV.$('#catLoc').checked = +c.needs_location === 1; }
      CV.modal.open('catModal'); };
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
  const sys = CV.$('#hoursForm');
  if (sys) {
    const S = 'api/admin_system.php';
    async function load() {
      const r = await CV.get(S, { action: 'settings' });
      Object.keys(r.settings).forEach((k) => { const el = CV.$('#' + k); if (!el) return; if (el.type === 'checkbox') el.checked = r.settings[k] === '1'; else el.value = r.settings[k]; });
      r.hours.forEach((h) => { const f = CV.$('#hoursForm'); f['active_' + h.day_of_week].checked = +h.active === 1; f['open_' + h.day_of_week].value = h.open_time || ''; f['close_' + h.day_of_week].value = h.close_time || ''; });
    }
    CV.$$('form[data-settings]').forEach((f) => CV.bindForm(f, { url: S, action: 'settings_save', busy: 'Saving…', noRedirect: true, success: () => CV.toast('Settings saved.') }));
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

  /* copy buttons: <button data-copy="#selector"> */
  CV.$$('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
    const t = (CV.$(b.dataset.copy) || {}).textContent || '';
    try { await navigator.clipboard.writeText(t); } catch (e) {
      const r = document.createRange(); r.selectNodeContents(CV.$(b.dataset.copy)); const sel = getSelection(); sel.removeAllRanges(); sel.addRange(r); document.execCommand('copy'); sel.removeAllRanges();
    }
    CV.toast('Copied.');
  }));
})();
