<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/shared_pages.php';
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
      <label class="srow"><span class="srow-t"><b>Route new concerns automatically</b><small>Each new concern goes to the owner of its type, even if the owner is busy. Types with no owner wait in the Assignment Queue.</small></span>
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
      <label class="srow"><span class="srow-t"><b>Public student sign-up</b><small>Off (recommended): accounts come from an imported masterlist in Users → Students → Import students.</small></span>
        <input type="hidden" name="student_signup" value="0"><input type="checkbox" class="switch" name="student_signup" value="1" id="student_signup"></label>
      <div class="srow"><span class="srow-t"><b>University email domain</b><small>Used for the first password <code>{StudentID}@domain</code> and sign-up checks.</small></span>
        <input class="input wide" id="signup_email_domain" name="signup_email_domain" placeholder="cityofmalabonuniversity.edu.ph" aria-label="Email domain"></div>
    </div>
    <div class="set-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save sign-in settings</button></div>
  </form>
</div>
<?php $sections[] = ['id' => 'signin', 'label' => 'Sign-in & security', 'icon' => 'shield', 'hint' => 'Lockout, sign-up', 'desc' => 'Protect accounts from guessing and control how students get accounts.', 'html' => ob_get_clean()];

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
    <p class="hint" style="margin:0">The test goes to <?= e((string)$u['email']) ?>. InfinityFree may block outgoing mail; reset codes from Users always work.</p>
  </div>
</div>
<?php $sections[] = ['id' => 'contact', 'label' => 'Contact & email', 'icon' => 'mail', 'hint' => 'Public contact, test email', 'desc' => 'What students see on the landing page, and the email sender.', 'html' => ob_get_clean()];

render_settings_page($u, $sections, ['admin-manage.js']);
