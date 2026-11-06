-- CampusVoice upgrade: "Remember me" on the sign-in page.
-- Run ONCE in phpMyAdmin (click the database, SQL tab, paste, Go). Nothing is removed.
-- Until you run it, the site still works; the Remember me box simply does nothing.
ALTER TABLE users ADD COLUMN remember_selector CHAR(18) NULL UNIQUE AFTER last_login_at,
                  ADD COLUMN remember_hash CHAR(64) NULL AFTER remember_selector,
                  ADD COLUMN remember_expires DATETIME NULL AFTER remember_hash;
