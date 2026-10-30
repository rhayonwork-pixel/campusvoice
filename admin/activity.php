<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'System Activity', 'activity.php', ['admin.css']);
page_head('System Activity', 'Audit trail of sign-ins, concerns and account changes.');
?>
<section class="card" id="auditTable">
  <div class="card-head"><div class="filters">
    <input class="input" id="fq" type="search" placeholder="Search details, user or IP" aria-label="Search">
    <input class="input" id="fuser" type="search" placeholder="User name or email" aria-label="User">
    <select class="select" id="ftype" aria-label="Action type"><option value="">All actions</option></select>
    <input class="input" id="ffrom" type="date" aria-label="From date">
    <input class="input" id="fto" type="date" aria-label="To date">
  </div></div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th class="nw">When</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th class="nw">IP address</th></tr></thead><tbody></tbody></table></div>
  <div class="pager"></div>
</section>
<?php panel_close(['admin-system.js']); ?>
