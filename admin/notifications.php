<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/shared_pages.php';
$u = require_role('admin');
render_notifications_page($u);
