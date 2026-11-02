<?php
/** Pages that look the same for every role: notifications, help, account settings. */
require_once __DIR__ . '/panel.php';

function render_notifications_page(array $u): void
{
    panel_open($u, 'Notifications', 'notifications.php', $u['role'] === 'admin' ? ['admin.css'] : []);
    page_head('Notifications', 'Updates about your concerns and the system.',
        '<button class="btn btn-ghost" type="button" id="readAll">' . icon('checkCircle') . ' Mark all as read</button>');
    ?>
<section class="card" id="notifTable">
  <div class="table-wrap"><table class="tbl"><thead><tr><th style="width:56px"></th><th>Notification</th><th>When</th></tr></thead><tbody></tbody></table></div>
  <div class="pager"></div>
</section>
<?php
    panel_close(['shared.js']);
}

function render_help_page(array $u): void
{
    $role = $u['role'];
    $faq = [
        'student' => [
            ['How do I report a concern?', 'Open My Concerns and press “New Concern”. Pick a category, location and severity, describe the problem and add photos if you can.'],
            ['What does “anonymous” really hide?', 'Staff and admins will not see your name, student number, email or phone — only your course, year and section so they know the context. You still see and track the concern yourself. An administrator can only reveal your identity for abuse or safety cases, and every reveal is logged.'],
            ['What do Minor, Major and Urgent mean?', 'Minor: inconvenient. Major: disrupts classes or daily use. Urgent: safety risk or total outage. Each has a target date shown on your concern.'],
            ['I am not satisfied with the fix.', 'Open the concern and press Reopen, then tell us what is still wrong.'],
        ],
        'staff' => [
            ['Where do I see my work?', 'Dashboard shows today\'s work with a “New” badge on fresh assignments. My Assignments lists everything assigned to you.'],
            ['How do I update a concern?', 'Open the concern, choose a new status, add a note and (optionally) a proof photo, then save.'],
            ['What is availability for?', 'Set yourself Available, Busy or On leave in Settings. Admins see this and avoid over-assigning you.'],
            ['Some concerns say “Anonymous Student”.', 'The reporter chose to stay anonymous. Treat the concern as normal — you can still see their course, year and section.'],
        ],
        'admin' => [
            ['How do I assign a concern?', 'Concern Management → Assignment Queue → Assign. The dialog suggests staff by department and current workload.'],
            ['Where is reassigning?', 'Under the Current Assignments tab, press Reassign on any active assignment.'],
            ['How do I see who changed what?', 'System Activity is the audit trail. Filter by date, user or action type.'],
            ['An anonymous concern looks abusive.', 'Open it and use “Reveal reporter”. You must give a reason, and the reveal is recorded in the audit trail.'],
        ],
    ][$role];
    panel_open($u, 'Help & Support', 'help.php');
    page_head('Help & Support', 'Quick answers and who to contact.');
    ?>
<section class="grid-2">
  <div class="card"><div class="card-head"><h3>Frequently asked</h3></div>
    <div class="list">
      <?php foreach ($faq as [$qq, $aa]): ?>
        <details class="list-item" style="display:block"><summary style="cursor:pointer;font-weight:600"><?= e($qq) ?></summary><p style="margin-top:8px;color:#44536a"><?= e($aa) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><div class="card-head"><h3>Contact the university</h3></div>
    <div class="list">
      <div class="list-item"><span class="li-ico" style="background:var(--info-bg);color:var(--info)"><?= icon('mail') ?></span><div class="li-main"><b>Email</b><span><?= e(setting('contact_email')) ?></span></div></div>
      <div class="list-item"><span class="li-ico" style="background:var(--ok-bg);color:var(--ok)"><?= icon('pin') ?></span><div class="li-main"><b>Visit us</b><span><?= e(setting('contact_address')) ?></span></div></div>
      <div class="list-item"><span class="li-ico" style="background:var(--warn-bg);color:var(--warn)"><?= icon('clock') ?></span><div class="li-main"><b>Office hours</b><span>Mon–Fri, 9:00 AM – 4:00 PM</span></div></div>
    </div>
  </div>
</section>
<?php
    panel_close();
}

/**
 * Settings: a section list on the left, ONE section shown at a time on the right (deep-linkable: settings.php#security).
 * $systemSections (admin only): [['id' => 'rules', 'label' => 'Concern rules', 'icon' => 'zap', 'hint' => '...', 'html' => '...'], ...]
 */
