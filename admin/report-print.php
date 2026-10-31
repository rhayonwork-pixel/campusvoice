<?php
/**
 * Printable concern report — a formal document (letterhead, reference no., summary, tables, signatures),
 * not a printout of the screen. Always light, A4. Open from Reports → Print report.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/reports.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('admin');

[$from, $to, $label] = report_range();
$withList = input('list', '1') !== '0';
$d = report_data($from, $to, $withList);
$s = $d['summary'];
$total = $s['total'];
$pct = static function (int $n, int $of): string { return $of > 0 ? number_format($n / $of * 100, 1) . '%' : '—'; };
$ref = 'CV-RPT-' . date('Ymd-Hi');
$address = setting('contact_address', 'Pampano cor. Maya-Maya Sts., Longos, City of Malabon');
$email = setting('contact_email', '');
audit('report_printed', 'Opened printable report (' . $label . ')');

// plain-language summary generated from the numbers
$lines = [];
if ($total === 0) {
    $lines[] = 'No concerns were reported during this period.';
} else {
    $lines[] = sprintf('A total of %d concern%s %s reported during the period. %d (%s) %s resolved or closed, and %d %s still open.',
        $total, $total === 1 ? '' : 's', $total === 1 ? 'was' : 'were', $s['resolved'], $pct($s['resolved'], $total), $s['resolved'] === 1 ? 'was' : 'were', $total - $s['resolved'], $total - $s['resolved'] === 1 ? 'remains' : 'remain');
    if ($d['by_category']) $lines[] = sprintf('The most frequent category was %s (%d concern%s). ', $d['by_category'][0]['label'], $d['by_category'][0]['n'], (int)$d['by_category'][0]['n'] === 1 ? '' : 's')
        . ($d['by_location'] ? sprintf('The busiest location was %s.', $d['by_location'][0]['label']) : '');
    if ($s['avg_hours'] !== null) $lines[] = sprintf('Resolved concerns took an average of %s hours from submission to resolution.', $s['avg_hours']);
    if ($s['overdue'] > 0) $lines[] = sprintf('%d open concern%s currently past the target date.', $s['overdue'], $s['overdue'] === 1 ? ' is' : 's are');
    if ((int)$d['rating']['n'] > 0) $lines[] = sprintf('Students rated the resolutions %s out of 5 on average (%d rating%s).', $d['rating']['avg_rating'], $d['rating']['n'], (int)$d['rating']['n'] === 1 ? '' : 's');
    if ($s['anonymous'] > 0) $lines[] = sprintf('%d concern%s submitted anonymously; reporter identities are not shown in this report.', $s['anonymous'], $s['anonymous'] === 1 ? ' was' : 's were');
}

function rep_table(string $title, string $col, array $rows, int $total, bool $unit = true): void
{ ?>
  <section class="block">
    <h3><?= e($title) ?></h3>
    <table class="rt">
      <thead><tr><th><?= e($col) ?></th><th class="num">Count</th><?php if ($unit): ?><th class="num">Share</th><?php endif; ?></tr></thead>
      <tbody>
      <?php if (!$rows): ?><tr><td colspan="3" class="none">No data</td></tr><?php endif; ?>
      <?php foreach ($rows as $row): $n = (int)$row['n']; ?>
        <tr><td><?= e($row['label']) ?></td><td class="num"><?= $n ?></td>
        <?php if ($unit): ?><td class="num share"><span class="bar"><i style="width:<?= $total ? round($n / $total * 100) : 0 ?>%"></i></span><?= $total ? number_format($n / $total * 100, 1) . '%' : '—' ?></td><?php endif; ?></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
<?php }
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($ref) ?> · Concern Management Report</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/report.css')) ?>">
</head>
<body class="reportpage">
<?php icon_sprite(); ?>

<!-- screen-only toolbar -->
<form class="toolbar-bar" method="get" action="">
  <a class="tb-btn" href="<?= e(url('admin/reports.php')) ?>"><?= icon('chevL') ?> Back to Reports</a>
  <span class="tb-spacer"></span>
  <label>Period
    <select name="days" id="days">
      <?php foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year', 0 => 'All time'] as $v => $t): ?>
        <option value="<?= $v ?>"<?= (!isset($_GET['from']) && input_int('days', 30) === $v) ? ' selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select></label>
  <label>From <input type="date" name="from" value="<?= e(isset($_GET['from']) ? input('from') : '') ?>"></label>
  <label>To <input type="date" name="to" value="<?= e(isset($_GET['to']) ? input('to') : '') ?>"></label>
  <label class="chk"><input type="checkbox" name="list" value="1"<?= $withList ? ' checked' : '' ?>> Include detailed list</label>
  <input type="hidden" name="list_present" value="1">
  <button class="tb-btn" type="submit">Apply</button>
  <button class="tb-btn primary" type="button" id="printNow"><?= icon('printer') ?> Print / Save as PDF</button>
</form>

<main class="sheet">
  <header class="letterhead">
    <?= logo('', 76) ?>
    <div class="lh-text">
      <p class="lh-uni">City of Malabon University</p>
      <p class="lh-sys">CampusVoice · Concern Management System</p>
      <p class="lh-addr"><?= e($address) ?><?= $email ? ' · ' . e($email) : '' ?></p>
    </div>
  </header>

  <div class="title-block">
    <h1>Concern Management Report</h1>
    <table class="meta">
      <tr><th>Reporting period</th><td><?= e($label) ?></td><th>Reference no.</th><td><?= e($ref) ?></td></tr>
      <tr><th>Prepared by</th><td><?= e(display_name($u)) ?>, Administrator</td><th>Date generated</th><td><?= e(date('F j, Y · g:i A')) ?></td></tr>
    </table>
  </div>

  <section class="block">
    <h2><span>1</span> Summary</h2>
    <table class="figures">
      <tr>
        <td><b><?= $total ?></b><span>Concerns reported</span></td>
        <td><b><?= $s['resolved'] ?></b><span>Resolved / closed</span></td>
        <td><b><?= $pct($s['resolved'], $total) ?></b><span>Resolution rate</span></td>
        <td><b><?= $s['avg_hours'] !== null ? e($s['avg_hours']) : '—' ?></b><span>Avg. hours to resolve</span></td>
        <td><b><?= (int)$d['rating']['n'] > 0 ? e($d['rating']['avg_rating']) . ' / 5' : '—' ?></b><span>Avg. rating</span></td>
        <td><b><?= $s['overdue'] ?></b><span>Currently overdue</span></td>
      </tr>
    </table>
    <p class="narrative"><?= e(implode(' ', $lines)) ?></p>
  </section>

  <h2><span>2</span> Breakdown</h2>
  <div class="two">
    <?php rep_table('By category', 'Category', $d['by_category'], $total); ?>
    <?php rep_table('By severity', 'Severity', $d['by_severity'], $total); ?>
    <?php rep_table('By status', 'Status', $d['by_status'], $total); ?>
    <?php rep_table('Busiest locations', 'Location', $d['by_location'], $total); ?>
  </div>

  <section class="block">
    <h2><span>3</span> Staff performance</h2>
    <table class="rt">
      <thead><tr><th>Staff member</th><th class="num">Assignments completed</th><th class="num">Avg. hours to complete</th></tr></thead>
      <tbody>
      <?php if (!$d['staff']): ?><tr><td colspan="3" class="none">No completed assignments in this period.</td></tr><?php endif; ?>
      <?php foreach ($d['staff'] as $row): ?>
        <tr><td><?= e($row['label']) ?></td><td class="num"><?= (int)$row['n'] ?></td><td class="num"><?= $row['avg_hours'] !== null ? e($row['avg_hours']) : '—' ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <?php if ($withList): ?>
  <section class="block list">
    <h2><span>4</span> Detailed list of concerns <small>(<?= count($d['list']) ?><?= count($d['list']) >= 1000 ? '+' : '' ?>)</small></h2>
    <table class="rt dense">
      <thead><tr><th>No.</th><th>Code</th><th>Concern</th><th>Category</th><th>Severity</th><th>Status</th><th>Filed</th><th>Resolved</th></tr></thead>
      <tbody>
      <?php if (!$d['list']): ?><tr><td colspan="8" class="none">No concerns in this period.</td></tr><?php endif; ?>
      <?php foreach ($d['list'] as $i => $row): ?>
        <tr>
          <td class="num"><?= $i + 1 ?></td><td class="nw"><?= e($row['concern_code']) ?></td>
          <td><b><?= e($row['title']) ?></b><br><small><?= e($row['location']) ?></small></td>
          <td><?= e($row['category_name']) ?></td><td class="sev sev-<?= e($row['severity']) ?>"><?= e($row['severity']) ?></td><td><?= e($row['status']) ?></td>
          <td class="nw"><?= e(date('M j, Y', strtotime($row['created_at']))) ?></td><td class="nw"><?= $row['resolved_at'] ? e(date('M j, Y', strtotime($row['resolved_at']))) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <section class="signatures">
    <div><span class="line"></span><b><?= e(display_name($u)) ?></b><small>Prepared by · Administrator</small></div>
    <div><span class="line"></span><b>&nbsp;</b><small>Noted by</small></div>
    <div><span class="line"></span><b>&nbsp;</b><small>Approved by</small></div>
  </section>

  <p class="end">— End of report · <?= e($ref) ?> —</p>
</main>

<script src="<?= e(asset('js/report-print.js')) ?>"></script>
</body>
</html>
