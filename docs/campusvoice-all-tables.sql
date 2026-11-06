-- =====================================================================
--  CampusVoice — complete database (18 tables)
--  City of Malabon University · Student Affairs and Service Office (SASO)
--  MySQL 8 / MariaDB · InnoDB · utf8mb4
--
--  To use: select an EMPTY database in phpMyAdmin → SQL tab → paste → Go.
--
--  Groups
--    A. People and offices ...... departments, users
--    B. Concern types ........... categories, category_handlers, locations
--    C. Concerns ................ concerns
--    D. Concern activity ........ assignments, visits, concern_updates,
--                                 photos, feedback, notifications
--    E. Security ................ login_attempts, password_resets, audit_logs
--    F. System .................. settings, working_hours, events
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
  visits, category_handlers, events, feedback, notifications, photos,
  concern_updates, assignments, concerns, working_hours, settings,
  audit_logs, password_resets, login_attempts, locations, categories,
  users, departments;


-- =====================================================================
--  A. PEOPLE AND OFFICES
-- =====================================================================

-- 1. departments: colleges and SASO offices
CREATE TABLE departments (
  department_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_name  VARCHAR(150) NOT NULL UNIQUE,
  department_type  ENUM('college','work_area','administration') NOT NULL DEFAULT 'college'
) ENGINE=InnoDB;

