<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Campus Data', 'campus-data.php', ['admin.css']);
page_head('Campus Data', 'Concern types, who handles them, and the campus locations for facility reports.');
$autoOn = setting('auto_assign', '1') === '1';
?>
<section class="card" id="campus">
  <div class="tabs" id="tabs">
    <button class="on" data-tab="cat"><?= icon('route') ?> Concern types &amp; routing</button>
    <button data-tab="loc"><?= icon('pin') ?> Locations</button>
  </div>
  <div class="tab-pane on" id="paneCat">
    <div class="route-banner <?= $autoOn ? 'on' : '' ?>">
      <span class="rb-ic"><?= icon('route') ?></span>
      <div><b>Automatic routing is <?= $autoOn ? 'ON' : 'OFF' ?></b>
        <span><?= $autoOn ? 'A new concern goes straight to the least-busy handler of its type, even when no admin is online. Types with no handler wait in the Assignment Queue.'
                          : 'Every new concern waits in the Assignment Queue for an admin. Turn routing on in System Settings → Concern rules.' ?></span></div>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/settings.php#rules')) ?>"><?= icon('settings') ?> Change</a>
    </div>
    <div class="card-head"><span class="cell-sub">Pick one or more handlers per type. Staff on leave are skipped.</span><button class="btn btn-primary btn-sm" type="button" id="addCat"><?= icon('plus') ?> Add type</button></div>
    <div class="table-wrap"><table class="tbl" id="catTable"><thead><tr><th>Concern type</th><th>Handled by</th><th>Office</th><th class="nw">Concerns</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead><tbody></tbody></table></div>
  </div>
  <div class="tab-pane" id="paneLoc">
    <div class="card-head"><span class="cell-sub">A location is a building, floor and area.</span><button class="btn btn-primary btn-sm" type="button" id="addLoc"><?= icon('plus') ?> Add location</button></div>
    <div class="table-wrap"><table class="tbl" id="locTable"><thead><tr><th>Building</th><th>Floor</th><th>Area</th><th class="nw">Concerns</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead><tbody></tbody></table></div>
  </div>
</section>

<div class="modal" id="catModal" role="dialog" aria-modal="true">
  <form class="modal-box" id="catForm"><div class="modal-head"><h3 id="catTitle">Concern type</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div><input type="hidden" name="category_id" id="catId" value="0">
      <div class="row two">
        <div class="field"><label for="catName">Name</label><input class="input" id="catName" name="category_name" maxlength="100" required></div>
        <div class="field"><label for="catDept">Office</label><select class="select" id="catDept" name="default_department_id"><option value="">None</option></select></div>
      </div>
      <div class="field"><label for="catDesc">Short description <span class="opt">shown on the type card</span></label><input class="input" id="catDesc" name="description" maxlength="255"></div>
      <div class="field"><label for="catNext">What happens next <span class="opt">shown to the student after choosing this type</span></label><textarea class="textarea" id="catNext" name="next_steps" maxlength="500" rows="3"></textarea></div>
      <label class="check"><input type="checkbox" name="needs_location" value="1" id="catLoc"> Ask for a location (building / floor / room)</label>
      <fieldset class="handler-pick"><legend>Handled by <span class="opt">new concerns of this type go to the least-busy one</span></legend>
        <div class="handler-list" id="catHandlers"></div></fieldset></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div></form>
</div>
<div class="modal" id="locModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="locForm"><div class="modal-head"><h3 id="locTitle">Location</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div><input type="hidden" name="location_id" id="locId" value="0">
      <div class="field"><label for="locB">Building</label><input class="input" id="locB" name="building" maxlength="100" required></div>
      <div class="row two"><div class="field"><label for="locF">Floor</label><input class="input" id="locF" name="floor" maxlength="40" required></div>
      <div class="field"><label for="locA">Area / room</label><input class="input" id="locA" name="area" maxlength="100" required></div></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div></form>
</div>
<?php panel_close(['admin-manage.js']); ?>
