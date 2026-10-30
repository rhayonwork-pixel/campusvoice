-- CampusVoice — fresh seed data (demo). Import AFTER schema.sql.
-- Every demo account uses the same password (see README_DEPLOY.md). Change it before going live.
SET NAMES utf8mb4;

INSERT INTO departments (department_id, department_name, department_type) VALUES
 (1,'College of Arts and Sciences (CAS)','college'),
 (2,'College of Criminal Justice Education (CCJE)','college'),
 (3,'College of Business and Accountancy (CBA)','college'),
 (4,'College of Accountancy (COA)','college'),
 (5,'College of Computer Studies (CCS)','college'),
 (6,'College of Teacher Education (CTE)','college'),
 (7,'Maintenance','work_area'),
 (8,'IT','work_area'),
 (9,'Security','work_area'),
 (10,'General Services','work_area'),
 (11,'Administration','administration');

-- users: 1 admin, 4 staff, 4 students
INSERT INTO users (user_id, role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, contact_number, course, year_level, section, availability) VALUES
 (1,'admin','ADM-0001','Maria','C','Santos','admin@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',11,'System Administrator','0917 100 0001',NULL,NULL,NULL,'available'),
 (2,'staff','STF-0101','Roberto','M','Dela Peña','roberto.delapena@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',7,'Maintenance Supervisor','0917 100 0101',NULL,NULL,NULL,'available'),
 (3,'staff','STF-0102','Angelica','R','Ramos','angelica.ramos@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',8,'IT Support Specialist','0917 100 0102',NULL,NULL,NULL,'available'),
 (4,'staff','STF-0103','Mario','V','Villanueva','mario.villanueva@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',9,'Security Officer','0917 100 0103',NULL,NULL,NULL,'busy'),
 (5,'staff','STF-0104','Jenny','L','Mercado','jenny.mercado@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',10,'General Services Aide','0917 100 0104',NULL,NULL,NULL,'available'),
 (6,'student','2023-0001','Juan','A','Dela Cruz','juan.delacruz@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',5,NULL,'0918 200 0001','BS Information Technology',3,'3A','available'),
 (7,'student','2023-0002','Maria','L','Reyes','maria.reyes@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',1,NULL,'0918 200 0002','BA Communication',2,'2B','available'),
 (8,'student','2022-0105','Paolo','','Santos','paolo.santos@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',3,NULL,'0918 200 0003','BS Business Administration',4,'4C','available'),
 (9,'student','2024-0210','Angela','T','Garcia','angela.garcia@cityofmalabonuniversity.edu.ph','$2y$10$R4U17P39OIYRvKlFf8dMBuJ1AYW4xRcWWeeJboSVkz91929SVmBQG',6,NULL,'0918 200 0004','BS Elementary Education',1,'1A','available');

INSERT INTO categories (category_id, category_name, description, default_department_id) VALUES
 (1,'Facilities','Furniture, fixtures, doors, windows and equipment',7),
 (2,'Electrical','Lights, outlets, wiring and power concerns',7),
 (3,'Plumbing & Water','Leaks, water supply and drainage',7),
 (4,'Cleanliness & Sanitation','Cleaning, waste and hygiene',10),
 (5,'IT & Internet','Computers, Wi-Fi, systems and connectivity',8),
 (6,'Safety & Security','Hazards, security and safety incidents',9),
 (7,'Lost & Found','Lost or found items',10),
 (8,'Other','Anything else related to the university',11);

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
 ('student_signup','1'),
 ('signup_email_domain','cityofmalabonuniversity.edu.ph'),
 ('contact_email','cmuofficial@cityofmalabonuniversity.edu.ph'),
 ('contact_address','Pampano cor. Maya-Maya Sts., Longos, City of Malabon');

INSERT INTO working_hours (day_of_week, open_time, close_time, active) VALUES
 (0,NULL,NULL,0),(1,'09:00','16:00',1),(2,'09:00','16:00',1),(3,'09:00','16:00',1),
 (4,'09:00','16:00',1),(5,'09:00','16:00',1),(6,NULL,NULL,0);

