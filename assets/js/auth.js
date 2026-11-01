/* Login, signup, forgot and reset forms — all submitted via AJAX. */
(function () {
  'use strict';
  const mismatch = (f) => (f.password && f.password_confirm && f.password.value !== f.password_confirm.value) ? 'Passwords do not match.' : '';
  // Turnstile tokens work once: refresh the widget after every attempt, and nudge the user if they skipped it
  const resetCaptcha = () => { try { if (window.turnstile) window.turnstile.reset(); } catch (e) { /* widget not on this page */ } };
  const needCaptcha = (f) => (f.querySelector('.cf-turnstile') && !(f.querySelector('[name="cf-turnstile-response"]') || {}).value ? 'Please complete the security check first.' : '');
  const reenable = (sel, label) => () => { const b = CV.$(sel); if (b) { b.disabled = false; b.textContent = label; } };

  CV.bindForm(CV.$('#loginForm'), {
    url: 'api/auth.php', action: 'login', busy: 'Signing in…', keepDisabled: true,
    validate: (f) => ((!f.email.value.trim() || !f.password.value) ? 'Please enter your email and password.' : '') || needCaptcha(f),
    success: (res, form, show) => { const n = CV.$('#notice'); if (n) n.className = 'form-msg'; show('Welcome! Redirecting…', 'ok'); },
    fail: () => { resetCaptcha(); reenable('#loginForm button[type=submit]', 'Sign in')(); const p = CV.$('#password'); p.value = ''; p.focus(); }
  });

  // PLACEHOLDER: Google sign-in is not connected yet.
  const googleBtn = CV.$('#googleBtn');
  if (googleBtn) googleBtn.addEventListener('click', () => CV.toast('Google sign-in is coming soon. Please use your university email for now.', 'ok'));

  CV.bindForm(CV.$('#signupForm'), {
    url: 'api/auth.php', action: 'signup', busy: 'Creating account…', delay: 900, keepDisabled: true,
    validate: (f) => mismatch(f) || needCaptcha(f), fail: () => { resetCaptcha(); reenable('#signupForm button[type=submit]', 'Create account')(); }
  });

  CV.bindForm(CV.$('#forgotForm'), {
    url: 'api/auth.php', action: 'forgot', busy: 'Sending…', validate: needCaptcha, fail: resetCaptcha,
    success: (res, form, show) => {
      resetCaptcha();
      show(res.message, 'ok');
      if (res.debug_link) { const a = document.createElement('a'); a.href = res.debug_link; a.textContent = ' [dev] open reset link'; a.style.fontWeight = '700'; CV.$('.form-msg', form).appendChild(a); }
    }
  });

  CV.bindForm(CV.$('#resetForm'), {
    url: 'api/auth.php', action: 'reset', busy: 'Updating…', delay: 900, keepDisabled: true,
    validate: mismatch, fail: reenable('#resetForm button[type=submit]', 'Update password')
  });
})();
