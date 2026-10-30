<?php
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
<link rel="icon" type="image/png" href="<?= e(url('assets/img/cmu-logo.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= e(url('assets/css/cv.css')) ?>">
<?php foreach ($css as $file): ?>
<link rel="stylesheet" href="<?= e(url('assets/css/' . $file)) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(url('assets/css/identity.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php
}

function html_foot(array $js = [], array $external = []): void
{
    foreach ($external as $src) echo '<script src="' . e($src) . '"></script>' . "\n";
    echo '<script src="' . e(url('assets/js/cv.js')) . '"></script>' . "\n";
    foreach ($js as $file) echo '<script src="' . e(url('assets/js/' . $file)) . '"></script>' . "\n";
    echo "</body>\n</html>\n";
}

/** Left-hand photo panel + right-hand card used by login / signup / reset pages. */
function auth_open(string $heading, string $sub, bool $wide = false): void
{
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
    echo "    </div>\n  </main>\n</div>\n";
}

function logo(string $class = '', int $h = 44): string
{
    return '<img class="logo-img ' . e($class) . '" src="' . e(url('assets/img/cmu-logo.png')) . '" alt="City of Malabon University seal" height="' . $h . '" style="height:' . $h . 'px;width:auto">';
}
