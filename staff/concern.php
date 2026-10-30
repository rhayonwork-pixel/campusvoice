<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('staff');
$id = (int)($_GET['id'] ?? 0);

panel_open($u, 'Assignment Details', 'concerns.php');
page_head('Assignment details', '', '<a class="btn btn-ghost" href="' . e(url('staff/concerns.php')) . '">' . icon('chevL') . ' Back to list</a>');
?>
<div id="detail" data-id="<?= $id ?>"><div class="card card-pad"><div class="skel" style="height:22px;width:50%"></div></div></div>
<?php panel_close(['staff.js']); ?>
