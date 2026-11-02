<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Users & Staff', 'users.php', ['admin.css']);
page_head('Users & Staff', 'Create accounts, assign roles and manage access. Students sign in with their student ID.',
    '<button class="btn btn-outline" type="button" id="importBtn">' . icon('upload') . ' Import students</button>'
    . '<button class="btn btn-primary" type="button" id="addUser">' . icon('plus') . ' Add user</button>');
$domain = setting('signup_email_domain', '') ?: 'cityofmalabonuniversity.edu.ph';
?>
<section class="card" id="userTable">
  <div class="card-head"><div class="toolbar" style="width:100%">
    <input class="input grow" id="fq" type="search" placeholder="Search by username, name or email" aria-label="Search users">
    <select class="select" id="frole" aria-label="Role"><option value="">All roles</option><option value="student">Students</option><option value="staff">Staff</option><option value="admin">Admins</option></select>
    <select class="select" id="fstatus" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select>
  </div></div>
  <div class="table-wrap"><table class="tbl">
    <thead><tr><th class="nw">Username</th><th>Name</th><th>Role</th><th>Department / Course</th><th>Email &amp; Google</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead>
    <tbody></tbody></table></div>
  <div class="pager"></div>
</section>

<div class="modal" id="userModal" role="dialog" aria-modal="true" aria-labelledby="umTitle">
  <form class="modal-box" id="userForm" novalidate>
    <div class="modal-head"><h3 id="umTitle">Add user</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body">
      <div class="form-msg" role="alert"></div>
      <input type="hidden" name="user_id" id="umId" value="0">
      <div class="row two">
        <div class="field"><label for="umRole">Role</label><select class="select" id="umRole" name="role"><option value="student">Student</option><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
        <div class="field"><label for="umSid">Username (ID number)</label><input class="input" id="umSid" name="school_id" maxlength="30" required></div>
      </div>
      <div class="row three">
        <div class="field"><label for="umFirst">First name</label><input class="input" id="umFirst" name="first_name" required></div>
        <div class="field"><label for="umMi">M.I.</label><input class="input" id="umMi" name="middle_initial" maxlength="2"></div>
        <div class="field"><label for="umLast">Last name</label><input class="input" id="umLast" name="last_name" required></div>
      </div>
      <div class="row two">
        <div class="field"><label for="umEmail">Email <span class="opt" id="umEmailOpt">optional for students</span></label><input class="input" id="umEmail" name="email" type="email"></div>
        <div class="field"><label for="umContact">Contact number</label><input class="input" id="umContact" name="contact_number"></div>
      </div>
      <div class="row two" id="umStaffRow">
        <div class="field"><label for="umDept">Department</label><select class="select" id="umDept" name="department_id"><option value="">None</option></select></div>
        <div class="field"><label for="umPos">Position</label><input class="input" id="umPos" name="position"></div>
      </div>
      <div class="row course-row" id="umStudentRow">
        <div class="field"><label for="umCourse">Course</label><input class="input" id="umCourse" name="course" maxlength="60" placeholder="Type or choose" data-course-picker></div>
        <div class="field"><label for="umYear">Year</label><select class="select" id="umYear" name="year_level"><?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">Year $i</option>"; ?></select></div>
        <div class="field"><label for="umSec">Section</label><input class="input" id="umSec" name="section"></div>
      </div>
      <label class="check" id="umForceWrap" hidden><input type="checkbox" name="force_change" value="1"> Require a password change at next login</label>
      <fieldset class="seg-field" id="umPwMode">
        <legend>First password</legend>
        <label class="radio-card"><input type="radio" name="password_mode" value="pattern" checked><span><b>Student ID pattern</b><small>{ID}@<?= e($domain) ?></small></span></label>
        <label class="radio-card"><input type="radio" name="password_mode" value="random"><span><b>Random</b><small>Shown once after saving</small></span></label>
      </fieldset>
      <p class="hint" id="umNote">The user must change the first password when they sign in.</p>
    </div>
    <div class="modal-foot"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button></div>
  </form>
</div>

<div class="modal" id="secretModal" role="dialog" aria-modal="true">
  <div class="modal-box sm">
    <div class="modal-head"><h3 id="secTitle">Temporary password</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <div class="modal-body"><p id="secText" style="margin-bottom:12px"></p><code class="temp-pw" id="secValue"></code><p class="hint" style="margin-top:12px">Share it privately. It is shown only once.</p></div>
    <div class="modal-foot"><button type="button" class="btn btn-primary" data-close-modal>Done</button></div>
  </div>
