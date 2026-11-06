<?php
/** Authenticated panel shell: collapsible icon sidebar, topbar with bell, no footer. */
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/icons.php';

function panel_nav(string $role): array
{
    $nav = [
        'student' => [
            ['dashboard.php', 'Dashboard', 'home'],
            ['concerns.php', 'My Concerns', 'list'],
            ['notifications.php', 'Notifications', 'bell'],
            ['help.php', 'Help & Support', 'help'],
            ['settings.php', 'Settings', 'settings'],
        ],
        'staff' => [
            ['dashboard.php', 'Dashboard', 'home'],
            ['concerns.php', 'My Assignments', 'briefcase'],
            ['calendar.php', 'My Calendar', 'calendar'],
            ['notifications.php', 'Notifications', 'bell'],
            ['help.php', 'Help & Support', 'help'],
            ['settings.php', 'Settings', 'settings'],
        ],
        'admin' => [
            ['dashboard.php', 'Dashboard', 'grid'],
            ['concerns.php', 'Concern Management', 'list'],
            ['users.php', 'Users', 'users', [['users.php?g=staff', 'Staff & Admins', 'user'], ['users.php?g=students', 'Students', 'users']]],
            ['campus-data.php', 'Campus Data', 'pin'],
            ['calendar.php', 'Calendar', 'calendar'],
            ['reports.php', 'Reports', 'chart'],
            ['activity.php', 'System Activity', 'activity'],
            ['notifications.php', 'Notifications', 'bell'],
            ['settings.php', 'System Settings', 'settings'],
        ],
    ];
    return $nav[$role];
}

/**
 * Open a panel page. $active = file name of the current nav item, $css/$js = extra assets.
 */
function panel_open(array $user, string $title, string $active, array $css = []): void
{
    $role = $user['role'];
    $initial = strtoupper(mb_substr($user['first_name'], 0, 1));
    html_head($title, array_merge(['panel.css'], $css), 'panel role-' . $role);
    icon_sprite();
    ?>
<div class="shell" id="shell">
  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sb-top">
      <a class="sb-brand" href="<?= e(url(home_for($role))) ?>" title="CampusVoice">
        <?= logo('', 40) ?>
        <span class="lbl"><b>CampusVoice</b><small><?= e(ucfirst($role)) ?> portal</small></span>
      </a>
      <button class="sb-toggle" id="sbToggle" type="button" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse / expand"><?= icon('chevL') ?></button>
    </div>
    <div class="sb-label"><?= $role === 'admin' ? 'Administration' : ($role === 'staff' ? 'My work' : 'Menu') ?></div>
    <nav class="sb-nav">
<?php foreach (panel_nav($role) as $item): [$file, $label, $ic] = $item; $kids = $item[3] ?? null; ?>
<?php if ($kids):
        $inGroup = false; foreach ($kids as $k) if ($active === $k[0]) $inGroup = true; ?>
      <div class="nav-grp<?= $inGroup ? ' open' : '' ?>">
        <button class="nav-i nav-parent<?= $inGroup ? ' on' : '' ?>" type="button" data-tip="<?= e($label) ?>" data-first="<?= e(url($role . '/' . $kids[0][0])) ?>" aria-expanded="<?= $inGroup ? 'true' : 'false' ?>">
          <?= icon($ic) ?><span class="lbl"><?= e($label) ?></span><span class="nav-chev"><?= icon('chevD') ?></span>
        </button>
        <div class="nav-sub"><div class="nav-sub-in">
<?php foreach ($kids as [$kfile, $klabel, $kic]): ?>
          <a class="nav-s<?= $active === $kfile ? ' on' : '' ?>" href="<?= e(url($role . '/' . $kfile)) ?>" <?= $active === $kfile ? 'aria-current="page"' : '' ?>><span class="lbl"><?= e($klabel) ?></span></a>
<?php endforeach; ?>
        </div></div>
      </div>
<?php continue; endif; ?>
      <a class="nav-i<?= $active === $file ? ' on' : '' ?>" href="<?= e(url($role . '/' . $file)) ?>" data-tip="<?= e($label) ?>" <?= $active === $file ? 'aria-current="page"' : '' ?>>
        <?= icon($ic) ?><span class="lbl"><?= e($label) ?></span>
<?php if ($file === 'notifications.php'): ?><span class="nav-count" id="navCount" hidden>0</span><?php endif; ?>
      </a>
<?php endforeach; ?>
    </nav>
    <div class="sb-bottom">
      <button class="nav-i" id="logoutBtn" type="button" data-tip="Sign out"><?= icon('logout') ?><span class="lbl">Sign out</span></button>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn mobile-only" id="menuBtn" type="button" aria-label="Open menu"><?= icon('menu') ?></button>
      <div class="tb-title"><b><?= e(['student' => 'Student portal', 'staff' => 'Staff portal', 'admin' => 'Admin portal'][$role]) ?></b><small><?= e(date('l, F j')) ?></small></div>
      <div class="tb-right">
        <div class="bell-wrap">
          <button class="icon-btn" id="bellBtn" type="button" aria-label="Notifications" aria-haspopup="true" aria-expanded="false"><?= icon('bell') ?><span class="dot" id="bellDot" hidden>0</span></button>
          <div class="bell-pop" id="bellPop" hidden>
            <div class="bp-head"><b>Notifications</b><button type="button" class="link-btn" id="markAll">Mark all read</button></div>
            <div class="bp-list" id="bpList"><div class="bp-empty">Loading…</div></div>
            <a class="bp-foot" href="<?= e(url($role . '/notifications.php')) ?>">View all</a>
          </div>
        </div>
        <a class="me" href="<?= e(url($role . '/settings.php')) ?>" title="My settings">
          <span class="avatar"><?php if ($user['avatar']): ?><img src="<?= e(url('file.php?avatar=' . (int)$user['user_id'] . '&v=' . urlencode((string)$user['updated_at']))) ?>" alt=""><?php else: echo e($initial); endif; ?></span>
          <span class="me-text"><b><?= e(display_name($user)) ?></b><small><?= e(ucfirst($role)) ?></small></span>
        </a>
      </div>
    </header>
    <main class="page" id="page">
<?php
}

function panel_close(array $js = [], array $external = []): void
{
    echo "    </main>\n  </div>\n</div>\n";
    html_foot(array_merge(['panel.js', 'concern-ui.js'], $js), $external);
}

/** Page header with title + optional action buttons (already-escaped HTML). */
function page_head(string $title, string $sub = '', string $actionsHtml = ''): void
{
    echo '<div class="page-head"><div><h1>' . e($title) . '</h1>' . ($sub !== '' ? '<p>' . e($sub) . '</p>' : '') . '</div>'
        . ($actionsHtml !== '' ? '<div class="page-actions">' . $actionsHtml . '</div>' : '') . '</div>';
}

function stat_card(string $label, string $value, string $iconName, string $tone = 'navy', string $id = ''): string
{
    return '<div class="stat-card tone-' . e($tone) . '"><span class="sc-label">' . icon($iconName) . e($label) . '</span><b' . ($id ? ' id="' . e($id) . '"' : '') . '>' . e($value) . '</b></div>';
}
