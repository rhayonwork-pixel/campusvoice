<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u['role']));

$token = isset($_GET['token']) && is_string($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', $_GET['token']) : '';

html_head('Set a new password', ['auth.css'], 'authpage');
auth_open('Set a new password', $token ? 'Choose a new password for your account.' : 'Enter your email, the reset code, and a new password.');
?>
      <form id="resetForm" novalidate>
        <div class="form-msg" role="alert"></div>
        <?php if ($token): ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
        <?php else: ?>
          <div class="field"><label for="email">University email</label><input class="input" id="email" name="email" type="email" required></div>
          <div class="field"><label for="code">Reset code</label><input class="input" id="code" name="code" maxlength="12" autocomplete="one-time-code" style="text-transform:uppercase;letter-spacing:.15em" required></div>
        <?php endif; ?>
        <div class="field"><label for="password">New password</label>
          <div class="input-wrap"><input class="input" id="password" name="password" type="password" autocomplete="new-password" data-strength="pwBar" required><button type="button" class="reveal" data-reveal="password">SHOW</button></div>
          <div class="strength" id="pwBar"><i></i></div></div>
        <div class="field"><label for="password_confirm">Confirm new password</label><input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
        <button class="btn btn-primary btn-block" type="submit">Update password</button>
      </form>
      <p class="auth-alt"><a href="<?= e(url('login.php')) ?>">← Back to sign in</a></p>
<?php auth_close(); html_foot(['auth.js']); ?>
