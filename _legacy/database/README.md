# CampusVoice Database — Final Handoff

## Database technology
The current implementation package uses **Supabase PostgreSQL**. Supabase Auth stores passwords and sessions; `public.users` stores CampusVoice profile and role information.

> **Important thesis consistency note:** an earlier Chapter 2 draft describes the Database Layer as **MySQL**. This implementation uses Supabase/PostgreSQL. The thesis/database documentation must use one database technology consistently. Do not present MySQL and Supabase PostgreSQL as the same implementation.

## Final database entities

### Core
- `users` — Student, Staff, Administrator, Super Admin profiles and roles
- `departments` — colleges, work areas, and administration groups
- `categories` — concern categories
- `locations` — Building + Floor + Area/Room
- `priorities` — Low, Medium, High, Urgent
- `statuses` — Submitted, Under Review, Assigned, In Progress, Waiting for Resources, Resolved, Reopened, Closed, Cancelled
- `concerns` — submitted concern records
- `assignments` — staff assignment and reassignment history
- `concern_updates` — response/action/status history
- `photos` — supporting, evidence, before, and after photos
- `notifications` — user notifications
- `feedback` — student resolution feedback/rating
- `activity_logs` — system activity records

### System/configuration
- `working_hours` — Monday–Friday, 9:00 AM–4:00 PM default; weekends inactive
- `holidays` — non-working dates
- `system_settings` — configurable system values

## Final role rules
- **Student:** creates and views only their own concerns; can add supporting photos and feedback.
- **Staff:** sees only active assignments; can add work updates and evidence/before/after photos for assigned concerns.
- **Admin:** manages concerns, assignments, categories, locations, priorities, updates, photos, and users within granted administrative permissions.
- **Super Admin:** full administrative control, including user management, working hours, holidays, and system settings.

## Data integrity included
- Unique Student ID, Staff ID, Admin ID, and email.
- Student concerns must belong to an active Student account.
- Assignments must target an active Staff account and be created by an active Administrator/Super Admin.
- Only one active assignment is allowed per concern; old assignments remain as reassignment history.
- Feedback can only be submitted by the student who owns the concern.
- User-sensitive fields are protected from ordinary self-editing.
- Public signup is always created as a Student; privileged roles must be provisioned through the authorized administrative/backend process.
- Concern IDs use the `CV-YYYY-####` format.
- RLS policies restrict Students, Staff, Admins, and Super Admins to their intended records.
- Storage access is restricted to the concern owner, assigned staff, and administrators.

## Seed reference data
### Colleges
- College of Arts and Sciences (CAS)
- College of Criminal Justice Education (CCJE)
- College of Business and Accountancy (CBA)
- College of Accountancy (COA)
- College of Computer Studies (CCS)
- College of Teacher Education (CTE)

### Staff work areas
- Maintenance
- IT
- Security
- General Services
- Administration

### Categories
Facilities; Electrical; Plumbing & Water; Cleanliness & Sanitation; IT & Internet; Safety & Security; Lost & Found; Other.

### Default working hours
Monday–Friday: **9:00 AM–4:00 PM**
Saturday–Sunday: inactive

Student submissions can be accepted at any time; processing-time calculations should use the configured working calendar and holidays.

## Handoff
Backend members should use this schema as the database contract. They are responsible for connecting the application services/API to these tables and handling server-side workflows that are not performed by the database itself.
