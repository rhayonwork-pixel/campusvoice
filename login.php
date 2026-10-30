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
      <!-- PLACEHOLDER: Google sign-in is not connected yet (see 'google' in config/config.php) -->
      <div class="or"><span>or</span></div>
      <button type="button" class="google-btn" id="googleBtn" aria-disabled="true">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.5 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.9a5.04 5.04 0 0 1-2.19 3.31v2.75h3.54c2.07-1.91 3.25-4.72 3.25-8.07z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.54-2.75c-.98.66-2.24 1.06-3.74 1.06-2.87 0-5.3-1.94-6.17-4.55H2.17v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.83 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.17a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.35c1.62 0 3.07.56 4.21 1.65l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.17 7.06l3.66 2.84C6.7 7.29 9.13 5.35 12 5.35z"/></svg>
        <span>Continue with Google</span><em>Soon</em>
      </button>
      <p class="auth-alt">New student? <a href="<?= e(url('signup.php')) ?>">Create an account</a></p>
<?php auth_close(); html_foot(['auth.js']); ?>
