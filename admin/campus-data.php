<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Campus Data', 'campus-data.php', ['admin.css']);
page_head('Campus Data', 'The categories and locations students can choose when reporting.');
?>
<section class="card" id="campus">
  <div class="tabs" id="tabs">
    <button class="on" data-tab="cat"><?= icon('list') ?> Categories</button>
    <button data-tab="loc"><?= icon('pin') ?> Locations</button>
  </div>
  <div class="tab-pane on" id="paneCat">
    <div class="card-head"><span class="cell-sub">Each category can suggest a department when assigning.</span><button class="btn btn-primary btn-sm" type="button" id="addCat"><?= icon('plus') ?> Add category</button></div>
    <div class="table-wrap"><table class="tbl" id="catTable"><thead><tr><th>Category</th><th>Suggested department</th><th class="nw">Concerns</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead><tbody></tbody></table></div>
  </div>
  <div class="tab-pane" id="paneLoc">
    <div class="card-head"><span class="cell-sub">A location is a building, floor and area.</span><button class="btn btn-primary btn-sm" type="button" id="addLoc"><?= icon('plus') ?> Add location</button></div>
    <div class="table-wrap"><table class="tbl" id="locTable"><thead><tr><th>Building</th><th>Floor</th><th>Area</th><th class="nw">Concerns</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead><tbody></tbody></table></div>
  </div>
</section>

<div class="modal" id="catModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="catForm"><div class="modal-head"><h3 id="catTitle">Category</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div><input type="hidden" name="category_id" id="catId" value="0">
      <div class="field"><label for="catName">Name</label><input class="input" id="catName" name="category_name" maxlength="100" required></div>
      <div class="field"><label for="catDesc">Description</label><input class="input" id="catDesc" name="description" maxlength="255"></div>
      <div class="field"><label for="catDept">Suggested department</label><select class="select" id="catDept" name="default_department_id"><option value="">None</option></select></div></div>
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