</div>
<div class="modal" id="importModal" role="dialog" aria-modal="true" aria-labelledby="imTitle">
  <div class="modal-box lg">
    <div class="modal-head"><h3 id="imTitle">Import students</h3><button type="button" class="modal-x" data-close-modal aria-label="Close"><?= icon('x') ?></button></div>
    <ol class="steps" id="imSteps" aria-label="Import progress"><li class="on">Upload</li><li>Check</li><li>Done</li></ol>

    <!-- step 1: upload -->
    <form class="modal-body im-pane on" id="imUpload" novalidate>
      <div class="form-msg" role="alert"></div>
      <label class="drop" id="imDrop">
        <input type="file" name="file" id="imFile" accept=".xlsx,.csv,.docx" hidden>
        <span class="drop-ic"><?= icon('upload') ?></span>
        <b>Drop the class masterlist here</b>
        <span>or <u>choose a file</u> &middot; Excel (.xlsx), CSV or Word (.docx) &middot; up to 500 students</span>
        <em id="imFileName"></em>
      </label>
      <div class="im-help">
        <p><b>What the file needs:</b> a <i>Student ID</i> column and either a <i>Full name</i> column (<code>LAST, FIRST MIDDLE</code>) or <i>Last name</i> + <i>First name</i>. Course, Year, Section and Email are optional. Other columns such as No. or Signature are ignored.</p>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('assets/templates/student-import-template.csv')) ?>" download><?= icon('download') ?> Download template</a>
      </div>
      <div class="modal-foot flush"><button type="button" class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit"><?= icon('eye') ?> Check the list</button></div>
    </form>

    <!-- step 2: preview + batch settings -->
    <form class="modal-body im-pane" id="imCommit" novalidate>
      <div class="form-msg" role="alert"></div>
      <input type="hidden" name="token" id="imToken">
      <div class="im-summary" id="imSummary"></div>
      <div class="im-grid">
        <div class="im-set">
          <h4>Class details</h4>
          <div class="detect" id="imDetect" hidden><?= icon('sparkle') ?><span id="imDetectText"></span></div>
          <div class="field"><label for="imCourse">Course</label><input class="input" id="imCourse" name="course" maxlength="60" placeholder="Type or choose" data-course-picker></div>
          <div class="row two">
            <div class="field"><label for="imYear">Year</label><select class="select" id="imYear" name="year_level"><?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">Year $i</option>"; ?></select></div>
            <div class="field"><label for="imSec">Section</label><input class="input" id="imSec" name="section" maxlength="20" placeholder="e.g. 3B"></div>
          </div>
          <div class="college-line" id="imCollege"><?= icon('building') ?><span>College <b id="imCollegeName">follows the course</b></span></div>
          <label class="switch-row tight" id="imMidWrap"><span><b>Last word of the name is the middle name</b><small>"ALREEN MIGUEL DE LEON" becomes Alreen Miguel, M.I. D. Turn off if the first name has several words.</small></span><input type="checkbox" class="switch" name="middle_from_last" value="1" id="imMid" checked></label>
          <fieldset class="seg-field">
            <legend>First password</legend>
            <label class="radio-card"><input type="radio" name="password_mode" value="pattern" checked><span><b>Student ID pattern</b><small>{ID}@<?= e($domain) ?></small></span></label>
            <label class="radio-card"><input type="radio" name="password_mode" value="random"><span><b>Random</b><small>Download the list after</small></span></label>
          </fieldset>
          <p class="hint">Students sign in with their student ID as the username and must change the password the first time.</p>
        </div>
        <div class="im-preview">
          <div class="table-wrap"><table class="tbl compact" id="imTable"><thead><tr><th>#</th><th class="nw">Username</th><th>Name</th><th>Check</th></tr></thead><tbody></tbody></table></div>
        </div>
      </div>
      <div class="modal-foot flush"><button type="button" class="btn btn-ghost" id="imBack"><?= icon('chevL') ?> Another file</button><button class="btn btn-primary" type="submit" id="imGo"><?= icon('users') ?> Create accounts</button></div>
    </form>

    <!-- step 3: results -->
    <div class="modal-body im-pane" id="imDone" data-domain="<?= e($domain) ?>">
      <div class="im-result">
        <span class="im-ok"><?= icon('checkCircle') ?></span>
        <div><h4 id="imDoneTitle"></h4><p id="imDoneText"></p></div>
      </div>
      <div class="im-actions">
        <button type="button" class="btn btn-outline" id="imCsv"><?= icon('download') ?> Download sign-in list (CSV)</button>
        <button type="button" class="btn btn-outline" id="imSlips"><?= icon('printer') ?> Print sign-in slips</button>
      </div>
      <p class="hint">Keep this list private. It is shown only now: passwords are stored encrypted and cannot be viewed again (use the key button on a user to issue a reset code).</p>
      <div class="modal-foot flush"><button type="button" class="btn btn-primary" data-close-modal>Done</button></div>
    </div>
  </div>
</div>
<?php panel_close(['admin-manage.js', 'course-picker.js']); ?>
