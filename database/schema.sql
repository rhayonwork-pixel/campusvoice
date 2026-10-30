-- CampusVoice — MySQL / MariaDB schema (InfinityFree compatible)
-- Import this file first, then seed.sql.
-- On InfinityFree: skip the CREATE DATABASE / USE lines and import into the database you created in the control panel.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS campusvoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusvoice;

DROP TABLE IF EXISTS events, feedback, notifications, photos, concern_updates, assignments, concerns,
  working_hours, settings, audit_logs, password_resets, login_attempts,
  locations, categories, users, departments;

CREATE TABLE departments (
  department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_name VARCHAR(150) NOT NULL UNIQUE,
  department_type ENUM('college','work_area','administration') NOT NULL DEFAULT 'college'
) ENGINE=InnoDB;

CREATE TABLE users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
  school_id VARCHAR(30) NOT NULL UNIQUE,            -- student no. / staff no. / admin no.
  first_name VARCHAR(80) NOT NULL,
  middle_initial CHAR(2) NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  department_id INT UNSIGNED NULL,
  position VARCHAR(120) NULL,
  contact_number VARCHAR(30) NULL,
  course VARCHAR(60) NULL,                           -- student only
  year_level TINYINT UNSIGNED NULL,                  -- student only
  section VARCHAR(20) NULL,                          -- student only
  avatar VARCHAR(255) NULL,
  availability ENUM('available','busy','on_leave') NOT NULL DEFAULT 'available',  -- staff only
  account_status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  pref_notify_inapp TINYINT(1) NOT NULL DEFAULT 1,
  pref_notify_email TINYINT(1) NOT NULL DEFAULT 1,
  pref_default_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_users_role (role, account_status),
  CONSTRAINT fk_users_dept FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  attempt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_email (email, created_at),
  KEY idx_attempt_ip (ip_address, created_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  reset_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,                      -- sha256 of the emailed token / admin-issued code
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reset_hash (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  default_department_id INT UNSIGNED NULL,           -- used to suggest staff on assignment
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_cat_dept FOREIGN KEY (default_department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE locations (
  location_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  building VARCHAR(100) NOT NULL,
  floor VARCHAR(40) NOT NULL,
  area VARCHAR(100) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  UNIQUE KEY uq_location (building, floor, area)
) ENGINE=InnoDB;

CREATE TABLE concerns (
  concern_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_code VARCHAR(20) NOT NULL UNIQUE,
  student_id INT UNSIGNED NOT NULL,                  -- always stored; hidden from staff/admin when is_anonymous = 1
  category_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  severity ENUM('Minor','Major','Urgent') NOT NULL DEFAULT 'Minor',
  status ENUM('Submitted','Under Review','Assigned','In Progress','Waiting for Resources','Resolved','Reopened','Closed','Cancelled') NOT NULL DEFAULT 'Submitted',
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  due_at DATETIME NULL,                              -- deadline derived from severity
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  closed_at DATETIME NULL,
  KEY idx_concern_student (student_id),
  KEY idx_concern_status (status, severity),
  KEY idx_concern_due (due_at),
  CONSTRAINT fk_con_student FOREIGN KEY (student_id) REFERENCES users(user_id),
  CONSTRAINT fk_con_cat FOREIGN KEY (category_id) REFERENCES categories(category_id),
  CONSTRAINT fk_con_loc FOREIGN KEY (location_id) REFERENCES locations(location_id)
) ENGINE=InnoDB;

CREATE TABLE assignments (
  assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL,
  staff_id INT UNSIGNED NOT NULL,
  assigned_by INT UNSIGNED NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  assignment_status ENUM('active','reassigned','completed') NOT NULL DEFAULT 'active',
  reason VARCHAR(255) NULL,
  seen_at DATETIME NULL,                             -- NULL = still "New" for the staff member
  completed_at DATETIME NULL,
  KEY idx_asg_staff (staff_id, assignment_status),
  KEY idx_asg_concern (concern_id, assignment_status),
  CONSTRAINT fk_asg_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_asg_staff FOREIGN KEY (staff_id) REFERENCES users(user_id),
  CONSTRAINT fk_asg_by FOREIGN KEY (assigned_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE concern_updates (
  update_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  status VARCHAR(30) NULL,
  update_type VARCHAR(60) NOT NULL,
  response TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_upd_concern (concern_id, created_at),
  CONSTRAINT fk_upd_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_upd_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE photos (
  photo_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL,
  photo_type ENUM('supporting','before','after') NOT NULL DEFAULT 'supporting',
  file_path VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_photo_concern (concern_id),
  CONSTRAINT fk_photo_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_photo_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  concern_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(500) NOT NULL,
  is_urgent TINYINT(1) NOT NULL DEFAULT 0,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id, is_read, created_at),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_notif_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE feedback (
  feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL UNIQUE,
  student_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_fb_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_fb_student FOREIGN KEY (student_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  user_label VARCHAR(190) NOT NULL,                  -- snapshot so history survives user deletion
  role VARCHAR(20) NOT NULL,
  action VARCHAR(60) NOT NULL,                       -- e.g. login, logout, concern_created
  details VARCHAR(500) NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_time (created_at),
  KEY idx_audit_action (action),
  KEY idx_audit_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(60) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE working_hours (
  day_of_week TINYINT UNSIGNED PRIMARY KEY,           -- 0 = Sunday
  open_time TIME NULL,
  close_time TIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE events (
  event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NULL,
  notes VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  CONSTRAINT fk_event_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
