<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('student');

panel_open($u, 'Dashboard', 'dashboard.php');
page_head('Hello, ' . $u['first_name'] . '!', 'Here is where your concerns stand today.',
    '<a class="btn btn-primary" href="' . e(url('student/concerns.php?new=1')) . '">' . icon('plus') . ' New Concern</a>');
?>
<section class="stat-grid" aria-label="Summary">
  <?= stat_card('Total concerns', '–', 'file', 'navy', 'sTotal') ?>
  <?= stat_card('Pending', '–', 'clock', 'info', 'sPending') ?>
  <?= stat_card('In progress', '–', 'zap', 'warn', 'sProgress') ?>
  <?= stat_card('Resolved', '–', 'checkCircle', 'ok', 'sResolved') ?>
</section>

<section class="note" id="ratingNote" hidden><?= icon('star') ?><div id="ratingText"></div></section>

<section class="grid-2">
  <div class="card">
    <div class="card-head"><h3>Recent activity</h3><a class="btn btn-ghost btn-sm" href="<?= e(url('student/concerns.php')) ?>">All concerns <?= icon('chevR') ?></a></div>
    <div class="list" id="recent"><div class="empty">Loading…</div></div>
  </div>
  <div class="card">
    <div class="card-head"><h3>How severity works</h3></div>
    <div class="list">
      <div class="list-item"><span class="badge sev-Minor">Minor</span><div class="li-main"><span>Inconvenient but not harmful. Target: resolved within <b id="hMinor">7</b> days.</span></div></div>
      <div class="list-item"><span class="badge sev-Major">Major</span><div class="li-main"><span>Disrupts classes or daily use. Target: within <b id="hMajor">3</b> days.</span></div></div>
      <div class="list-item"><span class="badge sev-Urgent">Urgent</span><div class="li-main"><span>Safety risk or total outage. Target: within <b id="hUrgent">24</b> hours.</span></div></div>
    </div>
  </div>
</section>
<?php panel_close(['student.js']); ?>
