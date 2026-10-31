<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if ($u = current_user()) redirect(home_for($u['role']));
$domain = setting('signup_email_domain', '');
$open   = setting('student_signup', '1') === '1';

html_head('Create account', ['auth.css'], 'authpage');
auth_open('Create your account', 'Student registration. Staff and admin accounts are created by the administrator.', true);
if (!$open): ?>
      <div class="form-msg show error">Registration is currently closed. Please contact the administrator.</div>
<?php else: ?>
      <form id="signupForm" novalidate>
        <div class="form-msg" role="alert"></div>
        <h4 class="form-sec">Account</h4>
        <div class="row two">
          <div class="field"><label for="school_id">Student number</label><input class="input" id="school_id" name="school_id" placeholder="2023-0001" maxlength="20" required></div>
          <div class="field"><label for="email">University email</label><input class="input" id="email" name="email" type="email" autocomplete="email" placeholder="name@<?= e($domain ?: 'school.edu.ph') ?>" required></div>
        </div>
        <h4 class="form-sec">Full name</h4>
        <div class="row three">
          <div class="field"><label for="first_name">First name</label><input class="input" id="first_name" name="first_name" autocomplete="given-name" maxlength="80" required></div>
          <div class="field"><label for="middle_initial">M.I.</label><input class="input" id="middle_initial" name="middle_initial" maxlength="2" placeholder="A"></div>
          <div class="field"><label for="last_name">Last name</label><input class="input" id="last_name" name="last_name" autocomplete="family-name" maxlength="80" required></div>
        </div>
        <h4 class="form-sec">Program</h4>
        <div class="row course-row">
          <div class="field"><label for="course">Course / program</label><input class="input" id="course" name="course" maxlength="60" placeholder="Type or choose your program" data-course-picker required></div>
          <div class="field"><label for="year_level">Year level</label>
            <select class="select" id="year_level" name="year_level" required><option value="">Select</option><?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">Year $i</option>"; ?></select></div>
          <div class="field"><label for="section">Section</label><input class="input" id="section" name="section" maxlength="20" placeholder="3A" required></div>
        </div>
        <h4 class="form-sec">Password</h4>
        <div class="row two">
          <div class="field"><label for="password">Password</label>
            <div class="input-wrap"><input class="input" id="password" name="password" type="password" autocomplete="new-password" data-strength="pwBar" required><button type="button" class="reveal" data-reveal="password">SHOW</button></div>
            <div class="strength" id="pwBar"><i></i></div><span class="hint">8+ characters with letters and numbers.</span></div>
          <div class="field"><label for="password_confirm">Confirm password</label><input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
      </form>
<?php endif; ?>
      <p class="auth-alt">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
<?php auth_close(); html_foot(['auth.js', 'course-picker.js']); ?>
