CAMPUSVOICE — FINAL FRONTEND + DATABASE PACKAGE

Open this folder in VS Code and use Live Server to preview the frontend.

SCOPE OF THIS MEMBER PACKAGE
- Frontend: Student, Staff, and Super Admin interfaces
- Database: Final Supabase/PostgreSQL schema, relationships, constraints, seed data, RLS foundation, and storage rules
- Backend/API integration is a separate team responsibility.

LOGIN DEMO ACCOUNTS
Student: student@cmu.edu.ph / 123456
Staff:   staff@cmu.edu.ph   / 123456
Admin:   admin@cmu.edu.ph   / 123456

These credentials are for the frontend prototype only and should not be presented as production credentials.

STUDENT SIGNUP
Click "Create an account" on the login page.
Public registration creates STUDENT accounts only.
The prototype signup stores demo data locally. The production Supabase Auth signup is part of the backend integration handoff.

DATABASE
Primary schema:
database/supabase_schema.sql

Supporting documents:
database/README.md
database/DATABASE_HANDOFF.md
database/ERD_REFERENCE.md
database/THESIS_DATABASE_ALIGNMENT.md

The database uses Supabase PostgreSQL. Passwords and sessions belong to Supabase Auth and are not stored as plaintext in public.users.

ROLE STYLES REMAIN SEPARATE
student.css, staff.css, and admin.css remain separate so role-specific changes do not affect the other roles.