function render_settings_page(array $u, array $systemSections = [], array $extraJs = []): void
{
    require_once __DIR__ . '/google.php';
    $role = $u['role'];
    $force = !empty($_GET['force']) || (int)$u['must_change_password'] === 1;
    $flash = $_SESSION['flash_settings'] ?? null; unset($_SESSION['flash_settings']);
    panel_open($u, $role === 'admin' ? 'System Settings' : 'Settings', 'settings.php', $role === 'admin' ? ['admin.css'] : []);
    page_head($role === 'admin' ? 'Settings' : 'Settings', $role === 'admin' ? 'Your account and how CampusVoice runs.' : 'Manage your account, sign-in and preferences.');

    $mine = [
        ['id' => 'profile', 'label' => 'Profile', 'icon' => 'user', 'hint' => 'Name, photo' . ($role === 'student' ? ', course' : ', position')],
        ['id' => 'security', 'label' => 'Password', 'icon' => 'lock', 'hint' => 'Change your password'],
        ['id' => 'linked', 'label' => 'Linked accounts', 'icon' => 'link', 'hint' => $u['google_sub'] ? 'Google linked' : 'Sign in with Google'],
        ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'hint' => 'What we tell you'],
        ['id' => 'appearance', 'label' => 'Appearance', 'icon' => 'sun', 'hint' => 'Light, dark or system'],
    ];
    if ($role === 'staff') array_splice($mine, 1, 0, [['id' => 'availability', 'label' => 'Availability', 'icon' => 'clock', 'hint' => 'Available, busy, on leave']]);
    $navItem = function (array $s) {
        return '<a class="set-link" href="#' . e($s['id']) . '" role="tab" data-sec="' . e($s['id']) . '" aria-controls="sec-' . e($s['id']) . '">'
            . '<span class="set-ic">' . icon($s['icon']) . '</span><span class="set-txt"><b>' . e($s['label']) . '</b><small>' . e($s['hint']) . '</small></span></a>';
    };
    $first = $force ? 'security' : 'profile';
    ?>
<?php if ($force): ?>
<section class="note warn"><?= icon('lock') ?><div><b>Please change your password.</b> You are using a first-time or temporary password. Set your own to continue.</div></section>
<?php endif; ?>
<section class="set-shell" id="settingsShell" data-first="<?= e($first) ?>">
  <nav class="set-nav" role="tablist" aria-label="Settings sections">
    <span class="set-group">Your account</span>
    <?php foreach ($mine as $s) echo $navItem($s); ?>
    <?php if ($systemSections): ?>
    <span class="set-group">System</span>
    <?php foreach ($systemSections as $s) echo $navItem($s); ?>
    <?php endif; ?>
  </nav>

  <div class="set-body">
    <section class="set-pane" id="sec-profile" role="tabpanel" data-sec="profile">
      <header class="set-head"><h2>Profile</h2><p>How you appear to the SASO office and staff.</p></header>
      <div class="card">
        <form class="card-pad set-form" id="profileForm" novalidate>
          <div class="form-msg" role="alert"></div>
          <div class="avatar-row">
            <span class="avatar lg"><?php if ($u['avatar']): ?><img src="<?= e(url('file.php?avatar=' . (int)$u['user_id'] . '&v=' . urlencode((string)$u['updated_at']))) ?>" alt=""><?php else: echo e(strtoupper(mb_substr($u['first_name'], 0, 1))); endif; ?></span>
            <div class="toolbar"><label class="btn btn-ghost btn-sm" style="cursor:pointer"><?= icon('camera') ?> Change photo<input type="file" id="avatarIn" accept="image/jpeg,image/png,image/webp" hidden></label>
              <?php if ($u['avatar']): ?><button type="button" class="btn btn-ghost btn-sm" id="avatarDel"><?= icon('trash') ?> Remove</button><?php endif; ?></div>
          </div>
          <div class="row two">
            <div class="field"><label>Username</label><input class="input mono" value="<?= e($u['school_id']) ?>" disabled><span class="hint">You sign in with this. Ask the admin to change it.</span></div>
            <div class="field"><label>Email</label><input class="input" value="<?= e($u['email'] ?: ($u['google_email'] ?: 'None yet')) ?>" disabled><span class="hint"><?= $u['email'] ? 'Used for password reset links.' : ($u['google_email'] ? 'From your linked Google account.' : 'Link Google to receive reset emails.') ?></span></div>
          </div>
          <div class="row three">
            <div class="field"><label for="first_name">First name</label><input class="input" id="first_name" name="first_name" value="<?= e($u['first_name']) ?>" required></div>
            <div class="field"><label for="middle_initial">M.I.</label><input class="input" id="middle_initial" name="middle_initial" maxlength="2" value="<?= e($u['middle_initial']) ?>"></div>
            <div class="field"><label for="last_name">Last name</label><input class="input" id="last_name" name="last_name" value="<?= e($u['last_name']) ?>" required></div>
          </div>
          <div class="field"><label for="contact_number">Contact number</label><input class="input" id="contact_number" name="contact_number" value="<?= e($u['contact_number']) ?>" placeholder="09XX XXX XXXX"></div>
          <?php if ($role === 'student'): ?>
          <div class="row course-row">
            <div class="field"><label for="course">Course</label><input class="input" id="course" name="course" maxlength="60" value="<?= e($u['course']) ?>" placeholder="Type or choose" data-course-picker></div>
            <div class="field"><label for="year_level">Year</label><select class="select" id="year_level" name="year_level"><?php for ($i = 1; $i <= 5; $i++) echo '<option value="' . $i . '"' . ((int)$u['year_level'] === $i ? ' selected' : '') . '>Year ' . $i . '</option>'; ?></select></div>
            <div class="field"><label for="section">Section</label><input class="input" id="section" name="section" value="<?= e($u['section']) ?>"></div>
          </div>
          <?php else: ?>
          <div class="field"><label for="position">Position</label><input class="input" id="position" name="position" value="<?= e($u['position']) ?>"></div>
          <?php endif; ?>
          <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save profile</button></div>
        </form>
      </div>
    </section>

