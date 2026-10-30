<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');
$id = (int)($_GET['id'] ?? 0);

panel_open($u, 'Concern Details', 'concerns.php', ['admin.css']);
page_head('Concern details', '', '<a class="btn btn-ghost" href="' . e(url('admin/concerns.php')) . '">' . icon('chevL') . ' Back to list</a>');
?>
<div id="detail" data-id="<?= $id ?>"><div class="card card-pad"><div class="skel" style="height:22px;width:50%"></div></div></div>

<div class="modal" id="assignModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="assignForm">
    <div class="modal-head"><h3 id="amTitle">Assign concern</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div><input type="hidden" name="id" id="amId">
      <div class="field"><label for="amStaff">Staff member</label><select class="select" id="amStaff" name="staff_id" required></select><span class="hint">Suggested by department, then lightest workload.</span></div>
      <div class="field" id="amReasonWrap" hidden><label for="amReason">Reason for reassigning</label><input class="input" id="amReason" name="reason" maxlength="255"></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </form>
</div>

<div class="modal" id="revealModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="revealForm">
    <div class="modal-head"><h3>Reveal anonymous reporter</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div>
      <div class="note warn" style="margin-bottom:14px"><?= icon('shield') ?><div>This is for abuse, threats or safety cases only. The reveal and your reason are written to the audit trail.</div></div>
      <input type="hidden" name="id" id="rvId">
      <div class="field"><label for="rvReason">Reason</label><textarea class="textarea" id="rvReason" name="reason" minlength="10" required></textarea></div>
      <div id="rvResult" hidden></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Close</button><button class="btn btn-danger" type="submit" id="rvBtn"><?= icon('eye') ?> Reveal</button></div>
  </form>
</div>

<div class="modal" id="statusModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="statusForm">
    <div class="modal-head"><h3>Change status</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div><input type="hidden" name="id" id="stId">
      <div class="field"><label for="stSel">New status</label><select class="select" id="stSel" name="status"><option>Under Review</option><option>Reopened</option><option>Closed</option><option>Cancelled</option></select></div>
      <div class="field"><label for="stNote">Note</label><textarea class="textarea" id="stNote" name="note" maxlength="500"></textarea></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
  </form>
</div>
<?php panel_close(['admin-concerns.js']); ?>
