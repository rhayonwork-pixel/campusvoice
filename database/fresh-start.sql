-- =====================================================================
--  CampusVoice: FRESH START
--  Wipes EVERYTHING (all accounts, concerns, logs...) and puts back only the starting setup,
--  exactly like a brand-new system:
--    * 1 admin + the 5 real SASO staff
--    * the offices and the 10 SASO concern types, each with its owner
--    * campus locations, settings, office hours
--  NO students and NO concerns. Add the real students with Admin > Users > Students > Import.
--
--  HOW: export a BACKUP first (it cannot be undone), click your database in phpMyAdmin,
--  SQL tab, paste this whole file, Go. The tables must already exist (they do on your site).
--  If it stops half-way with an error, just run the whole file again: it is safe to repeat.
--  Then delete the files inside the "uploads" folder on the server (old test photos).
--
--  SIGN IN AFTER:  username ADM-0001  ·  password CampusVoice#2026
--  (admin and staff must choose their own password at first sign-in)
-- =====================================================================
SET NAMES utf8mb4;

-- Delete child tables first, so foreign keys never get in the way (works even with key checks ON).
DELETE FROM visits;
DELETE FROM photos;
DELETE FROM feedback;
DELETE FROM notifications;
DELETE FROM concern_updates;
DELETE FROM assignments;
DELETE FROM concerns;
DELETE FROM category_handlers;
DELETE FROM categories;
DELETE FROM locations;
DELETE FROM events;
DELETE FROM audit_logs;
DELETE FROM login_attempts;
DELETE FROM password_resets;
DELETE FROM working_hours;
DELETE FROM settings;
DELETE FROM users;
DELETE FROM departments;

-- numbering starts again from 1 (first concern becomes CV-...-0001)
ALTER TABLE visits AUTO_INCREMENT = 1;
ALTER TABLE photos AUTO_INCREMENT = 1;
ALTER TABLE feedback AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;
ALTER TABLE concern_updates AUTO_INCREMENT = 1;
ALTER TABLE assignments AUTO_INCREMENT = 1;
ALTER TABLE concerns AUTO_INCREMENT = 1;
ALTER TABLE events AUTO_INCREMENT = 1;
ALTER TABLE audit_logs AUTO_INCREMENT = 1;
ALTER TABLE login_attempts AUTO_INCREMENT = 1;
ALTER TABLE password_resets AUTO_INCREMENT = 1;

-- ---------- offices ----------
INSERT INTO departments (department_id, department_name, department_type) VALUES
 (1,'College of Arts and Sciences (CAS)','college'),
 (2,'College of Criminal Justice Education (CCJE)','college'),
 (3,'College of Business and Accountancy (CBA)','college'),
 (4,'College of Accountancy (COA)','college'),
 (5,'College of Computer Studies (CCS)','college'),
 (6,'College of Teacher Education (CTE)','college'),
 (7,'Student Affairs and Service Office','work_area'),
 (8,'University Student Organizations','work_area'),
 (9,'Sports and Activities','work_area'),
 (10,'Student Discipline Office','work_area'),
 (11,'Cultural and Performing Arts','work_area'),
 (12,'SASO Administration','administration');

