<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Calendar', 'calendar.php', ['admin.css']);
page_head('Calendar', 'Concern deadlines, assignments and campus events.',
    '<button class="btn btn-primary" type="button" data-open-modal="eventModal">' . icon('plus') . ' Add event</button>');
?>
<section class="card">
  <div class="cal-legend" style="padding-top:16px">
    <span><i style="background:#b42318"></i>Urgent deadline</span><span><i style="background:#b25e09"></i>Major deadline</span><span><i style="background:#0f7b4f"></i>Minor deadline</span>
    <span><i style="background:#5440b8"></i>Assignment</span><span><i style="background:#173d7a"></i>Event</span>
  </div>
  <div class="cal-card"><div id="calendar" aria-label="Calendar"></div></div>
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
<div class="modal" id="eventView" role="dialog" aria-modal="true">
  <div class="modal-box sm"><div class="modal-head"><h3 id="evvTitle">Event</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><p id="evvWhen" class="cell-sub"></p><p id="evvNotes" style="margin-top:8px"></p></div>
    <div class="modal-foot"><button type="button" class="btn btn-danger" id="evvDelete"><?= icon('trash') ?> Delete</button><button type="button" class="btn btn-ghost" data-close-modal>Close</button></div></div>
</div>
<?php panel_close(['admin-system.js'], ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js']); ?>
