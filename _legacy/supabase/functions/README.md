# CampusVoice Supabase Edge Functions

`admin-create-user` is the server-side path for Admin/Super Admin account creation.

Why this is server-side:
- Creating another Auth user requires privileged Supabase Admin API access.
- The service-role key must NEVER be exposed in browser JavaScript.

Deploy this function with the Supabase CLI after the database is created. Supabase supplies the required project environment variables; do not hard-code them.

Permission rule:
- Admin can create Staff.
- Super Admin can create Staff and Administrators.
- Students are created through public Student Signup.
