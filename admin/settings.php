<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/shared_pages.php';
require_once __DIR__ . '/../includes/google.php';
$u = require_role('admin');

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$sections = [];

/* ---------- Concern rules ---------- */
ob_start(); ?>
<div class="card">
  <form class="set-form flush" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('route') ?></span><div><h3>Automatic routing</h3><p>Work keeps moving even when no admin is online.</p></div></div>
      <label class="srow"><span class="srow-t"><b>Route new concerns automatically</b><small>Each new concern goes to the least-busy handler of its type. Types with no handler wait in the Assignment Queue.</small></span>
        <input type="hidden" name="auto_assign" value="0"><input type="checkbox" class="switch" name="auto_assign" value="1" id="auto_assign"></label>
      <a class="set-link-inline" href="<?= e(url('admin/campus-data.php')) ?>"><?= icon('users') ?> Choose who handles each concern type</a>
    </div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('clock') ?></span><div><h3>Target dates</h3><p>How long each severity may take. Concerns past it show as overdue.</p></div></div>
      <div class="sla-grid">
        <label class="sla s-Minor"><span class="sla-tag">Minor</span><span class="unit"><input class="input" id="sla_minor" name="sla_minor" type="number" min="1" max="2000" aria-label="Minor hours"><i>hours</i></span><small>Not harmful</small></label>
        <label class="sla s-Major"><span class="sla-tag">Major</span><span class="unit"><input class="input" id="sla_major" name="sla_major" type="number" min="1" max="2000" aria-label="Major hours"><i>hours</i></span><small>Disrupts use</small></label>
        <label class="sla s-Urgent"><span class="sla-tag">Urgent</span><span class="unit"><input class="input" id="sla_urgent" name="sla_urgent" type="number" min="1" max="2000" aria-label="Urgent hours"><i>hours</i></span><small>Safety risk</small></label>
      </div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save concern rules</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'rules', 'label' => 'Concern rules', 'icon' => 'zap', 'hint' => 'Routing and target dates', 'desc' => 'How new concerns are routed and how long each severity may take.', 'html' => ob_get_clean()];

/* ---------- Sign-in & security ---------- */
ob_start(); ?>
<div class="card">
  <form class="set-form flush" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('shield') ?></span><div><h3>Protection</h3><p>Slows down password guessing and signs out forgotten sessions.</p></div></div>
      <div class="srow"><span class="srow-t"><b>Wrong tries before sign-in locks</b><small>Counted per account. Students see how many tries are left.</small></span>
        <span class="unit"><input class="input" id="sec_max_failed" name="sec_max_failed" type="number" min="3" max="20" aria-label="Wrong tries"><i>tries</i></span></div>
      <div class="srow"><span class="srow-t"><b>Lock time</b><small>How long the countdown runs after the last wrong try.</small></span>
        <span class="unit"><input class="input" id="sec_lockout_minutes" name="sec_lockout_minutes" type="number" min="1" max="240" aria-label="Lock minutes"><i>minutes</i></span></div>
      <div class="srow"><span class="srow-t"><b>Idle sign-out</b><small>Sign out someone who stopped using the system.</small></span>
        <span class="unit"><input class="input" id="sec_idle_minutes" name="sec_idle_minutes" type="number" min="5" max="480" aria-label="Idle minutes"><i>minutes</i></span></div>
    </div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('users') ?></span><div><h3>Student accounts</h3><p>How students get an account.</p></div></div>
      <label class="srow"><span class="srow-t"><b>Public student sign-up</b><small>Off (recommended): accounts come from an imported masterlist in Users &amp; Staff → Import students.</small></span>
        <input type="hidden" name="student_signup" value="0"><input type="checkbox" class="switch" name="student_signup" value="1" id="student_signup"></label>
      <div class="srow"><span class="srow-t"><b>University email domain</b><small>Used for the first password <code>{StudentID}@domain</code> and sign-up checks.</small></span>
        <input class="input wide" id="signup_email_domain" name="signup_email_domain" placeholder="cityofmalabonuniversity.edu.ph" aria-label="Email domain"></div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save sign-in settings</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'signin', 'label' => 'Sign-in & security', 'icon' => 'shield', 'hint' => 'Lockout, sign-up', 'desc' => 'Protect accounts from guessing and control how students get accounts.', 'html' => ob_get_clean()];

