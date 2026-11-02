<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/turnstile.php';
if ($u = current_user()) redirect(home_for($u['role']));

html_head('Forgot password', ['auth.css'], 'authpage');
auth_open('Reset your password', "Enter your username or email and we'll send a reset link to the email on your account.");
?>
      <form id="forgotForm" novalidate>
        <div class="form-msg" role="alert"></div>
        <div class="field"><label for="username">Username or email</label><input class="input" id="username" name="username" autocomplete="username" placeholder="Student ID, e.g. 202300378" required></div>
        <?= turnstile_widget() ?>
        <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
      </form>
      <p class="auth-alt">Have a reset code from the administrator? <a href="<?= e(url('reset-password.php')) ?>">Enter it here</a></p>
      <p class="auth-alt"><a href="<?= e(url('login.php')) ?>">← Back to sign in</a></p>
<?php auth_close(); html_foot(['auth.js'], turnstile_scripts()); ?>