<?php if ($role === 'staff'): ?>
    <section class="set-pane" id="sec-availability" role="tabpanel" data-sec="availability">
      <header class="set-head"><h2>Availability</h2><p>Routing skips you while you are on leave, and prefers available staff over busy ones.</p></header>
      <div class="card">
        <form class="card-pad set-form" id="availForm">
          <div class="form-msg" role="alert"></div>
          <div class="avail-pick" role="radiogroup" aria-label="Your status">
            <?php foreach (['available' => ['Available', 'Taking new concerns', 'checkCircle'], 'busy' => ['Busy', 'Only if nobody else is free', 'clock'], 'on_leave' => ['On leave', 'No new concerns', 'x']] as $k => [$t, $d, $ic]): ?>
            <label class="radio-card av-<?= $k ?>"><input type="radio" name="availability" value="<?= $k ?>" <?= $u['availability'] === $k ? 'checked' : '' ?>><span><?= icon($ic) ?><b><?= $t ?></b><small><?= $d ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save status</button></div>
        </form>
      </div>
    </section>
<?php endif; ?>

    <section class="set-pane" id="sec-security" role="tabpanel" data-sec="security">
      <header class="set-head"><h2>Password</h2><p>Use at least <?= (int)cfg('security.min_password_length') ?> characters with letters and numbers. After <?= sec('max_failed_per_email') ?> wrong tries, sign-in locks for <?= sec('lockout_minutes') ?> minutes.</p></header>
      <div class="card" id="pwCard">
        <form class="card-pad set-form" id="pwForm" novalidate>
          <div class="form-msg" role="alert"></div>
          <div class="field"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
          <div class="row two">
            <div class="field"><label for="password">New password</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" data-strength="pwBar" required><div class="strength" id="pwBar"><i></i></div></div>
            <div class="field"><label for="password_confirm">Confirm</label><input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
          </div>
          <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('lock') ?> Update password</button></div>
        </form>
      </div>
    </section>

    <section class="set-pane" id="sec-linked" role="tabpanel" data-sec="linked">
      <header class="set-head"><h2>Linked accounts</h2><p>Link Google to sign in with one tap. Your username and password keep working.</p></header>
      <?php if ($flash): ?><div class="form-msg show <?= $flash['ok'] ? 'ok' : 'error' ?>" role="status"><?= e($flash['text']) ?></div><?php endif; ?>
      <div class="card">
        <div class="card-pad link-row">
          <span class="link-logo" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.5 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.9a5.04 5.04 0 0 1-2.19 3.31v2.75h3.54c2.07-1.91 3.25-4.72 3.25-8.07z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.54-2.75c-.98.66-2.24 1.06-3.74 1.06-2.87 0-5.3-1.94-6.17-4.55H2.17v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.83 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.17a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.35c1.62 0 3.07.56 4.21 1.65l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.17 7.06l3.66 2.84C6.7 7.29 9.13 5.35 12 5.35z"/></svg></span>
          <div class="link-main">
            <b>Google</b>
            <?php if ($u['google_sub']): ?>
              <span class="link-on"><?= icon('checkCircle') ?> Linked to <?= e($u['google_email']) ?></span>
              <small>Since <?= e(date('M j, Y', strtotime((string)$u['google_linked_at']))) ?></small>
            <?php elseif (google_enabled()): ?>
              <span>Not linked<?= cfg('google.allowed_domain') ? ' · use your @' . e(cfg('google.allowed_domain')) . ' account' : '' ?></span>
            <?php else: ?>
              <span>Not available yet<?= $role === 'admin' ? ': fill in the <code>google</code> block in config/config.php (see README_DEPLOY.md).' : '. The SASO office has not turned it on.' ?></span>
            <?php endif; ?>
          </div>
          <?php if ($u['google_sub']): ?>
            <button type="button" class="btn btn-ghost" id="googleUnlink"><?= icon('unlink') ?> Unlink</button>
          <?php elseif (google_enabled()): ?>
            <a class="btn btn-primary" href="<?= e(url('google-start.php?mode=link')) ?>"><?= icon('link') ?> Link Google</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="set-pane" id="sec-notifications" role="tabpanel" data-sec="notifications">
      <header class="set-head"><h2>Notifications</h2><p>Choose how CampusVoice keeps you posted.</p></header>
      <div class="card">
        <form class="card-pad set-form" id="prefForm">
          <div class="form-msg" role="alert"></div>
          <label class="switch-row"><span><b>In-app notifications</b><small>Show updates in the bell and the Notifications page.</small></span><input type="checkbox" class="switch" name="pref_notify_inapp" value="1" <?= $u['pref_notify_inapp'] ? 'checked' : '' ?>></label>
          <label class="switch-row"><span><b>Email me important updates</b><small>Password resets and urgent notices (when an email is on file).</small></span><input type="checkbox" class="switch" name="pref_notify_email" value="1" <?= $u['pref_notify_email'] ? 'checked' : '' ?>></label>
