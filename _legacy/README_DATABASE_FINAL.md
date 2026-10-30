# CampusVoice — Frontend + Database Final Package

This package contains the approved frontend prototype and the finalized database design for the member assigned to **Frontend + Database**.

## Frontend
- Student, Staff, and Super Admin interfaces are separated by role.
- Role-specific CSS files are preserved.
- Student signup uses the six participating colleges.
- The UI reflects the database entities for categories, locations, priorities, statuses, assignments, updates, photos, notifications, feedback, and user profiles.

## Database
The final schema is in:

`database/supabase_schema.sql`

Supporting database documentation:
- `database/README.md`
- `database/DATABASE_HANDOFF.md`
- `database/ERD_REFERENCE.md`
- `database/THESIS_DATABASE_ALIGNMENT.md`

## Important
The database is a **Supabase PostgreSQL** implementation. Backend members should connect their application services to this schema. Do not put Supabase service-role/secret credentials in the frontend.

The schema includes integrity constraints, indexes, RLS policies, role validation, assignment validation, feedback ownership validation, storage policies, seed reference data, and the default Monday-Friday 9:00 AM-4:00 PM working schedule.

### v3 corrections
Priority is administrator-controlled. Student submissions do not set a priority and are stored with `priority_id = NULL` until an authorized administrator reviews the concern. New concerns always start at `Submitted`. Active category/location checks and workflow notification triggers are included in the final SQL schema.
