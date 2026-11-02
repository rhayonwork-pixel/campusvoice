<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Reports', 'reports.php', ['admin.css']);
page_head('Reports', 'How concerns are being handled.',
    '<a class="btn btn-ghost" id="printBtn" target="_blank" rel="noopener" href="' . e(url('admin/report-print.php')) . '">' . icon('printer') . ' Print report</a>' .
    '<a class="btn btn-primary" id="xlsBtn" href="' . e(url('api/admin_system.php?action=export')) . '">' . icon('download') . ' Export Excel</a>' .
    '<a class="btn btn-ghost" id="csvBtn" href="' . e(url('api/admin_system.php?action=export&format=csv')) . '">' . icon('file') . ' CSV</a>');
?>

<section class="card rep-bar-card">
  <div class="card-head rep-bar">
    <div class="rep-field"><label for="range">Period</label>
      <select class="select" id="range">
        <option value="7">Last 7 days</option><option value="30" selected>Last 30 days</option><option value="90">Last 90 days</option>
        <option value="365">Last year</option><option value="0">All time</option><option value="custom">Custom range…</option>
      </select></div>
    <div class="rep-field" id="fromWrap" hidden><label for="from">From</label><input class="input" type="date" id="from"></div>
    <div class="rep-field" id="toWrap" hidden><label for="to">To</label><input class="input" type="date" id="to"></div>
    <button class="btn btn-primary" type="button" id="applyRange" hidden><?= icon('check') ?> Apply</button>
    <span class="rep-meta" id="periodLabel">Loading…</span>
  </div>
</section>

<section class="stat-grid n5">
  <?= stat_card('Concerns', '–', 'file', 'navy', 'rTotal') ?>
  <?= stat_card('Resolution rate', '–', 'checkCircle', 'ok', 'rRate') ?>
  <?= stat_card('Avg. hours to resolve', '–', 'clock', 'info', 'rHours') ?>
  <?= stat_card('Avg. rating', '–', 'star', 'gold', 'rRating') ?>
  <?= stat_card('Anonymous', '–', 'eyeOff', 'warn', 'rAnon') ?>
</section>

<section class="rep-grid">
  <div class="card"><div class="card-head"><h3>By category</h3></div><div class="bars" id="bCat"></div></div>
  <div class="card"><div class="card-head"><h3>By severity</h3></div><div class="bars" id="bSev"></div></div>
  <div class="card"><div class="card-head"><h3>By status</h3></div><div class="bars" id="bStatus"></div></div>
  <div class="card"><div class="card-head"><h3>Busiest locations</h3></div><div class="bars" id="bLoc"></div></div>
</section>

<section class="card">
  <div class="card-head"><h3>Staff performance</h3><span class="cell-sub">Completed assignments</span></div>
  <div class="table-wrap"><table class="tbl" id="staffRep"><thead><tr><th>Staff</th><th class="nw">Completed</th><th class="nw">Avg. hours to complete</th></tr></thead><tbody></tbody></table></div>
</section>
<?php panel_close(['admin-system.js']); ?>