-- concerns (dates are relative to import time so the demo always looks current)
INSERT INTO concerns (concern_id, concern_code, student_id, category_id, location_id, severity, status, title, description, is_anonymous, due_at, created_at, resolved_at, closed_at) VALUES
 (1,'CV-2026-0001',6,5,5,'Major','In Progress','Wi-Fi keeps disconnecting in CompLab 1','The wireless connection drops every few minutes during class. Several classmates are affected and it interrupts our online exams.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 1 DAY,NULL,NULL),
 (2,'CV-2026-0002',7,2,4,'Urgent','Assigned','Exposed wires near the ceiling fan','There are exposed wires hanging beside the ceiling fan in Room 204. It sparks sometimes when the fan is turned on. Please check it as soon as possible.',1,NOW()+INTERVAL 20 HOUR,NOW()-INTERVAL 4 HOUR,NULL,NULL),
 (3,'CV-2026-0003',8,3,10,'Major','Under Review','Leaking faucet in the canteen','The faucet at the hand-washing area keeps leaking and the floor is slippery.',0,NOW()+INTERVAL 3 DAY,NOW()-INTERVAL 5 HOUR,NULL,NULL),
 (4,'CV-2026-0004',9,4,2,'Minor','Submitted','Trash bins overflowing at the lobby','The bins beside the guard post are full by noon and the smell reaches the lobby.',0,NOW()+INTERVAL 7 DAY,NOW()-INTERVAL 40 MINUTE,NULL,NULL),
 (5,'CV-2026-0005',6,1,3,'Minor','Resolved','Broken chair in Room 201','Two chairs have broken backrests. Someone could get hurt.',0,NOW()-INTERVAL 1 DAY,NOW()-INTERVAL 6 DAY,NOW()-INTERVAL 2 DAY,NULL),
 (6,'CV-2026-0006',7,6,12,'Urgent','Closed','Gate light is out at night','The main gate is completely dark after 6 PM. It is unsafe for students leaving late.',1,NOW()-INTERVAL 8 DAY,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY,NOW()-INTERVAL 8 DAY),
 (7,'CV-2026-0007',8,5,6,'Major','Waiting for Resources','Projector not working in CompLab 2','The projector shows no signal. IT said a replacement lamp is needed.',0,NOW()+INTERVAL 1 DAY,NOW()-INTERVAL 3 DAY,NULL,NULL),
 (8,'CV-2026-0008',9,7,7,'Minor','Closed','Lost umbrella at the library','I left a black umbrella at the reading area yesterday afternoon.',0,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY,NOW()-INTERVAL 10 DAY),
 (9,'CV-2026-0009',6,4,9,'Major','Assigned','Dirty restroom near the gymnasium','The restroom has not been cleaned for days and the sink is clogged.',0,NOW()+INTERVAL 2 DAY,NOW()-INTERVAL 2 HOUR,NULL,NULL),
 (10,'CV-2026-0010',8,6,11,'Urgent','In Progress','Loose railing on the courtyard steps','The metal railing on the steps is loose and wobbles when you hold it.',1,NOW()+INTERVAL 6 HOUR,NOW()-INTERVAL 18 HOUR,NULL,NULL);

INSERT INTO assignments (concern_id, staff_id, assigned_by, assigned_at, assignment_status, reason, seen_at, completed_at) VALUES
 (1,3,1,NOW()-INTERVAL 23 HOUR,'active',NULL,NOW()-INTERVAL 22 HOUR,NULL),
 (2,2,1,NOW()-INTERVAL 3 HOUR,'active',NULL,NULL,NULL),
 (5,2,1,NOW()-INTERVAL 5 DAY,'completed',NULL,NOW()-INTERVAL 5 DAY,NOW()-INTERVAL 2 DAY),
 (6,4,1,NOW()-INTERVAL 10 DAY,'completed',NULL,NOW()-INTERVAL 10 DAY,NOW()-INTERVAL 9 DAY),
 (7,3,1,NOW()-INTERVAL 3 DAY,'active',NULL,NOW()-INTERVAL 3 DAY,NULL),
 (8,5,1,NOW()-INTERVAL 12 DAY,'completed',NULL,NOW()-INTERVAL 12 DAY,NOW()-INTERVAL 11 DAY),
 (9,5,1,NOW()-INTERVAL 1 HOUR,'active',NULL,NULL,NULL),
 (10,4,1,NOW()-INTERVAL 17 HOUR,'reassigned','Staff was on another urgent call',NOW()-INTERVAL 17 HOUR,NULL),
 (10,2,1,NOW()-INTERVAL 16 HOUR,'active','Reassigned to Maintenance',NOW()-INTERVAL 15 HOUR,NULL);

INSERT INTO concern_updates (concern_id, user_id, status, update_type, response, created_at) VALUES
 (1,6,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 1 DAY),
 (1,1,'Assigned','Assigned','Assigned to IT Support.',NOW()-INTERVAL 23 HOUR),
 (1,3,'In Progress','Status Updated','Checking the access point and switch in CompLab 1.',NOW()-INTERVAL 20 HOUR),
 (2,7,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 4 HOUR),
 (2,1,'Assigned','Assigned','Marked Urgent and assigned to Maintenance.',NOW()-INTERVAL 3 HOUR),
 (3,8,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 5 HOUR),
 (3,1,'Under Review','Status Updated','The administrator is reviewing this concern.',NOW()-INTERVAL 4 HOUR),
 (4,9,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 40 MINUTE),
 (5,6,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 6 DAY),
 (5,1,'Assigned','Assigned','Assigned to Maintenance.',NOW()-INTERVAL 5 DAY),
 (5,2,'In Progress','Status Updated','Chairs collected for repair.',NOW()-INTERVAL 4 DAY),
 (5,2,'Resolved','Resolved','Both chairs repaired and returned to Room 201.',NOW()-INTERVAL 2 DAY),
 (6,7,'Submitted','Concern Submitted','Your concern has been received and is awaiting review.',NOW()-INTERVAL 10 DAY),
 (6,4,'Resolved','Resolved','Replaced the gate light fixture.',NOW()-INTERVAL 9 DAY),
 (6,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 8 DAY),
 (7,3,'Waiting for Resources','Status Updated','Waiting for a replacement projector lamp from procurement.',NOW()-INTERVAL 2 DAY),
 (8,5,'Resolved','Resolved','Umbrella found and returned to the owner.',NOW()-INTERVAL 11 DAY),
 (8,1,'Closed','Closed','Resolution confirmed and concern closed.',NOW()-INTERVAL 10 DAY),
 (9,1,'Assigned','Assigned','Assigned to General Services.',NOW()-INTERVAL 1 HOUR),
 (10,2,'In Progress','Status Updated','Railing barricaded; welder scheduled for this afternoon.',NOW()-INTERVAL 14 HOUR);

INSERT INTO feedback (concern_id, student_id, rating, comment, created_at) VALUES
 (8,9,5,'Thank you, got my umbrella back the same day!',NOW()-INTERVAL 10 DAY),
 (6,7,4,'Fixed quickly. Much safer now.',NOW()-INTERVAL 8 DAY);

INSERT INTO notifications (user_id, concern_id, title, message, is_urgent, is_read, created_at) VALUES
 (1,2,'Urgent concern','CV-2026-0002 — Exposed wires near the ceiling fan needs immediate attention.',1,0,NOW()-INTERVAL 4 HOUR),
 (1,10,'Urgent concern','CV-2026-0010 — Loose railing on the courtyard steps needs immediate attention.',1,0,NOW()-INTERVAL 18 HOUR),
 (1,4,'New concern','CV-2026-0004 — Trash bins overflowing at the lobby was submitted.',0,0,NOW()-INTERVAL 40 MINUTE),
 (2,2,'New assignment','You were assigned CV-2026-0002 (Urgent).',1,0,NOW()-INTERVAL 3 HOUR),
 (3,1,'Status noted','CV-2026-0001 is now In Progress.',0,1,NOW()-INTERVAL 20 HOUR),
 (6,1,'Concern update','CV-2026-0001 is now In Progress.',0,0,NOW()-INTERVAL 20 HOUR),
 (6,5,'Concern resolved','CV-2026-0005 was marked as resolved.',0,0,NOW()-INTERVAL 2 DAY);

INSERT INTO events (title, start_at, end_at, notes, created_by) VALUES
 ('Campus facilities inspection', NOW()+INTERVAL 3 DAY, NULL, 'Quarterly walkthrough with Maintenance.',1),
 ('Wi-Fi upgrade window', NOW()+INTERVAL 5 DAY, NOW()+INTERVAL 5 DAY + INTERVAL 3 HOUR, 'Expect brief network downtime in the Main Building.',1);

INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address, created_at) VALUES
 (1,'Maria C. Santos <admin@cityofmalabonuniversity.edu.ph>','admin','login','Signed in','127.0.0.1',NOW()-INTERVAL 1 DAY),
 (6,'Juan A. Dela Cruz <juan.delacruz@cityofmalabonuniversity.edu.ph>','student','concern_created','Created CV-2026-0001',  '127.0.0.1',NOW()-INTERVAL 1 DAY),
 (1,'Maria C. Santos <admin@cityofmalabonuniversity.edu.ph>','admin','concern_assigned','CV-2026-0001 assigned to Angelica Ramos','127.0.0.1',NOW()-INTERVAL 23 HOUR),
 (3,'Angelica R. Ramos <angelica.ramos@cityofmalabonuniversity.edu.ph>','staff','concern_updated','CV-2026-0001 set to In Progress','127.0.0.1',NOW()-INTERVAL 20 HOUR),
 (1,'Maria C. Santos <admin@cityofmalabonuniversity.edu.ph>','admin','concern_reassigned','CV-2026-0010 reassigned to Roberto Dela Peña','127.0.0.1',NOW()-INTERVAL 16 HOUR),
 (2,'Roberto M. Dela Peña <roberto.delapena@cityofmalabonuniversity.edu.ph>','staff','concern_resolved','CV-2026-0005 marked Resolved','127.0.0.1',NOW()-INTERVAL 2 DAY);
