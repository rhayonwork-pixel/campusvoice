<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Calendar', 'calendar.php', ['admin.css']);
page_head('Calendar', 'Concern deadlines and campus events. Busy days show counts; click one to see its concerns.',
    '<button class="btn btn-primary" type="button" data-open-modal="eventModal">' . icon('plus') . ' Add event</button>');
$cats = q('SELECT category_id, category_name FROM categories WHERE status = "active" ORDER BY category_name = "Other", category_name')->fetchAll();
?>
<section class="card">
  <div class="cal-top">
    <div class="cal-legend cal-legend-tog">
      <button type="button" class="lgi on" data-sev="Urgent" aria-pressed="true"><i style="background:#b42318"></i>Urgent</button>
      <button type="button" class="lgi on" data-sev="Major" aria-pressed="true"><i style="background:#b25e09"></i>Major</button>
      <button type="button" class="lgi on" data-sev="Minor" aria-pressed="true"><i style="background:#0f7b4f"></i>Minor</button>
      <button type="button" class="lgi on" data-vis="1" aria-pressed="true"><i style="background:#0f7f86"></i>Meetings</button>
      <button type="button" class="lgi" data-asg="1" aria-pressed="false"><i style="background:#5440b8"></i>Assignments</button>
      <span class="lgi static"><i style="background:#173d7a"></i>Event</span>
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
  <div class="cal-card"><div id="calendar" aria-label="Calendar"></div></div>
</section>

<!-- a day's concerns (opened by clicking a day or a count) -->
<div class="modal" id="dayModal" role="dialog" aria-modal="true" aria-labelledby="dmTitle">
  <div class="modal-box">
    <div class="modal-head"><div><span class="cd-kicker" id="dmKicker">Day</span><h3 id="dmTitle">—</h3></div><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <div class="cd-counts" id="dmCounts"></div>
      <div class="cd-events" id="dmEvents"></div>
      <div class="cd-visits" id="dmVisits"></div>
      <div class="cd-list" id="dmList"></div>
      <div class="cd-more" id="dmMore" hidden><button type="button" class="btn btn-ghost btn-block" id="dmMoreBtn">Show more</button></div>
    </div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" id="dmAdd"><?= icon('plus') ?> Add event this day</button><button type="button" class="btn btn-primary" data-close-modal>Close</button></div>
  </div>
</div>

<div class="modal" id="eventModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="eventForm"><div class="modal-head"><h3>Add event</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div>
      <div class="field"><label for="evTitle">Title</label><input class="input" id="evTitle" name="title" maxlength="150" required></div>
      <div class="row two"><div class="field"><label for="evStart">Starts</label><input class="input" id="evStart" name="start_at" type="datetime-local" required></div>
      <div class="field"><label for="evEnd">Ends (optional)</label><input class="input" id="evEnd" name="end_at" type="datetime-local"></div></div>
      <div class="field"><label for="evNotes">Notes</label><input class="input" id="evNotes" name="notes" maxlength="255"></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save event</button></div></form>
</div>
<div class="modal" id="eventView" role="dialog" aria-modal="true">
  <div class="modal-box sm"><div class="modal-head"><h3 id="evvTitle">Event</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><p id="evvWhen" class="cell-sub"></p><p id="evvNotes" style="margin-top:8px"></p></div>
    <div class="modal-foot"><button type="button" class="btn btn-danger" id="evvDelete"><?= icon('trash') ?> Delete</button><button type="button" class="btn btn-ghost" data-close-modal>Close</button></div></div>
</div>
<?php panel_close(['admin-system.js'], ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js']); ?>