-- 2. users: students, SASO staff and admins (username = school_id)
CREATE TABLE users (
  user_id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role                    ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
  school_id               VARCHAR(30)  NOT NULL UNIQUE,
  first_name              VARCHAR(80)  NOT NULL,
  middle_initial          CHAR(2)      NULL,
  last_name               VARCHAR(80)  NOT NULL,
  email                   VARCHAR(190) NULL UNIQUE,
  password_hash           VARCHAR(255) NOT NULL,
  department_id           INT UNSIGNED NULL,
  position                VARCHAR(120) NULL,
  contact_number          VARCHAR(30)  NULL,
  course                  VARCHAR(60)  NULL,            -- students
  year_level              TINYINT UNSIGNED NULL,        -- students
  section                 VARCHAR(20)  NULL,            -- students
  avatar                  VARCHAR(255) NULL,
  availability            ENUM('available','busy','on_leave') NOT NULL DEFAULT 'available',  -- staff
  account_status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  must_change_password    TINYINT(1) NOT NULL DEFAULT 0,
  pref_notify_inapp       TINYINT(1) NOT NULL DEFAULT 1,
  pref_notify_email       TINYINT(1) NOT NULL DEFAULT 1,
  pref_default_anonymous  TINYINT(1) NOT NULL DEFAULT 0,
  google_sub              VARCHAR(64)  NULL UNIQUE,     -- linked Google account
  google_email            VARCHAR(190) NULL,
  google_linked_at        DATETIME NULL,
  last_login_at           DATETIME NULL,
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_users_role (role, account_status),
  CONSTRAINT fk_users_dept FOREIGN KEY (department_id)
    REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- =====================================================================
--  B. CONCERN TYPES (SASO areas) AND PLACES
-- =====================================================================

-- 3. categories: the SASO areas a student can choose
CREATE TABLE categories (
  category_id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name          VARCHAR(100) NOT NULL UNIQUE,
  description            VARCHAR(255) NULL,
  default_department_id  INT UNSIGNED NULL,
  needs_location         TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = ask building/floor/room
  next_steps             VARCHAR(500) NULL,               -- shown to the student
  status                 ENUM('active','inactive') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_cat_dept FOREIGN KEY (default_department_id)
    REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. category_handlers: the owner (staff) of each concern type
CREATE TABLE category_handlers (
  category_id  INT UNSIGNED NOT NULL,
  staff_id     INT UNSIGNED NOT NULL,
  PRIMARY KEY (category_id, staff_id),
  CONSTRAINT fk_ch_cat   FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
  CONSTRAINT fk_ch_staff FOREIGN KEY (staff_id)    REFERENCES users(user_id)          ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. locations: campus places (for Facility concerns)
CREATE TABLE locations (
  location_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building     VARCHAR(100) NOT NULL,
  floor        VARCHAR(40)  NOT NULL,
  area         VARCHAR(100) NOT NULL,
  status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  UNIQUE KEY uq_location (building, floor, area)
) ENGINE=InnoDB;


-- =====================================================================
--  C. CONCERNS
-- =====================================================================

-- 6. concerns: what the student reports
CREATE TABLE concerns (
  concern_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_code   VARCHAR(20) NOT NULL UNIQUE,            -- e.g. CV-2026-0001
  student_id     INT UNSIGNED NOT NULL,                  -- hidden from staff when anonymous
  category_id    INT UNSIGNED NOT NULL,
  location_id    INT UNSIGNED NULL,
  severity       ENUM('Minor','Major','Urgent') NOT NULL DEFAULT 'Minor',
  status         ENUM('Submitted','Pending','Under Review','Additional Info Needed','Assigned',
                      'In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled')
                 NOT NULL DEFAULT 'Submitted',
  title          VARCHAR(150) NOT NULL,
  description    TEXT NOT NULL,
  is_anonymous   TINYINT(1) NOT NULL DEFAULT 0,
  wants_meeting  TINYINT(1) NOT NULL DEFAULT 0,          -- asked to meet face to face
  meeting_pref   VARCHAR(120) NULL,
  due_at         DATETIME NULL,                          -- deadline from severity
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at    DATETIME NULL,
  closed_at      DATETIME NULL,
  KEY idx_concern_student (student_id),
  KEY idx_concern_status (status, severity),
  KEY idx_concern_due (due_at),
  CONSTRAINT fk_con_student FOREIGN KEY (student_id)  REFERENCES users(user_id),
  CONSTRAINT fk_con_cat     FOREIGN KEY (category_id) REFERENCES categories(category_id),
  CONSTRAINT fk_con_loc     FOREIGN KEY (location_id) REFERENCES locations(location_id)
) ENGINE=InnoDB;


-- =====================================================================
--  D. CONCERN ACTIVITY
-- =====================================================================

-- 7. assignments: which staff handles a concern (history kept)
CREATE TABLE assignments (
  assignment_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id         INT UNSIGNED NOT NULL,
  staff_id           INT UNSIGNED NOT NULL,
  assigned_by        INT UNSIGNED NULL,                  -- NULL = routed automatically
  assigned_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  assignment_status  ENUM('active','reassigned','completed') NOT NULL DEFAULT 'active',
  reason             VARCHAR(255) NULL,
  seen_at            DATETIME NULL,
  completed_at       DATETIME NULL,
  KEY idx_asg_staff (staff_id, assignment_status),
  KEY idx_asg_concern (concern_id, assignment_status),
  CONSTRAINT fk_asg_concern FOREIGN KEY (concern_id)  REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_asg_staff   FOREIGN KEY (staff_id)    REFERENCES users(user_id),
  CONSTRAINT fk_asg_by      FOREIGN KEY (assigned_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 8. visits: face-to-face meetings at the SASO office
CREATE TABLE visits (
  visit_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id    INT UNSIGNED NOT NULL,
  scheduled_by  INT UNSIGNED NULL,
  scheduled_at  DATETIME NOT NULL,
  place         VARCHAR(120) NOT NULL DEFAULT 'SASO Office',
  note          VARCHAR(255) NULL,                       -- message for the student
  status        ENUM('scheduled','confirmed','reschedule','attended','no_show','cancelled')
                NOT NULL DEFAULT 'scheduled',
  student_note  VARCHAR(255) NULL,                       -- why another time is needed
  outcome_note  VARCHAR(255) NULL,                       -- staff note after the meeting
  responded_at  DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_visit_concern (concern_id, scheduled_at),
  KEY idx_visit_when (scheduled_at, status),
  CONSTRAINT fk_visit_concern FOREIGN KEY (concern_id)   REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_visit_by      FOREIGN KEY (scheduled_by) REFERENCES users(user_id)       ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. concern_updates: the timeline of a concern
CREATE TABLE concern_updates (
  update_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id   INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NULL,                        -- NULL = done by the system
  status       VARCHAR(30) NULL,
  update_type  VARCHAR(60) NOT NULL,
  response     TEXT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_upd_concern (concern_id, created_at),
  CONSTRAINT fk_upd_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_upd_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. photos: pictures attached to a concern
CREATE TABLE photos (
  photo_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id   INT UNSIGNED NOT NULL,
  uploaded_by  INT UNSIGNED NOT NULL,
  photo_type   ENUM('supporting','before','after') NOT NULL DEFAULT 'supporting',
  file_path    VARCHAR(255) NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_photo_concern (concern_id),
  CONSTRAINT fk_photo_concern FOREIGN KEY (concern_id)  REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_photo_user    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 11. feedback: the student's rating after the concern is resolved (one per concern)
CREATE TABLE feedback (
  feedback_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id   INT UNSIGNED NOT NULL UNIQUE,
  student_id   INT UNSIGNED NOT NULL,
  rating       TINYINT UNSIGNED NOT NULL,
  comment      VARCHAR(500) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_fb_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_fb_student FOREIGN KEY (student_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 12. notifications: in-app alerts (bell icon)
CREATE TABLE notifications (
  notification_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NOT NULL,
  concern_id       INT UNSIGNED NULL,
  title            VARCHAR(150) NOT NULL,
  message          VARCHAR(500) NOT NULL,
  is_urgent        TINYINT(1) NOT NULL DEFAULT 0,
  is_read          TINYINT(1) NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id, is_read, created_at),
  CONSTRAINT fk_notif_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
  CONSTRAINT fk_notif_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =====================================================================
--  E. SECURITY
-- =====================================================================

-- 13. login_attempts: used for the 3-try / 5-minute lock
CREATE TABLE login_attempts (
  attempt_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(190) NOT NULL,                     -- the username or email that was tried
  ip_address  VARCHAR(45)  NOT NULL,
  success     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_email (email, created_at),
  KEY idx_attempt_ip (ip_address, created_at)
) ENGINE=InnoDB;

-- 14. password_resets: one-time reset codes (only the hash is stored)
CREATE TABLE password_resets (
  reset_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reset_hash (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 15. audit_logs: who did what and when (kept even if the user is deleted)
CREATE TABLE audit_logs (
  log_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,                         -- no FK on purpose: logs must survive
  user_label  VARCHAR(190) NOT NULL,
  role        VARCHAR(20)  NOT NULL,
  action      VARCHAR(60)  NOT NULL,
  details     VARCHAR(500) NULL,
  ip_address  VARCHAR(45)  NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_time (created_at),
  KEY idx_audit_action (action),
  KEY idx_audit_user (user_id)
) ENGINE=InnoDB;


-- =====================================================================
--  F. SYSTEM
-- =====================================================================

-- 16. settings: system options (routing on/off, deadlines, lock rules)
CREATE TABLE settings (
  setting_key    VARCHAR(60)  PRIMARY KEY,
  setting_value  VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- 17. working_hours: office hours per day (0 = Sunday)
CREATE TABLE working_hours (
  day_of_week  TINYINT UNSIGNED PRIMARY KEY,
  open_time    TIME NULL,
  close_time   TIME NULL,
  active       TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 18. events: calendar events added by the admin
CREATE TABLE events (
  event_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  start_at    DATETIME NOT NULL,
  end_at      DATETIME NULL,
  notes       VARCHAR(255) NULL,
  created_by  INT UNSIGNED NULL,
  CONSTRAINT fk_event_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
