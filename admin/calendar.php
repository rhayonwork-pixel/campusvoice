<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Calendar', 'calendar.php', ['admin.css']);
page_head('Calendar', 'See how busy each day is. Tap a day to open its concerns.',
    '<button class="btn btn-primary" type="button" id="addEventBtn">' . icon('plus') . ' Add event</button>');
$cats = q('SELECT category_id, category_name FROM categories WHERE status = "active" ORDER BY category_name = "Other", category_name')->fetchAll();
?>
<section class="cal2" id="cal2">
  <div class="cal-main card">
    <div class="cal-bar">
      <div class="cal-nav">
        <button type="button" class="icon-btn" id="calPrev" aria-label="Previous month"><?= icon('chevL') ?></button>
        <h2 id="calTitle">Month</h2>
        <button type="button" class="icon-btn" id="calNext" aria-label="Next month"><?= icon('chevR') ?></button>
        <button type="button" class="btn btn-ghost btn-sm" id="calToday">Today</button>
      </div>
      <div class="cal-filters">
        <div class="seg2" id="calMode" role="radiogroup" aria-label="Show">
          <button type="button" role="radio" data-mode="due" aria-checked="true">Due dates</button>
          <button type="button" role="radio" data-mode="new" aria-checked="false">Submitted</button>
        </div>
        <select class="select" id="calCat" aria-label="Concern type"><option value="">All types</option>
          <?php foreach ($cats as $c) echo '<option value="' . (int)$c['category_id'] . '">' . e($c['category_name']) . '</option>'; ?></select>
      </div>
    </div>

    <div class="cal-sum" id="calSum" aria-live="polite"></div>

    <div class="cal-legend2">
      <button type="button" class="lg Urgent on" data-sev="Urgent" aria-pressed="true"><i></i>Urgent</button>
      <button type="button" class="lg Major on" data-sev="Major" aria-pressed="true"><i></i>Major</button>
      <button type="button" class="lg Minor on" data-sev="Minor" aria-pressed="true"><i></i>Minor</button>
      <span class="lg-note"><span class="ev-dot"></span>Event &nbsp; <span class="late-dot"></span>Past target</span>
    </div>

    <div class="cal-grid-wrap">
      <div class="cal-dow" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
      <div class="cal-grid" id="calGrid" role="grid" aria-label="Month"></div>
    </div>
  </div>

  <aside class="cal-day card" id="calDay" aria-live="polite">
    <div class="cd-head">
      <div><span class="cd-kicker" id="cdKicker">Selected day</span><h3 id="cdTitle">—</h3></div>
      <button type="button" class="btn btn-ghost btn-sm" id="cdAdd"><?= icon('plus') ?> Event</button>
    </div>
    <div class="cd-counts" id="cdCounts"></div>
    <div class="cd-events" id="cdEvents"></div>
    <div class="cd-list" id="cdList"></div>
    <div class="cd-more" id="cdMore" hidden><button type="button" class="btn btn-ghost btn-block" id="cdMoreBtn">Show more</button></div>
  </aside>
</section>

<div class="modal" id="eventModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="eventForm"><div class="modal-head"><h3>Add event</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div>
      <div class="field"><label for="evTitle">Title</label><input class="input" id="evTitle" name="title" maxlength="150" required></div>
      <div class="row two"><div class="field"><label for="evStart">Starts</label><input class="input" id="evStart" name="start_at" type="datetime-local" required></div>
      <div class="field"><label for="evEnd">Ends (optional)</label><input class="input" id="evEnd" name="end_at" type="datetime-local"></div></div>
      <div class="field"><label for="evNotes">Notes</label><input class="input" id="evNotes" name="notes" maxlength="255"></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save event</button></div></form>
</div>
<?php panel_close(['admin-system.js']); ?>
