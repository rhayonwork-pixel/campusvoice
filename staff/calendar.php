<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('staff');

panel_open($u, 'My Calendar', 'calendar.php');
page_head('My Calendar', 'Your face-to-face meetings and the days your concerns are due.');
?>
<section class="card">
  <div class="card-head"><h3><?= icon('calendar') ?> Meetings and due dates</h3>
    <span class="mc-legend"><i class="mc-d mc-ok"></i>Meeting <i class="mc-d mc-wait"></i>Due date <i class="mc-d mc-late"></i>Past due</span></div>
  <div class="mc-wrap">
    <div class="mc-cal">
      <div class="mc-nav"><button type="button" class="btn btn-ghost btn-sm" id="scPrev" aria-label="Previous month"><?= icon('chevL') ?></button><b id="scTitle"></b><button type="button" class="btn btn-ghost btn-sm" id="scNext" aria-label="Next month"><?= icon('chevR') ?></button></div>
      <div class="mc-dow"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
      <div class="mc-grid" id="scGrid"></div>
    </div>
    <div class="mc-side"><div class="mc-sidehead" id="scSideHead"></div><div class="mc-items" id="scItems"></div></div>
  </div>
</section>
<?php panel_close(['staff.js']); ?>
