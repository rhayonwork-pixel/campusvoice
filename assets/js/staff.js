/* Staff panel: dashboard (today's work + quick actions), assignments list, concern detail + update. */
(function () {
  'use strict';
  const E = CV.esc, ic = CV.ui.ic;
  const reporter = (r) => '<div class="cell-title">' + E(r.reporter_name) + '</div><div class="cell-sub">' +
    E([r.reporter_course, r.reporter_year ? 'Year ' + r.reporter_year : '', r.reporter_section].filter(Boolean).join(' · ')) + '</div>';
  const newBadge = (r) => (+r.is_new === 1 ? ' <span class="badge badge-new">New</span>' : '');
  const titleCell = (r) => '<div class="cell-title">' + E(r.title) + newBadge(r) + CV.ui.anon(r) + CV.ui.visitTag(r) + '</div><div class="cell-sub">' + E(r.concern_code) + ' · ' + E(r.category_name) + (r.location ? ' · ' + E(r.location) : '') + '</div>';
  const viewUrl = (r) => CV.url('staff/concern.php?id=' + r.concern_id);

  /* ---------- dashboard ---------- */
  const today = CV.$('#todayTable');
  if (today) {
    const greet = CV.$('#greetClose');
    if (greet) { greet.addEventListener('click', () => CV.$('#greet').remove()); setTimeout(() => { const g = CV.$('#greet'); if (g) g.remove(); }, 9000); }

    async function loadSummary() {
      const r = await CV.get('api/staff.php', { action: 'summary' });
      CV.$('#sActive').textContent = r.counts.active_total || 0; CV.$('#sProgress').textContent = r.counts.in_progress || 0;
      CV.$('#sWaiting').textContent = r.counts.waiting || 0; CV.$('#sOverdue').textContent = r.counts.overdue || 0; CV.$('#sDone').textContent = r.counts.resolved_week || 0;
    }
    async function loadToday() {
      const r = await CV.get('api/staff.php', { action: 'today' });
      CV.$('#todayCount').textContent = r.rows.length;
      CV.$('tbody', today).innerHTML = r.rows.length ? r.rows.map((x) => {
        const canStart = ['Assigned', 'Reopened', 'Submitted', 'Pending', 'Under Review'].includes(x.status);
        return '<tr class="' + (+x.is_new ? 'is-new' : '') + '"><td>' + titleCell(x) + '</td><td>' + CV.badge.sev(x.severity) + '</td><td>' + CV.badge.st(x.status) + '</td><td class="nw">' + CV.ui.due(x) + '</td>' +
          '<td class="nw"><a class="btn btn-ghost btn-sm" href="' + viewUrl(x) + '">' + ic('eye') + ' View</a> ' +
          (canStart ? '<button class="btn btn-primary btn-sm" data-start="' + x.concern_id + '">' + ic('play') + ' Start</button>' : '') + '</td></tr>';
      }).join('') : '<tr><td colspan="5"><div class="empty"><b>Nothing due today</b>You are all caught up. Check All assignments for the rest.</div></td></tr>';
    }
    async function loadVisits() {
      const box = CV.$('#visitList'); if (!box) return;
      const r = await CV.get('api/staff.php', { action: 'visits' });
      CV.$('#visitCount').textContent = r.rows.length;
      const day = (at) => new Date(String(at).replace(' ', 'T'));
      const lab = { scheduled: 'Waiting for the student', confirmed: 'Student will come', reschedule: 'Wants another time' };
      box.innerHTML = r.rows.length ? r.rows.map((v) => {
        const d = day(v.scheduled_at), today = d.toDateString() === new Date().toDateString();
        return '<a class="list-item" href="' + CV.url('staff/concern.php?id=' + v.concern_id) + '"><span class="vc-cal sm"><small>' + E(d.toLocaleDateString('en-PH', { month: 'short' })) + '</small><b>' + d.getDate() + '</b></span>' +
          '<div class="li-main"><b>' + E(v.title) + (+v.is_anonymous ? ' <span class="badge badge-anon">Anonymous</span>' : '') + '</b><span>' + (today ? 'Today' : E(d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }))) + ' · ' + E(d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })) + ' · ' + E(v.place) + ' · ' + E(v.concern_code) + '</span></div>' +
          (+v.needs_result ? '<span class="badge badge-over">Record result</span>' : '<span class="badge ' + (v.status === 'confirmed' ? 'st-Resolved' : v.status === 'reschedule' ? 'st-Additional-Info-Needed' : 'st-Pending') + '">' + lab[v.status] + '</span>') + '</a>';
      }).join('') : '<div class="empty sm"><b>No meetings planned</b>When you schedule a face-to-face visit it appears here.</div>';
    }
    const refresh = () => Promise.all([loadSummary(), loadToday(), loadVisits()]).catch((e) => CV.toast(e.message, 'error'));
    today.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-start]'); if (!b) return;
      b.disabled = true;
      try { const r = await CV.post('api/staff.php', { action: 'start', id: b.dataset.start }); CV.toast(r.message); refresh(); }
      catch (err) { CV.toast(err.message, 'error'); b.disabled = false; }
    });
    refresh(); setInterval(() => { if (!document.hidden) refresh(); }, 45000);
  }

  /* ---------- assignments list ---------- */
  const host = CV.$('#assignTable');
  if (host) {
    let tab = 'active';
    const tbl = CV.table({
      el: host, url: 'api/staff.php', cols: 5, per: 10,
      params: () => ({ action: 'list', tab, q: CV.$('#fq').value.trim(), severity: CV.$('#fsev').value }),
      empty: { title: 'No assignments here', text: 'New assignments will appear automatically.' },
      row: (r) => '<tr class="clickable ' + (+r.is_new ? 'is-new' : '') + '" data-href="' + viewUrl(r) + '"><td>' + titleCell(r) + '</td><td>' + reporter(r) + '</td><td>' + CV.badge.sev(r.severity) + '</td><td>' + CV.badge.st(r.status) + '</td><td class="nw">' + CV.ui.due(r) + '</td></tr>'
    });
    tbl.first();
    host.addEventListener('click', (e) => { const tr = e.target.closest('tr[data-href]'); if (tr) location.href = tr.dataset.href; });
    CV.$$('#tabs button').forEach((b) => b.addEventListener('click', () => { CV.$$('#tabs button').forEach((x) => x.classList.toggle('on', x === b)); tab = b.dataset.tab; tbl.first(); }));
    CV.$('#fq').addEventListener('input', CV.debounce(() => tbl.first(), 350));
    CV.$('#fsev').addEventListener('change', () => tbl.first());
  }

  /* ---------- detail ---------- */
  const detail = CV.$('#detail');
  if (detail) {
    const id = +detail.dataset.id;
    async function load() {
      try { render(await CV.get('api/staff.php', { action: 'detail', id })); }
      catch (err) { detail.innerHTML = '<div class="card"><div class="empty"><b>Not available</b>' + E(err.message) + '</div></div>'; }
    }
    function render(r) {
      const c = r.concern, active = r.assignment_status === 'active';
      const who = '<b>' + E(c.reporter_name) + '</b>' + (+c.is_anonymous ? '' : '<br><span class="cell-sub">' + E(c.reporter_school_id || '') + ' · ' + E(c.reporter_email || '') + (c.reporter_contact ? ' · ' + E(c.reporter_contact) : '') + '</span>') +
        '<br><span class="cell-sub">' + E([c.reporter_course, c.reporter_year ? 'Year ' + c.reporter_year : '', c.reporter_section].filter(Boolean).join(' · ')) + '</span>';
      const form = active ? '<div class="card"><div class="card-head"><h3>Update this concern</h3></div><form class="card-pad" id="updForm" enctype="multipart/form-data"><div class="form-msg" role="alert"></div>' +
        '<div class="field"><label for="st">New status</label><select class="select" id="st" name="status"><option>In Progress</option><option value="Additional Info Needed">Additional info needed (ask the student)</option><option>Waiting for Resources</option><option>Resolved</option></select></div>' +
        '<div class="field"><label for="note" id="noteLabel">Note <span class="hint">(required for Resolved / Waiting)</span></label><textarea class="textarea" id="note" name="note" maxlength="1000" placeholder="What was done, or what is needed?"></textarea></div>' +
        '<div class="field"><span class="label">Proof photo <span class="hint">(optional)</span></span><label class="dropzone">' + ic('camera') + '<span id="proofName">Click to add a photo</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp"></label></div>' +
        '<button class="btn btn-primary" type="submit">' + ic('check') + ' Save update</button></form></div>' : '<div class="note">' + ic('checkCircle') + '<div>This concern is no longer active for you.</div></div>';

      detail.innerHTML = '<div class="grid-2"><div style="display:flex;flex-direction:column;gap:var(--gap)">' +
        '<div class="card"><div class="card-head"><div><h3>' + E(c.title) + '</h3><div class="cell-sub">' + E(c.concern_code) + '</div></div><div class="toolbar">' + CV.badge.sev(c.severity) + CV.badge.st(c.status) + CV.ui.anon(c) + '</div></div>' +
        '<dl class="kv"><dt>Reporter</dt><dd>' + who + '</dd><dt>Type</dt><dd>' + E(c.category_name) + '</dd>' + (c.location ? '<dt>Location</dt><dd>' + E(c.location) + '</dd>' : '') + '<dt>Submitted</dt><dd>' + CV.fmtDate(c.created_at, true) + '</dd>' +
        '<dt>Target date</dt><dd>' + CV.ui.due(c) + '</dd><dt>Description</dt><dd>' + E(c.description).replace(/\n/g, '<br>') + '</dd></dl>' + CV.ui.photos(r.photos, 'Photo') + '</div>' + CV.ui.visitStaff(c, r.visits, r.visit) + form + '</div>' +
        '<div class="card"><div class="card-head"><h3>Progress</h3></div>' + CV.ui.timeline(r.timeline) + '</div></div>';

      CV.ui.bindVisitStaff(detail, 'api/staff.php', id, r.visit, load);
      const f = CV.$('#updForm', detail);
      if (f) {
        // asking the student: the note becomes the question they will see
        f.status.addEventListener('change', () => {
          const ask = f.status.value === 'Additional Info Needed';
          CV.$('#noteLabel').innerHTML = ask ? 'Question for the student <span class="hint">(they will see this)</span>' : 'Note <span class="hint">(required for Resolved / Waiting)</span>';
          f.note.placeholder = ask ? 'e.g. Please send a screenshot of the message and the date it happened.' : 'What was done, or what is needed?';
        });
        f.proof.addEventListener('change', () => { CV.$('#proofName').textContent = f.proof.files[0] ? f.proof.files[0].name : 'Click to add a photo'; });
        const fd = document.createElement('input'); fd.type = 'hidden'; fd.name = 'id'; fd.value = id; f.appendChild(fd);
        CV.bindForm(f, { url: 'api/staff.php', action: 'update', busy: 'Saving…', noRedirect: true,
          validate: (x) => (x.status.value === 'Additional Info Needed' && x.note.value.trim().length < 10 ? 'Write the question for the student (at least 10 characters).' :
            ['Resolved', 'Waiting for Resources'].includes(x.status.value) && x.note.value.trim().length < 5 ? 'Please add a short note.' : ''),
          success: (res) => { CV.toast(res.message); load(); } });
      }
    }
    load();
  }
})();
