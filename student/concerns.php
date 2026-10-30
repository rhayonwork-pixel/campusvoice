<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('student');

panel_open($u, 'My Concerns', 'concerns.php');
page_head('My Concerns', 'Everything you have reported, and where it stands.',
    '<button class="btn btn-primary" type="button" data-open-modal="newConcern">' . icon('plus') . ' New Concern</button>');
?>
<section class="card" id="concernTable">
  <div class="card-head">
    <div class="toolbar" style="width:100%">
      <input class="input grow" id="fq" type="search" placeholder="Search by title, code or category" aria-label="Search concerns">
      <select class="select" id="fstatus" aria-label="Filter by status"><option value="">All statuses</option>
        <?php foreach (['Submitted','Under Review','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled'] as $s) echo '<option>' . e($s) . '</option>'; ?></select>
      <select class="select" id="fsev" aria-label="Filter by severity"><option value="">All severities</option><option>Minor</option><option>Major</option><option>Urgent</option></select>
    </div>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Concern</th><th>Category</th><th>Severity</th><th>Status</th><th class="nw">Submitted</th><th class="nw">Target date</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="pager"></div>
</section>

<!-- New concern modal (replaces the old Report page) -->
<div class="modal" id="newConcern" role="dialog" aria-modal="true" aria-labelledby="ncTitle">
  <form class="modal-box" id="concernForm" enctype="multipart/form-data" novalidate>
    <div class="modal-head"><h3 id="ncTitle">New Concern</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-msg" role="alert"></div>
      <div class="field"><label for="ncTitleIn">Title</label><input class="input" id="ncTitleIn" name="title" maxlength="150" placeholder="e.g. Wi-Fi keeps disconnecting in CompLab 1" required></div>
      <div class="row two">
        <div class="field"><label for="ncCat">Category</label><select class="select" id="ncCat" name="category_id" required><option value="">Loading…</option></select></div>
        <div class="field"><label for="ncLoc">Location</label><select class="select" id="ncLoc" name="location_id" required><option value="">Loading…</option></select></div>
      </div>
      <div class="field"><span class="label">How severe is it?</span>
        <div class="sev-choice">
          <label><input type="radio" name="severity" value="Minor" checked><span class="s-Minor">Minor<small id="hintMinor">Not harmful</small></span></label>
          <label><input type="radio" name="severity" value="Major"><span class="s-Major">Major<small id="hintMajor">Disrupts use</small></span></label>
          <label><input type="radio" name="severity" value="Urgent"><span class="s-Urgent">Urgent<small id="hintUrgent">Safety risk</small></span></label>
        </div>
      </div>
      <div class="field"><label for="ncDesc">What happened?</label><textarea class="textarea" id="ncDesc" name="description" maxlength="3000" placeholder="Describe the problem and where exactly it is." required></textarea></div>
      <div class="field"><span class="label">Photos <span class="hint">(optional, up to 4, 3 MB each)</span></span>
        <label class="dropzone" id="dz"><?= icon('camera') ?><span>Click or drop photos here</span><input type="file" id="ncPhotos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
        <div class="previews" id="previews"></div>
      </div>
      <label class="anon-box check" for="ncAnon">
        <input type="checkbox" id="ncAnon" name="is_anonymous" value="1">
        <span style="display:flex;gap:10px"><?= icon('eyeOff') ?><span><b>Send as anonymous</b><span>Staff and admins will see only your course, year and section — not your name or ID. You can still track this concern.</span></span></span>
      </label>
    </div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary"><?= icon('send') ?> Submit concern</button></div>
  </form>
</div>
<?php panel_close(['student.js']); ?>
