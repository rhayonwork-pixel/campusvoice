# CampusVoice — PHP + MySQL (InfinityFree ready)

Plain PHP 8 + MySQL, no framework, no build step. The old Supabase prototype is kept in `_legacy/` for reference only; delete it before uploading.

## Demo accounts (fresh seed data)

Everyone signs in with a **username** (their ID). All demo accounts share one password: `CampusVoice#2026` — **change it before going live**.

| Role | Username |
|---|---|
| Admin (SASO Director) | `ADM-0001` |
| Staff | `STF-0102` Guidance, `STF-0103` Discipline, `STF-0104` Scholarship, `STF-0105` Student Orgs, `STF-0101` Facilities |
| Student | `2023-0001`, `2023-0002`, `2022-0105`, `2024-0210` |

## Updating an EXISTING live database (SASO update)

Run once in phpMyAdmin: click your database → **SQL** tab → paste `database/upgrade-2026-11-saso.sql` → **Go**. Export a backup first.
It keeps all users and concerns, hides the old facility categories, adds the 9 SASO types, turns public sign-up off, turns routing on and sets the 3-try / 5-minute lock.
Then: **Campus Data → Concern types** → open each type and tick its handlers, and **Users & Staff** → set each staff member's office.
(Fresh install instead: import `schema.sql` then `seed.sql`.)

## Student accounts from a masterlist

**Users & Staff → Import students** → drop the class list (.xlsx, .csv or the Word masterlist .docx) → check the preview → set Course / Year / Section → **Create accounts**.
- Needs a *Student ID* column and a *Full name* (`LAST, FIRST MIDDLE`) or *Last name* + *First name* column. Columns like No. or Signature are ignored.
- Username = student ID. First password = `{StudentID}@cityofmalabonuniversity.edu.ph` (or Random). Students must change it on first sign-in.
- Existing IDs, blank IDs and duplicates are skipped and listed. Download the CSV or print sign-in slips right after; passwords cannot be shown again.
- Template: `assets/templates/student-import-template.csv`.

## Google sign-in (link / unlink)

The system only offers **link / unlink**: students open **Settings → Linked accounts → Link Google** once, and then "Continue with Google" works on the login page. Admins see who is linked in Users & Staff. The Google keys themselves live in `config/config.php`:

1. https://console.cloud.google.com → create a project **CampusVoice**.
2. **Google Auth Platform** (OAuth consent screen): app name CampusVoice, audience **External**, add a contact email, finish.
3. **Clients → Create client → Web application.** Authorized redirect URI: `https://campusvoice.freedev.app/google-callback.php` (exactly this, from your own domain).
4. Copy the **Client ID** and **Client secret** into the `google` block of `config/config.php`:
   ```php
   'google' => [
       'enabled'        => true,
       'client_id'      => 'xxxx.apps.googleusercontent.com',
       'client_secret'  => 'xxxx',
       'redirect_uri'   => '',      // leave empty (built from your domain)
       'allowed_domain' => '',      // '' = any Google account; or 'school.edu.ph' = school accounts only
   ],
   ```
5. In Google, **Audience → Publish app** so anyone (not just test users) can link. Plain sign-in needs no Google review.

A visitor who has no CampusVoice account still cannot get in: Google sign-in only works for accounts that were first linked from inside a signed-in profile.

## Concern routing (who gets what)

Each concern type has handlers (Campus Data). With **Settings → Concern rules → Route new concerns automatically** on, a new concern goes straight to the least-busy available handler (busy staff only if nobody is free, staff on leave never). Types without handlers — and every concern when routing is off — wait in the admin's Assignment Queue. Admins can still reassign anything.

## Anonymous concerns
Staff see only "Anonymous Student" plus course/year/section. The SASO admin sees the real sender (marked as anonymous). CSV exports stay masked.

## Run locally (XAMPP)

1. Start Apache + MySQL in XAMPP and put this folder at `C:\xampp\htdocs\CampusVoice`.
2. In phpMyAdmin import `database/schema.sql`, then `database/seed.sql`.
3. Check `config/config.php` (host `localhost`, user `root`, empty password, db `campusvoice`).
4. Open `http://localhost/CampusVoice/`.

## Deploy to InfinityFree

1. **Control panel → MySQL Databases** → create a database. Note the *hostname, database name, username* (they look like `sql123.infinityfree.com`, `if0_12345678_campusvoice`).
2. **phpMyAdmin** → click your database in the left list first → Import `database/schema.sql`, then `database/seed.sql` (the schema no longer contains `CREATE DATABASE`/`USE`, which InfinityFree rejects).
3. Fill in `config/config.local.php` (host, db name, user, password) and **upload it by hand into the server's `config/` folder** — it is git-ignored, so a Git-based upload never carries it. If the site shows “The service is temporarily unavailable”, this file is missing or its values are wrong; with `'debug' => true` the page shows the exact database error. Set `'debug' => false` when it works.
4. Upload everything inside `htdocs/` via the File Manager or FTP (`index.php`, `login.php`, `assets/`, `api/`, `includes/`, `config/`, `uploads/`, `admin/`, `staff/`, `student/`, `.htaccess`, …). **Do not upload `_legacy/` or `database/`** once the import is done.
5. Make sure `uploads/` is writable (it normally is). Its `.htaccess` blocks direct access; photos are served only through `file.php` after a permission check.
6. Visit your site, sign in as admin, and change every demo password.

