<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/panel.php';
$u = require_role('admin');

panel_open($u, 'Users & Staff', 'users.php', ['admin.css']);
page_head('Users & Staff', 'Create accounts, assign roles and manage access.',
    '<button class="btn btn-primary" type="button" id="addUser">' . icon('plus') . ' Add user</button>');
?>
<section class="card" id="userTable">
  <div class="card-head"><div class="toolbar" style="width:100%">
    <input class="input grow" id="fq" type="search" placeholder="Search by ID, name or email" aria-label="Search users">
    <select class="select" id="frole" aria-label="Role"><option value="">All roles</option><option value="student">Students</option><option value="staff">Staff</option><option value="admin">Admins</option></select>
    <select class="select" id="fstatus" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select>
  </div></div>
  <div class="table-wrap"><table class="tbl">
    <thead><tr><th class="nw">ID</th><th>Name</th><th>Role</th><th>Department / Course</th><th>Email</th><th>Status</th><th class="nw" style="text-align:right">Actions</th></tr></thead>
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
        <div class="field"><label for="umSid">ID number</label><input class="input" id="umSid" name="school_id" maxlength="30" required></div>
      </div>
      <div class="row three">
        <div class="field"><label for="umFirst">First name</label><input class="input" id="umFirst" name="first_name" required></div>
        <div class="field"><label for="umMi">M.I.</label><input class="input" id="umMi" name="middle_initial" maxlength="2"></div>
        <div class="field"><label for="umLast">Last name</label><input class="input" id="umLast" name="last_name" required></div>
      </div>
      <div class="row two">
        <div class="field"><label for="umEmail">Email</label><input class="input" id="umEmail" name="email" type="email" required></div>
        <div class="field"><label for="umContact">Contact number</label><input class="input" id="umContact" name="contact_number"></div>
      </div>
      <div class="row two" id="umStaffRow">
        <div class="field"><label for="umDept">Department</label><select class="select" id="umDept" name="department_id"><option value="">None</option></select></div>
        <div class="field"><label for="umPos">Position</label><input class="input" id="umPos" name="position"></div>
      </div>
      <div class="row three" id="umStudentRow">
        <div class="field"><label for="umCourse">Course</label><input class="input" id="umCourse" name="course"></div>
        <div class="field"><label for="umYear">Year</label><select class="select" id="umYear" name="year_level"><?php for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">Year $i</option>"; ?></select></div>
        <div class="field"><label for="umSec">Section</label><input class="input" id="umSec" name="section"></div>
      </div>
      <label class="check" id="umForceWrap" hidden><input type="checkbox" name="force_change" value="1"> Require a password change at next login</label>
      <p class="hint" id="umNote">A temporary password is generated for new accounts and shown once.</p>
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
<?php panel_close(['admin-manage.js']); ?>
