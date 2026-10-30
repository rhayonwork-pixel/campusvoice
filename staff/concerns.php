<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('staff');

panel_open($u, 'My Assignments', 'concerns.php');
page_head('My Assignments', 'Concerns assigned to you.');
?>
<section class="card" id="assignTable">
  <div class="tabs" role="tablist" id="tabs">
    <button class="on" data-tab="active"><?= icon('briefcase') ?> Active</button>
    <button data-tab="done"><?= icon('checkCircle') ?> Completed</button>
    <button data-tab="all"><?= icon('list') ?> All</button>
  </div>
  <div class="card-head"><div class="toolbar" style="width:100%">
    <input class="input grow" id="fq" type="search" placeholder="Search by title or code" aria-label="Search">
    <select class="select" id="fsev" aria-label="Severity"><option value="">All severities</option><option>Minor</option><option>Major</option><option>Urgent</option></select>
  </div></div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Concern</th><th>Reporter</th><th>Severity</th><th>Status</th><th class="nw">Due</th></tr></thead><tbody></tbody></table></div>
  <div class="pager"></div>
</section>
<?php panel_close(['staff.js']); ?>
