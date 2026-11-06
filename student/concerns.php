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
        <?php foreach (['Pending','Under Review','Additional Info Needed','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled'] as $s) echo '<option>' . e($s) . '</option>'; ?></select>
      <select class="select" id="fsev" aria-label="Filter by severity"><option value="">All severities</option><option>Minor</option><option>Major</option><option>Urgent</option></select>
    </div>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Concern</th><th>Type</th><th>Severity</th><th>Status</th><th class="nw">Submitted</th><th class="nw">Target date</th></tr></thead>
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
      <fieldset class="type-pick">
        <legend>What is your concern about?</legend>
        <div class="type-grid" id="ncTypes"><div class="skel" style="height:64px"></div></div>
      </fieldset>
      <div class="next-steps" id="ncNext" hidden><?= icon('route') ?><div><b>What happens next</b><span id="ncNextText"></span></div></div>
      <div class="field" id="ncLocWrap" hidden><label for="ncLoc">Where is it?</label><select class="select" id="ncLoc" name="location_id"><option value="">Loading…</option></select></div>
      <div class="field"><label for="ncTitleIn">Title</label><input class="input" id="ncTitleIn" name="title" maxlength="150" placeholder="A short summary of your concern" required></div>
      <div class="field"><span class="label">How severe is it?</span>
        <div class="sev-choice">
          <label><input type="radio" name="severity" value="Minor" checked><span class="s-Minor">Minor<small id="hintMinor">Not harmful</small></span></label>
          <label><input type="radio" name="severity" value="Major"><span class="s-Major">Major<small id="hintMajor">Disrupts use</small></span></label>
          <label><input type="radio" name="severity" value="Urgent"><span class="s-Urgent">Urgent<small id="hintUrgent">Safety risk</small></span></label>
        </div>
      </div>
      <div class="field"><label for="ncDesc">What happened?</label><textarea class="textarea" id="ncDesc" name="description" maxlength="3000" placeholder="Tell us what happened, when, and who or what is involved." required></textarea></div>
      <div class="field"><span class="label">Photos <span class="hint">(optional, up to 4, 3 MB each)</span></span>
        <label class="dropzone" id="dz"><?= icon('camera') ?><span>Click or drop photos here</span><input type="file" id="ncPhotos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
        <div class="previews" id="previews"></div>
      </div>
      <div class="meet-box">
        <label class="anon-box check" for="ncMeet">
          <input type="checkbox" id="ncMeet" name="wants_meeting" value="1">
          <span style="display:flex;gap:10px"><?= icon('pin') ?><span><b>I'd like to meet in person</b><span>Most concerns are settled face to face. The office picks a private meeting and tells you the date here. It is confidential, and an anonymous concern stays anonymous until you choose to come.</span></span></span>
        </label>
        <div class="field" id="ncMeetPref" hidden><label for="ncPref">Best days or times <span class="opt">optional</span></label><input class="input" id="ncPref" name="meeting_pref" maxlength="120" placeholder="e.g. weekday afternoons"></div>
      </div>
      <label class="anon-box check" for="ncAnon">
        <input type="checkbox" id="ncAnon" name="is_anonymous" value="1">
        <span style="display:flex;gap:10px"><?= icon('eyeOff') ?><span><b>Send as anonymous</b><span>The staff handling it see only your course, year and section — not your name or ID. Only the SASO admin can see who sent it, so they can still protect and contact you if needed. You can track it as usual.</span></span></span>
      </label>
    </div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary"><?= icon('send') ?> Submit concern</button></div>
  </form>
</div>
<?php panel_close(['student.js']); ?>
