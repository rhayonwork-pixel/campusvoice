<?php
require_once __DIR__ . '/icons.php';
/** HTML shell helpers shared by public pages and role panels. */

function html_head(string $title, array $css = [], string $bodyClass = ''): void
{
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · CampusVoice</title>
<meta name="description" content="CampusVoice — the concern management system of City of Malabon University.">
<meta name="cv-base" content="<?= e(base_path()) ?>">
<meta name="cv-csrf" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#0f2a5e">
<link rel="icon" type="image/webp" href="<?= e(url('assets/img/cmu-logo.webp')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="<?= e(asset('js/theme.js')) ?>"></script>
<link rel="stylesheet" href="<?= e(asset('css/cv.css')) ?>">
<?php foreach ($css as $file): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . $file)) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(asset('css/identity.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/layout.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/dark.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>" media="print">
</head>
<body class="<?= e($bodyClass) ?>">
<?php
}

function html_foot(array $js = [], array $external = []): void
{
    foreach ($external as $src) echo '<script src="' . e($src) . '"></script>' . "\n";
    echo '<script src="' . e(asset('js/cv.js')) . '"></script>' . "\n";
    foreach ($js as $file) echo '<script src="' . e(asset('js/' . $file)) . '"></script>' . "\n";
    echo "</body>\n</html>\n";
}

/** URL of a CSS/JS file with its modified time appended, so browsers always fetch the newest upload. */
function asset(string $path): string
{
    $file = CV_ROOT . '/assets/' . $path;
    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Left-hand photo panel + right-hand card used by login / signup / reset pages. */
function auth_open(string $heading, string $sub, bool $wide = false): void
{
    $t = 0; $d = 0;
    try {
        $row = q('SELECT COUNT(*) t, SUM(status IN ("Resolved","Closed")) d FROM concerns')->fetch();
        $t = (int)$row['t']; $d = (int)$row['d'];
    } catch (Throwable $e) { /* numbers are decoration only */ }
    $rate = $t ? (int)round($d / $t * 100) : 0;
    icon_sprite();
    ?>
<div class="auth">
  <aside class="auth-side" id="authSide">
    <div class="auth-side-bg"></div>
    <a class="auth-brand" href="<?= e(url('index.php')) ?>" aria-label="Back to CampusVoice home">
      <?= logo('', 58) ?>
      <span><b>CampusVoice</b><small>City of Malabon University</small></span>
    </a>
    <div class="auth-quote">
      <span class="wave"><i></i><i></i><i></i><i></i><i></i></span>
      <h2>Every concern<br>deserves to be <em>heard</em>.</h2>
      <p>Report, track and resolve campus concerns in one place.</p>
    </div>
    <dl class="auth-stats" aria-label="CampusVoice in numbers">
      <div><dt>Reported</dt><dd><?= $t ?></dd></div>
      <div><dt>Resolved</dt><dd><?= $d ?></dd></div>
      <div><dt>Resolution rate</dt><dd><?= $rate ?>%</dd></div>
    </dl>
  </aside>
  <main class="auth-main">
    <div class="auth-card<?= $wide ? ' wide' : '' ?>">
      <a class="auth-back" href="<?= e(url('index.php')) ?>">← Back to home</a>
      <h1><?= e($heading) ?></h1>
      <p class="auth-sub"><?= e($sub) ?></p>
<?php
}

function auth_close(): void
{
    echo '      <ul class="auth-trust"><li>' . icon('shield') . 'Protected sign-in</li><li>' . icon('eyeOff') . 'Anonymous reports</li><li>' . icon('lock') . 'Private by role</li></ul>' . "\n";
    echo "    </div>\n  </main>\n</div>\n";
}

function logo(string $class = '', int $h = 44): string
{
    return '<img class="logo-img ' . e($class) . '" src="' . e(url('assets/img/cmu-logo.webp')) . '" alt="City of Malabon University seal" height="' . $h . '" style="height:' . $h . 'px;width:auto">';
}
