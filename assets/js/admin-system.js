/* Admin: audit trail, calendar (FullCalendar via AJAX), reports. */
(function () {
  'use strict';
  const E = CV.esc, S = 'api/admin_system.php';

  /* ---------- audit trail ---------- */
  const at = CV.$('#auditTable');
  if (at) {
    let typesLoaded = false;
    const tbl = CV.table({
      el: at, url: S, cols: 6, per: 15,
      params: () => ({ action: 'activity', q: CV.$('#fq').value.trim(), user: CV.$('#fuser').value.trim(), type: CV.$('#ftype').value, from: CV.$('#ffrom').value, to: CV.$('#fto').value }),
      empty: { title: 'No activity found', text: 'Try widening the filters.' },
      onData: (j) => { if (typesLoaded) return; typesLoaded = true; CV.$('#ftype').innerHTML += j.types.map((t) => '<option value="' + E(t) + '">' + E(t.replace(/_/g, ' ')) + '</option>').join(''); },
      row: (r) => '<tr><td class="nw">' + CV.fmtDate(r.created_at, true) + '</td><td><div class="cell-title">' + E(r.user_label.replace(/ <.*$/, '')) + '</div><div class="cell-sub">' + E((r.user_label.match(/<(.*)>/) || [])[1] || '') + '</div></td>' +
        '<td><span class="role-tag ' + E(r.role) + '">' + E(r.role) + '</span></td><td><span class="act-tag ' + E(r.action) + '">' + E(r.action.replace(/_/g, ' ')) + '</span></td><td>' + E(r.details || '') + '</td><td class="nw cell-sub">' + E(r.ip_address) + '</td></tr>'
    });
    tbl.first();
    CV.$('#fq').addEventListener('input', CV.debounce(() => tbl.first(), 350));
    CV.$('#fuser').addEventListener('input', CV.debounce(() => tbl.first(), 350));
    ['#ftype', '#ffrom', '#fto'].forEach((s) => CV.$(s).addEventListener('change', () => tbl.first()));
  }

  /* ---------- calendar ---------- */
  const calEl = CV.$('#calendar');
  if (calEl && window.FullCalendar) {
    let selected = null;
    const cal = new FullCalendar.Calendar(calEl, {
      initialView: window.innerWidth < 700 ? 'listMonth' : 'dayGridMonth',
      height: 'auto', firstDay: 0, nowIndicator: true, dayMaxEvents: 3, navLinks: true,
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
      // text arrows instead of FullCalendar's embedded icon font (our CSP blocks data: fonts, which left empty boxes)
      buttonIcons: false,
      buttonText: { prev: '\u2039', next: '\u203A', today: 'Today', month: 'Month', week: 'Week', list: 'List' },
      buttonHints: { prev: 'Previous $0', next: 'Next $0', today: 'Go to today' },
      events: (info, ok, fail) => CV.get(S, { action: 'calendar', start: info.startStr, end: info.endStr }).then((r) => ok(r.events)).catch((e) => { CV.toast(e.message, 'error'); fail(e); }),
      eventClick: (i) => {
        const p = i.event.extendedProps;
        if (i.event.url) return;            // concern links open normally
        if (p.kind === 'event') {
          i.jsEvent.preventDefault(); selected = p.event_id;
          CV.$('#evvTitle').textContent = i.event.title;
          CV.$('#evvWhen').textContent = i.event.start.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
          CV.$('#evvNotes').textContent = p.notes || ''; CV.modal.open('eventView');
        }
      }
    });
    cal.render();
    window.addEventListener('resize', CV.debounce(() => cal.updateSize(), 200));
    CV.bindForm(CV.$('#eventForm'), { url: S, action: 'event_save', busy: 'Saving…', noRedirect: true,
      success: () => { CV.toast('Event added.'); CV.modal.close('eventModal'); CV.$('#eventForm').reset(); cal.refetchEvents(); } });
    CV.$('#evvDelete').addEventListener('click', async () => {
      if (!selected || !confirm('Delete this event?')) return;
      try { await CV.post(S, { action: 'event_delete', event_id: selected }); CV.modal.close('eventView'); cal.refetchEvents(); CV.toast('Event removed.'); } catch (e) { CV.toast(e.message, 'error'); }
    });
  } else if (calEl) { calEl.innerHTML = '<div class="empty"><b>Calendar could not load</b>Check your internet connection and refresh.</div>'; }

  /* ---------- reports ---------- */
  if (CV.$('#rTotal')) {
    const sel = CV.$('#range'), from = CV.$('#from'), to = CV.$('#to'), apply = CV.$('#applyRange');
    const iso = (d) => d.toISOString().slice(0, 10);
    to.value = iso(new Date()); from.value = iso(new Date(Date.now() - 29 * 864e5)); to.max = to.value;
    const params = () => (sel.value === 'custom' ? { from: from.value, to: to.value } : { days: sel.value });
    const bars = (el, rows) => {
      const max = Math.max(1, ...rows.map((r) => +r.n));
      el.innerHTML = rows.length ? rows.map((r) => '<div class="bar-row"><span title="' + E(r.label) + '">' + E(r.label) + '</span><div class="bar"><i style="width:' + (+r.n / max * 100) + '%"></i></div><b>' + r.n + '</b></div>').join('') : '<div class="empty">No data for this period.</div>';
    };
    async function load() {
      try {
        const r = await CV.get(S, Object.assign({ action: 'reports' }, params())), s = r.summary, total = +s.total || 0;
        CV.$('#periodLabel').textContent = r.label;
        CV.$('#printBtn').href = CV.url('admin/report-print.php?' + new URLSearchParams(params()).toString());
        CV.$('#xlsBtn').href = CV.url('api/admin_system.php?' + new URLSearchParams(Object.assign({ action: 'export' }, params())).toString());
        CV.$('#csvBtn').href = CV.url('api/admin_system.php?' + new URLSearchParams(Object.assign({ action: 'export', format: 'csv' }, params())).toString());
        CV.$('#rTotal').textContent = total; CV.$('#rRate').textContent = total ? Math.round((+s.resolved || 0) / total * 100) + '%' : '—';
        CV.$('#rHours').textContent = s.avg_hours != null ? s.avg_hours : '—'; CV.$('#rRating').textContent = r.rating.avg_rating != null ? r.rating.avg_rating + ' / 5' : '—'; CV.$('#rAnon').textContent = +s.anonymous || 0;
        bars(CV.$('#bCat'), r.by_category); bars(CV.$('#bSev'), r.by_severity); bars(CV.$('#bStatus'), r.by_status); bars(CV.$('#bLoc'), r.by_location);
        CV.$('tbody', CV.$('#staffRep')).innerHTML = r.staff.length ? r.staff.map((x) => '<tr><td class="cell-title">' + E(x.label) + '</td><td class="nw">' + x.n + '</td><td class="nw">' + (x.avg_hours != null ? x.avg_hours : '—') + '</td></tr>').join('') : '<tr><td colspan="3"><div class="empty">No completed assignments in this period.</div></td></tr>';
      } catch (e) { CV.toast(e.message, 'error'); }
    }
    sel.addEventListener('change', () => {
      const custom = sel.value === 'custom';
      CV.$('#fromWrap').hidden = CV.$('#toWrap').hidden = apply.hidden = !custom;
      if (!custom) load();
    });
    apply.addEventListener('click', () => {
      if (!from.value || !to.value) return CV.toast('Choose both dates.', 'error');
      if (from.value > to.value) return CV.toast('The start date must be before the end date.', 'error');
      load();
    });
    load();
  }
})();
