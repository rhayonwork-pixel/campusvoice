-- CampusVoice upgrade: SASO-only concern types + the real SASO people + owner-based routing.
-- Run ONCE in phpMyAdmin on the existing database (click the database first, then SQL tab, paste, Go).
-- Needs the earlier upgrade files to have been run. What it does:
--   * old generic types (Academic, Financial, Scholarship, Other ...) are set INACTIVE, so old concerns keep their type but nobody can pick them again;
--   * adds the SASO areas as concern types, each with ONE owner;
--   * turns the demo accounts ADM-0001 and STF-0101 to STF-0105 into the real SASO people (same usernames and password; they must change it at first sign-in).
SET NAMES utf8mb4;

-- 1. offices
INSERT IGNORE INTO departments (department_name, department_type) VALUES
 ('Student Affairs and Service Office','work_area'),
 ('University Student Organizations','work_area'),
 ('Sports and Activities','work_area'),
 ('Student Discipline Office','work_area'),
 ('Cultural and Performing Arts','work_area'),
 ('SASO Administration','administration');
SET @d_saso = (SELECT department_id FROM departments WHERE department_name = 'Student Affairs and Service Office');
SET @d_org  = (SELECT department_id FROM departments WHERE department_name = 'University Student Organizations');
SET @d_spo  = (SELECT department_id FROM departments WHERE department_name = 'Sports and Activities');
SET @d_dis  = (SELECT department_id FROM departments WHERE department_name = 'Student Discipline Office');
SET @d_art  = (SELECT department_id FROM departments WHERE department_name = 'Cultural and Performing Arts');
SET @d_adm  = (SELECT department_id FROM departments WHERE department_name = 'SASO Administration');

-- 2. the real people (these usernames were the demo accounts)
UPDATE users SET first_name='Melanie',  middle_initial='M', last_name='Asidao',        email=NULL, department_id=@d_adm, position='SASO Secretary', must_change_password=1 WHERE school_id='ADM-0001' AND role='admin';
UPDATE users SET first_name='Richard',  middle_initial='',  last_name='Caranguian',    email=NULL, department_id=@d_saso, position='Student Affairs and Service Office', availability='available', must_change_password=1 WHERE school_id='STF-0101';
UPDATE users SET first_name='Hazel',    middle_initial='C', last_name='Gascon',        email=NULL, department_id=@d_org, position='Coordinator, University Student Organizations', availability='available', must_change_password=1 WHERE school_id='STF-0102';
UPDATE users SET first_name='Jesper',   middle_initial='M', last_name='Perez',         email=NULL, department_id=@d_spo, position='Sports and Activities Coordinator', availability='available', must_change_password=1 WHERE school_id='STF-0103';
UPDATE users SET first_name='Bong',     middle_initial='J', last_name='Rollan, RCrim', email=NULL, department_id=@d_dis, position='Student Discipline Officer', availability='available', must_change_password=1 WHERE school_id='STF-0104';
UPDATE users SET first_name='Primo',    middle_initial='J', last_name='Siatan',        email=NULL, department_id=@d_art, position='Head, Cultural and Performing Arts', availability='available', must_change_password=1 WHERE school_id='STF-0105';

-- 3. retire the old generic types (existing concerns keep them)
UPDATE categories SET status = 'inactive'
 WHERE category_name IN ('Academic','Bullying and harassment','Student discipline','Financial assistance','Scholarship','Student organization','Personal and welfare','Campus facilities','Other');
DELETE FROM category_handlers WHERE category_id IN (SELECT category_id FROM categories WHERE status = 'inactive');

-- 4. SASO areas
INSERT INTO categories (category_name, description, default_department_id, needs_location, next_steps, status) VALUES
 ('Mental health','Feeling overwhelmed, anxious, depressed or in need of someone to talk to',@d_saso,0,'The Student Affairs and Service Office reaches out to you privately. You can ask for a confidential face-to-face meeting. If you are in immediate danger, call the campus hotline.','active'),
 ('Guidance and counseling','Personal, family, study or career guidance and counseling sessions',@d_saso,0,'The office schedules a confidential guidance or counseling session with you and tells you the date here.','active'),
 ('Psychological impact','Bullying, harassment, abuse or an incident that is affecting you emotionally',@d_saso,0,'Your safety comes first. The office contacts you privately within one working day and refers you for support if you need it.','active'),
 ('Gender development','Gender equality, respect, harassment, inclusion and gender-related concerns',@d_saso,0,'The office handles gender-related concerns with care and confidentiality, and may invite you for a private conversation.','active'),
 ('Student organizations','Accreditation, activities, permits, officers and organization concerns',@d_org,0,'The University Student Organizations coordinator reviews your request and tells you the requirements or schedules a consultation.','active'),
 ('Sports','Sports programs, tryouts, teams, equipment, schedules and activities',@d_spo,0,'The Sports and Activities coordinator reviews your concern and replies with the schedule, requirements or next steps.','active'),
 ('Performing arts','Auditions, cultural groups, rehearsals, performances and cultural activities',@d_art,0,'The Cultural and Performing Arts head reviews your concern and tells you the schedule or requirements.','active'),
 ('Student discipline and behavior','Violations of the student handbook, misconduct, vaping or other behavior concerns',@d_dis,0,'The Student Discipline Office validates the report, may ask for a written statement, and handles the case under the student handbook.','active'),
 ('Safety and security','Theft, lost and found, unsafe persons or places, threats and emergencies on campus',@d_dis,0,'The Student Discipline Office looks into the report and coordinates with campus security. Urgent reports are handled first.','active'),
 ('Facility','Rooms, restrooms, lights, water, furniture, Wi-Fi and other campus facilities',@d_dis,1,'The office inspects the location you reported and arranges the repair. You will be updated once it is fixed.','active')
 ON DUPLICATE KEY UPDATE description = VALUES(description), default_department_id = VALUES(default_department_id), needs_location = VALUES(needs_location), next_steps = VALUES(next_steps), status = 'active';

-- 5. owners: each type has ONE owner. A concern goes to the owner even when the owner is busy.
DELETE FROM category_handlers WHERE category_id IN (SELECT category_id FROM categories WHERE status = 'active');
INSERT INTO category_handlers (category_id, staff_id)
SELECT c.category_id, u.user_id FROM (
  SELECT 'Mental health' n, 'STF-0101' s UNION ALL SELECT 'Guidance and counseling','STF-0101' UNION ALL SELECT 'Psychological impact','STF-0101' UNION ALL SELECT 'Gender development','STF-0101'
  UNION ALL SELECT 'Student organizations','STF-0102' UNION ALL SELECT 'Sports','STF-0103' UNION ALL SELECT 'Performing arts','STF-0105'
  UNION ALL SELECT 'Student discipline and behavior','STF-0104' UNION ALL SELECT 'Safety and security','STF-0104' UNION ALL SELECT 'Facility','STF-0104'
) m JOIN categories c ON c.category_name = m.n JOIN users u ON u.school_id = m.s;
