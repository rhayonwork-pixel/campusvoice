# CampusVoice Database Handoff Checklist

This document is for the two backend developers. It defines the database contract for the frontend/database owner.

## Required relationships
- `users 1:N concerns` through `concerns.student_id`
- `departments 1:N users` through `users.department_id`
- `categories 1:N concerns` through `concerns.category_id`
- `locations 1:N concerns` through `concerns.location_id`
- `priorities 1:N concerns` through `concerns.priority_id`
- `statuses 1:N concerns` through `concerns.status_id`
- `concerns 1:N assignments`
- `users 1:N assignments` as staff
- `users 1:N assignments` as assigning administrator
- `concerns 1:N concern_updates`
- `concerns 1:N photos`
- `users 1:N notifications`
- `concerns 1:0..1 feedback`

## Frontend-to-database field mapping
### Student
- First Name → `users.first_name`
- Middle Initial → `users.middle_initial`
- Last Name → `users.last_name`
- Student ID → `users.student_id`
- CMU Email → `users.email`
- College/Department → `users.department_id`
- Contact Number → `users.contact_number`
- Profile Picture → `users.profile_picture`

### Concern
- Concern ID → `concerns.concern_code`
- Title → `concerns.title`
- Description → `concerns.description`
- Category → `concerns.category_id`
- Building/Floor/Area → `concerns.location_id`
- Priority → `concerns.priority_id`
- Status → `concerns.status_id`
- Date Submitted → `concerns.date_submitted`

### Staff
- Staff ID → `users.staff_id`
- Department/Work Area → `users.department_id`
- Position → `users.position`
- Work Area/Coverage → `users.coverage`

### Assignment
- Assigned Staff → `assignments.staff_id`
- Assigned By → `assignments.assigned_by`
- Assigned Date → `assignments.assigned_at`
- Reassignment → new active assignment + previous assignment marked `reassigned`
- Reason → `assignments.reason`

### Staff update
- Status → `concern_updates.status_id`
- Student-facing Response → `concern_updates.response`
- Actual work → `concern_updates.action_taken`
- Evidence → `photos` with `photo_type = evidence/before/after`

## Important implementation rules
1. Do not store passwords in `public.users`.
2. Do not expose a Supabase service-role/secret key in the browser.
3. Do not allow public signup to create Staff/Admin/Super Admin roles.
4. Do not bypass RLS from the client.
5. Use the database's `concern_code`; do not generate permanent concern IDs only in JavaScript.
6. Do not hard-delete concern records as part of normal workflow.
7. For reassignment, preserve history instead of overwriting the old assignment.
8. Student-facing response and Action Taken are separate fields.
9. `Waiting for Resources` is a real status and should not be treated as Staff failure.
10. Use working hours and holidays for resolution-time calculations.

## Final workflow rules (v3)
- Students do not select concern priority. New concerns are submitted without a priority and start with `Submitted`.
- Administrator/Super Admin assigns priority during review.
- Students may select only active categories and active locations.
- Active assignment must point to an active Staff account; one active assignment per concern is enforced.
- Database triggers create notifications for administrators when a new concern is submitted, for staff when a concern is assigned, and for students when staff/admin updates a concern timeline.
- Student submission timeline entries are limited to the `Concern Submitted` event.
