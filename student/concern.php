<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('student');
$id = (int)($_GET['id'] ?? 0);

panel_open($u, 'Concern Details', 'concerns.php');
page_head('Concern details', '', '<a class="btn btn-ghost" href="' . e(url('student/concerns.php')) . '">' . icon('chevL') . ' Back to list</a>');
?>
<div id="detail" data-id="<?= $id ?>"><div class="card card-pad"><div class="skel" style="height:22px;width:50%"></div></div></div>

<div class="modal" id="reopenModal" role="dialog" aria-modal="true">
  <form class="modal-box sm" id="reopenForm">
    <div class="modal-head"><h3>Reopen concern</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><div class="form-msg" role="alert"></div>
      <div class="field"><label for="reason">What is still wrong?</label><textarea class="textarea" id="reason" name="reason" required></textarea></div></div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Reopen</button></div>
  </form>
</div>
<?php panel_close(['student.js']); ?>
