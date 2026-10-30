<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Dashboard', 'dashboard.php', ['admin.css']);
page_head('Dashboard', 'What needs the administrator today.',
    '<a class="btn btn-primary" href="' . e(url('admin/concerns.php')) . '">' . icon('list') . ' Manage concerns</a>');
?>
<section class="stat-grid n5" aria-label="Summary">
  <?= stat_card('Total concerns', '–', 'file', 'navy', 'sTotal') ?>
  <?= stat_card('Unassigned', '–', 'inbox', 'info', 'sUnassigned') ?>
  <?= stat_card('In progress', '–', 'zap', 'warn', 'sProgress') ?>
  <?= stat_card('Overdue', '–', 'alert', 'bad', 'sOverdue') ?>
  <?= stat_card('Resolved', '–', 'checkCircle', 'ok', 'sResolved') ?>
</section>

<section class="grid-2">
  <div class="card attention" id="attention">
    <div class="card-head"><h3><?= icon('alert') ?> Needs your attention <span class="tab-count" id="attCount">0</span></h3><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/notifications.php')) ?>">Notifications <?= icon('chevR') ?></a></div>
    <div class="list" id="attList"><div class="empty">Loading…</div></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>Concerns by status</h3></div>
    <div class="bars" id="statusBars"><div class="empty">Loading…</div></div>
  </div>
</section>

<section class="card">
  <div class="card-head"><h3>Latest concerns</h3><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/concerns.php')) ?>">View all <?= icon('chevR') ?></a></div>
  <div class="table-wrap"><table class="tbl" id="recentTable"><thead><tr><th>Concern</th><th>Category</th><th>Severity</th><th>Status</th><th class="nw">Submitted</th></tr></thead><tbody></tbody></table></div>
</section>
<?php panel_close(['admin-concerns.js']); ?>
