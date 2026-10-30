<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if ($u = current_user()) redirect(home_for($u['role']));

$notice = '';
if (!empty($_SESSION['flash_login'])) { $notice = $_SESSION['flash_login']; unset($_SESSION['flash_login']); }
elseif (isset($_GET['registered'])) $notice = 'Account created. Sign in to continue.';
elseif (isset($_GET['reset'])) $notice = 'Password updated. Sign in with your new password.';

html_head('Sign in', ['auth.css'], 'authpage');
auth_open('Welcome back', 'Sign in to your CampusVoice portal.');
?>
      <div class="form-msg <?= $notice ? 'show ok' : '' ?>" id="notice"><?= e($notice) ?></div>
      <form id="loginForm" novalidate>
        <div class="form-msg" id="loginMsg" role="alert"></div>
        <div class="field">
          <label for="email">University email</label>
          <input class="input" id="email" name="email" type="email" autocomplete="username" placeholder="name@cityofmalabonuniversity.edu.ph" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
            <button type="button" class="reveal" data-reveal="password">SHOW</button>
          </div>
        </div>
        <div class="auth-row"><span></span><a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      </form>
      <p class="auth-alt">New student? <a href="<?= e(url('signup.php')) ?>">Create an account</a></p>
<?php auth_close(); html_foot(['auth.js']); ?>
