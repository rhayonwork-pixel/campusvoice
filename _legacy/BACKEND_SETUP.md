# CampusVoice Backend Integration — VS Code Setup

This frontend is now prepared for Supabase. The current UI remains usable as a visual prototype until Supabase credentials are entered.

## 1. Create the Supabase project
Create one Supabase project for CampusVoice.

## 2. Create the database
Open Supabase → SQL Editor and run:

`database/supabase_schema.sql`

This creates the PostgreSQL tables, relationships, seed reference data, Auth profile trigger, RLS policies, and the private Storage bucket used for CampusVoice files.

## 3. Connect the frontend
Open:

`js/supabase-config.js`

Replace:

- `YOUR-PROJECT-REF`
- `YOUR_SUPABASE_PUBLISHABLE_KEY`

with your actual Supabase Project URL and Publishable key.

Do NOT paste a service-role/secret key into this file.

## 4. Run the frontend
Use VS Code Live Server (or another local web server). Do not rely on `file://` URLs for authentication redirects.

## 5. What is connected now
- Student signup uses Supabase Auth when configured.
- Student profile is created/updated in `public.users`.
- Login uses Supabase Auth when configured.
- Role is read from `public.users`.
- The existing frontend routing remains in place.
- Google login is wired to Supabase OAuth when configured.

## 6. What comes next
The next implementation pass should connect the actual role pages to PostgreSQL/Storage:

### Student
- Dashboard metrics
- Submit concern
- Photo upload
- My concerns
- Concern details/timeline
- Notifications
- Profile
- Feedback

### Staff
- Assigned concerns
- Status updates
- Response templates
- Action taken
- Evidence photos
- Resolution

### Admin / Super Admin
- All concerns
- Assignment/reassignment
- Users
- Categories
- Locations
- Reports
- Activity logs
- Working hours/holidays/settings

Those page operations should use the same RLS-protected database defined in the schema.
