<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Reports', 'reports.php', ['admin.css']);
page_head('Reports', 'How concerns are being handled.',
    '<select class="select" id="range" aria-label="Period" style="width:auto"><option value="7">Last 7 days</option><option value="30" selected>Last 30 days</option><option value="90">Last 90 days</option><option value="365">Last year</option></select>' .
    '<a class="btn btn-ghost" href="' . e(url('api/admin_system.php?action=export')) . '">' . icon('download') . ' Export CSV</a>');
?>
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
<section class="card"><div class="card-head"><h3>Staff performance</h3><span class="cell-sub">Completed assignments</span></div>
  <div class="table-wrap"><table class="tbl" id="staffRep"><thead><tr><th>Staff</th><th class="nw">Completed</th><th class="nw">Avg. hours to complete</th></tr></thead><tbody></tbody></table></div></section>
<?php panel_close(['admin-system.js']); ?>
