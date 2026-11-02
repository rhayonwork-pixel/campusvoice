<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('staff');

// Greeting appears once per login session, then never again until the next sign-in.
$showGreeting = empty($_SESSION['greeted']);
$_SESSION['greeted'] = true;

panel_open($u, 'Dashboard', 'dashboard.php');
if ($showGreeting): ?>
<section class="greet" id="greet" role="status">
  <span class="g-ico"><?= icon(date('G') < 18 ? 'sparkle' : 'bell') ?></span>
  <div><h3><?= e(greeting()) ?>, <?= e($u['first_name']) ?>!</h3><p>Here is what needs your attention today.</p></div>
  <button class="g-x" type="button" id="greetClose" aria-label="Dismiss greeting"><?= icon('x') ?></button>
</section>
<?php endif;
page_head('Today\'s Work', 'New and due-today assignments, most urgent first.',
    '<a class="btn btn-ghost" href="' . e(url('staff/concerns.php')) . '">' . icon('briefcase') . ' All assignments</a>');
?>
<section class="stat-grid n5" aria-label="Summary">
  <?= stat_card('Active assignments', '–', 'briefcase', 'navy', 'sActive') ?>
  <?= stat_card('In progress', '–', 'zap', 'warn', 'sProgress') ?>
  <?= stat_card('Waiting on others', '–', 'clock', 'info', 'sWaiting') ?>
  <?= stat_card('Overdue', '–', 'alert', 'bad', 'sOverdue') ?>
  <?= stat_card('Resolved this week', '–', 'checkCircle', 'ok', 'sDone') ?>
</section>

<section class="card">
  <div class="card-head"><h3>Today's work <span class="tab-count" id="todayCount">0</span></h3><span class="live">Updates automatically</span></div>
  <div class="table-wrap">
    <table class="tbl" id="todayTable"><thead><tr><th>Concern</th><th>Severity</th><th>Status</th><th class="nw">Due</th><th class="nw">Quick action</th></tr></thead>
      <tbody><tr class="tbl-skel"><td><div class="skel"></div></td><td><div class="skel"></div></td><td><div class="skel"></div></td><td><div class="skel"></div></td><td><div class="skel"></div></td></tr></tbody></table>
  </div>
</section>
<?php panel_close(['staff.js']); ?>