-- ---------- people: 1 admin + 5 SASO staff (emails empty: add them under Users) ----------
INSERT INTO users (user_id, role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, availability, must_change_password) VALUES
 (1,'admin','ADM-0001','Melanie','M','Asidao',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',12,'SASO Secretary','available',1),
 (2,'staff','STF-0101','Richard','','Caranguian',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',7,'Student Affairs and Service Office','available',1),
 (3,'staff','STF-0102','Hazel','C','Gascon',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',8,'Coordinator, University Student Organizations','available',1),
 (4,'staff','STF-0103','Jesper','M','Perez',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',9,'Sports and Activities Coordinator','available',1),
 (5,'staff','STF-0104','Bong','J','Rollan, RCrim',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',10,'Student Discipline Officer','available',1),
 (6,'staff','STF-0105','Primo','J','Siatan',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',11,'Head, Cultural and Performing Arts','available',1);

-- ---------- the SASO concern types (only SASO areas) ----------
INSERT INTO categories (category_id, category_name, description, default_department_id, needs_location, next_steps) VALUES
 (1,'Mental health','Feeling overwhelmed, anxious, depressed or in need of someone to talk to',7,0,'The Student Affairs and Service Office reaches out to you privately. You can ask for a confidential face-to-face meeting. If you are in immediate danger, call the campus hotline.'),
 (2,'Guidance and counseling','Personal, family, study or career guidance and counseling sessions',7,0,'The office schedules a confidential guidance or counseling session with you and tells you the date here.'),
 (3,'Psychological impact','Bullying, harassment, abuse or an incident that is affecting you emotionally',7,0,'Your safety comes first. The office contacts you privately within one working day and refers you for support if you need it.'),
 (4,'Gender development','Gender equality, respect, harassment, inclusion and gender-related concerns',7,0,'The office handles gender-related concerns with care and confidentiality, and may invite you for a private conversation.'),
 (5,'Student organizations','Accreditation, activities, permits, officers and organization concerns',8,0,'The University Student Organizations coordinator reviews your request and tells you the requirements or schedules a consultation.'),
 (6,'Sports','Sports programs, tryouts, teams, equipment, schedules and activities',9,0,'The Sports and Activities coordinator reviews your concern and replies with the schedule, requirements or next steps.'),
 (7,'Performing arts','Auditions, cultural groups, rehearsals, performances and cultural activities',11,0,'The Cultural and Performing Arts head reviews your concern and tells you the schedule or requirements.'),
 (8,'Student discipline and behavior','Violations of the student handbook, misconduct, vaping or other behavior concerns',10,0,'The Student Discipline Office validates the report, may ask for a written statement, and handles the case under the student handbook.'),
 (9,'Safety and security','Theft, lost and found, unsafe persons or places, threats and emergencies on campus',10,0,'The Student Discipline Office looks into the report and coordinates with campus security. Urgent reports are handled first.'),
 (10,'Facility','Rooms, restrooms, lights, water, furniture, Wi-Fi and other campus facilities',10,1,'The office inspects the location you reported and arranges the repair. You will be updated once it is fixed.');

-- each type has ONE owner; a concern goes to the owner even when they are busy
INSERT INTO category_handlers (category_id, staff_id) VALUES
 (1,2),(2,2),(3,2),(4,2),(5,3),(6,4),(7,6),(8,5),(9,5),(10,5);

-- ---------- campus places (edit these under Admin > Campus Data > Locations) ----------
INSERT INTO locations (location_id, building, floor, area) VALUES
 (1,'Main Building','Ground Floor','Registrar''s Office'),
 (2,'Main Building','Ground Floor','Main Lobby'),
 (3,'Main Building','2nd Floor','Room 201'),
 (4,'Main Building','2nd Floor','Room 204'),
 (5,'Main Building','3rd Floor','Computer Laboratory 1'),
 (6,'Main Building','3rd Floor','Computer Laboratory 2'),
 (7,'Library Building','Ground Floor','Reading Area'),
 (8,'Library Building','2nd Floor','Study Room'),
 (9,'Gymnasium','Ground Floor','Court'),
 (10,'Canteen','Ground Floor','Dining Area'),
 (11,'Campus Grounds','Outdoor','Courtyard'),
 (12,'Campus Grounds','Outdoor','Main Gate');

-- ---------- settings and office hours ----------
INSERT INTO settings (setting_key, setting_value) VALUES
 ('sla_minor','168'),
 ('sla_major','72'),
 ('sla_urgent','24'),
 ('student_signup','0'),
 ('auto_assign','1'),
 ('sec_max_failed','3'),
 ('sec_lockout_minutes','5'),
 ('signup_email_domain','cityofmalabonuniversity.edu.ph'),
 ('contact_email','cmuofficial@cityofmalabonuniversity.edu.ph'),
 ('contact_address','Pampano cor. Maya-Maya Sts., Longos, City of Malabon');

INSERT INTO working_hours (day_of_week, open_time, close_time, active) VALUES
 (0,NULL,NULL,0),(1,'09:00','16:00',1),(2,'09:00','16:00',1),(3,'09:00','16:00',1),
 (4,'09:00','16:00',1),(5,'09:00','16:00',1),(6,NULL,NULL,0);

-- ---------- quick check ----------
SELECT 'admin' AS what, COUNT(*) AS total FROM users WHERE role = 'admin'
UNION ALL SELECT 'staff', COUNT(*) FROM users WHERE role = 'staff'
UNION ALL SELECT 'students', COUNT(*) FROM users WHERE role = 'student'
UNION ALL SELECT 'concern types', COUNT(*) FROM categories
UNION ALL SELECT 'owners', COUNT(*) FROM category_handlers
UNION ALL SELECT 'concerns', COUNT(*) FROM concerns;
