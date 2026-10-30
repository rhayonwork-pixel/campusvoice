/* Admin: dashboard, concern management (queue / assignments / all / staff activity), concern detail. */
(function () {
  'use strict';
  const E = CV.esc, ic = CV.ui.ic, API = 'api/admin.php';
  const ctx = (r) => E([r.reporter_course, r.reporter_year ? 'Year ' + r.reporter_year : '', r.reporter_section].filter(Boolean).join(' · '));
  const reporter = (r) => '<div class="cell-title">' + E(r.reporter_name) + '</div><div class="cell-sub">' + ctx(r) + '</div>';
  const titleCell = (r) => '<div class="cell-title">' + E(r.title) + CV.ui.anon(r) + '</div><div class="cell-sub">' + E(r.concern_code) + ' · ' + E(r.location) + '</div>';
  const viewUrl = (id) => CV.url('admin/concern.php?id=' + id);

  /* ---------- dashboard ---------- */
  if (CV.$('#sTotal')) {
    const load = async () => {
      const r = await CV.get(API, { action: 'dashboard' });
      const c = r.counts;
      CV.$('#sTotal').textContent = c.total || 0; CV.$('#sUnassigned').textContent = c.unassigned || 0; CV.$('#sProgress').textContent = c.in_progress || 0;
      CV.$('#sOverdue').textContent = c.overdue || 0; CV.$('#sResolved').textContent = c.resolved || 0;
      CV.$('#attCount').textContent = r.attention.length;
      // Needs Your Attention = URGENT only. Every row (and the card) leads to the Notifications page.
      CV.$('#attList').innerHTML = r.attention.length ? r.attention.map((a) =>
        '<a class="list-item" href="' + CV.url('admin/notifications.php') + '"><span class="li-ico">' + ic('alert') + '</span><div class="li-main"><b>' + E(a.concern_code) + ' · ' + E(a.title) + '</b>' +
        '<span>' + CV.badge.st(a.status) + ' ' + (+a.overdue ? '<span class="badge badge-over">Overdue</span> ' : '') + (a.staff_name ? 'Assigned to ' + E(a.staff_name) : 'Unassigned') + ' · due ' + CV.fmtDate(a.due_at, true) + '</span></div>' + ic('chevR') + '</a>').join('')
        : '<div class="empty"><b>No urgent concerns</b>Nothing is waiting on you right now.</div>';
      const max = Math.max(1, ...r.by_status.map((s) => +s.n));
      CV.$('#statusBars').innerHTML = r.by_status.length ? r.by_status.map((s) =>
        '<div class="bar-row"><span>' + E(s.status) + '</span><div class="bar"><i style="width:' + (+s.n / max * 100) + '%"></i></div><b>' + s.n + '</b></div>').join('') : '<div class="empty">No data yet.</div>';
      CV.$('tbody', CV.$('#recentTable')).innerHTML = r.recent.map((x) =>
        '<tr class="clickable" data-href="' + viewUrl(x.concern_id) + '"><td>' + titleCell(x) + '</td><td>' + E(x.category_name) + '</td><td>' + CV.badge.sev(x.severity) + '</td><td>' + CV.badge.st(x.status) + '</td><td class="nw">' + CV.fmtDate(x.created_at) + '</td></tr>').join('');
    };
    load().catch((e) => CV.toast(e.message, 'error'));
    CV.$('#recentTable').addEventListener('click', (e) => { const tr = e.target.closest('tr[data-href]'); if (tr) location.href = tr.dataset.href; });
    CV.$('#attention').addEventListener('click', (e) => { if (!e.target.closest('a')) location.href = CV.url('admin/notifications.php'); });
    setInterval(() => { if (!document.hidden) load().catch(() => {}); }, 60000);
  }

  /* ---------- shared assign modal ---------- */
  const modal = CV.$('#assignModal');
  let afterAssign = () => {};
  async function openAssign(c, reassign) {
    CV.$('#amId').value = c.concern_id;
    CV.$('#amTitle').textContent = reassign ? 'Reassign concern' : 'Assign concern';
    CV.$('#amReasonWrap').hidden = !reassign;
    CV.$('#amReason').required = !!reassign; CV.$('#amReason').value = '';
    const info = CV.$('#amInfo'); if (info) info.textContent = c.concern_code + ' — ' + c.title;
    CV.$('#amStaff').innerHTML = '<option>Loading…</option>';
    CV.modal.open('assignModal');
    try {
      const r = await CV.get(API, { action: 'staff_options', category_id: c.category_id });
      CV.$('#amStaff').innerHTML = '<option value="">Select staff</option>' + r.staff.map((s) =>
        '<option value="' + s.user_id + '"' + (+c.staff_id === +s.user_id ? ' disabled' : '') + '>' + E(s.name) + ' — ' + E(s.department_name || 'No dept') + ' · ' + s.active_load + ' active' + (s.availability === 'busy' ? ' · busy' : '') + (+s.dept_match ? ' ★' : '') + '</option>').join('');
    } catch (e) { CV.toast(e.message, 'error'); }
  }
  if (modal) CV.bindForm(CV.$('#assignForm'), { url: API, action: 'assign', busy: 'Saving…', noRedirect: true,
    validate: (f) => (!f.staff_id.value ? 'Choose a staff member.' : ''),
    success: (res) => { CV.toast(res.message); CV.modal.close(modal); afterAssign(); } });

  /* ---------- concern management ---------- */
  const mgmt = CV.$('#mgmt');
  if (mgmt) {
    let tab = 'queue', timer = null, current = null;
    const heads = {
      queue: '<tr><th>Concern</th><th>Reporter</th><th>Severity</th><th>Status</th><th class="nw">Submitted</th><th class="nw">Action</th></tr>',
      assignments: '<tr><th>Concern</th><th>Assigned to</th><th>Severity</th><th>Status</th><th class="nw">Due</th><th class="nw">Action</th></tr>',
      all: '<tr><th>Concern</th><th>Reporter</th><th>Severity</th><th>Status</th><th>Assigned to</th><th class="nw">Due</th></tr>'
    };
    const rows = {
      queue: (r) => '<tr><td>' + titleCell(r) + '</td><td>' + reporter(r) + '</td><td>' + CV.badge.sev(r.severity) + '</td><td>' + CV.badge.st(r.status) + '</td><td class="nw">' + CV.fmtDate(r.created_at) + '</td>' +
        '<td class="nw"><button class="btn btn-primary btn-sm" data-assign="' + r.concern_id + '">' + ic('users') + ' Assign</button> <a class="btn btn-ghost btn-sm" href="' + viewUrl(r.concern_id) + '">' + ic('eye') + '</a></td></tr>',
      assignments: (r) => '<tr><td>' + titleCell(r) + '</td><td><div class="cell-title">' + E(r.staff_name || '—') + '</div></td><td>' + CV.badge.sev(r.severity) + '</td><td>' + CV.badge.st(r.status) + '</td><td class="nw">' + CV.ui.due(r) + '</td>' +
        '<td class="nw"><button class="btn btn-ghost btn-sm" data-reassign="' + r.concern_id + '">' + ic('refresh') + ' Reassign</button> <a class="btn btn-ghost btn-sm" href="' + viewUrl(r.concern_id) + '" aria-label="View">' + ic('eye') + '</a></td></tr>',
      all: (r) => '<tr class="clickable" data-href="' + viewUrl(r.concern_id) + '"><td>' + titleCell(r) + '</td><td>' + reporter(r) + '</td><td>' + CV.badge.sev(r.severity) + '</td><td>' + CV.badge.st(r.status) + '</td><td>' + E(r.staff_name || '—') + '</td><td class="nw">' + CV.ui.due(r) + '</td></tr>'
    };
    const store = {};
    // table object needs the tab-specific row renderer, so wrap it
    const tbl = CV.table({
      el: CV.$('#paneList'), url: API, cols: 6, per: 10,
      params: () => ({ action: 'list', tab, q: CV.$('#fq').value.trim(), status: CV.$('#fstatus').value, severity: CV.$('#fsev').value }),
      empty: { title: 'Nothing here', text: 'No concerns match this view.' },
      row: (r) => { store[r.concern_id] = r; return rows[tab](r); },
      onData: (j) => { CV.$('#cQueue').textContent = j.tab_counts.queue; CV.$('#cAssign').textContent = j.tab_counts.assignments; }
    });
    // the generic table looks for these inside its host
    const pager = CV.$('#listPager'); CV.$('#paneList').appendChild(pager);

    function setTab(t) {
      tab = t; clearInterval(timer);
      CV.$$('#tabs button').forEach((b) => b.classList.toggle('on', b.dataset.tab === t));
      CV.$('#paneList').classList.toggle('on', t !== 'activity'); CV.$('#paneActivity').classList.toggle('on', t === 'activity');
      if (t === 'activity') { loadWork(); timer = setInterval(() => { if (!document.hidden) loadWork(true); }, 20000); }
      else { CV.$('#listHead').innerHTML = heads[t]; CV.$('#fstatus').parentElement.parentElement.hidden = false; tbl.first(); }
      history.replaceState(null, '', '#' + t);
    }

    async function loadWork(quiet) {
      try {
        const r = await CV.get(API, { action: 'workload' });
        const max = Math.max(1, ...r.rows.map((s) => s.assigned));
        CV.$('tbody', CV.$('#workTable')).innerHTML = r.rows.length ? r.rows.map((s) =>
          '<tr><td><div class="cell-title">' + E(s.name) + '</div><div class="cell-sub">' + E(s.department_name || s.position || '') + '</div></td>' +
          '<td><span class="avail ' + E(s.availability) + '">' + ({ available: 'Available', busy: 'Busy', on_leave: 'On leave' }[s.availability]) + '</span></td>' +
          '<td class="nw"><b>' + s.assigned + '</b>' + (s.overdue ? ' <span class="badge badge-over">' + s.overdue + ' overdue</span>' : '') + '</td><td class="nw">' + s.in_progress + '</td><td class="nw">' + s.pending + '</td><td class="nw">' + s.resolved + '</td>' +
          '<td style="min-width:120px"><div class="load-bar"><i style="width:' + (s.assigned / max * 100) + '%"></i></div></td><td>' + E(s.current_status) + '</td></tr>').join('')
          : '<tr><td colspan="8"><div class="empty"><b>No staff yet</b>Add staff in Users &amp; Staff.</div></td></tr>';
        CV.$('#liveStamp').textContent = 'Live · updated ' + new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', second: '2-digit' });
      } catch (e) { if (!quiet) CV.toast(e.message, 'error'); }
    }

    CV.$$('#tabs button').forEach((b) => b.addEventListener('click', () => setTab(b.dataset.tab)));
    CV.$('#fq').addEventListener('input', CV.debounce(() => tbl.first(), 350));
    CV.$('#fstatus').addEventListener('change', () => tbl.first());
    CV.$('#fsev').addEventListener('change', () => tbl.first());
    mgmt.addEventListener('click', (e) => {
      const a = e.target.closest('[data-assign]'), ra = e.target.closest('[data-reassign]');
      if (a) { afterAssign = () => tbl.reload(true); openAssign(store[a.dataset.assign], false); return; }
      if (ra) { afterAssign = () => tbl.reload(true); openAssign(store[ra.dataset.reassign], true); return; }
      const tr = e.target.closest('tr[data-href]'); if (tr && !e.target.closest('a,button')) location.href = tr.dataset.href;
    });
    const start = location.hash.replace('#', '');
    setTab(['queue', 'assignments', 'all', 'activity'].includes(start) ? start : 'queue');
  }

  /* ---------- concern detail ---------- */
  const detail = CV.$('#detail');
  if (detail) {
    const id = +detail.dataset.id; let data = null;
    async function load() {
      try { data = await CV.get(API, { action: 'detail', id }); render(data); }
      catch (err) { detail.innerHTML = '<div class="card"><div class="empty"><b>Not found</b>' + E(err.message) + '</div></div>'; }
    }
    function render(r) {
      const c = r.concern, a = r.assignment, closed = ['Closed', 'Cancelled'].includes(c.status);
      const anon = +c.is_anonymous === 1;
      const who = '<b>' + E(c.reporter_name) + '</b>' + (anon ? '' : '<br><span class="cell-sub">' + E(c.reporter_school_id || '') + ' · ' + E(c.reporter_email || '') + (c.reporter_contact ? ' · ' + E(c.reporter_contact) : '') + '</span>') + '<br><span class="cell-sub">' + ctx(c) + '</span>' +
        (anon ? '<br><button type="button" class="btn btn-ghost btn-sm" style="margin-top:8px" data-open-modal="revealModal">' + ic('eye') + ' Reveal reporter</button>' : '');
      const actions = closed ? '' : '<button class="btn btn-primary btn-sm" id="doAssign">' + ic('users') + ' ' + (a ? 'Reassign' : 'Assign') + '</button> <button class="btn btn-ghost btn-sm" data-open-modal="statusModal">' + ic('edit') + ' Change status</button> ' +
        '<select class="select" id="sevSel" aria-label="Severity" style="width:auto;padding:.4rem .7rem">' + ['Minor', 'Major', 'Urgent'].map((s) => '<option' + (s === c.severity ? ' selected' : '') + '>' + s + '</option>').join('') + '</select>';
      detail.innerHTML = '<div class="grid-2"><div style="display:flex;flex-direction:column;gap:var(--gap)">' +
        '<div class="card"><div class="card-head"><div><h3>' + E(c.title) + '</h3><div class="cell-sub">' + E(c.concern_code) + '</div></div><div class="toolbar">' + CV.badge.sev(c.severity) + CV.badge.st(c.status) + CV.ui.anon(c) + '</div></div>' +
        '<dl class="kv"><dt>Reporter</dt><dd>' + who + '</dd><dt>Category</dt><dd>' + E(c.category_name) + '</dd><dt>Location</dt><dd>' + E(c.location) + '</dd><dt>Submitted</dt><dd>' + CV.fmtDate(c.created_at, true) + '</dd>' +
        '<dt>Target date</dt><dd>' + CV.ui.due(c) + '</dd><dt>Assigned to</dt><dd>' + (a ? E(a.staff_name) : 'Not assigned') + '</dd><dt>Description</dt><dd>' + E(c.description).replace(/\n/g, '<br>') + '</dd>' +
        (r.feedback ? '<dt>Rating</dt><dd>' + CV.ui.stars(+r.feedback.rating) + (r.feedback.comment ? '<br>' + E(r.feedback.comment) : '') + '</dd>' : '') + '</dl>' +
        CV.ui.photos(r.photos, 'Photo') + (actions ? '<div class="card-head" style="border-top:1px solid var(--line);border-bottom:0"><span class="cell-sub">Actions</span><div class="toolbar">' + actions + '</div></div>' : '') + '</div>' +
        '<div class="card"><div class="card-head"><h3>Assignment history</h3></div>' + (r.history.length ? '<div class="list">' + r.history.map((h) => '<div class="list-item"><div class="li-main"><b>' + E(h.staff_name) + '</b><span>' + CV.fmtDate(h.assigned_at, true) + (h.reason ? ' · ' + E(h.reason) : '') + '</span></div><span class="badge st-' + (h.assignment_status === 'active' ? 'In-Progress' : h.assignment_status === 'completed' ? 'Resolved' : 'Cancelled') + '">' + E(h.assignment_status) + '</span></div>').join('') + '</div>' : '<div class="empty">Not assigned yet.</div>') + '</div></div>' +
        '<div class="card"><div class="card-head"><h3>Progress</h3></div>' + CV.ui.timeline(r.timeline) + '</div></div>';

      const da = CV.$('#doAssign', detail);
      if (da) da.addEventListener('click', () => { afterAssign = load; openAssign(Object.assign({ staff_id: a ? a.staff_id : 0 }, c), !!a); });
      const sv = CV.$('#sevSel', detail);
      if (sv) sv.addEventListener('change', async () => { try { const x = await CV.post(API, { action: 'set_severity', id, severity: sv.value }); CV.toast(x.message); load(); } catch (e) { CV.toast(e.message, 'error'); load(); } });
    }
    CV.$('#revealForm') && (CV.$('#rvId').value = id);
    CV.$('#stId') && (CV.$('#stId').value = id);
    CV.bindForm(CV.$('#statusForm'), { url: API, action: 'set_status', busy: 'Saving…', noRedirect: true, success: (res) => { CV.toast(res.message); CV.modal.close('statusModal'); load(); } });
    CV.bindForm(CV.$('#revealForm'), { url: API, action: 'reveal', busy: 'Revealing…', noRedirect: true, keepDisabled: true,
      success: (res, form) => {
        const r = res.reporter; const box = CV.$('#rvResult'); box.hidden = false;
        box.innerHTML = '<div class="note">' + ic('user') + '<div><b>' + E(r.name) + '</b><br>' + E(r.school_id) + ' · ' + E(r.email) + (r.contact ? ' · ' + E(r.contact) : '') + '</div></div>';
        CV.$('#rvBtn').hidden = true;
      } });
    CV.$('#revealModal') && CV.$('#revealModal').addEventListener('cv:open', () => { CV.$('#rvResult').hidden = true; CV.$('#rvBtn').hidden = false; CV.$('#rvBtn').disabled = false; CV.$('#rvReason').value = ''; });
    load();
  }
})();
