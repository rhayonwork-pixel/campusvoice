/* Admin: audit trail, calendar (FullCalendar showing per-day counts), reports. */
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

  /* ---------- calendar: FullCalendar, but it is fed COUNTS per day (never one bar per concern) ---------- */
  const calEl = CV.$('#calendar');
  if (calEl && window.FullCalendar) {
    const SEV = ['Urgent', 'Major', 'Minor'];
    const COL = { Urgent: '#b42318', Major: '#b25e09', Minor: '#0f7b4f' };
    const ORDER = { Urgent: 1, Major: 2, Minor: 3 };
    const st = { mode: 'due', cat: '', sev: { Urgent: true, Major: true, Minor: true }, asg: false, view: window.innerWidth < 700 ? 'listMo' : 'dayGridMonth', sel: null, page: 1, loaded: [] };
    let selectedEvent = null;
    const nice = (s) => new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' });
    const today = new Date().toISOString().slice(0, 10);
    const ord = (e) => { const p = (e && e.extendedProps) || {}; return p.o != null ? p.o : 9; };

    function build(r) {
      const list = st.view.indexOf('list') === 0, evs = r.events.map((e) => Object.assign(e, { extendedProps: Object.assign({ o: 0 }, e.extendedProps) }));
      let sums = { Urgent: 0, Major: 0, Minor: 0 }, late = 0;
      Object.keys(r.days).forEach((date) => {
        const d = r.days[date];
        SEV.forEach((k) => {
          const n = (d.sev && d.sev[k]) || 0; sums[k] += n; if (!n || !st.sev[k]) return;
          evs.push({ id: 'sum-' + k + date, start: date, allDay: true, color: COL[k], classNames: ['sum'],
            title: n + ' ' + (list ? k.toLowerCase() + ' concern' + (n > 1 ? 's' : '') + (st.mode === 'due' ? ' due' : ' submitted') : k),
            extendedProps: { kind: 'sum', date, o: ORDER[k], n } });
        });
        late += d.late || 0;
        if (st.asg && d.asg) evs.push({ id: 'asg-' + date, start: date, allDay: true, color: '#5440b8', classNames: ['sum'], title: d.asg + (list ? ' assignment' + (d.asg > 1 ? 's' : '') + ' made' : ' assigned'), extendedProps: { kind: 'sum', date, o: 4 } });
        // week + list: the few most urgent concerns of the day, by name
        if (st.sev.Urgent && (st.view === 'dayGridWeek' || list) && d.top) d.top.forEach((t) => evs.push({ id: 'top-' + t.id, start: t.at, allDay: true, color: '#b42318', textColor: '#b42318', classNames: ['top'],
          title: t.code + ' · ' + t.title, url: CV.url('admin/concern.php?id=' + t.id), extendedProps: { kind: 'top', o: 3.5 } }));
      });
      const tot = SEV.reduce((n, k) => n + sums[k], 0);
      CV.$('#calSum').innerHTML = '<span class="cchip"><b>' + tot + '</b> ' + (st.mode === 'due' ? 'due in this view' : 'submitted in this view') + '</span>' +
        SEV.map((k) => '<span class="cchip ' + k + '"><i></i><b>' + sums[k] + '</b> ' + k + '</span>').join('') +
        (st.mode === 'due' && late ? '<span class="cchip bad"><b>' + late + '</b> past target</span>' : '') +
        (r.overdue_now ? '<span class="cchip bad"><b>' + r.overdue_now + '</b> overdue now (all months)</span>' : '');
      return evs;
    }

    const cal = new FullCalendar.Calendar(calEl, {
      initialView: st.view,
      height: 'auto', firstDay: 0, nowIndicator: true, dayMaxEvents: 4, navLinks: false,
      views: { dayGridWeek: { dayMaxEvents: false }, listMonth: { buttonText: 'List' }, listWk: { type: 'list', duration: { weeks: 1 }, buttonText: 'Week' }, listMo: { type: 'list', duration: { months: 1 }, buttonText: 'List' } },
      // phones: week becomes a readable list (7 columns would be unreadably narrow)
      headerToolbar: { left: 'prev,next today', center: 'title', right: window.innerWidth < 700 ? 'dayGridMonth,listWk,listMo' : 'dayGridMonth,dayGridWeek,listMonth' },
      // text arrows instead of FullCalendar's embedded icon font (our CSP blocks data: fonts, which left empty boxes)
      buttonIcons: false,
      buttonText: { prev: '‹', next: '›', today: 'Today', month: 'Month', week: 'Week' },
      buttonHints: { prev: 'Previous $0', next: 'Next $0', today: 'Go to today' },
      eventOrder: (a, b) => ord(a) - ord(b),
      // small month cells: just the coloured number (the legend above says which colour is which)
      eventContent: (arg) => {
        const p = arg.event.extendedProps;
        if (p.kind === 'sum' && p.n != null && arg.view.type === 'dayGridMonth' && window.innerWidth < 700) return { html: '<span class="sum-n">' + p.n + '</span>' };
        return true;                                     // everything else keeps FullCalendar's normal title text
      },
      moreLinkClick: (i) => { openDay(i.date.toISOString().slice(0, 10)); return 'none'; },
      events: (info, ok, fail) => CV.get(S, { action: 'calendar_range', start: info.startStr, end: info.endStr, mode: st.mode, category_id: st.cat, top: st.view === 'dayGridMonth' ? 0 : 1, assign: st.asg ? 1 : 0 })
        .then((r) => ok(build(r))).catch((e) => { CV.toast(e.message, 'error'); fail(e); }),
      eventDidMount: (i) => { i.el.title = i.event.title; },                   // full text on hover when a long title is cut off
      datesSet: (i) => {
        const relabel = () => { [['.fc-listWk-button', 'Week'], ['.fc-listMo-button', 'List'], ['.fc-listMonth-button', 'List']].forEach(([q, t]) => { const b = calEl.querySelector(q); if (b) b.textContent = t; }); };
        relabel(); setTimeout(relabel, 0);                  // phone views are named by duration; give their buttons the right words
        const v = i.view.type; if (v !== st.view) { st.view = v; cal.refetchEvents(); } },
      dateClick: (i) => openDay(i.dateStr.slice(0, 10)),
      eventClick: (i) => {
        const p = i.event.extendedProps;
        if (p.kind === 'sum') { i.jsEvent.preventDefault(); return openDay(p.date); }
        if (i.event.url) return;                              // an urgent concern link opens normally
        if (p.kind === 'event') {
          i.jsEvent.preventDefault(); selectedEvent = p.event_id;
          CV.$('#evvTitle').textContent = i.event.title;
          CV.$('#evvWhen').textContent = i.event.start.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
          CV.$('#evvNotes').textContent = p.notes || ''; CV.modal.open('eventView');
        }
      }
    });
    cal.render();
    const wasNarrow = window.innerWidth < 700;
    window.addEventListener('resize', CV.debounce(() => { if ((window.innerWidth < 700) !== wasNarrow) location.reload(); else cal.updateSize(); }, 250));

    /* ----- filters ----- */
    CV.$('#calCat').addEventListener('change', (e) => { st.cat = e.target.value; cal.refetchEvents(); });
    CV.$$('#calMode button').forEach((b) => b.addEventListener('click', () => {
      st.mode = b.dataset.mode; CV.$$('#calMode button').forEach((x) => x.setAttribute('aria-checked', String(x === b))); cal.refetchEvents();
    }));
    CV.$$('.lgi[data-sev]').forEach((b) => b.addEventListener('click', () => {
      st.sev[b.dataset.sev] = !st.sev[b.dataset.sev]; b.classList.toggle('on', st.sev[b.dataset.sev]); b.setAttribute('aria-pressed', String(st.sev[b.dataset.sev])); cal.refetchEvents();
    }));
    const asgBtn = CV.$('.lgi[data-asg]');
    asgBtn.addEventListener('click', () => { st.asg = !st.asg; asgBtn.classList.toggle('on', st.asg); asgBtn.setAttribute('aria-pressed', String(st.asg)); cal.refetchEvents(); });

    /* ----- a day's concerns (paged, urgent first) ----- */
    async function openDay(key, more) {
      st.sel = key;
      if (!more) { st.page = 1; st.loaded = []; CV.$('#dmList').innerHTML = '<div class="skel" style="height:56px"></div><div class="skel" style="height:56px;margin-top:8px"></div>'; CV.modal.open('dayModal'); }
      else st.page++;
      CV.$('#dmTitle').textContent = nice(key);
      CV.$('#dmKicker').textContent = (key === today ? 'Today · ' : '') + (st.mode === 'due' ? 'Due on this day' : 'Submitted on this day');
      try {
        const sev = SEV.filter((k) => st.sev[k]);
        const r = await CV.get(S, { action: 'calendar_day', date: key, mode: st.mode, category_id: st.cat, severity: sev.join(','), page: st.page, per: 10 });
        if (st.sel !== key) return;
        st.loaded = st.loaded.concat(r.rows);
        CV.$('#dmCounts').innerHTML = SEV.map((k) => '<span class="cchip ' + k + (st.sev[k] ? '' : ' off') + '"><i></i><b>' + r.counts[k] + '</b> ' + k + '</span>').join('');
        CV.$('#dmEvents').innerHTML = r.events.map((e) => '<div class="ev-row"><span class="ev-dot"></span><div><b>' + E(e.title) + '</b><small>' + new Date(e.start_at.replace(' ', 'T')).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) + (e.notes ? ' · ' + E(e.notes) : '') + '</small></div>' +
          '<button type="button" class="icon-btn sm" data-evdel="' + e.event_id + '" aria-label="Delete event">' + CV.ui.ic('trash') + '</button></div>').join('');
        CV.$('#dmList').innerHTML = st.loaded.length ? st.loaded.map((x) =>
          '<a class="cd-item ' + x.severity + '" href="' + CV.url('admin/concern.php?id=' + x.concern_id) + '"><span class="bar"></span><div class="ci-main"><b>' + E(x.title) + '</b>' +
          '<span>' + E(x.concern_code) + ' · ' + E(x.category_name) + (x.staff_name ? ' · ' + E(x.staff_name) : ' · <u>unassigned</u>') + '</span></div>' +
          '<div class="ci-end">' + CV.badge.st(x.status) + (+x.overdue ? '<em class="late">Past target</em>' : '') + '</div></a>').join('')
          : '<div class="empty sm"><b>Nothing here</b>' + (r.events.length ? 'Only events on this day.' : 'No concerns ' + (st.mode === 'due' ? 'are due' : 'were submitted') + ' on this day.') + '</div>';
        const shown = (st.page - 1) * 10 + r.rows.length;
        CV.$('#dmMore').hidden = shown >= r.total;
        CV.$('#dmMoreBtn').textContent = 'Show more (' + (r.total - shown) + ' left)';
      } catch (e) { CV.toast(e.message, 'error'); }
    }
    CV.$('#dmMoreBtn').addEventListener('click', () => openDay(st.sel, true));
    CV.$('#dmAdd').addEventListener('click', () => { CV.modal.close('dayModal'); CV.$('#eventForm').reset(); CV.$('#evStart').value = st.sel + 'T09:00'; CV.modal.open('eventModal'); });
    CV.$('#dmEvents').addEventListener('click', async (e) => {
      const b = e.target.closest('[data-evdel]'); if (!b || !confirm('Delete this event?')) return;
      try { await CV.post(S, { action: 'event_delete', event_id: b.dataset.evdel }); CV.toast('Event removed.'); cal.refetchEvents(); openDay(st.sel); } catch (x) { CV.toast(x.message, 'error'); }
    });

    /* ----- events ----- */
    CV.bindForm(CV.$('#eventForm'), { url: S, action: 'event_save', busy: 'Saving…', noRedirect: true,
      success: () => { CV.toast('Event added.'); CV.modal.close('eventModal'); CV.$('#eventForm').reset(); cal.refetchEvents(); } });
    CV.$('#evvDelete').addEventListener('click', async () => {
      if (!selectedEvent || !confirm('Delete this event?')) return;
      try { await CV.post(S, { action: 'event_delete', event_id: selectedEvent }); CV.modal.close('eventView'); cal.refetchEvents(); CV.toast('Event removed.'); } catch (e) { CV.toast(e.message, 'error'); }
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