<?php if ($role === 'student'): ?>
          <label class="switch-row"><span><b>Send concerns anonymously by default</b><small>Staff will not see your name. You can still change it on each concern.</small></span><input type="checkbox" class="switch" name="pref_default_anonymous" value="1" <?= $u['pref_default_anonymous'] ? 'checked' : '' ?>></label>
<?php endif; ?>
          <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
        </form>
      </div>
    </section>

    <section class="set-pane" id="sec-appearance" role="tabpanel" data-sec="appearance">
      <header class="set-head"><h2>Appearance</h2><p>Saved on this browser.</p></header>
      <div class="card">
        <div class="card-pad set-form">
          <div class="theme-pick" id="themeSeg" role="radiogroup" aria-label="Theme">
            <button type="button" role="radio" data-theme-opt="light" aria-checked="false"><span class="tp-prev light"><i></i><i></i></span><?= icon('sun') ?> Light</button>
            <button type="button" role="radio" data-theme-opt="dark" aria-checked="false"><span class="tp-prev dark"><i></i><i></i></span><?= icon('moon') ?> Dark</button>
            <button type="button" role="radio" data-theme-opt="system" aria-checked="false"><span class="tp-prev system"><i></i><i></i></span><?= icon('monitor') ?> System</button>
          </div>
          <p class="hint" id="themeHint">System follows your device and switches automatically.</p>
        </div>
      </div>
    </section>

<?php foreach ($systemSections as $s): ?>
    <section class="set-pane" id="sec-<?= e($s['id']) ?>" role="tabpanel" data-sec="<?= e($s['id']) ?>">
      <header class="set-head"><h2><?= e($s['label']) ?></h2><p><?= e($s['desc'] ?? $s['hint']) ?></p></header>
      <?= $s['html'] ?>
    </section>
<?php endforeach; ?>
  </div>
</section>
<?php
    panel_close(array_merge(['shared.js', 'course-picker.js'], $extraJs));
}
