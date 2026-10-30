<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u['role']));

html_head('Forgot password', ['auth.css'], 'authpage');
auth_open('Reset your password', "Enter your university email and we'll send a reset link.");
?>
      <form id="forgotForm" novalidate>
        <div class="form-msg" role="alert"></div>
        <div class="field"><label for="email">University email</label><input class="input" id="email" name="email" type="email" autocomplete="email" required></div>
        <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
      </form>
      <p class="auth-alt">Have a reset code from the administrator? <a href="<?= e(url('reset-password.php')) ?>">Enter it here</a></p>
      <p class="auth-alt"><a href="<?= e(url('login.php')) ?>">← Back to sign in</a></p>
<?php auth_close(); html_foot(['auth.js']); ?>
