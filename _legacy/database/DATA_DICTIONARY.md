# CampusVoice Data Dictionary — Final

| Table | Primary Key | Main fields / purpose |
|---|---|---|
| `departments` | `department_id` | `department_name`, `department_type`, `description`, `status` — colleges, work areas, administration |
| `users` | `user_id` | `student_id`, `staff_id`, `admin_id`, `first_name`, `middle_initial`, `last_name`, `email`, `role`, `department_id`, `position`, `coverage`, `contact_number`, `profile_picture`, `account_status` |
| `categories` | `category_id` | `category_name`, `description`, `status`, `created_by` |
| `locations` | `location_id` | `building`, `floor`, `area`, `status`, `created_by` |
| `priorities` | `priority_id` | `priority_name`, `description`, response/resolution target hours, `status` |
| `statuses` | `status_id` | `status_name`, `description`, `status_type` |
| `concerns` | `concern_id` | `concern_code`, student, category, location, priority, status, title, description, submitted/updated/resolved/closed timestamps |
| `assignments` | `assignment_id` | concern, staff, assigning administrator, assigned/reassigned dates, assignment status, reason, completion date |
| `concern_updates` | `update_id` | concern, user, status, update type, response, action taken, timestamp |
| `photos` | `photo_id` | concern, uploader, photo type, storage path, timestamp |
| `notifications` | `notification_id` | recipient, optional concern, title, message, read state, timestamp |
| `feedback` | `feedback_id` | concern, student, rating, comment, timestamp |
| `activity_logs` | `activity_id` | user, action, reference, details, timestamp |
| `working_hours` | `working_hours_id` | day of week, opening/closing time, active flag |
| `holidays` | `holiday_id` | holiday date, name, active flag |
| `system_settings` | `setting_key` | JSON setting value, updated by, updated timestamp |

## Key constraints

- Email, Student ID, Staff ID, and Admin ID are unique.
- A Student must have a Student ID.
- A Staff member must have a Staff ID.
- An Administrator/Super Admin must have an Admin ID.
- A concern owner must be an active Student.
- An assignment target must be an active Staff member.
- An assignment creator must be an active Administrator or Super Admin.
- Only one assignment can be active for a concern at a time.
- Feedback must belong to the Student who submitted the concern.
- Feedback rating is 1–5.
- Concern titles/descriptions cannot be blank.
- Working hours must contain a valid time range when active.
