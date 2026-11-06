-- CampusVoice upgrade: face-to-face visits (schedule, confirm, mark attended) + "I'd like to meet in person".
-- Run ONCE in phpMyAdmin on the existing database (click the database first, then SQL tab, paste, Go).
-- Needs database/upgrade-2026-11-saso.sql to have been run before. Nothing is removed.
SET NAMES utf8mb4;

ALTER TABLE concerns ADD COLUMN wants_meeting TINYINT(1) NOT NULL DEFAULT 0 AFTER is_anonymous,
                     ADD COLUMN meeting_pref VARCHAR(120) NULL AFTER wants_meeting;

CREATE TABLE IF NOT EXISTS visits (
  visit_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  concern_id INT UNSIGNED NOT NULL,
  scheduled_by INT UNSIGNED NULL,
  scheduled_at DATETIME NOT NULL,
  place VARCHAR(120) NOT NULL DEFAULT 'SASO Office',
  note VARCHAR(255) NULL,
  status ENUM('scheduled','confirmed','reschedule','attended','no_show','cancelled') NOT NULL DEFAULT 'scheduled',
  student_note VARCHAR(255) NULL,
  outcome_note VARCHAR(255) NULL,
  responded_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_visit_concern (concern_id, scheduled_at),
  KEY idx_visit_when (scheduled_at, status),
  CONSTRAINT fk_visit_concern FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
  CONSTRAINT fk_visit_by FOREIGN KEY (scheduled_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