### InfinityFree limits to know
- **Email:** outbound mail is restricted, so the emailed reset link may not arrive. The fallback is built in: Users & Staff → key icon → issue a one-time **reset code**, give it to the user, who enters it at *Forgot password → “Enter it here”*.
- No cron jobs: deadlines/overdue are computed when pages load; the staff workload tab and bell refresh by polling (20–60 s), which is light on hits.
- Uploads are limited to 3 MB JPG/PNG/WebP, max 4 per concern (change in `config/config.php`).
- FullCalendar loads from the jsDelivr CDN; the calendar needs internet access.
- InfinityFree adds an anti-bot cookie check for non-browser clients; normal browser AJAX is unaffected.

## What changed vs. the old prototype

| Area | Change |
|---|---|
| Backend | Supabase → PHP sessions + PDO prepared statements + MySQL. |
| Login | Server-side sessions, CSRF on every POST, bcrypt, generic errors, lockout after 5 failed tries/15 min (admin-tunable), idle timeout, session bound to browser, audit of every sign-in. |
| Access control | Every page and API checks the role on the server (the old `localStorage` role check is gone). |
| Landing page | New `index.php` with photo slideshow hero, live stats, features, follow-us section (placeholder social links in `index.php`). |
| Footer | Removed from login, student, staff and admin. |
| Student | Report page replaced by **New Concern** modal (AJAX); Minor/Major/Urgent severity badges; photo lightbox; **anonymous** option; rating after resolution; confirm/reopen/cancel. |
| Staff | Greeting once per login; Today's Work with Quick Action (Start) + “New” badge; lightbox; availability setting. |
| Admin | Urgent-only “Needs your attention” → Notifications; Reassign moved to **Current Assignments**; **Staff Activity** tab (auto-refresh); Users table ID-first with `Surname, Firstname, MI.` for students; real audit trail with search/filters/pagination; FullCalendar; reports + CSV export; system settings. |

## Database changes (summary)
New MySQL schema in `database/schema.sql`. Notable additions vs. the old Postgres schema: `concerns.severity` (Minor/Major/Urgent), `concerns.is_anonymous`, `concerns.due_at`, `assignments.seen_at` (drives the “New” badge), `users.availability`, `users.course/year_level/section`, `users.must_change_password`, `login_attempts`, `password_resets`, `audit_logs` (with IP), `events`, `settings`. `super_admin` was removed.

## Test checklist
1. **Landing** `/` — photo slideshow cross-fades with no buttons; stats count up; Login in the nav opens `/login.php`.
2. **Login security** — sign in with a username; each wrong password shows how many tries are left; the 3rd locks sign-in for 5 minutes with a live countdown (survives a refresh).
3. **Student** — Concerns → *New Concern* modal → submit with a photo (list refreshes without reload); tick *Send as anonymous*; open a photo (lightbox: ✕, click outside and `Esc` close it); open a resolved concern → rate, confirm or reopen.
4. **Staff** (`angelica…`) — greeting shows once after login, not on refresh; Today's Work shows a gold “New” row with **Start**; update a concern with a proof photo.
5. **Admin** — Dashboard shows only Urgent items and clicking opens Notifications; Concern Management → Assignment Queue → Assign; Current Assignments → Reassign; Staff Activity updates itself; Users table shows ID first and `Dela Cruz, Juan A.`; System Activity filters by date/user/action; Calendar shows deadlines, assignments, events; open the anonymous concern and try *Reveal reporter*.
6. **Responsive** — desktop: collapse/expand sidebar (remembered); tablet (901–1199 px): icon rail that opens over the content; phone: slide-in drawer.

## Email (Gmail) setup

1. Google Account → **Security** → turn on **2-Step Verification**, then open **App passwords** (myaccount.google.com/apppasswords). Create one named "CampusVoice" and copy the 16-character code (spaces are fine).
2. In `config/config.php`, fill the `smtp` block: `'enabled' => true`, `'user' => 'yourname@gmail.com'`, `'pass' => 'abcd efgh ijkl mnop'` (host `smtp.gmail.com`, port `587`, secure `tls` stay as they are). Upload the file. Never paste the app password in chat or commit it to a public repo.
3. Sign in as admin → **System Settings → Email → Send test email**. It tells you exactly what went wrong if it fails (wrong password, blocked connection, ...).
4. **InfinityFree blocks outgoing SMTP**, so the test will probably say it could not connect. Nothing breaks: password reset still works with the admin-issued reset code, and the styled emails work on any host that allows SMTP.
5. **Admin → System Settings → Email → Preview email** shows the styled email without sending anything.

## Cloudflare Turnstile (human check on Login / Register / Forgot password)

1. Create a free account at https://dash.cloudflare.com (your domain does **not** need to be on Cloudflare).
2. Open **Turnstile** in the dashboard → **Add widget**.
3. **Widget name:** CampusVoice. **Hostname management:** add your site domain (e.g. `campusvoice.freedev.app`; add `localhost` too if you test locally). **Widget mode:** Managed (recommended). Press **Create**.
4. Copy the **Site key** (public) and the **Secret key** (private).
5. In `config/config.php` fill the `turnstile` block: `'enabled' => true`, `'site_key' => '...'`, `'secret_key' => '...'`. Upload it.
6. Open the login page: the check appears above the Sign in button. Keys wrong or domain missing in step 3 = the widget shows an error; fix the hostname list in the dashboard.

For local testing without a Cloudflare account use Cloudflare's dummy keys: site key `1x00000000000000000000AA` with secret `1x0000000000000000000000000000000AA` (always passes). Never use dummy keys on the live site — they protect nothing.
