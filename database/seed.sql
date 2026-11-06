-- CampusVoice — fresh seed data (SASO demo). Import AFTER schema.sql.
-- Sign in with the USERNAME (school ID). Every demo account uses the same password (see README_DEPLOY.md).
-- Change it before going live.
SET NAMES utf8mb4;

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

-- users: 1 admin (SASO Secretary), 5 SASO staff (the real office), 4 demo students. Username = school_id.
-- Staff emails are left empty on purpose: fill them in from Users when the office is ready to receive e-mail.
INSERT INTO users (user_id, role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, contact_number, course, year_level, section, availability) VALUES
 (1,'admin','ADM-0001','Melanie','M','Asidao',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',12,'SASO Secretary',NULL,NULL,NULL,NULL,'available'),
 (2,'staff','STF-0101','Richard','','Caranguian',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',7,'Student Affairs and Service Office',NULL,NULL,NULL,NULL,'available'),
 (3,'staff','STF-0102','Hazel','C','Gascon',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',8,'Coordinator, University Student Organizations',NULL,NULL,NULL,NULL,'available'),
 (4,'staff','STF-0103','Jesper','M','Perez',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',9,'Sports and Activities Coordinator',NULL,NULL,NULL,NULL,'busy'),
 (5,'staff','STF-0104','Bong','J','Rollan, RCrim',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',10,'Student Discipline Officer',NULL,NULL,NULL,NULL,'available'),
 (6,'student','2023-0001','Juan','A','Dela Cruz','juan.delacruz@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',5,NULL,'0918 200 0001','BS Information Technology',3,'3A','available'),
 (7,'student','2023-0002','Maria','L','Reyes','maria.reyes@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',1,NULL,'0918 200 0002','BA Communication',2,'2B','available'),
 (8,'student','2022-0105','Paolo','','Santos','paolo.santos@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',3,NULL,'0918 200 0003','BS Business Administration',4,'4C','available'),
 (9,'student','2024-0210','Angela','T','Garcia','angela.garcia@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',6,NULL,'0918 200 0004','BS Elementary Education',1,'1A','available'),
 (10,'staff','STF-0105','Primo','J','Siatan',NULL,'$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',11,'Head, Cultural and Performing Arts',NULL,NULL,NULL,NULL,'available');

-- SASO concern types: ONLY the areas SASO really handles. Every type has ONE owner (below), so a concern always reaches the right person.
-- Only Facility asks for a location.
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

-- Routing: each type goes to its OWNER, even if that person is busy. Busy never moves a concern to someone whose job does not match.
INSERT INTO category_handlers (category_id, staff_id) VALUES
 (1,2),(2,2),(3,2),(4,2),(5,3),(6,4),(7,10),(8,5),(9,5),(10,5);

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

-- concerns (dates are relative to import time so the demo always looks current)
INSERT INTO concerns (concern_id, concern_code, student_id, category_id, location_id, severity, status, title, description, is_anonymous, due_at, created_at, resolved_at, closed_at) VALUES
 (1,'CV-2026-0001',6,10,5,'Major','In Progress','Wi-Fi keeps disconnecting in CompLab 1','The wireless connection drops every few minutes during class. Several classmates are affected and it interrupts our online quizzes.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 1 DAY,NULL,NULL),
 (2,'CV-2026-0002',7,3,NULL,'Urgent','Assigned','Repeated name-calling in our group chat','A group of classmates keeps posting insulting memes about me in our section group chat. It has been going on for two weeks and I no longer want to attend class.',1,NOW()+INTERVAL 20 HOUR,NOW()-INTERVAL 4 HOUR,NULL,NULL),
 (3,'CV-2026-0003',8,6,NULL,'Major','Additional Info Needed','Basketball tryouts schedule and requirements','I would like to try out for the university basketball team. What are the requirements and when are the tryouts?',0,NOW()+INTERVAL 3 DAY,NOW()-INTERVAL 1 DAY,NULL,NULL),
 (4,'CV-2026-0004',9,7,NULL,'Minor','Pending','Audition for the university chorale','I would like to audition for the chorale. Is there an upcoming audition schedule?',0,NOW()+INTERVAL 7 DAY,NOW()-INTERVAL 40 MINUTE,NULL,NULL),
 (5,'CV-2026-0005',6,10,3,'Minor','Resolved','Broken chair in Room 201','Two chairs have broken backrests. Someone could get hurt.',0,NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 6 DAY,NOW()-INTERVAL 2 DAY,NULL),
 (6,'CV-2026-0006',7,8,NULL,'Urgent','Closed','Students vaping inside the 3rd floor restroom','Every lunch break a group of students vapes inside the restroom. The smoke makes it hard to breathe.',1,NOW()-INTERVAL 8 DAY,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY,NOW()-INTERVAL 8 DAY),
 (7,'CV-2026-0007',8,5,NULL,'Major','Waiting for Resources','Organization activity permit still not approved','Our organization submitted the permit for a seminar next week but we have not received the approval yet.',0,NOW()+INTERVAL 1 DAY,NOW()-INTERVAL 3 DAY,NULL,NULL),
 (8,'CV-2026-0008',9,5,NULL,'Minor','Closed','Requirements for club accreditation','We want to accredit our new reading club. What documents do we need to submit?',0,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY,NOW()-INTERVAL 10 DAY),
 (9,'CV-2026-0009',6,1,NULL,'Major','Assigned','I need someone to talk to','I have been feeling overwhelmed with school and family problems. I would like to talk to someone.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 2 HOUR,NULL,NULL),
 (10,'CV-2026-0010',8,9,NULL,'Urgent','In Progress','Older students blocking the stairs','Some older students block the stairs near the gym and force first years to give them snacks or money.',1,NOW()+INTERVAL 6 HOUR,NOW()-INTERVAL 18 HOUR,NULL,NULL);

-- some students asked to meet in person
UPDATE concerns SET wants_meeting = 1, meeting_pref = 'Weekday afternoons' WHERE concern_id IN (2, 9);

-- assigned_by NULL = assigned automatically to the owner of that concern type
INSERT INTO assignments (concern_id, staff_id, assigned_by, assigned_at, assignment_status, reason, seen_at, completed_at) VALUES
 (1,5,NULL,NOW()-INTERVAL 1 DAY,'active',NULL,NOW()-INTERVAL 22 HOUR,NULL),
 (2,2,NULL,NOW()-INTERVAL 4 HOUR,'active',NULL,NULL,NULL),
 (3,4,NULL,NOW()-INTERVAL 1 DAY,'active',NULL,NOW()-INTERVAL 20 HOUR,NULL),
 (5,5,NULL,NOW()-INTERVAL 6 DAY,'completed',NULL,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 2 DAY),
 (6,5,NULL,NOW()-INTERVAL 10 DAY,'completed',NULL,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY),
 (7,3,NULL,NOW()-INTERVAL 3 DAY,'active',NULL,NOW()-INTERVAL 3 DAY,NULL),
 (8,3,NULL,NOW()-INTERVAL 12 DAY,'completed',NULL,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY),
 (9,2,NULL,NOW()-INTERVAL 2 HOUR,'active',NULL,NULL,NULL),
 (10,5,NULL,NOW()-INTERVAL 18 HOUR,'active',NULL,NOW()-INTERVAL 15 HOUR,NULL);

INSERT INTO concern_updates (concern_id, user_id, status, update_type, response, created_at) VALUES
 (1,6,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 1 DAY),
 (1,NULL,'Assigned','Assigned','Routed automatically to Student Discipline Office.',NOW()-INTERVAL 1 DAY),
 (1,5,'In Progress','Status Updated','Checking the access point and switch in CompLab 1.',NOW()-INTERVAL 20 HOUR),
 (2,7,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 4 HOUR),
 (2,NULL,'Assigned','Assigned','Routed automatically to Student Affairs and Service Office.',NOW()-INTERVAL 4 HOUR),
 (3,8,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 1 DAY),
 (3,NULL,'Assigned','Assigned','Routed automatically to Sports and Activities.',NOW()-INTERVAL 1 DAY),
 (3,4,'Additional Info Needed','Information Requested','Please tell us your year level and send a photo of your medical clearance so we can add you to the tryout list.',NOW()-INTERVAL 20 HOUR),
 (4,9,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 40 MINUTE),
 (5,6,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 6 DAY),
 (5,NULL,'Assigned','Assigned','Routed automatically to Student Discipline Office.',NOW()-INTERVAL 6 DAY),
 (5,5,'In Progress','Status Updated','Chairs collected for repair.',NOW()-INTERVAL 4 DAY),
 (5,5,'Resolved','Resolved','Both chairs repaired and returned to Room 201.',NOW()-INTERVAL 2 DAY),
 (6,7,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 10 DAY),
 (6,5,'Resolved','Resolved','Restroom is now checked by a discipline officer during lunch breaks. The students involved were called for a conference.',NOW()-INTERVAL 9 DAY),
 (6,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 8 DAY),
 (7,3,'Waiting for Resources','Status Updated','The venue approval is still with the administration. We will update you as soon as it is released.',NOW()-INTERVAL 2 DAY),
 (8,3,'Resolved','Resolved','Sent the accreditation checklist and the form. Submit them at the SASO office.',NOW()-INTERVAL 11 DAY),
 (8,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 10 DAY),
 (9,NULL,'Assigned','Assigned','Routed automatically to Student Affairs and Service Office.',NOW()-INTERVAL 2 HOUR),
 (10,5,'In Progress','Status Updated','Discipline officers now monitor the stairs during breaks. The students involved were identified.',NOW()-INTERVAL 14 HOUR);

INSERT INTO feedback (concern_id, student_id, rating, comment, created_at) VALUES
 (8,9,5,'Very clear instructions, thank you!',NOW()-INTERVAL 10 DAY),
 (6,7,4,'Acted on quickly. The restroom is usable again.',NOW()-INTERVAL 8 DAY);

INSERT INTO notifications (user_id, concern_id, title, message, is_urgent, is_read, created_at) VALUES
 (1,2,'Urgent concern','CV-2026-0002 — Repeated name-calling in our group chat needs immediate attention.',1,0,NOW()-INTERVAL 4 HOUR),
 (1,10,'Urgent concern','CV-2026-0010 — Older students blocking the stairs needs immediate attention.',1,0,NOW()-INTERVAL 18 HOUR),
 (1,4,'New concern','CV-2026-0004 — Audition for the university chorale is waiting in the queue.',0,0,NOW()-INTERVAL 40 MINUTE),
 (2,2,'New assignment','CV-2026-0002 (Urgent, Psychological impact) was routed to you.',1,0,NOW()-INTERVAL 4 HOUR),
 (2,9,'New assignment','CV-2026-0009 was routed to you.',0,0,NOW()-INTERVAL 2 HOUR),
 (8,3,'More information needed','CV-2026-0003 — the office asked for more details. Open the concern to reply.',0,0,NOW()-INTERVAL 20 HOUR),
 (6,1,'Concern update','CV-2026-0001 is now In Progress.',0,0,NOW()-INTERVAL 20 HOUR),
 (6,5,'Concern resolved','CV-2026-0005 was marked as resolved.',0,0,NOW()-INTERVAL 2 DAY);

INSERT INTO events (title, start_at, end_at, notes, created_by) VALUES
 ('Anti-bullying orientation', NOW()+INTERVAL 3 DAY, NULL, 'First-year orientation at the gymnasium.',1),
 ('Student organization accreditation deadline', NOW()+INTERVAL 5 DAY, NULL, 'Last day to submit requirements at the SASO office.',1);

INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address, created_at) VALUES
 (1,'Melanie M. Asidao <ADM-0001>','admin','login','Signed in','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (6,'Juan A. Dela Cruz <2023-0001>','student','concern_created','Created CV-2026-0001','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (NULL,'System','system','concern_assigned','CV-2026-0001 routed automatically to Bong J. Rollan, RCrim','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (5,'Bong J. Rollan, RCrim <STF-0104>','staff','concern_updated','CV-2026-0001 set to In Progress','127.0.0.1',NOW()-INTERVAL 20 HOUR),
 (4,'Jesper M. Perez <STF-0103>','staff','info_requested','CV-2026-0003 asked the student for more information','127.0.0.1',NOW()-INTERVAL 20 HOUR);

-- face-to-face visits (dates are relative to import time)
INSERT INTO visits (concern_id, scheduled_by, scheduled_at, place, note, status, student_note, outcome_note, responded_at, created_at) VALUES
 (9, 2, DATE_FORMAT(NOW() + INTERVAL 1 DAY, '%Y-%m-%d 14:00:00'), 'SASO Office', 'Please bring your school ID.', 'scheduled', NULL, NULL, NULL, NOW() - INTERVAL 1 HOUR),
 (2, 2, DATE_FORMAT(NOW() + INTERVAL 2 DAY, '%Y-%m-%d 10:00:00'), 'SASO Office', 'Please bring your school ID.', 'confirmed', NULL, NULL, NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 2 HOUR),
 (3, 4, DATE_FORMAT(NOW() - INTERVAL 1 DAY, '%Y-%m-%d 09:30:00'), 'SASO Office', NULL, 'attended', NULL, 'Brought the medical clearance; added to the tryout list.', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 2 DAY);
INSERT INTO concern_updates (concern_id, user_id, status, update_type, response, created_at) VALUES
 (9, 2, NULL, 'Visit Scheduled', 'Face-to-face meeting set at the SASO Office. Please bring your school ID.', NOW() - INTERVAL 1 HOUR),
 (3, 4, NULL, 'Visit Attended', 'The student attended the face-to-face meeting. Brought the medical clearance; added to the tryout list.', NOW() - INTERVAL 1 DAY);
INSERT INTO notifications (user_id, concern_id, title, message, is_urgent, is_read, created_at) VALUES
 (6, 9, 'Face-to-face visit', 'CV-2026-0009 — please come to the SASO Office tomorrow at 2:00 PM. Open the concern to confirm.', 1, 0, NOW() - INTERVAL 1 HOUR);
