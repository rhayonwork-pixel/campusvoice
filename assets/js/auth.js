/* Login, signup, forgot and reset forms — all submitted via AJAX. */
(function () {
  'use strict';
  const mismatch = (f) => (f.password && f.password_confirm && f.password.value !== f.password_confirm.value) ? 'Passwords do not match.' : '';
  // Turnstile tokens work once: refresh the widget after every attempt, and nudge the user if they skipped it
  const resetCaptcha = () => { try { if (window.turnstile) window.turnstile.reset(); } catch (e) { /* widget not on this page */ } };
  const needCaptcha = (f) => (f.querySelector('.cf-turnstile') && !(f.querySelector('[name="cf-turnstile-response"]') || {}).value ? 'Please complete the security check first.' : '');
  const reenable = (sel, label) => () => { const b = CV.$(sel); if (b) { b.disabled = false; b.textContent = label; } };

  /* Login: attempts left + lockout countdown (the unlock time survives a page refresh). */
  const loginForm = CV.$('#loginForm');
  if (loginForm) {
    const KEY = 'cv_lock_until';
    const box = CV.$('#lockBox'), time = CV.$('#lockTime'), tries = CV.$('#tries');
    const btn = loginForm.querySelector('button[type=submit]');
    const max = parseInt(loginForm.dataset.max, 10) || 3;
    let timer = null, total = 0;
    const store = (v) => { try { v ? localStorage.setItem(KEY, String(v)) : localStorage.removeItem(KEY); } catch (e) { /* private mode */ } };
    const stored = () => { try { return parseInt(localStorage.getItem(KEY), 10) || 0; } catch (e) { return 0; } };
    const showTries = (left) => {
      if (left == null || left >= max) { tries.hidden = true; return; }
      tries.hidden = false;
      tries.innerHTML = Array.from({ length: max }, (_, i) => '<i class="' + (i < left ? 'on' : '') + '"></i>').join('') +
        '<span>' + left + ' of ' + max + ' tries left</span>';
    };
    const unlock = () => {
      clearInterval(timer); timer = null; store(0);
      box.hidden = true; loginForm.classList.remove('locked'); showTries(null);
      btn.disabled = false; btn.textContent = 'Sign in';
      const m = CV.$('#loginMsg'); if (m) m.className = 'form-msg';
      CV.$('#password').focus();
    };
    const tick = () => {
      const left = Math.max(0, Math.round((stored() - Date.now()) / 1000));
      if (!left) return unlock();
      time.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
      box.style.setProperty('--lock-p', (left / total).toFixed(4));
      btn.disabled = true; btn.textContent = 'Locked · ' + time.textContent;
    };
    const lock = (seconds) => {
      const until = Date.now() + seconds * 1000;
      store(until); total = Math.max(seconds, 1);
      box.hidden = false; loginForm.classList.add('locked'); tries.hidden = true;
      clearInterval(timer); tick(); timer = setInterval(tick, 1000);
    };
    // resume a lock from a previous visit
    const resume = Math.round((stored() - Date.now()) / 1000);
    if (resume > 0) lock(resume); else store(0);

    CV.bindForm(loginForm, {
      url: 'api/auth.php', action: 'login', busy: 'Signing in…', keepDisabled: true,
      validate: (f) => (timer ? 'Sign-in is locked. Please wait for the timer.' : '') ||
        ((!f.username.value.trim() || !f.password.value) ? 'Please enter your username and password.' : '') || needCaptcha(f),
      success: (res, form, show) => { store(0); const n = CV.$('#notice'); if (n) n.className = 'form-msg'; show('Welcome! Redirecting…', 'ok'); },
      fail: (err) => {
        resetCaptcha();
        const d = (err && err.data) || {};
        const p = CV.$('#password'); p.value = '';
        if (d.retry_after) { lock(d.retry_after); return; }
        reenable('#loginForm button[type=submit]', 'Sign in')();
        showTries(d.attempts_left);
        p.focus();
      }
    });
  }

  // Google sign-in placeholder (shown only while Google is not configured)
  const googleBtn = CV.$('#googleBtn');
  if (googleBtn) googleBtn.addEventListener('click', () => CV.toast('Google sign-in is not set up yet. Please use your username.', 'ok'));

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