/* ---------- Google sign-in ---------- */
$gReady = google_enabled();
$gHasId = (bool)google_cfg('client_id'); $gHasSecret = (bool)google_cfg('client_secret');
$redirect = google_redirect_uri();
$domain = (string)google_cfg('allowed_domain');
$idShown = (string)google_cfg('client_id');
$linked = (int)q('SELECT COUNT(*) FROM users WHERE google_sub IS NOT NULL')->fetchColumn();
$total = (int)q('SELECT COUNT(*) FROM users')->fetchColumn();
ob_start(); ?>
<div class="card">
  <div class="g-status <?= $gReady ? 'ready' : '' ?>">
    <span class="g-badge"><?= $gReady ? icon('checkCircle') : icon('alert') ?></span>
    <div><b><?= $gReady ? 'Google sign-in is ON' : 'Google sign-in is OFF' ?></b>
      <span><?= $gReady ? $linked . ' of ' . $total . ' accounts have linked Google.' : 'Follow the 5 steps below, paste two codes, and switch it on. No coding needed.' ?></span></div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>1 · Get the two codes from Google</h3></div>
  <ol class="guide">
    <li><b>Open Google Cloud</b> and sign in with the SASO Gmail: <a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener">console.cloud.google.com</a>. Press <em>Create project</em>, name it <em>CampusVoice</em>.</li>
    <li><b>Make the sign-in screen.</b> Open the menu → <em>APIs &amp; Services</em> → <em>OAuth consent screen</em> → <em>Get started</em>. App name: <em>CampusVoice</em>. Audience: <em>External</em>. Add your email, then Finish.</li>
    <li><b>Create the codes.</b> Go to <em>Credentials</em> → <em>Create credentials</em> → <em>OAuth client ID</em> → type <em>Web application</em>.</li>
    <li><b>Paste this link</b> in <em>Authorized redirect URIs</em> (press Add URI), then Create:
      <div class="copy-box"><code id="gRedirect"><?= e($redirect) ?></code><button type="button" class="btn btn-ghost btn-sm" data-copy="#gRedirect"><?= icon('file') ?> Copy</button></div></li>
    <li><b>Google shows two codes</b>: a <em>Client ID</em> and a <em>Client secret</em>. Copy them into the boxes below.</li>
  </ol>
</div>

<div class="card">
  <form class="set-form flush" id="googleForm" novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('key') ?></span><div><h3>2 · Paste them here</h3><p>Saved safely. The secret is hidden once saved.</p></div></div>
      <div class="field"><label for="g_client_id">Client ID</label><input class="input" id="g_client_id" name="client_id" value="<?= e($idShown) ?>" placeholder="123456-abc.apps.googleusercontent.com" autocomplete="off" spellcheck="false"></div>
      <div class="field"><label for="g_client_secret">Client secret</label><input class="input" id="g_client_secret" name="client_secret" type="password" autocomplete="new-password" placeholder="<?= $gHasSecret ? '•••••••• saved — leave empty to keep it' : 'Paste the secret from Google' ?>"></div>
      <div class="field"><label for="g_domain">Only allow school Google accounts <span class="opt">recommended</span></label><input class="input" id="g_domain" name="domain" value="<?= e($domain) ?>" placeholder="cityofmalabonuniversity.edu.ph" autocomplete="off"><span class="hint">Students with other Gmail accounts will be refused. Leave empty to allow any Google account.</span></div>
      <label class="srow"><span class="srow-t"><b>Turn on Google sign-in</b><small>Shows “Continue with Google” on the login page and “Link Google” in students’ Settings.</small></span>
        <input type="hidden" name="enabled" value="0"><input type="checkbox" class="switch" name="enabled" value="1" <?= google_cfg('enabled') ? 'checked' : '' ?>></label>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save Google settings</button></div>
  </form>
