-- CampusVoice: clear the TEST data, keep the setup.
-- Run ONCE in phpMyAdmin (click the database, SQL tab, paste, Go). EXPORT A BACKUP FIRST.
--
-- DELETED (test data):
--   all concerns and everything attached to them (updates, assignments, meetings, photos, feedback),
--   all notifications, the audit log, login attempts, password-reset codes, calendar events,
--   and the 4 demo student accounts (2023-0001, 2023-0002, 2022-0105, 2024-0210).
--
-- KEPT (your setup):
--   admin and staff accounts, departments, the SASO concern types and their owners,
--   locations, settings, office hours. Any real students you imported are NOT touched.
--
-- Photos are files, not database rows: after this, also delete the files inside the
-- "uploads" folder on the server (FTP) so no old test pictures stay behind.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE visits;
TRUNCATE TABLE photos;
TRUNCATE TABLE feedback;
TRUNCATE TABLE notifications;
TRUNCATE TABLE concern_updates;
TRUNCATE TABLE assignments;
TRUNCATE TABLE concerns;
TRUNCATE TABLE events;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE login_attempts;
TRUNCATE TABLE password_resets;

DELETE FROM users WHERE role = 'student' AND school_id IN ('2023-0001', '2023-0002', '2022-0105', '2024-0210');

SET FOREIGN_KEY_CHECKS = 1;

-- quick check: what is left
SELECT 'concerns' AS what, COUNT(*) AS rows_left FROM concerns
UNION ALL SELECT 'students', COUNT(*) FROM users WHERE role = 'student'
UNION ALL SELECT 'staff and admin', COUNT(*) FROM users WHERE role <> 'student'
UNION ALL SELECT 'concern types (active)', COUNT(*) FROM categories WHERE status = 'active'
UNION ALL SELECT 'owners', COUNT(*) FROM category_handlers;
