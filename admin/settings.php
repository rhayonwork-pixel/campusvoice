<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/shared_pages.php';
$u = require_role('admin');

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
ob_start(); ?>
<h2 class="page-sub">System</h2>
<section class="set-grid" id="systemSettings">
  <div class="card">
    <div class="card-head"><h3>Concern deadlines</h3></div>
    <form class="card-pad" id="sysForm" novalidate>
      <div class="form-msg" role="alert"></div>
      <p class="hint">Hours allowed to resolve a concern, by severity.</p>
      <div class="row three">
        <div class="field"><label for="sla_minor">Minor (hrs)</label><input class="input" id="sla_minor" name="sla_minor" type="number" min="1" max="2000"></div>
        <div class="field"><label for="sla_major">Major (hrs)</label><input class="input" id="sla_major" name="sla_major" type="number" min="1" max="2000"></div>
        <div class="field"><label for="sla_urgent">Urgent (hrs)</label><input class="input" id="sla_urgent" name="sla_urgent" type="number" min="1" max="2000"></div>
      </div>
      <h4 class="form-sec">Security</h4>
      <div class="row three">
        <div class="field"><label for="sec_max_failed">Max failed logins</label><input class="input" id="sec_max_failed" name="sec_max_failed" type="number" min="3" max="20"></div>
        <div class="field"><label for="sec_lockout_minutes">Lockout (minutes)</label><input class="input" id="sec_lockout_minutes" name="sec_lockout_minutes" type="number" min="1" max="240"></div>
        <div class="field"><label for="sec_idle_minutes">Idle sign-out (min)</label><input class="input" id="sec_idle_minutes" name="sec_idle_minutes" type="number" min="5" max="480"></div>
      </div>
      <h4 class="form-sec">Registration &amp; contact</h4>
      <label class="check"><input type="checkbox" name="student_signup" value="1" id="student_signup"> Allow students to register</label>
      <div class="field"><label for="signup_email_domain">Required email domain</label><input class="input" id="signup_email_domain" name="signup_email_domain" placeholder="school.edu.ph"><span class="hint">Leave empty to accept any email address.</span></div>
      <div class="row">
        <div class="field"><label for="contact_email">Contact email</label><input class="input" id="contact_email" name="contact_email" type="email"></div>
        <div class="field"><label for="contact_address">Contact address</label><input class="input" id="contact_address" name="contact_address"></div>
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save settings</button>
    </form>
  </div>

  <div class="col-stack">
    <div class="card">
      <div class="card-head"><h3>Working hours</h3></div>
      <form class="card-pad" id="hoursForm">
        <div class="form-msg" role="alert"></div>
        <?php foreach ($days as $i => $d): ?>
        <div class="hours-row"><b><?= $d ?></b><input type="checkbox" name="active_<?= $i ?>" value="1" aria-label="<?= $d ?> open"><input class="input" type="time" name="open_<?= $i ?>" aria-label="<?= $d ?> opens"><input class="input" type="time" name="close_<?= $i ?>" aria-label="<?= $d ?> closes"></div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save hours</button>
      </form>
    </div>

    <div class="card">
      <div class="card-head"><h3>Email</h3></div>
      <div class="card-pad" style="display:flex;flex-direction:column;gap:16px">
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
        <p class="hint">The test goes to your own email address (<?= e($u['email']) ?>).</p>
      </div>
    </div>
  </div>
</section>
<?php
$extra = ob_get_clean();
render_settings_page($u, $extra, ['admin-manage.js']);