</div>

<p class="guide-note"><?= icon('alert') ?><span><b>Good to know:</b> while the Google screen says <em>Testing</em>, only emails you add under <em>Test users</em> can link. Press <em>Publish app</em> there so every student can. Then each student opens <em>Settings → Linked accounts → Link Google</em> once.</span></p>
<?php $sections[] = ['id' => 'google', 'label' => 'Google sign-in', 'icon' => 'link', 'hint' => $gReady ? 'On · ' . $linked . ' linked' : 'Off · setup steps', 'desc' => 'Let students link their Google account and sign in with one tap.', 'html' => ob_get_clean()];

/* ---------- Office hours ---------- */
ob_start(); ?>
<div class="card">
  <form class="set-form flush" id="hoursForm">
    <div class="form-msg" role="alert"></div>
    <div class="hours-list">
    <?php foreach ($days as $i => $d): ?>
      <div class="hours-row"><b><?= $d ?></b>
        <label class="hours-sw"><input type="checkbox" class="switch" name="active_<?= $i ?>" value="1" aria-label="<?= $d ?> open"><span>Open</span></label>
        <span class="hours-times"><input class="input" type="time" name="open_<?= $i ?>" aria-label="<?= $d ?> opens"><i>to</i><input class="input" type="time" name="close_<?= $i ?>" aria-label="<?= $d ?> closes"></span></div>
    <?php endforeach; ?>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save hours</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'hours', 'label' => 'Office hours', 'icon' => 'calendar', 'hint' => 'When SASO is open', 'desc' => 'Shown to students and used on the calendar.', 'html' => ob_get_clean()];

/* ---------- Contact & email ---------- */
ob_start(); ?>
<div class="card">
  <form class="set-form flush" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="blk">
      <div class="blk-head"><span class="blk-ic"><?= icon('pin') ?></span><div><h3>Public contact</h3><p>Shown on the landing page.</p></div></div>
      <div class="row two">
        <div class="field"><label for="contact_email">Contact email</label><input class="input" id="contact_email" name="contact_email" type="email"></div>
        <div class="field"><label for="contact_address">Contact address</label><input class="input" id="contact_address" name="contact_address"></div>
      </div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save contact</button></div>
  </form>
</div>
<div class="card">
  <div class="card-pad set-form flush">
    <div class="blk-head"><span class="blk-ic"><?= icon('mail') ?></span><div><h3>Outgoing email</h3><p>Password reset links and notices.</p></div></div>
    <div class="mail-grid">
      <div><small>Sending via</small><b><?= cfg('smtp.enabled') ? 'Gmail SMTP' : 'PHP mail() (basic)' ?></b></div>
      <div><small>Sender</small><b><?= e(cfg('smtp.enabled') && cfg('smtp.user') ? cfg('smtp.user') : cfg('app.mail_from')) ?></b></div>
      <div><small>Gmail login</small><b><?= cfg('smtp.enabled') ? (cfg('smtp.user') && cfg('smtp.pass') ? 'Set' : 'Missing') : 'Off' ?></b></div>
    </div>
    <div class="form-msg" id="mailMsg" role="alert"></div>
    <div class="toolbar">
      <button class="btn btn-primary" type="button" id="testMail"><?= icon('mail') ?> Send test email</button>
      <a class="btn btn-ghost" href="<?= e(url('admin/email-preview.php')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Preview email</a>
    </div>
    <p class="hint" style="margin:0">The test goes to <?= e((string)$u['email']) ?>. InfinityFree may block outgoing mail; reset codes from Users &amp; Staff always work.</p>
  </div>
</div>
<?php $sections[] = ['id' => 'contact', 'label' => 'Contact & email', 'icon' => 'mail', 'hint' => 'Public contact, test email', 'desc' => 'What students see on the landing page, and the email sender.', 'html' => ob_get_clean()];

render_settings_page($u, $sections, ['admin-manage.js']);
