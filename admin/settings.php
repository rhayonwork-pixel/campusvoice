<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/shared_pages.php';
$u = require_role('admin');

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$sections = [];

ob_start(); ?>
<div class="card">
  <form class="card-pad set-form" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <label class="switch-row"><span><b>Route new concerns automatically</b><small>Each new concern goes to the least-busy handler of its type (set handlers in Campus Data → Concern types). Work keeps moving even when no admin is online. Types with no handler wait in the Assignment Queue.</small></span>
      <input type="hidden" name="auto_assign" value="0"><input type="checkbox" class="switch" name="auto_assign" value="1" id="auto_assign"></label>
    <a class="set-link-inline" href="<?= e(url('admin/campus-data.php')) ?>"><?= icon('route') ?> Choose who handles each concern type</a>
    <h4 class="form-sec">Target dates</h4>
    <p class="hint">Hours allowed to resolve a concern, by severity. Concerns past this time show as overdue.</p>
    <div class="row three">
      <div class="field"><label for="sla_minor">Minor (hrs)</label><input class="input" id="sla_minor" name="sla_minor" type="number" min="1" max="2000"></div>
      <div class="field"><label for="sla_major">Major (hrs)</label><input class="input" id="sla_major" name="sla_major" type="number" min="1" max="2000"></div>
      <div class="field"><label for="sla_urgent">Urgent (hrs)</label><input class="input" id="sla_urgent" name="sla_urgent" type="number" min="1" max="2000"></div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save concern rules</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'rules', 'label' => 'Concern rules', 'icon' => 'zap', 'hint' => 'Routing and target dates', 'desc' => 'How new concerns are routed and how long each severity may take.', 'html' => ob_get_clean()];

ob_start(); ?>
<div class="card">
  <form class="card-pad set-form" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="row three">
      <div class="field"><label for="sec_max_failed">Wrong tries before lock</label><input class="input" id="sec_max_failed" name="sec_max_failed" type="number" min="3" max="20"></div>
      <div class="field"><label for="sec_lockout_minutes">Lock time (minutes)</label><input class="input" id="sec_lockout_minutes" name="sec_lockout_minutes" type="number" min="1" max="240"></div>
      <div class="field"><label for="sec_idle_minutes">Idle sign-out (min)</label><input class="input" id="sec_idle_minutes" name="sec_idle_minutes" type="number" min="5" max="480"></div>
    </div>
    <h4 class="form-sec">Accounts</h4>
    <label class="switch-row"><span><b>Public student sign-up</b><small>Off: students get their account from an imported masterlist (Users &amp; Staff → Import students). Recommended.</small></span>
      <input type="hidden" name="student_signup" value="0"><input type="checkbox" class="switch" name="student_signup" value="1" id="student_signup"></label>
    <div class="field"><label for="signup_email_domain">University email domain</label><input class="input" id="signup_email_domain" name="signup_email_domain" placeholder="cityofmalabonuniversity.edu.ph"><span class="hint">Used for sign-up checks and for the default student password <code>{StudentID}@domain</code>.</span></div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save sign-in settings</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'signin', 'label' => 'Sign-in & security', 'icon' => 'shield', 'hint' => 'Lockout, sign-up', 'desc' => 'Protect accounts from guessing and control how students get accounts.', 'html' => ob_get_clean()];

ob_start(); ?>
<div class="card">
  <form class="card-pad set-form" id="hoursForm">
    <div class="form-msg" role="alert"></div>
    <div class="hours-list">
    <?php foreach ($days as $i => $d): ?>
      <div class="hours-row"><b><?= $d ?></b><input type="checkbox" class="switch" name="active_<?= $i ?>" value="1" aria-label="<?= $d ?> open"><input class="input" type="time" name="open_<?= $i ?>" aria-label="<?= $d ?> opens"><input class="input" type="time" name="close_<?= $i ?>" aria-label="<?= $d ?> closes"></div>
    <?php endforeach; ?>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save hours</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'hours', 'label' => 'Office hours', 'icon' => 'calendar', 'hint' => 'When SASO is open', 'desc' => 'Shown to students and used on the calendar.', 'html' => ob_get_clean()];

ob_start(); ?>
<div class="card">
  <form class="card-pad set-form" data-settings novalidate>
    <div class="form-msg" role="alert"></div>
    <div class="row two">
      <div class="field"><label for="contact_email">Contact email</label><input class="input" id="contact_email" name="contact_email" type="email"></div>
      <div class="field"><label for="contact_address">Contact address</label><input class="input" id="contact_address" name="contact_address"></div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save contact</button></div>
  </form>
</div>
<div class="card">
  <div class="card-head"><h3><?= icon('mail') ?> Outgoing email</h3></div>
  <div class="card-pad set-form">
    <dl class="kv" style="padding:0;gap:8px 16px">
      <dt>Sending via</dt><dd><?= cfg('smtp.enabled') ? 'Gmail SMTP' : 'PHP mail() (basic)' ?></dd>
      <dt>Sender</dt><dd><?= e(cfg('smtp.enabled') && cfg('smtp.user') ? cfg('smtp.user') : cfg('app.mail_from')) ?></dd>
      <dt>Gmail login</dt><dd><?= cfg('smtp.enabled') ? (cfg('smtp.user') && cfg('smtp.pass') ? 'Email and app password are set' : 'Missing email or app password') : 'Off — turn on in config/config.php' ?></dd>
    </dl>
    <div class="form-msg" id="mailMsg" role="alert"></div>
    <div class="toolbar">
      <button class="btn btn-primary" type="button" id="testMail"><?= icon('mail') ?> Send test email</button>
      <a class="btn btn-ghost" href="<?= e(url('admin/email-preview.php')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Preview email</a>
    </div>
    <p class="hint">The test goes to your own email address (<?= e((string)$u['email']) ?>).</p>
  </div>
</div>
<?php $sections[] = ['id' => 'contact', 'label' => 'Contact & email', 'icon' => 'mail', 'hint' => 'Public contact, test email', 'desc' => 'What students see on the landing page, and the email sender.', 'html' => ob_get_clean()];

render_settings_page($u, $sections, ['admin-manage.js']);
