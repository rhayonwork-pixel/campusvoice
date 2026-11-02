-- CampusVoice upgrade: SASO categories, username login, Google link, auto-routing.
-- Run ONCE in phpMyAdmin on the EXISTING database (click the database first, then SQL tab, paste, Go).
-- Your users, concerns and history are kept. Back up first (Export tab) to be safe.
SET NAMES utf8mb4;

-- Users: email becomes optional, Google link columns
ALTER TABLE users MODIFY email VARCHAR(190) NULL;
ALTER TABLE users ADD COLUMN google_sub VARCHAR(64) NULL UNIQUE AFTER must_change_password,
                  ADD COLUMN google_email VARCHAR(190) NULL AFTER google_sub,
                  ADD COLUMN google_linked_at DATETIME NULL AFTER google_email;

-- Categories: location flag + next steps
ALTER TABLE categories ADD COLUMN needs_location TINYINT(1) NOT NULL DEFAULT 0 AFTER default_department_id,
                       ADD COLUMN next_steps VARCHAR(500) NULL AFTER needs_location;

-- Concerns: location optional, new statuses
ALTER TABLE concerns MODIFY location_id INT UNSIGNED NULL;
ALTER TABLE concerns MODIFY status ENUM('Submitted','Pending','Under Review','Additional Info Needed','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled') NOT NULL DEFAULT 'Submitted';
UPDATE concerns SET status = 'Pending' WHERE status = 'Submitted';
UPDATE concern_updates SET status = 'Pending' WHERE status = 'Submitted';

-- Assignments: NULL assigned_by = automatic routing
ALTER TABLE assignments MODIFY assigned_by INT UNSIGNED NULL;

-- Category routing table
CREATE TABLE IF NOT EXISTS category_handlers (
  category_id INT UNSIGNED NOT NULL,
  staff_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (category_id, staff_id),
  CONSTRAINT fk_ch_cat FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
  CONSTRAINT fk_ch_staff FOREIGN KEY (staff_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- SASO offices
INSERT IGNORE INTO departments (department_name, department_type) VALUES
 ('Guidance and Counseling','work_area'),
 ('Scholarship and Financial Assistance','work_area'),
 ('Student Discipline','work_area'),
 ('Student Organizations and Activities','work_area'),
 ('Facilities and Maintenance','work_area');

-- Old facility categories stay for history but are hidden from the New Concern form.
-- Their old concerns keep their location.
UPDATE categories SET status = 'inactive';
UPDATE categories SET category_name = CONCAT(category_name, ' (old)') WHERE category_name IN ('Other');

INSERT INTO categories (category_name, description, default_department_id, needs_location, next_steps, status) VALUES
 ('Academic','Grades, subjects, enrollment, faculty or class concerns',(SELECT department_id FROM departments WHERE department_name='Guidance and Counseling' LIMIT 1),0,'A SASO officer reviews your concern and coordinates with your college or the faculty involved. You may be invited for a short meeting.','active'),
 ('Bullying and harassment','Verbal, physical, online or sexual harassment, threats or intimidation',(SELECT department_id FROM departments WHERE department_name='Guidance and Counseling' LIMIT 1),0,'A guidance counselor contacts you privately within one working day. Your safety comes first; you can ask for a confidential meeting.','active'),
 ('Student discipline','Violations of the student handbook that you witnessed or were involved in',(SELECT department_id FROM departments WHERE department_name='Student Discipline' LIMIT 1),0,'The Student Discipline Office validates the report, may ask for a written statement, and handles the case under the student handbook.','active'),
 ('Financial assistance','Tuition installment, emergency aid, fee concerns',(SELECT department_id FROM departments WHERE department_name='Scholarship and Financial Assistance' LIMIT 1),0,'The Scholarship and Financial Assistance office checks your eligibility. Prepare your assessment form and proof of need in case they ask.','active'),
 ('Scholarship','Applications, renewals, grants, allowances',(SELECT department_id FROM departments WHERE department_name='Scholarship and Financial Assistance' LIMIT 1),0,'The scholarship coordinator checks your records and replies with the requirements or the status of your grant.','active'),
 ('Student organization','Accreditation, activities, permits, organization disputes',(SELECT department_id FROM departments WHERE department_name='Student Organizations and Activities' LIMIT 1),0,'The Student Organizations office reviews your request and tells you the requirements or schedules a consultation.','active'),
 ('Personal and welfare','Personal, family, health or mental-wellness concerns',(SELECT department_id FROM departments WHERE department_name='Guidance and Counseling' LIMIT 1),0,'A guidance counselor reaches out to schedule a confidential session. If you are in immediate danger, call the campus hotline.','active'),
 ('Campus facilities','Rooms, restrooms, lights, water, furniture, Wi-Fi and other campus facilities',(SELECT department_id FROM departments WHERE department_name='Facilities and Maintenance' LIMIT 1),1,'Facilities and Maintenance inspects the location you reported and schedules the repair. You will be updated once it is fixed.','active'),
 ('Other','Anything that does not fit the other types',(SELECT department_id FROM departments WHERE department_type='administration' LIMIT 1),0,'The SASO admin reads your concern and forwards it to the right office.','active');

-- Settings: signup off, routing on, 3 tries then a 5-minute lock
INSERT INTO settings (setting_key, setting_value) VALUES
 ('student_signup','0'),('auto_assign','1'),('sec_max_failed','3'),('sec_lockout_minutes','5')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Then: Admin -> Campus Data -> Concern types -> pick the handlers for each type,
-- and Users -> edit each staff member's office.
