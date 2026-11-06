/* Student panel: dashboard, My Concerns (+ New Concern modal), concern detail. */
(function () {
  'use strict';
  const E = CV.esc, ic = CV.ui.ic;

  /* ---------- "My meetings" calendar on the student dashboard ---------- */
  function initMeetCal() {
    const card = CV.$('#mcCard'); if (!card) return;
    card.hidden = false;
    const grid = CV.$('#mcGrid'), items = CV.$('#mcItems'), head = CV.$('#mcSideHead'), title = CV.$('#mcTitle');
    const pad = (n) => String(n).padStart(2, '0'), iso = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const todayIso = iso(new Date());
    const when = (at) => new Date(String(at).replace(' ', 'T'));
    const lab = { scheduled: ['Waiting for you', 'st-Pending'], confirmed: ['You will come', 'st-Resolved'], reschedule: ['Another time asked', 'st-Additional-Info-Needed'], attended: ['Attended', 'st-Closed'], no_show: ['Missed', 'st-Reopened'] };
    const dot = (v) => (['attended', 'no_show'].includes(v.status) ? 'mc-past' : v.status === 'confirmed' ? 'mc-ok' : 'mc-wait');
    const rel = (d) => { const n = Math.round((new Date(iso(d)) - new Date(todayIso)) / 864e5); return n === 0 ? 'Today' : n === 1 ? 'Tomorrow' : n > 1 && n < 8 ? 'In ' + n + ' days' : ''; };
    let cur = new Date(); cur.setDate(1); cur.setHours(0, 0, 0, 0);
    let sel = null, rows = [];

    function item(v) {
      const d = when(v.scheduled_at), r = rel(d), l = lab[v.status] || ['', ''];
      return '<div class="mc-item"><span class="vc-cal sm"><small>' + E(d.toLocaleDateString('en-PH', { month: 'short' })) + '</small><b>' + d.getDate() + '</b></span>' +
        '<div class="mc-main"><b>' + E(d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })) + ' · ' + E(v.place) + (r ? ' <span class="mc-rel">' + r + '</span>' : '') + '</b>' +
        '<a href="' + CV.url('student/concern.php?id=' + v.concern_id) + '">' + E(v.concern_code) + ' · ' + E(v.title) + '</a>' +
        '<span class="mc-acts"><span class="badge ' + l[1] + '">' + l[0] + '</span>' +
        (v.status === 'scheduled' ? '<button type="button" class="btn btn-primary btn-sm" data-ok="' + v.visit_id + '" data-c="' + v.concern_id + '">' + ic('check') + " I'll be there</button>" : '') +
        (['scheduled', 'confirmed'].includes(v.status) ? '<a class="btn btn-ghost btn-sm" href="' + CV.url('api/student.php?action=visit_ics&visit_id=' + v.visit_id) + '">' + ic('calendar') + ' Add to my phone</a>' : '') +
        '</span></div></div>';
    }

    function draw() {
      title.textContent = cur.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
      const byDay = {};
      rows.forEach((v) => { (byDay[String(v.scheduled_at).slice(0, 10)] = byDay[String(v.scheduled_at).slice(0, 10)] || []).push(v); });
      const days = new Date(cur.getFullYear(), cur.getMonth() + 1, 0).getDate();
      let h = '<span></span>'.repeat(cur.getDay());
      for (let n = 1; n <= days; n++) {
        const k = cur.getFullYear() + '-' + pad(cur.getMonth() + 1) + '-' + pad(n), vs = byDay[k] || [];
        h += '<button type="button" class="mc-day' + (k === todayIso ? ' is-today' : '') + (k === sel ? ' is-sel' : '') + (vs.length ? ' has' : '') + '" data-d="' + k + '"' + (vs.length ? ' aria-label="' + n + ': ' + vs.length + ' meeting' + (vs.length > 1 ? 's' : '') + '"' : '') + '>' + n +
          (vs.length ? '<i class="mc-d ' + dot(vs[0]) + '"></i>' : '') + '</button>';
      }
      grid.innerHTML = h;
      const list = sel ? (byDay[sel] || []) : rows.filter((v) => String(v.scheduled_at).slice(0, 10) >= todayIso && !['attended', 'no_show'].includes(v.status));
      head.textContent = sel ? when(sel + ' 00:00:00').toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' }) : (list.length ? 'Coming up this month' : 'Nothing coming up this month');
      items.innerHTML = list.length ? list.map(item).join('') : '<div class="mc-none">' + (sel ? 'No meeting on this day.' : 'Use the arrows to look at other months.') + '</div>';
    }

    async function load() {
      const last = new Date(cur.getFullYear(), cur.getMonth() + 1, 0);
      try { rows = (await CV.get('api/student.php', { action: 'visits_month', start: iso(cur), end: iso(last) })).rows; } catch (e) { CV.toast(e.message, 'error'); rows = []; }
      draw();
    }

    grid.addEventListener('click', (e) => { const b = e.target.closest('.mc-day'); if (!b) return; sel = sel === b.dataset.d ? null : b.dataset.d; draw(); });
    CV.$('#mcPrev').addEventListener('click', () => { cur.setMonth(cur.getMonth() - 1); sel = null; load(); });
    CV.$('#mcNext').addEventListener('click', () => { cur.setMonth(cur.getMonth() + 1); sel = null; load(); });
    items.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-ok]'); if (!b) return;
      b.disabled = true;
      try { const x = await CV.post('api/student.php', { action: 'visit_confirm', id: b.dataset.c, visit_id: b.dataset.ok }); CV.toast(x.message); load(); }
      catch (er) { CV.toast(er.message, 'error'); b.disabled = false; }
    });
    load();
  }

  /* ---------- dashboard ---------- */
  if (CV.$('#sTotal')) {
    CV.get('api/student.php', { action: 'summary' }).then((r) => {
      CV.$('#sTotal').textContent = r.counts.total || 0;
      CV.$('#sPending').textContent = r.counts.pending || 0;
      CV.$('#sProgress').textContent = r.counts.in_progress || 0;
      CV.$('#sResolved').textContent = r.counts.resolved || 0;
      if (r.visit_total > 0) initMeetCal();
      const vn = CV.$('#visitNote');
      if (vn && r.visits && r.visits.length) {
        const v = r.visits[0], d = new Date(String(v.scheduled_at).replace(' ', 'T'));
        const rl = (x) => { const n = Math.round((new Date(x.toDateString()) - new Date(new Date().toDateString())) / 864e5); return n === 0 ? 'Today' : n === 1 ? 'Tomorrow' : ''; };
        CV.$('#visitText').innerHTML = '<b>' + (v.status === 'confirmed' ? (rl(d) ? "You're confirmed for " + rl(d).toLowerCase() : "You're confirmed for your meeting") : rl(d) ? 'Your meeting is ' + rl(d).toLowerCase() : 'You have a face-to-face meeting') + '</b> · ' + E(d.toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' })) + ' at ' + E(d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })) + ', ' + E(v.place) + '. ' +
          '<a href="' + CV.url('student/concern.php?id=' + v.concern_id) + '"><u>' + (v.status === 'scheduled' ? 'Confirm or ask for another time' : 'See details') + '</u></a>';
        vn.hidden = false;
      }
      const ni = CV.$('#infoNote');
      if (ni && r.needs_info && r.needs_info.length) {
        CV.$('#infoText').innerHTML = '<b>The office needs more information.</b> Reply on ' + r.needs_info.map((a) => '<a href="' + CV.url('student/concern.php?id=' + a.concern_id) + '#reply"><u>' + E(a.concern_code) + '</u></a>').join(', ') + ' so they can continue.';
        ni.hidden = false;
      }
      CV.$('#recent').innerHTML = r.recent.length ? r.recent.map((x) =>
        '<a class="list-item" href="' + CV.url('student/concern.php?id=' + x.concern_id) + '"><span class="li-ico" style="background:var(--info-bg);color:var(--info)">' + ic('activity') + '</span>' +
        '<div class="li-main"><b>' + E(x.update_type) + ' · ' + E(x.title) + '</b><span>' + E(x.concern_code) + ' · ' + CV.ago(x.created_at) + '</span></div>' + ic('chevR') + '</a>').join('')
        : '<div class="empty"><b>No activity yet</b>Your updates will show up here.</div>';
      if (r.awaiting_rating.length) {
        CV.$('#ratingText').innerHTML = '<b>How did we do?</b> Rate ' + r.awaiting_rating.map((a) => '<a href="' + CV.url('student/concern.php?id=' + a.concern_id) + '"><u>' + E(a.concern_code) + '</u></a>').join(', ') + '.';
        CV.$('#ratingNote').hidden = false;
      }
    }).catch((e) => CV.toast(e.message, 'error'));
    CV.get('api/student.php', { action: 'options' }).then((o) => {
      CV.$('#hMinor').textContent = Math.round(o.hours.Minor / 24); CV.$('#hMajor').textContent = Math.round(o.hours.Major / 24); CV.$('#hUrgent').textContent = o.hours.Urgent;
    }).catch(() => {});
  }

  /* ---------- concerns list + modal ---------- */
  const host = CV.$('#concernTable');
  if (host) {
    const tbl = CV.table({
      el: host, url: 'api/student.php', cols: 6, per: 10,
      params: () => ({ action: 'list', q: CV.$('#fq').value.trim(), status: CV.$('#fstatus').value, severity: CV.$('#fsev').value }),
      empty: { title: 'No concerns found', text: 'Use “New Concern” to report one.' },
      row: (r) => '<tr class="clickable" data-href="' + CV.url('student/concern.php?id=' + r.concern_id) + '">' +
        '<td><div class="cell-title">' + E(r.title) + CV.ui.anon(r) + CV.ui.visitTag(r) + '</div><div class="cell-sub">' + E(r.concern_code) + (r.location ? ' · ' + E(r.location) : '') + (r.photo_count > 0 ? ' · ' + r.photo_count + ' photo' + (r.photo_count > 1 ? 's' : '') : '') + '</div></td>' +
        '<td>' + E(r.category_name) + '</td><td>' + CV.badge.sev(r.severity) + '</td><td>' + CV.badge.st(r.status) + '</td>' +
        '<td class="nw">' + CV.fmtDate(r.created_at) + '</td><td class="nw">' + CV.ui.due(r) + '</td></tr>'
    });
    tbl.first();
    host.addEventListener('click', (e) => { const tr = e.target.closest('tr[data-href]'); if (tr) location.href = tr.dataset.href; });
    CV.$('#fq').addEventListener('input', CV.debounce(() => tbl.first(), 350));
    CV.$('#fstatus').addEventListener('change', () => tbl.first());
    CV.$('#fsev').addEventListener('change', () => tbl.first());

    // modal form
    const form = CV.$('#concernForm'), modal = CV.$('#newConcern');
    let loaded = false, cats = {};
    const TYPE_ICONS = { academic: 'file', bullying: 'shield', discipline: 'alert', financial: 'briefcase', scholarship: 'star', organization: 'users', welfare: 'message', facilit: 'building', other: 'sparkle' };
    const typeIcon = (name) => { const n = String(name).toLowerCase(); const k = Object.keys(TYPE_ICONS).find((x) => n.indexOf(x) !== -1); return k ? TYPE_ICONS[k] : 'list'; };
    // location only for types that need it (campus facilities); show what SASO does next
    // sensitive types are usually settled in person, so the "meet in person" box starts ticked (the student can untick it)
    const MEET_TYPES = ['Mental health', 'Guidance and counseling', 'Psychological impact', 'Gender development', 'Student discipline and behavior'];
    const meet = CV.$('#ncMeet'), pref = CV.$('#ncMeetPref');
    const showPref = () => { pref.hidden = !meet.checked; };
    meet.addEventListener('change', () => { meet.dataset.touched = '1'; showPref(); });
    function pickType() {
      const sel = CV.$('input[name=category_id]:checked', form), c = sel ? cats[sel.value] : null;
      if (c && !meet.dataset.touched) { meet.checked = MEET_TYPES.indexOf(c.category_name) !== -1; showPref(); }
      const needLoc = !!(c && +c.needs_location);
      CV.$('#ncLocWrap').hidden = !needLoc; CV.$('#ncLoc').required = needLoc;
      if (!needLoc) CV.$('#ncLoc').value = '';
      CV.$('#ncNext').hidden = !(c && c.next_steps);
      if (c && c.next_steps) CV.$('#ncNextText').textContent = c.next_steps;
    }
    form.addEventListener('change', (e) => { if (e.target.name === 'category_id') pickType(); });
    async function loadOptions() {
      if (loaded) return;
      try {
        const o = await CV.get('api/student.php', { action: 'options' });
        cats = {};
        CV.$('#ncTypes').innerHTML = o.categories.map((c) => { cats[c.category_id] = c;
          return '<label class="type-card"><input type="radio" name="category_id" value="' + c.category_id + '"><span><i>' + ic(typeIcon(c.category_name)) + '</i><b>' + E(c.category_name) + '</b>' +
            (c.description ? '<small>' + E(c.description) + '</small>' : '') + '</span></label>'; }).join('');
        pickType();
        CV.$('#ncLoc').innerHTML = '<option value="">Select a location</option>' + o.locations.map((l) => '<option value="' + l.location_id + '">' + E(l.label) + '</option>').join('');
        CV.$('#hintMinor').textContent = 'Fix within ' + Math.round(o.hours.Minor / 24) + ' days';
        CV.$('#hintMajor').textContent = 'Fix within ' + Math.round(o.hours.Major / 24) + ' days';
        CV.$('#hintUrgent').textContent = 'Fix within ' + o.hours.Urgent + ' hrs';
        CV.$('#ncAnon').checked = !!o.default_anonymous;
        loaded = true;
      } catch (err) { CV.toast(err.message, 'error'); }
    }
    modal.addEventListener('cv:open', loadOptions);
    if (new URLSearchParams(location.search).get('new')) CV.modal.open('newConcern');

    // photo previews + drag & drop
    const input = CV.$('#ncPhotos'), prev = CV.$('#previews'), dz = CV.$('#dz');
    const showPrev = () => {
      prev.innerHTML = '';
      Array.from(input.files).slice(0, 4).forEach((f) => { const im = document.createElement('img'); im.alt = f.name; im.src = URL.createObjectURL(f); prev.appendChild(im); });
    };
    input.addEventListener('change', showPrev);
    ['dragover', 'dragenter'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((ev) => dz.addEventListener(ev, () => dz.classList.remove('over')));
    dz.addEventListener('drop', (e) => { e.preventDefault(); if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; showPrev(); } });

    CV.bindForm(form, {
      url: 'api/student.php', action: 'create', busy: 'Submitting…', noRedirect: true,
      validate: (f) => {
        const t = CV.$('input[name=category_id]:checked', f);
        if (!t) return 'Choose what your concern is about.';
        if (cats[t.value] && +cats[t.value].needs_location && !f.location_id.value) return 'Choose where the problem is.';
        if (f.title.value.trim().length < 5) return 'Please enter a title (at least 5 characters).';
        if (f.description.value.trim().length < 15) return 'Please describe the concern in a bit more detail.';
        if (f['photos[]'] && f['photos[]'].files && f['photos[]'].files.length > 4) return 'You can attach up to 4 photos.';
        return '';
      },
      success: (res) => {
        CV.toast(res.message + (res.routed ? ' It went straight to the right office.' : ''), 'ok'); CV.modal.close(modal);
        form.reset(); delete meet.dataset.touched; showPref(); prev.innerHTML = ''; pickType(); loaded = false; tbl.first(); CV.refreshBell && CV.refreshBell();
      }
    });
  }

  /* ---------- concern detail ---------- */
  const detail = CV.$('#detail');
  if (detail) {
    const id = +detail.dataset.id;
    async function load() {
      try {
        const r = await CV.get('api/student.php', { action: 'detail', id });
        render(r);
      } catch (err) { detail.innerHTML = '<div class="card"><div class="empty"><b>Concern not found</b>' + E(err.message) + '</div></div>'; }
    }
    function render(r) {
      const c = r.concern, done = c.status === 'Resolved' || c.status === 'Closed';
      let actions = '';
      if (c.status === 'Resolved') actions += '<button class="btn btn-primary btn-sm" data-act="confirm">' + ic('checkCircle') + ' Close without rating</button> ';
      if (done) actions += '<button class="btn btn-ghost btn-sm" data-open-modal="reopenModal">' + ic('refresh') + ' Reopen</button>';
      if (['Submitted', 'Pending', 'Assigned'].includes(c.status)) actions += '<button class="btn btn-ghost btn-sm" data-act="cancel">' + ic('x') + ' Cancel concern</button>';
      // the office asked for more information → reply box right on top
      let reply = '';
      if (c.status === 'Additional Info Needed') {
        const ask = r.timeline.slice().reverse().find((t) => t.status === 'Additional Info Needed');
        reply = '<div class="card reply-card" id="reply"><div class="card-head"><h3>' + ic('reply') + ' The office needs more information</h3></div><form class="card-pad" id="replyForm"><div class="form-msg" role="alert"></div>' +
          (ask && ask.response ? '<blockquote class="ask"><span>' + E(ask.actor || 'SASO') + ' asked</span>' + E(ask.response).replace(/\n/g, '<br>') + '</blockquote>' : '') +
          '<div class="field"><label for="rpMsg">Your reply</label><textarea class="textarea" id="rpMsg" name="message" maxlength="2000" required placeholder="Answer the question above."></textarea></div>' +
          '<div class="field"><label for="rpPhotos">Add photos or a screenshot <span class="opt">optional</span></label><input class="input" type="file" id="rpPhotos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></div>' +
          '<input type="hidden" name="id" value="' + c.concern_id + '"><button class="btn btn-primary" type="submit">' + ic('send') + ' Send reply</button></form></div>';
      }

      let fb = '';
      if (done && !r.feedback) {
        fb = '<div class="card' + (c.status === 'Resolved' ? ' card-hot' : '') + '"><div class="card-head"><h3>' + (c.status === 'Resolved' ? 'Resolved. Rate it to close this concern' : 'Rate this resolution') + '</h3></div><form class="card-pad" id="fbForm"><div class="form-msg" role="alert"></div>' +
          '<div class="star-input" role="radiogroup" aria-label="Rating">' + [1, 2, 3, 4, 5].map((n) => '<button type="button" data-n="' + n + '" aria-label="' + n + ' star">' + ic('star') + '</button>').join('') + '</div>' +
          '<input type="hidden" name="id" value="' + c.concern_id + '"><input type="hidden" name="rating" value=""><div class="field" style="margin-top:14px"><label for="fbc">Comment (optional)</label><textarea class="textarea" id="fbc" name="comment" maxlength="500"></textarea></div>' +
          '<button class="btn btn-primary" type="submit">' + ic('send') + (c.status === 'Resolved' ? ' Rate and close' : ' Send feedback') + '</button></form></div>';
      } else if (r.feedback) {
        fb = '<div class="card"><div class="card-head"><h3>Your feedback</h3></div><div class="card-pad">' + CV.ui.stars(+r.feedback.rating) + (r.feedback.comment ? '<p style="margin-top:8px">' + E(r.feedback.comment) + '</p>' : '') + '</div></div>';
      }

      detail.innerHTML = CV.ui.stepper(c.status) + '<div class="grid-2">' +
        '<div style="display:flex;flex-direction:column;gap:var(--gap)">' + (c.status === 'Resolved' ? fb : '') + reply + CV.ui.visitStudent(c, r.visits, r.visit) +
          '<div class="card"><div class="card-head"><div><h3>' + E(c.title) + '</h3><div class="cell-sub">' + E(c.concern_code) + '</div></div><div class="toolbar">' + CV.badge.sev(c.severity) + CV.badge.st(c.status) + CV.ui.anon(c) + '</div></div>' +
          '<dl class="kv"><dt>Type</dt><dd>' + E(c.category_name) + '</dd>' + (c.location ? '<dt>Location</dt><dd>' + E(c.location) + '</dd>' : '') + '<dt>Submitted</dt><dd>' + CV.fmtDate(c.created_at, true) + '</dd>' +
          '<dt>Target date</dt><dd>' + CV.ui.due(c) + '</dd><dt>Assigned to</dt><dd>' + (r.assignee ? E(r.assignee.staff_name) + (r.assignee.position ? ' · ' + E(r.assignee.position) : '') : 'Not assigned yet') + '</dd>' +
          '<dt>Description</dt><dd>' + E(c.description).replace(/\n/g, '<br>') + '</dd>' +
          (c.next_steps && !done ? '<dt>Next steps</dt><dd class="muted">' + E(c.next_steps) + '</dd>' : '') + '</dl>' + CV.ui.photos(r.photos, 'Photo') +
          (actions ? '<div class="card-head" style="border-top:1px solid var(--line);border-bottom:0"><span></span><div class="toolbar">' + actions + '</div></div>' : '') + '</div>' + (c.status === 'Resolved' ? '' : fb) +
        '</div>' +
        '<div class="card"><div class="card-head"><h3>Progress</h3></div>' + CV.ui.timeline(r.timeline) + '</div></div>';

      CV.ui.bindVisitStudent(detail, id, r.visit, load);
      const stars = CV.$$('.star-input button', detail);
      stars.forEach((b) => b.addEventListener('click', () => { const n = +b.dataset.n; CV.$('input[name=rating]', detail).value = n; stars.forEach((s) => s.classList.toggle('on', +s.dataset.n <= n)); }));
      const rp = CV.$('#replyForm', detail);
      if (rp) {
        CV.bindForm(rp, { url: 'api/student.php', action: 'reply', busy: 'Sending…', noRedirect: true,
          validate: (x) => (x.message.value.trim().length < 5 ? 'Please write your reply.' : ''),
          success: (res) => { CV.toast(res.message); load(); } });
        if (location.hash === '#reply') CV.$('#rpMsg', detail).focus();
      }
      const f = CV.$('#fbForm', detail);
      if (f) CV.bindForm(f, { url: 'api/student.php', action: 'feedback', busy: 'Sending…', noRedirect: true,
        validate: (x) => (x.rating.value ? '' : 'Please choose a star rating.'),
        success: (res) => { CV.toast(res.message); load(); } });
    }
    detail.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-act]'); if (!b) return;
      const act = b.dataset.act;
      if (act === 'cancel' && !confirm('Cancel this concern?')) return;
      b.disabled = true;
      try { const r = await CV.post('api/student.php', { action: act, id }); CV.toast(r.message); load(); } catch (err) { CV.toast(err.message, 'error'); b.disabled = false; }
    });
    CV.bindForm(CV.$('#reopenForm'), { url: 'api/student.php', action: 'reopen', busy: 'Reopening…', noRedirect: true,
      success: (res) => { CV.toast(res.message); CV.modal.close('reopenModal'); CV.$('#reason').value = ''; load(); } });
    // reopen needs the concern id on the form
    CV.$('#reopenForm').addEventListener('submit', () => {}, true);
    const rf = CV.$('#reopenForm'); const hid = document.createElement('input'); hid.type = 'hidden'; hid.name = 'id'; hid.value = id; rf.appendChild(hid);
    load();
  }
})();
