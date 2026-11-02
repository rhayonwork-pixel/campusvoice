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
 (7,'Guidance and Counseling','work_area'),
 (8,'Scholarship and Financial Assistance','work_area'),
 (9,'Student Discipline','work_area'),
 (10,'Student Organizations and Activities','work_area'),
 (11,'Facilities and Maintenance','work_area'),
 (12,'SASO Administration','administration');

-- users: 1 admin, 5 SASO staff, 4 students. Username = school_id.
INSERT INTO users (user_id, role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, contact_number, course, year_level, section, availability) VALUES
 (1,'admin','ADM-0001','Maria','C','Santos','admin@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',12,'SASO Director','0917 100 0001',NULL,NULL,NULL,'available'),
 (2,'staff','STF-0101','Roberto','M','Dela Peña','roberto.delapena@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',11,'Facilities Supervisor','0917 100 0101',NULL,NULL,NULL,'available'),
 (3,'staff','STF-0102','Angelica','R','Ramos','angelica.ramos@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',7,'Guidance Counselor','0917 100 0102',NULL,NULL,NULL,'available'),
 (4,'staff','STF-0103','Mario','V','Villanueva','mario.villanueva@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',9,'Student Discipline Officer','0917 100 0103',NULL,NULL,NULL,'busy'),
 (5,'staff','STF-0104','Jenny','L','Mercado','jenny.mercado@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',8,'Scholarship Coordinator','0917 100 0104',NULL,NULL,NULL,'available'),
 (6,'student','2023-0001','Juan','A','Dela Cruz','juan.delacruz@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',5,NULL,'0918 200 0001','BS Information Technology',3,'3A','available'),
 (7,'student','2023-0002','Maria','L','Reyes','maria.reyes@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',1,NULL,'0918 200 0002','BA Communication',2,'2B','available'),
 (8,'student','2022-0105','Paolo','','Santos','paolo.santos@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',3,NULL,'0918 200 0003','BS Business Administration',4,'4C','available'),
 (9,'student','2024-0210','Angela','T','Garcia','angela.garcia@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',6,NULL,'0918 200 0004','BS Elementary Education',1,'1A','available'),
 (10,'staff','STF-0105','Carlo','D','Bautista','carlo.bautista@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',10,'Student Activities Coordinator','0917 100 0105',NULL,NULL,NULL,'available');

-- SASO concern types. Only Campus facilities asks for a location.
INSERT INTO categories (category_id, category_name, description, default_department_id, needs_location, next_steps) VALUES
 (1,'Academic','Grades, subjects, enrollment, faculty or class concerns',7,0,'A SASO officer reviews your concern and coordinates with your college or the faculty involved. You may be invited for a short meeting.'),
 (2,'Bullying and harassment','Verbal, physical, online or sexual harassment, threats or intimidation',7,0,'A guidance counselor contacts you privately within one working day. Your safety comes first; you can ask for a confidential meeting.'),
 (3,'Student discipline','Violations of the student handbook that you witnessed or were involved in',9,0,'The Student Discipline Office validates the report, may ask for a written statement, and handles the case under the student handbook.'),
 (4,'Financial assistance','Tuition installment, emergency aid, fee concerns',8,0,'The Scholarship and Financial Assistance office checks your eligibility. Prepare your assessment form and proof of need in case they ask.'),
 (5,'Scholarship','Applications, renewals, grants, allowances',8,0,'The scholarship coordinator checks your records and replies with the requirements or the status of your grant.'),
 (6,'Student organization','Accreditation, activities, permits, organization disputes',10,0,'The Student Organizations office reviews your request and tells you the requirements or schedules a consultation.'),
 (7,'Personal and welfare','Personal, family, health or mental-wellness concerns',7,0,'A guidance counselor reaches out to schedule a confidential session. If you are in immediate danger, call the campus hotline.'),
 (8,'Campus facilities','Rooms, restrooms, lights, water, furniture, Wi-Fi and other campus facilities',11,1,'Facilities and Maintenance inspects the location you reported and schedules the repair. You will be updated once it is fixed.'),
 (9,'Other','Anything that does not fit the other types',12,0,'The SASO admin reads your concern and forwards it to the right office.');

