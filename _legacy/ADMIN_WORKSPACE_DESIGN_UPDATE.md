CampusVoice Admin Workspace and Cozy UI Update

Base project: CampusVoice_Final_Approved(1).zip supplied by the user.

Changes in this update:
- Consolidated the admin sidebar's All Concerns + Assignments entries into one Concern Management entry.
- Consolidated Categories + Locations into one Campus Data entry.
- Added contextual workspace tabs on the existing pages so the original concern/assignment and category/location page behavior is retained while users can switch between related areas.
- Replaced the admin sidebar's text glyphs with small inline SVG line icons.
- Added subtle navy/gold accents, warmer neutral surfaces, rounded interactive navigation, keyboard focus states, responsive tab behavior, and reduced-motion support.
- Kept the existing pages as the source of their current behavior; no backend, schema, RLS policy, Edge Function, or smart-processor changes were made.

Limitations:
- This is a navigation/visual refinement pass, not a claim that all previously discussed functional requirements are complete.
- Live Supabase/browser end-to-end testing has not been performed in this environment.
- Existing login/register pages and styles were not edited.
