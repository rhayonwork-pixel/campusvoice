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

/** Account settings usable by every role. Role-specific blocks appear only for the right role. */
function render_settings_page(array $u, string $extraHtml = '', array $extraJs = []): void
{
    $role = $u['role'];
    $force = !empty($_GET['force']) || (int)$u['must_change_password'] === 1;
    panel_open($u, $role === 'admin' ? 'System Settings' : 'Settings', 'settings.php', $role === 'admin' ? ['admin.css'] : []);
    page_head($role === 'admin' ? 'System Settings' : 'Settings', 'Manage your account' . ($role === 'admin' ? ' and the system.' : '.'));
    if ($force): ?>
<section class="note warn"><?= icon('lock') ?><div><b>Please change your password.</b> You are using a temporary password. Set a new one to continue.</div></section>
    <?php endif; ?>

<section class="grid-eq" id="settingsGrid">
  <div class="card">
    <div class="card-head"><h3>Profile</h3></div>
    <form class="card-pad" id="profileForm" novalidate>
      <div class="form-msg" role="alert"></div>
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px">
        <span class="avatar" style="width:64px;height:64px;font-size:1.5rem"><?php if ($u['avatar']): ?><img src="<?= e(url('file.php?avatar=' . (int)$u['user_id'] . '&v=' . urlencode((string)$u['updated_at']))) ?>" alt=""><?php else: echo e(strtoupper(mb_substr($u['first_name'], 0, 1))); endif; ?></span>
        <div class="toolbar"><label class="btn btn-ghost btn-sm" style="cursor:pointer"><?= icon('camera') ?> Change photo<input type="file" id="avatarIn" accept="image/jpeg,image/png,image/webp" hidden></label>
          <?php if ($u['avatar']): ?><button type="button" class="btn btn-ghost btn-sm" id="avatarDel"><?= icon('trash') ?> Remove</button><?php endif; ?></div>
      </div>
      <div class="row two">
        <div class="field"><label>ID</label><input class="input" value="<?= e($u['school_id']) ?>" disabled></div>
        <div class="field"><label>Email</label><input class="input" value="<?= e($u['email']) ?>" disabled></div>
      </div>
      <div class="row three">
        <div class="field"><label for="first_name">First name</label><input class="input" id="first_name" name="first_name" value="<?= e($u['first_name']) ?>" required></div>
        <div class="field"><label for="middle_initial">M.I.</label><input class="input" id="middle_initial" name="middle_initial" maxlength="2" value="<?= e($u['middle_initial']) ?>"></div>
        <div class="field"><label for="last_name">Last name</label><input class="input" id="last_name" name="last_name" value="<?= e($u['last_name']) ?>" required></div>
      </div>
      <div class="field"><label for="contact_number">Contact number</label><input class="input" id="contact_number" name="contact_number" value="<?= e($u['contact_number']) ?>" placeholder="09XX XXX XXXX"></div>
      <?php if ($role === 'student'): ?>
      <div class="row three">
        <div class="field"><label for="course">Course</label><input class="input" id="course" name="course" value="<?= e($u['course']) ?>"></div>
        <div class="field"><label for="year_level">Year</label><select class="select" id="year_level" name="year_level"><?php for ($i = 1; $i <= 5; $i++) echo '<option value="' . $i . '"' . ((int)$u['year_level'] === $i ? ' selected' : '') . '>Year ' . $i . '</option>'; ?></select></div>
        <div class="field"><label for="section">Section</label><input class="input" id="section" name="section" value="<?= e($u['section']) ?>"></div>
      </div>
      <?php else: ?>
      <div class="field"><label for="position">Position</label><input class="input" id="position" name="position" value="<?= e($u['position']) ?>"></div>
      <?php endif; ?>
      <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save profile</button>
    </form>
  </div>

  <div style="display:flex;flex-direction:column;gap:var(--gap)">
    <div class="card" id="pwCard">
      <div class="card-head"><h3>Change password</h3></div>
      <form class="card-pad" id="pwForm" novalidate>
        <div class="form-msg" role="alert"></div>
        <div class="field"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div class="row two">
          <div class="field"><label for="password">New password</label><input class="input" id="password" name="password" type="password" autocomplete="new-password" data-strength="pwBar" required><div class="strength" id="pwBar"><i></i></div></div>
          <div class="field"><label for="password_confirm">Confirm</label><input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
        </div>
        <button class="btn btn-primary" type="submit"><?= icon('lock') ?> Update password</button>
      </form>
    </div>

<?php if ($role === 'staff'): ?>
    <div class="card">
      <div class="card-head"><h3>Availability</h3></div>
      <form class="card-pad" id="availForm">
        <div class="form-msg" role="alert"></div>
        <div class="field"><label for="availability">Your status</label>
          <select class="select" id="availability" name="availability">
            <?php foreach (['available' => 'Available', 'busy' => 'Busy', 'on_leave' => 'On leave'] as $k => $v) echo '<option value="' . $k . '"' . ($u['availability'] === $k ? ' selected' : '') . '>' . $v . '</option>'; ?>
          </select><span class="hint">Admins see this when assigning work. “On leave” hides you from suggestions.</span></div>
        <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save status</button>
      </form>
    </div>
<?php endif; ?>

    <div class="card">
      <div class="card-head"><h3>Appearance</h3></div>
      <div class="card-pad">
        <div class="seg" id="themeSeg" role="radiogroup" aria-label="Theme">
          <button type="button" role="radio" data-theme-opt="light" aria-checked="false"><?= icon('sun') ?> Light</button>
          <button type="button" role="radio" data-theme-opt="dark" aria-checked="false"><?= icon('moon') ?> Dark</button>
          <button type="button" role="radio" data-theme-opt="system" aria-checked="false"><?= icon('monitor') ?> System</button>
        </div>
        <p class="hint" id="themeHint" style="margin-top:10px">System follows your device and switches automatically. Saved on this browser.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Preferences</h3></div>
      <form class="card-pad" id="prefForm">
        <div class="form-msg" role="alert"></div>
        <label class="check" style="margin-bottom:12px"><input type="checkbox" name="pref_notify_inapp" value="1" <?= $u['pref_notify_inapp'] ? 'checked' : '' ?>><span><b>In-app notifications</b><br><small class="cell-sub">Show updates in the bell and Notifications page.</small></span></label>
        <label class="check" style="margin-bottom:12px"><input type="checkbox" name="pref_notify_email" value="1" <?= $u['pref_notify_email'] ? 'checked' : '' ?>><span><b>Email me important updates</b><br><small class="cell-sub">Password resets and urgent notices (when email is available).</small></span></label>
<?php if ($role === 'student'): ?>
        <label class="check" style="margin-bottom:14px"><input type="checkbox" name="pref_default_anonymous" value="1" <?= $u['pref_default_anonymous'] ? 'checked' : '' ?>><span><b>Send concerns anonymously by default</b><br><small class="cell-sub">You can still change it on each concern.</small></span></label>
<?php endif; ?>
        <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save preferences</button>
      </form>
    </div>
  </div>
</section>
<?= $extraHtml ?>
<?php
    panel_close(array_merge(['shared.js'], $extraJs));
}
