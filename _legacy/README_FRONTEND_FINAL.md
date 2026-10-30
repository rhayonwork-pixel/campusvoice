# CampusVoice — Frontend Final Approved Build

This package incorporates the approved Student, Staff, and Super Admin frontend workflow before full Supabase integration.

Open `index.html` with VS Code/Live Server. The frontend includes a demo data layer so pages and interactions can be reviewed before Supabase credentials are configured.

The demo layer is not a production database. Replace demo operations with Supabase API calls during backend integration.

## Frontend v3 correction pass
This build fixes demo-role session persistence, Staff login/demo routing, Student My Concerns filtering, Admin quick-filter rendering, admin-controlled priority, dynamic Building → Floor → Area/Room selection, assignment eligibility guidance, and report coverage.


### V5 UI fix
The public login footer is in normal document flow and appears after the login viewport/content; it no longer overlays the login form or remains visually fixed while scrolling.
