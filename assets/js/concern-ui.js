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

  /* ---------- face-to-face visits (shared by student / staff / admin) ---------- */
  const VS = { scheduled: ['Waiting for the student', 'st-Pending'], confirmed: ['Student will come', 'st-Resolved'], reschedule: ['Wants another time', 'st-Additional-Info-Needed'],
               attended: ['Attended', 'st-Closed'], no_show: ['Did not come', 'st-Reopened'], cancelled: ['Cancelled', 'st-Cancelled'] };
  const vstat = (s) => '<span class="badge ' + VS[s][1] + '">' + VS[s][0] + '</span>';
  const vdate = (at) => new Date(String(at).replace(' ', 'T'));
  const vday = (at) => vdate(at).toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
  const vtime = (at) => vdate(at).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
  const isoToday = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
  const vwhen = (v) => '<div class="vc-when"><span class="vc-cal"><small>' + E(vdate(v.scheduled_at).toLocaleDateString('en-PH', { month: 'short' })) + '</small><b>' + vdate(v.scheduled_at).getDate() + '</b></span>' +
    '<div><b>' + E(vday(v.scheduled_at)) + ' · ' + E(vtime(v.scheduled_at)) + '</b><span>' + ic('pin') + ' ' + E(v.place) + '</span></div></div>';

  /** Small tag for lists: "Visit Fri, Nov 7" or "Wants to meet". */
  CV.ui.visitTag = (r) => {
    if (r.next_visit) return ' <span class="badge badge-visit">' + ic('pin') + ' ' + E(vday(r.next_visit)) + '</span>';
    if (+r.wants_meeting && !['Resolved', 'Closed', 'Cancelled'].includes(r.status)) return ' <span class="badge badge-want">Wants to meet</span>';
    return '';
  };

  /** Staff + admin card: set a date, then record whether the student came. */
  CV.ui.visitStaff = (c, visits, active) => {
    if (['Resolved', 'Closed', 'Cancelled'].includes(c.status)) return '';   // finished: nothing to schedule
    const closed = ['Resolved', 'Closed', 'Cancelled'].includes(c.status);
    const hist = visits.filter((v) => !active || v.visit_id !== active.visit_id).slice(0, 4);
    let h = '<div class="card visit-card" id="visitCard"><div class="card-head"><h3>' + ic('pin') + ' Face-to-face meeting</h3>' + (active ? vstat(active.status) : '') + '</div><div class="card-pad vc-body">';
    if (+c.wants_meeting && !active) h += '<div class="vc-banner">' + ic('message') + '<div><b>The student asked to meet in person.</b>' + (c.meeting_pref ? '<span>Best time: ' + E(c.meeting_pref) + '</span>' : '') + '</div></div>';
    if (active) {
      h += vwhen(active) + (active.note ? '<p class="vc-note">' + E(active.note) + '</p>' : '');
      if (active.status === 'reschedule') h += '<div class="vc-banner warn">' + ic('reply') + '<div><b>The student asked for another time</b><span>' + E(active.student_note || '') + '</span></div></div>';
      h += '<div class="field vc-outnote"><label for="vcNote">Note after the meeting <span class="opt">optional</span></label><input class="input" id="vcNote" maxlength="255" placeholder="e.g. Referred to the counselor"></div>' +
        '<div class="vc-actions">' + (active.status !== 'reschedule' ? '<button type="button" class="btn btn-primary" data-vr="attended">' + ic('checkCircle') + ' Student attended</button><button type="button" class="btn btn-ghost" data-vr="no_show">' + ic('x') + ' Did not come</button>' : '') +
        '<button type="button" class="btn btn-ghost" id="vcResched">' + ic('refresh') + ' ' + (active.status === 'reschedule' ? 'Set a new time' : 'Reschedule') + '</button>' +
        '<button type="button" class="btn btn-ghost" data-vcancel="1">Cancel visit</button></div>';
    } else if (!closed) h += '<p class="vc-hint">Most concerns are settled in person. Set a day and the student is notified right away.' + (+c.is_anonymous ? ' <b>This concern is anonymous:</b> you can still schedule; the student will identify themselves only if they choose to come.' : '') + '</p>';
    if (!closed) {
      h += '<form class="vc-form" id="vcForm"' + (active && active.status !== 'reschedule' ? ' hidden' : '') + ' novalidate><div class="form-msg" role="alert"></div>' +
        '<div class="vc-row"><div class="field"><label for="vcDate">Date</label><input class="input" id="vcDate" name="date" type="date" min="' + isoToday() + '" required></div>' +
        '<div class="field"><label for="vcTime">Time</label><input class="input" id="vcTime" name="time" type="time" value="10:00" required></div></div>' +
        '<div class="field"><label for="vcPlace">Where</label><input class="input" id="vcPlace" name="place" maxlength="120" value="SASO Office" required></div>' +
        '<div class="field"><label for="vcNoteFor">Message for the student <span class="opt">optional</span></label><input class="input" id="vcNoteFor" name="note" maxlength="255" placeholder="e.g. Please bring your school ID"></div>' +
        '<button class="btn btn-primary" type="submit">' + ic('calendar') + ' ' + (active ? 'Save new time' : 'Schedule visit') + '</button></form>';
    }
    if (hist.length) h += '<div class="vc-hist"><span>Earlier meetings</span>' + hist.map((v) => '<div><b>' + E(vday(v.scheduled_at)) + '</b> ' + vstat(v.status) + (v.outcome_note ? '<small>' + E(v.outcome_note) + '</small>' : '') + '</div>').join('') + '</div>';
    return h + '</div></div>';
  };

  /** Wire the staff/admin card. api = 'api/staff.php' or 'api/admin.php'. */
  CV.ui.bindVisitStaff = (root, api, concernId, active, reload) => {
    const form = CV.$('#vcForm', root);
    const resched = CV.$('#vcResched', root);
    if (resched && form) resched.addEventListener('click', () => { form.hidden = !form.hidden; if (!form.hidden) CV.$('#vcDate', root).focus(); });
    if (form) {
      const hid = document.createElement('input'); hid.type = 'hidden'; hid.name = 'id'; hid.value = concernId; form.appendChild(hid);
      CV.bindForm(form, { url: api, action: 'visit_schedule', busy: 'Saving…', noRedirect: true,
        validate: (f) => (!f.date.value ? 'Choose a date.' : !f.time.value ? 'Choose a time.' : ''), success: (res) => { CV.toast(res.message); reload(); } });
    }
    CV.$$('[data-vr]', root).forEach((b) => b.addEventListener('click', async () => {
      const r = b.dataset.vr;
      if (!confirm(r === 'attended' ? 'Record that the student attended this meeting?' : 'Record that the student did not come?')) return;
      b.disabled = true;
      try { const x = await CV.post(api, { action: 'visit_result', id: concernId, visit_id: active.visit_id, result: r, note: (CV.$('#vcNote', root) || {}).value || '' }); CV.toast(x.message); reload(); }
      catch (e) { CV.toast(e.message, 'error'); b.disabled = false; }
    }));
    const cancel = CV.$('[data-vcancel]', root);
    if (cancel) cancel.addEventListener('click', async () => {
      if (!confirm('Cancel this visit? The student will be told.')) return;
      try { const x = await CV.post(api, { action: 'visit_cancel', id: concernId, visit_id: active.visit_id }); CV.toast(x.message); reload(); } catch (e) { CV.toast(e.message, 'error'); }
    });
  };

  /** Student card: see the date, confirm, or ask for another time. */
  CV.ui.visitStudent = (c, visits, active) => {
    if (['Resolved', 'Closed', 'Cancelled'].includes(c.status)) return '';
    const last = visits.find((v) => v.status === 'attended');
    if (!active && !(+c.wants_meeting) && !last) return '';
    let h = '<div class="card visit-card' + (active ? ' is-live' : '') + '" id="visitCard"><div class="card-head"><h3>' + ic('pin') + ' Face-to-face meeting</h3>' + (active ? vstat(active.status) : '') + '</div><div class="card-pad vc-body">';
    if (active) {
      h += vwhen(active) + (active.note ? '<p class="vc-note">' + E(active.note) + '</p>' : '') +
        '<p class="vc-hint">Please bring your school ID. The meeting is private: only the staff handling your concern will be there.</p>';
      if (active.status === 'reschedule') h += '<div class="vc-banner warn">' + ic('reply') + '<div><b>You asked for another time.</b><span>The office will set a new date and tell you here.</span></div></div>';
      else {
        h += '<div class="vc-actions">' + (active.status === 'scheduled' ? '<button type="button" class="btn btn-primary" id="vsConfirm">' + ic('check') + " I'll be there</button>" : '<span class="vc-ok">' + ic('checkCircle') + " You confirmed you'll come</span>") +
          '<button type="button" class="btn btn-ghost" id="vsAnother">' + ic('refresh') + ' I need another time</button></div>' +
          '<form class="vc-form" id="vsForm" hidden novalidate><div class="form-msg" role="alert"></div><div class="field"><label for="vsReason">Why do you need another time?</label><textarea class="textarea" id="vsReason" name="reason" maxlength="255" placeholder="e.g. I have an exam that morning. Afternoons are better." required></textarea></div><button class="btn btn-primary" type="submit">' + ic('send') + ' Send</button></form>';
      }
    } else if (+c.wants_meeting) {
      h += '<div class="vc-banner">' + ic('message') + '<div><b>You asked to meet in person.</b><span>The office will pick a day' + (c.meeting_pref ? ' (you said: ' + E(c.meeting_pref) + ')' : '') + ' and tell you here.</span></div></div>';
    }
    if (last && !active) h += '<p class="vc-hint">' + ic('checkCircle') + ' You met the office on ' + E(vday(last.scheduled_at)) + '.</p>';
    return h + '</div></div>';
  };

  CV.ui.bindVisitStudent = (root, concernId, active, reload) => {
    if (!active) return;
    const confirmBtn = CV.$('#vsConfirm', root), another = CV.$('#vsAnother', root), form = CV.$('#vsForm', root);
    if (confirmBtn) confirmBtn.addEventListener('click', async () => {
      confirmBtn.disabled = true;
      try { const x = await CV.post('api/student.php', { action: 'visit_confirm', id: concernId, visit_id: active.visit_id }); CV.toast(x.message); reload(); } catch (e) { CV.toast(e.message, 'error'); confirmBtn.disabled = false; }
    });
    if (another && form) another.addEventListener('click', () => { form.hidden = !form.hidden; if (!form.hidden) CV.$('#vsReason', root).focus(); });
    if (form) {
      const a = document.createElement('input'); a.type = 'hidden'; a.name = 'id'; a.value = concernId; form.appendChild(a);
      const b = document.createElement('input'); b.type = 'hidden'; b.name = 'visit_id'; b.value = active.visit_id; form.appendChild(b);
      CV.bindForm(form, { url: 'api/student.php', action: 'visit_reschedule', busy: 'Sending…', noRedirect: true,
        validate: (f) => (f.reason.value.trim().length < 5 ? 'Please tell us briefly why.' : ''), success: (res) => { CV.toast(res.message); reload(); } });
    }
  };

  /* ---------- progress step bar: shows where a concern is AND where it ends ---------- */
  CV.ui.stepper = (status) => {
    if (status === 'Cancelled') return '<div class="stp-cancel"><span class="badge st-Cancelled">Cancelled</span> This concern was cancelled. Nothing more to do.</div>';
    const idx = { Submitted: 0, Pending: 0, 'Under Review': 0, Reopened: 0, Assigned: 1, 'In Progress': 2, 'Waiting for Resources': 2, 'Additional Info Needed': 2, Resolved: 3, Closed: 4 }[status];
    if (idx === undefined) return '';
    const steps = ['Received', 'Assigned', 'In progress', 'Resolved', 'Closed'];
    const hint = { Reopened: 'Reopened by the student. It goes back to the owner.', 'Additional Info Needed': 'Waiting for the student to reply.', 'Waiting for Resources': 'Waiting for resources.',
      Resolved: 'Waiting for the student to rate it (closes by itself after 3 days) or reopen it.', Closed: 'Finished. Nothing more to do.' }[status] || '';
    return '<div class="stp-wrap"><ol class="stp" aria-label="Progress">' + steps.map((t, i) => {
      const cls = idx === 4 || i < idx ? 'done' : i === idx ? 'now' : '';
      return '<li class="' + cls + '"><span class="stp-dot">' + (cls === 'done' ? ic('check') : i + 1) + '</span><b>' + t + '</b></li>';
    }).join('') + '</ol>' + (hint ? '<p class="stp-hint">' + hint + '</p>' : '') + '</div>';
  };


  /* ---------- locked concern (staff + admin): no edits until it is reopened ---------- */
  CV.ui.lockedBox = (c) => {
    if (!['Resolved', 'Closed'].includes(c.status)) return '';
    return '<div class="card lock-card" id="lockCard"><div class="card-pad"><div class="lock-row"><span class="lock-ic">' + ic('lock') + '</span><div><b>This concern is ' + (c.status === 'Closed' ? 'closed' : 'resolved') + ' and locked</b>' +
      '<p>Nothing can be changed until you reopen it. Reopening sends it back to work and tells the student.</p></div>' +
      '<button type="button" class="btn btn-ghost btn-sm" id="lockOpen">' + ic('refresh') + ' Reopen</button></div>' +
      '<form class="lock-form" id="lockForm" hidden novalidate><div class="form-msg" role="alert"></div><div class="field"><label for="lockWhy">Why is it being reopened?</label>' +
      '<textarea class="textarea" id="lockWhy" name="reason" maxlength="500" placeholder="e.g. The student came back, the problem is still there."></textarea></div>' +
      '<button class="btn btn-primary" type="submit">' + ic('refresh') + ' Reopen concern</button></form></div></div>';
  };
  CV.ui.bindLocked = (root, api, id, reload) => {
    const open = CV.$('#lockOpen', root), form = CV.$('#lockForm', root);
    if (!open || !form) return;
    open.addEventListener('click', () => { form.hidden = !form.hidden; if (!form.hidden) CV.$('#lockWhy', root).focus(); });
    const hid = document.createElement('input'); hid.type = 'hidden'; hid.name = 'id'; hid.value = id; form.appendChild(hid);
    CV.bindForm(form, { url: api, action: 'reopen', busy: 'Reopening…', noRedirect: true,
      validate: (f) => (f.reason.value.trim().length < 5 ? 'Please tell us why (at least 5 characters).' : ''), success: (res) => { CV.toast(res.message); reload(); } });
  };

})();
