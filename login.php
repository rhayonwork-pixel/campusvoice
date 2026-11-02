<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/turnstile.php';
require_once __DIR__ . '/includes/google.php';

if ($u = current_user()) redirect(home_for($u['role']));

$notice = '';
if (!empty($_SESSION['flash_login'])) { $notice = $_SESSION['flash_login']; unset($_SESSION['flash_login']); }
elseif (isset($_GET['registered'])) $notice = 'Account created. Sign in to continue.';
elseif (isset($_GET['reset'])) $notice = 'Password updated. Sign in with your new password.';
$noticeBad = false;
if (!empty($_SESSION['flash_login_error'])) { $notice = $_SESSION['flash_login_error']; $noticeBad = true; unset($_SESSION['flash_login_error']); }
$googleOn = google_enabled();
$signupOn = setting('student_signup', '0') === '1';
$maxTries = sec('max_failed_per_email');

html_head('Sign in', ['auth.css'], 'authpage');
auth_open('Welcome back', 'Sign in to your CampusVoice portal.');
?>
      <div class="form-msg <?= $notice ? 'show ' . ($noticeBad ? 'err' : 'ok') : '' ?>" id="notice"><?= e($notice) ?></div>
      <form id="loginForm" novalidate data-max="<?= (int)$maxTries ?>">
        <div class="form-msg" id="loginMsg" role="alert"></div>
        <div class="lock-box" id="lockBox" hidden aria-live="polite">
          <svg class="lock-ring" viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="19" class="lr-track"/><circle cx="22" cy="22" r="19" class="lr-fill" pathLength="1"/></svg>
          <div><strong>Sign-in locked</strong><span>Too many wrong passwords. Try again in <b id="lockTime">0:00</b></span></div>
        </div>
        <div class="field">
          <label for="username">Username</label>
          <input class="input" id="username" name="username" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="Your student ID, e.g. 202300378" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
            <button type="button" class="reveal" data-reveal="password">SHOW</button>
          </div>
        </div>
        <div class="auth-row"><span class="tries" id="tries" aria-live="polite" hidden></span><a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></div>
        <?= turnstile_widget() ?>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      </form>
      <?php if ($googleOn): ?>
      <div class="or"><span>or</span></div>
      <a class="google-btn" href="<?= e(url('google-start.php?mode=login')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.5 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.9a5.04 5.04 0 0 1-2.19 3.31v2.75h3.54c2.07-1.91 3.25-4.72 3.25-8.07z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.54-2.75c-.98.66-2.24 1.06-3.74 1.06-2.87 0-5.3-1.94-6.17-4.55H2.17v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.83 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.17a11 11 0 0 0 0 9.88l3.66-2.84z"/><path fill="#EA4335" d="M12 5.35c1.62 0 3.07.56 4.21 1.65l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.17 7.06l3.66 2.84C6.7 7.29 9.13 5.35 12 5.35z"/></svg>
        <span>Continue with Google</span>
      </a>
      <p class="auth-note">Works after you link Google in <b>Settings → Linked accounts</b>.</p>
      <?php endif; ?>
      <?php if ($signupOn): ?>
      <p class="auth-alt">New student? <a href="<?= e(url('signup.php')) ?>">Create an account</a></p>
      <?php else: ?>
      <p class="auth-alt">No account yet? Your username and first password come from the SASO office.</p>
      <?php endif; ?>
<?php auth_close(); html_foot(['auth.js'], turnstile_scripts()); ?>
