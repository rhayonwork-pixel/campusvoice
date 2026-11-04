/* Admin: audit trail, calendar (counts per day + day panel), reports. */
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

  /* ---------- calendar: counts per day (never one chip per concern) + a paged day panel ---------- */
  const calGrid = CV.$('#calGrid');
  if (calGrid) {
    const SEV = ['Urgent', 'Major', 'Minor'];
    const st = { month: new Date(), sel: null, mode: 'due', cat: '', sev: { Urgent: true, Major: true, Minor: true }, days: {}, page: 1, loaded: [] };
    st.month.setDate(1);
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const ym = (d) => iso(d).slice(0, 7);
    const today = iso(new Date());
    const nice = (s) => new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' });
    const dayTotal = (d) => d ? SEV.reduce((n, k) => n + (st.sev[k] ? (d.sev && d.sev[k]) || 0 : 0), 0) : 0;
    let reqId = 0;

    async function loadMonth() {
      const my = ++reqId;
      calGrid.setAttribute('aria-busy', 'true');
      try {
        const r = await CV.get(S, { action: 'calendar_month', month: ym(st.month), mode: st.mode, category_id: st.cat });
        if (my !== reqId) return;
        st.days = r.days; st.overdueNow = r.overdue_now;
        drawGrid();
      } catch (e) { CV.toast(e.message, 'error'); }
      calGrid.removeAttribute('aria-busy');
    }

    function drawGrid() {
      CV.$('#calTitle').textContent = st.month.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
      const first = new Date(st.month), startOffset = first.getDay();
      const daysIn = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
      const cells = Math.ceil((startOffset + daysIn) / 7) * 7;
      const totals = Object.keys(st.days).map((k) => dayTotal(st.days[k]));
      const max = Math.max(1, ...totals);
      let html = '', sumAll = 0, sumSev = { Urgent: 0, Major: 0, Minor: 0 }, late = 0;
      for (let i = 0; i < cells; i++) {
        const n = i - startOffset + 1;
        if (n < 1 || n > daysIn) { html += '<div class="cd out" aria-hidden="true"></div>'; continue; }
        const d = new Date(first.getFullYear(), first.getMonth(), n), key = iso(d), info = st.days[key] || {};
        const tot = dayTotal(info), level = tot ? Math.min(4, Math.ceil(tot / max * 4)) : 0;
        SEV.forEach((k) => { sumSev[k] += (info.sev && info.sev[k]) || 0; });
        sumAll += (info.sev ? SEV.reduce((a, k) => a + (info.sev[k] || 0), 0) : 0); late += info.late || 0;
        const chips = SEV.filter((k) => st.sev[k] && info.sev && info.sev[k]).map((k) => '<span class="chip-s ' + k + '"><i></i><b>' + info.sev[k] + '</b><em>' + k + '</em></span>').join('');
        const label = nice(key) + (tot ? ': ' + SEV.filter((k) => info.sev && info.sev[k]).map((k) => info.sev[k] + ' ' + k).join(', ') : ': nothing') + (info.ev ? ', ' + info.ev + ' event' : '');
        html += '<button type="button" class="cd day' + (key === today ? ' today' : '') + (key === st.sel ? ' sel' : '') + (tot || info.ev ? ' has' : '') + '" data-d="' + key + '" data-l="' + level + '" role="gridcell" aria-label="' + E(label) + '">' +
          '<span class="dn">' + n + '</span>' + ((info.ev) ? '<span class="ev-dot" title="' + info.ev + ' event"></span>' : '') + ((info.late && st.mode === 'due') ? '<span class="late-dot" title="Past target"></span>' : '') +
          '<span class="chips">' + chips + '</span></button>';
      }
      calGrid.innerHTML = html;
      const lateTxt = st.mode === 'due' && late ? '<span class="sm bad"><b>' + late + '</b> past target</span>' : '';
      CV.$('#calSum').innerHTML = '<span class="sm"><b>' + sumAll + '</b> ' + (st.mode === 'due' ? 'due this month' : 'submitted this month') + '</span>' +
        SEV.map((k) => '<span class="sm ' + k + '"><i></i><b>' + sumSev[k] + '</b> ' + k + '</span>').join('') + lateTxt +
        (st.overdueNow ? '<span class="sm bad"><b>' + st.overdueNow + '</b> overdue now (all months)</span>' : '');
    }

    /* ----- day panel ----- */
    async function openDay(key, more) {
      st.sel = key;
      CV.$$('.cd.day', calGrid).forEach((b) => b.classList.toggle('sel', b.dataset.d === key));
      if (!more) { st.page = 1; st.loaded = []; CV.$('#cdList').innerHTML = '<div class="skel" style="height:56px"></div><div class="skel" style="height:56px;margin-top:8px"></div>'; }
      else st.page++;
      CV.$('#cdTitle').textContent = nice(key);
      CV.$('#cdKicker').textContent = (key === today ? 'Today · ' : '') + (st.mode === 'due' ? 'Due on this day' : 'Submitted on this day');
      try {
        const sev = SEV.filter((k) => st.sev[k]);
        const r = await CV.get(S, { action: 'calendar_day', date: key, mode: st.mode, category_id: st.cat, severity: sev.join(','), page: st.page, per: 10 });
        if (st.sel !== key) return;
        st.loaded = st.loaded.concat(r.rows);
        CV.$('#cdCounts').innerHTML = SEV.map((k) => '<span class="sm ' + k + (st.sev[k] ? '' : ' off') + '"><i></i><b>' + r.counts[k] + '</b> ' + k + '</span>').join('');
        CV.$('#cdEvents').innerHTML = r.events.map((e) => '<div class="ev-row"><span class="ev-dot"></span><div><b>' + E(e.title) + '</b><small>' + new Date(e.start_at.replace(' ', 'T')).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) + (e.notes ? ' · ' + E(e.notes) : '') + '</small></div>' +
          '<button type="button" class="icon-btn sm" data-evdel="' + e.event_id + '" aria-label="Delete event">' + CV.ui.ic('trash') + '</button></div>').join('');
        CV.$('#cdList').innerHTML = st.loaded.length ? st.loaded.map((x) =>
          '<a class="cd-item ' + x.severity + '" href="' + CV.url('admin/concern.php?id=' + x.concern_id) + '"><span class="bar"></span><div class="ci-main"><b>' + E(x.title) + '</b>' +
          '<span>' + E(x.concern_code) + ' · ' + E(x.category_name) + (x.staff_name ? ' · ' + E(x.staff_name) : ' · <u>unassigned</u>') + '</span></div>' +
          '<div class="ci-end">' + CV.badge.st(x.status) + (+x.overdue ? '<em class="late">Past target</em>' : '') + '</div></a>').join('')
          : '<div class="empty sm"><b>Nothing here</b>' + (r.events.length ? 'Only events on this day.' : 'No concerns ' + (st.mode === 'due' ? 'are due' : 'were submitted') + ' on this day.') + '</div>';
        const shown = (st.page - 1) * 10 + r.rows.length;
        CV.$('#cdMore').hidden = shown >= r.total;
        CV.$('#cdMoreBtn').textContent = 'Show more (' + (r.total - shown) + ' left)';
        CV.$('#cdTitle').dataset.date = key;
      } catch (e) { CV.toast(e.message, 'error'); }
    }

    calGrid.addEventListener('click', (e) => {
      const b = e.target.closest('.cd.day'); if (!b) return;
      openDay(b.dataset.d);
      if (window.innerWidth < 1100) CV.$('#calDay').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    CV.$('#cdMoreBtn').addEventListener('click', () => openDay(st.sel, true));
    const go = (delta) => { st.month = new Date(st.month.getFullYear(), st.month.getMonth() + delta, 1); loadMonth(); };
    CV.$('#calPrev').addEventListener('click', () => go(-1));
    CV.$('#calNext').addEventListener('click', () => go(1));
    CV.$('#calToday').addEventListener('click', () => { st.month = new Date(); st.month.setDate(1); loadMonth().then(() => openDay(today)); });
    CV.$('#calCat').addEventListener('change', (e) => { st.cat = e.target.value; loadMonth(); if (st.sel) openDay(st.sel); });
    CV.$$('#calMode button').forEach((b) => b.addEventListener('click', () => {
      st.mode = b.dataset.mode; CV.$$('#calMode button').forEach((x) => x.setAttribute('aria-checked', String(x === b))); loadMonth(); if (st.sel) openDay(st.sel);
    }));
    CV.$$('.cal-legend2 .lg').forEach((b) => b.addEventListener('click', () => {
      st.sev[b.dataset.sev] = !st.sev[b.dataset.sev]; b.classList.toggle('on', st.sev[b.dataset.sev]); b.setAttribute('aria-pressed', String(st.sev[b.dataset.sev]));
      drawGrid(); if (st.sel) openDay(st.sel);
    }));
    // keyboard: arrows move between days
    calGrid.addEventListener('keydown', (e) => {
      const keys = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }; if (!(e.key in keys)) return;
      const all = CV.$$('.cd.day', calGrid), i = all.indexOf(document.activeElement); if (i < 0) return;
      const t = all[i + keys[e.key]]; if (t) { e.preventDefault(); t.focus(); }
    });

    /* events */
    const openEvent = (date) => {
      CV.$('#eventForm').reset();
      const d = date || st.sel || today; CV.$('#evStart').value = d + 'T09:00';
      CV.modal.open('eventModal');
    };
    CV.$('#addEventBtn').addEventListener('click', () => openEvent());
    CV.$('#cdAdd').addEventListener('click', () => openEvent(st.sel));
    CV.bindForm(CV.$('#eventForm'), { url: S, action: 'event_save', busy: 'Saving…', noRedirect: true,
      success: () => { CV.toast('Event added.'); CV.modal.close('eventModal'); loadMonth(); if (st.sel) openDay(st.sel); } });
    CV.$('#cdEvents').addEventListener('click', async (e) => {
      const b = e.target.closest('[data-evdel]'); if (!b || !confirm('Delete this event?')) return;
      try { await CV.post(S, { action: 'event_delete', event_id: b.dataset.evdel }); CV.toast('Event removed.'); loadMonth(); openDay(st.sel); } catch (x) { CV.toast(x.message, 'error'); }
    });

    loadMonth().then(() => openDay(today));
  }

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
