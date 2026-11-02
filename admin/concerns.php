<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Concern Management', 'concerns.php', ['admin.css']);
page_head('Concern Management', 'Triage new concerns, manage assignments and watch staff workload.');
?>
<section class="card" id="mgmt">
  <div class="tabs" role="tablist" id="tabs">
    <button class="on" data-tab="queue"><?= icon('inbox') ?> Assignment Queue <span class="tab-count" id="cQueue">0</span></button>
    <button data-tab="assignments"><?= icon('users') ?> Current Assignments <span class="tab-count" id="cAssign">0</span></button>
    <button data-tab="all"><?= icon('list') ?> All Concerns</button>
    <button data-tab="activity"><?= icon('activity') ?> Staff Activity</button>
  </div>

  <div class="tab-pane on" id="paneList">
    <div class="card-head"><div class="toolbar" style="width:100%">
      <input class="input grow" id="fq" type="search" placeholder="Search by title, code or category" aria-label="Search">
      <select class="select" id="fstatus" aria-label="Status"><option value="">All statuses</option>
        <?php foreach (['Pending','Under Review','Additional Info Needed','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled'] as $s) echo '<option>' . e($s) . '</option>'; ?></select>
      <select class="select" id="fcat" aria-label="Concern type"><option value="">All types</option>
        <?php foreach (q('SELECT category_id, category_name FROM categories ORDER BY status = "inactive", category_name')->fetchAll() as $c) echo '<option value="' . (int)$c['category_id'] . '">' . e($c['category_name']) . '</option>'; ?></select>
      <select class="select" id="fsev" aria-label="Severity"><option value="">All severities</option><option>Minor</option><option>Major</option><option>Urgent</option></select>
    </div></div>
    <div class="table-wrap" id="listTable"><table class="tbl"><thead id="listHead"></thead><tbody></tbody></table></div>
    <div class="pager" id="listPager"></div>
  </div>

  <div class="tab-pane" id="paneActivity">
    <div class="card-head"><div><h3 style="font-size:1rem">Staff workload</h3><span class="cell-sub">Current activity of every staff member</span></div><span class="live" id="liveStamp">Live</span></div>
    <div class="table-wrap"><table class="tbl" id="workTable"><thead><tr><th>Staff</th><th>Availability</th><th class="nw">Assigned</th><th class="nw">In progress</th><th class="nw">Pending</th><th class="nw">Resolved</th><th>Load</th><th>Current status</th></tr></thead><tbody></tbody></table></div>
  </div>
</section>

<!-- assign / reassign -->
<div class="modal" id="assignModal" role="dialog" aria-modal="true" aria-labelledby="amTitle">
  <form class="modal-box sm" id="assignForm">
    <div class="modal-head"><h3 id="amTitle">Assign concern</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-msg" role="alert"></div>
      <input type="hidden" name="id" id="amId">
      <p class="cell-sub" id="amInfo" style="margin-bottom:12px"></p>
      <div class="field"><label for="amStaff">Staff member</label><select class="select" id="amStaff" name="staff_id" required></select><span class="hint" id="amHint">Suggested by department, then lightest workload.</span></div>
      <div class="field" id="amReasonWrap" hidden><label for="amReason">Reason for reassigning</label><input class="input" id="amReason" name="reason" maxlength="255"></div>
    </div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </form>
</div>
<?php panel_close(['admin-concerns.js']); ?>