-- Category routing: new concerns of each type go to the least-busy handler automatically.
INSERT INTO category_handlers (category_id, staff_id) VALUES
 (1,3),(2,3),(2,4),(3,4),(4,5),(5,5),(6,10),(7,3),(8,2);

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
 (1,'CV-2026-0001',6,8,5,'Major','In Progress','Wi-Fi keeps disconnecting in CompLab 1','The wireless connection drops every few minutes during class. Several classmates are affected and it interrupts our online quizzes.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 1 DAY,NULL,NULL),
 (2,'CV-2026-0002',7,2,NULL,'Urgent','Assigned','Repeated name-calling in our group chat','A group of classmates keeps posting insulting memes about me in our section group chat. It has been going on for two weeks and I no longer want to attend class.',1,NOW()+INTERVAL 20 HOUR,NOW()-INTERVAL 4 HOUR,NULL,NULL),
 (3,'CV-2026-0003',8,4,NULL,'Major','Additional Info Needed','Request for tuition installment','My family cannot pay the full midterm balance this month. I would like to ask if I can pay in two installments.',0,NOW()+INTERVAL 3 DAY,NOW()-INTERVAL 1 DAY,NULL,NULL),
 (4,'CV-2026-0004',9,9,NULL,'Minor','Pending','Extend library hours during finals week','Many of us have no quiet place to review at home. Could the library stay open until 8 PM during finals week?',0,NOW()+INTERVAL 7 DAY,NOW()-INTERVAL 40 MINUTE,NULL,NULL),
 (5,'CV-2026-0005',6,8,3,'Minor','Resolved','Broken chair in Room 201','Two chairs have broken backrests. Someone could get hurt.',0,NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 6 DAY,NOW()-INTERVAL 2 DAY,NULL),
 (6,'CV-2026-0006',7,3,NULL,'Urgent','Closed','Students vaping inside the 3rd floor restroom','Every lunch break a group of students vapes inside the restroom. The smoke makes it hard to breathe.',1,NOW()-INTERVAL 8 DAY,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY,NOW()-INTERVAL 8 DAY),
 (7,'CV-2026-0007',8,5,NULL,'Major','Waiting for Resources','Scholarship allowance not yet released','I am a city scholar and my allowance for this semester has not been released yet. Is there an update?',0,NOW()+INTERVAL 1 DAY,NOW()-INTERVAL 3 DAY,NULL,NULL),
 (8,'CV-2026-0008',9,6,NULL,'Minor','Closed','Requirements for club accreditation','We want to accredit our new reading club. What documents do we need to submit?',0,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY,NOW()-INTERVAL 10 DAY),
 (9,'CV-2026-0009',6,7,NULL,'Major','Assigned','I need someone to talk to','I have been feeling overwhelmed with school and family problems. I would like to talk to a counselor.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 2 HOUR,NULL,NULL),
 (10,'CV-2026-0010',8,2,NULL,'Urgent','In Progress','Older students blocking the stairs','Some older students block the stairs near the gym and force first years to give them snacks or money.',1,NOW()+INTERVAL 6 HOUR,NOW()-INTERVAL 18 HOUR,NULL,NULL);

-- assigned_by NULL = assigned automatically by category routing
INSERT INTO assignments (concern_id, staff_id, assigned_by, assigned_at, assignment_status, reason, seen_at, completed_at) VALUES
 (1,2,NULL,NOW()-INTERVAL 1 DAY,'active',NULL,NOW()-INTERVAL 22 HOUR,NULL),
 (2,3,1,NOW()-INTERVAL 3 HOUR,'active',NULL,NULL,NULL),
 (3,5,NULL,NOW()-INTERVAL 1 DAY,'active',NULL,NOW()-INTERVAL 20 HOUR,NULL),
 (5,2,NULL,NOW()-INTERVAL 6 DAY,'completed',NULL,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 2 DAY),
 (6,4,NULL,NOW()-INTERVAL 10 DAY,'completed',NULL,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY),
 (7,5,NULL,NOW()-INTERVAL 3 DAY,'active',NULL,NOW()-INTERVAL 3 DAY,NULL),
 (8,10,NULL,NOW()-INTERVAL 12 DAY,'completed',NULL,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY),
 (9,3,NULL,NOW()-INTERVAL 2 HOUR,'active',NULL,NULL,NULL),
 (10,3,NULL,NOW()-INTERVAL 18 HOUR,'reassigned','Needs the discipline office',NOW()-INTERVAL 17 HOUR,NULL),
 (10,4,1,NOW()-INTERVAL 16 HOUR,'active','Reassigned to Student Discipline',NOW()-INTERVAL 15 HOUR,NULL);

INSERT INTO concern_updates (concern_id, user_id, status, update_type, response, created_at) VALUES
 (1,6,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 1 DAY),
 (1,NULL,'Assigned','Assigned','Routed automatically to Facilities and Maintenance.',NOW()-INTERVAL 1 DAY),
 (1,2,'In Progress','Status Updated','Checking the access point and switch in CompLab 1.',NOW()-INTERVAL 20 HOUR),
 (2,7,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 4 HOUR),
 (2,1,'Assigned','Assigned','Marked Urgent and assigned to Guidance and Counseling.',NOW()-INTERVAL 3 HOUR),
 (3,8,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 1 DAY),
 (3,NULL,'Assigned','Assigned','Routed automatically to Scholarship and Financial Assistance.',NOW()-INTERVAL 1 DAY),
 (3,5,'Additional Info Needed','Information Requested','Please send a photo of your assessment form and tell us how much you can pay on each installment date.',NOW()-INTERVAL 20 HOUR),
 (4,9,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 40 MINUTE),
 (5,6,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 6 DAY),
 (5,NULL,'Assigned','Assigned','Routed automatically to Facilities and Maintenance.',NOW()-INTERVAL 6 DAY),
 (5,2,'In Progress','Status Updated','Chairs collected for repair.',NOW()-INTERVAL 4 DAY),
 (5,2,'Resolved','Resolved','Both chairs repaired and returned to Room 201.',NOW()-INTERVAL 2 DAY),
 (6,7,'Pending','Concern Submitted','Your concern has been received.',NOW()-INTERVAL 10 DAY),
 (6,4,'Resolved','Resolved','Restroom is now checked by a discipline officer during lunch breaks. The students involved were called for a conference.',NOW()-INTERVAL 9 DAY),
 (6,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 8 DAY),
 (7,5,'Waiting for Resources','Status Updated','The city scholarship office has not released the funds yet. We will update you as soon as it arrives.',NOW()-INTERVAL 2 DAY),
 (8,10,'Resolved','Resolved','Sent the accreditation checklist and the form. Submit them at the SASO office.',NOW()-INTERVAL 11 DAY),
 (8,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 10 DAY),
 (9,NULL,'Assigned','Assigned','Routed automatically to Guidance and Counseling.',NOW()-INTERVAL 2 HOUR),
 (10,4,'In Progress','Status Updated','Discipline officers now monitor the stairs during breaks. The students involved were identified.',NOW()-INTERVAL 14 HOUR);

INSERT INTO feedback (concern_id, student_id, rating, comment, created_at) VALUES
 (8,9,5,'Very clear instructions, thank you!',NOW()-INTERVAL 10 DAY),
 (6,7,4,'Acted on quickly. The restroom is usable again.',NOW()-INTERVAL 8 DAY);

INSERT INTO notifications (user_id, concern_id, title, message, is_urgent, is_read, created_at) VALUES
 (1,2,'Urgent concern','CV-2026-0002 — Repeated name-calling in our group chat needs immediate attention.',1,0,NOW()-INTERVAL 4 HOUR),
 (1,10,'Urgent concern','CV-2026-0010 — Older students blocking the stairs needs immediate attention.',1,0,NOW()-INTERVAL 18 HOUR),
 (1,4,'New concern','CV-2026-0004 — Extend library hours during finals week is waiting in the queue.',0,0,NOW()-INTERVAL 40 MINUTE),
 (3,2,'New assignment','You were assigned CV-2026-0002 (Urgent).',1,0,NOW()-INTERVAL 3 HOUR),
 (3,9,'New assignment','CV-2026-0009 was routed to you.',0,0,NOW()-INTERVAL 2 HOUR),
 (8,3,'More information needed','CV-2026-0003 — the office asked for more details. Open the concern to reply.',0,0,NOW()-INTERVAL 20 HOUR),
 (6,1,'Concern update','CV-2026-0001 is now In Progress.',0,0,NOW()-INTERVAL 20 HOUR),
 (6,5,'Concern resolved','CV-2026-0005 was marked as resolved.',0,0,NOW()-INTERVAL 2 DAY);

INSERT INTO events (title, start_at, end_at, notes, created_by) VALUES
 ('Anti-bullying orientation', NOW()+INTERVAL 3 DAY, NULL, 'First-year orientation at the gymnasium.',1),
 ('Scholarship renewal deadline', NOW()+INTERVAL 5 DAY, NULL, 'Last day to submit renewal requirements at the SASO office.',1);

INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address, created_at) VALUES
 (1,'Maria C. Santos <ADM-0001>','admin','login','Signed in','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (6,'Juan A. Dela Cruz <2023-0001>','student','concern_created','Created CV-2026-0001','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (NULL,'System','system','concern_assigned','CV-2026-0001 routed automatically to Roberto Dela Peña','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (2,'Roberto M. Dela Peña <STF-0101>','staff','concern_updated','CV-2026-0001 set to In Progress','127.0.0.1',NOW()-INTERVAL 20 HOUR),
 (1,'Maria C. Santos <ADM-0001>','admin','concern_reassigned','CV-2026-0010 reassigned to Mario Villanueva','127.0.0.1',NOW()-INTERVAL 16 HOUR),
 (5,'Jenny L. Mercado <STF-0104>','staff','info_requested','CV-2026-0003 asked the student for more information','127.0.0.1',NOW()-INTERVAL 20 HOUR);
