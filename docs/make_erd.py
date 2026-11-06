"""Builds docs/database-erd.svg: a clean ERD of the CampusVoice database (18 tables, all columns).

Rules that keep it clean:
  * every line is straight or has ONE bend, and no two lines cross;
  * columns that only point to users / departments get a small tag ("-> users") instead of a line;
  * lines go from the exact column on the "1" side to the exact column on the "N" side.
Run: python docs/make_erd.py
"""
import os

NAVY, GOLD, TXT, GREY, SOFT = "#1f2d5c", "#b07d1a", "#222", "#8a8f9e", "#f4f6fa"
HEAD, ROW, PAD = 30, 19, 8


def cols(spec):  # "name:type"; prefix * = PK, + = FK, *+ = PK+FK; "name:type>users" = tagged link
    out = []
    for part in spec.split(","):
        part = part.strip()
        tag = ""
        if ">" in part:
            part, tag = part.split(">")
        n, t = part.split(":")
        key = "PK FK" if n.startswith("*+") else "PK" if n.startswith("*") else "FK" if n.startswith("+") else ""
        out.append((key, n.lstrip("*+"), t, tag))
    return out


S = {
 "departments": cols("*department_id:int, department_name:varchar, department_type:enum"),
 "users": cols("*user_id:int, role:enum, school_id:varchar, first_name:varchar, middle_initial:char, last_name:varchar, email:varchar, password_hash:varchar, +department_id:int, position:varchar, contact_number:varchar, course:varchar, year_level:tinyint, section:varchar, avatar:varchar, availability:enum, account_status:enum, must_change_password:bool, pref_notify_inapp:bool, pref_notify_email:bool, pref_default_anonymous:bool, google_sub:varchar, google_email:varchar, google_linked_at:datetime, last_login_at:datetime, created_at:datetime, updated_at:datetime"),
 "category_handlers": cols("*+category_id:int, *+staff_id:int>users"),
 "categories": cols("*category_id:int, category_name:varchar, description:varchar, +default_department_id:int>departments, needs_location:bool, next_steps:varchar, status:enum"),
 "locations": cols("*location_id:int, building:varchar, floor:varchar, area:varchar, status:enum"),
 "concerns": cols("*concern_id:int, concern_code:varchar, +student_id:int, +category_id:int, +location_id:int, severity:enum, status:enum, title:varchar, description:text, is_anonymous:bool, wants_meeting:bool, meeting_pref:varchar, due_at:datetime, created_at:datetime, updated_at:datetime, resolved_at:datetime, closed_at:datetime"),
 "assignments": cols("*assignment_id:int, +concern_id:int, +staff_id:int>users, +assigned_by:int>users, assigned_at:datetime, assignment_status:enum, reason:varchar, seen_at:datetime, completed_at:datetime"),
 "visits": cols("*visit_id:int, +concern_id:int, +scheduled_by:int>users, scheduled_at:datetime, place:varchar, note:varchar, status:enum, student_note:varchar, outcome_note:varchar, responded_at:datetime, created_at:datetime, updated_at:datetime"),
 "concern_updates": cols("*update_id:int, +concern_id:int, +user_id:int>users, status:varchar, update_type:varchar, response:text, created_at:datetime"),
 "photos": cols("*photo_id:int, +concern_id:int, +uploaded_by:int>users, photo_type:enum, file_path:varchar, created_at:datetime"),
 "feedback": cols("*feedback_id:int, +concern_id:int, +student_id:int>users, rating:tinyint, comment:varchar, created_at:datetime"),
 "notifications": cols("*notification_id:int, +concern_id:int, +user_id:int>users, title:varchar, message:varchar, is_urgent:bool, is_read:bool, created_at:datetime"),
 "password_resets": cols("*reset_id:int, +user_id:int>users, token_hash:char, expires_at:datetime, used_at:datetime, created_at:datetime"),
 "events": cols("*event_id:int, title:varchar, start_at:datetime, end_at:datetime, notes:varchar, +created_by:int>users"),
 "audit_logs": cols("*log_id:int, user_id:int, user_label:varchar, role:varchar, action:varchar, details:varchar, ip_address:varchar, created_at:datetime"),
 "login_attempts": cols("*attempt_id:int, email:varchar, ip_address:varchar, success:bool, created_at:datetime"),
 "settings": cols("*setting_key:varchar, setting_value:varchar"),
 "working_hours": cols("*day_of_week:tinyint, open_time:time, close_time:time, active:bool"),
}


def h(n):
    return HEAD + len(S[n]) * ROW + PAD


def rowc(n, col):  # y of the middle of a column row
    i = [c[1] for c in S[n]].index(col)
    return POS[n][1] + HEAD + PAD // 2 + i * ROW + ROW // 2


POS = {}
CW, CG, CX0 = 214, 12, 330
POS["departments"] = (40, 80, 240)
POS["users"] = (40, 215, 240)
# concerns sits so that its student_id row is level with users.user_id (a straight horizontal line)
POS["concerns"] = (560, 0, 270)
POS["concerns"] = (560, int(215 + HEAD + PAD // 2 + ROW // 2 - (HEAD + PAD // 2 + 2 * ROW + ROW // 2)), 270)
cy0 = POS["concerns"][1]
POS["category_handlers"] = (1010, 80, 270)
POS["categories"] = (1010, int(cy0 + HEAD + PAD // 2 + 3 * ROW + ROW // 2 - (HEAD + PAD // 2 + ROW // 2)), 270)
POS["locations"] = (1010, POS["categories"][1] + h("categories") + 50, 270)
CH = ["assignments", "visits", "concern_updates", "photos", "feedback", "notifications"]
conc_bottom = cy0 + h("concerns")
loc_bottom = POS["locations"][1] + h("locations")
CY = max(conc_bottom, loc_bottom) + 70
for i, n in enumerate(CH):
    POS[n] = (CX0 + i * (CW + CG), CY, CW)
BY = CY + max(h(n) for n in CH) + 60
small = ["password_resets", "events", "audit_logs", "login_attempts", "settings", "working_hours"]
for i, n in enumerate(small):
    POS[n] = (40 + i * (CW + CG + 16), BY, CW)

W = CX0 + 6 * CW + 5 * CG + 40
H = BY + max(h(n) for n in small) + 70

o = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" font-family="Arial, Helvetica, sans-serif">',
     f'<rect width="{W}" height="{H}" fill="#fff"/>',
     f'<text x="40" y="44" font-size="24" font-weight="bold" fill="{NAVY}">CampusVoice Database (ERD)</text>',
     f'<text x="40" y="64" font-size="13" fill="{GREY}">18 tables, every column. Line = link from the “1” side to the “N” (many) side.</text>']
a = o.append


def ln(d, col=NAVY):
    a(f'<path d="{d}" fill="none" stroke="{col}" stroke-width="1.7" stroke-linejoin="round"/>')


def mark(x, y, t, col=NAVY, anchor="middle"):
    a(f'<text x="{x}" y="{y}" font-size="11" font-weight="bold" fill="{col}" text-anchor="{anchor}">{t}</text>')


def lab(x, y, t, anchor="middle"):
    a(f'<text x="{x}" y="{y}" font-size="10.5" fill="{GREY}" text-anchor="{anchor}">{t}</text>')


U, D, C, K, L = (POS[n] for n in ("users", "departments", "concerns", "categories", "locations"))

# departments -> users
x = U[0] + 120
ln(f"M{x} {D[1] + h('departments')}V{U[1]}")
mark(x + 8, D[1] + h("departments") + 14, "1", anchor="start"); mark(x + 8, U[1] - 5, "N", anchor="start")
lab(x + 22, (D[1] + h("departments") + U[1]) // 2 + 4, "department_id", "start")

# users -> concerns (student_id), straight
y = rowc("users", "user_id")
ln(f"M{U[0] + U[2]} {y}H{C[0]}")
mark(U[0] + U[2] + 10, y - 6, "1", anchor="start"); mark(C[0] - 10, y - 6, "N", anchor="end")
lab((U[0] + U[2] + C[0]) // 2, y - 8, "student_id")

# concerns -> categories, straight
y = rowc("concerns", "category_id")
ln(f"M{C[0] + C[2]} {y}H{K[0]}")
mark(C[0] + C[2] + 10, y - 6, "N", anchor="start"); mark(K[0] - 10, y - 6, "1", anchor="end")
lab((C[0] + C[2] + K[0]) // 2, y - 8, "category_id")

# concerns -> locations, one bend (the bend sits between the two tables)
y1, y2 = rowc("concerns", "location_id"), rowc("locations", "location_id")
bx = C[0] + C[2] + 50
ln(f"M{C[0] + C[2]} {y1}H{bx}V{y2}H{L[0]}")
mark(C[0] + C[2] + 10, y1 + 14, "N", anchor="start"); mark(L[0] - 10, y2 - 6, "1", anchor="end")
lab(bx + 8, (y1 + y2) // 2, "location_id", "start")

# category_handlers -> categories, straight down
x = K[0] + 135
H_ = POS["category_handlers"]
ln(f"M{x} {H_[1] + h('category_handlers')}V{K[1]}")
mark(x + 8, H_[1] + h("category_handlers") + 14, "N", anchor="start"); mark(x + 8, K[1] - 5, "1", anchor="start")
lab(x + 22, (H_[1] + h("category_handlers") + K[1]) // 2 + 4, "category_id", "start")

# concerns -> six activity tables: one line down, one bar, six drops
cx = [POS[n][0] + CW // 2 for n in CH]
bar = CY - 32
mx = C[0] + C[2] // 2
ln(f"M{mx} {C[1] + h('concerns')}V{bar}")
ln(f"M{cx[0]} {bar}H{cx[-1]}")
mark(mx + 8, C[1] + h("concerns") + 14, "1", anchor="start")
for n, x in zip(CH, cx):
    ln(f"M{x} {bar}V{CY}")
    mark(x + 8, CY - 6, "N", anchor="start")
lab(cx[0] - 6, bar - 8, "concern_id  (every table below links to its concern)", "start")


def box(n):
    x, y, w = POS[n]
    hh = h(n)
    a(f'<rect x="{x}" y="{y}" width="{w}" height="{hh}" rx="6" fill="#fff" stroke="{NAVY}" stroke-width="1.5"/>')
    a(f'<path d="M{x} {y + HEAD}V{y + 6}a6 6 0 016-6h{w - 12}a6 6 0 016 6V{y + HEAD}z" fill="{NAVY}"/>')
    a(f'<text x="{x + 12}" y="{y + 20}" font-size="13.5" font-weight="bold" fill="#fff">{n}</text>')
    for i, (k, c, t, tag) in enumerate(S[n]):
        ry = y + HEAD + PAD // 2 + i * ROW
        if i % 2:
            a(f'<rect x="{x + 1}" y="{ry}" width="{w - 2}" height="{ROW}" fill="{SOFT}"/>')
        kc = GOLD if k.startswith("PK") else NAVY
        a(f'<text x="{x + 8}" y="{ry + 14}" font-size="9" font-weight="bold" fill="{kc}">{k.replace(" ", "·")}</text>')
        a(f'<text x="{x + 38}" y="{ry + 14}" font-size="12" fill="{TXT}" font-weight="{"bold" if k else "normal"}">{c}</text>')
        if tag:
            a(f'<text x="{x + w - 8}" y="{ry + 14}" font-size="10.5" font-weight="bold" fill="{GOLD if tag == "users" else NAVY}" text-anchor="end">→ {tag}</text>')
        else:
            a(f'<text x="{x + w - 8}" y="{ry + 14}" font-size="10.5" fill="{GREY}" text-anchor="end">{t}</text>')


for n in POS:
    box(n)

a(f'<text x="40" y="{BY - 14}" font-size="12" font-weight="bold" fill="{GREY}">ACCOUNT AND SYSTEM TABLES</text>')
a(f'<text x="{40 + 3 * (CW + CG + 16)}" y="{BY - 14}" font-size="11.5" fill="{GREY}">audit_logs.user_id has no link on purpose, so the history stays if a user is deleted</text>')
a(f'<g font-size="11.5" fill="{GREY}"><text x="{W - 40}" y="{H - 24}" text-anchor="end">'
  f'PK primary key · FK foreign key · <tspan fill="{GOLD}" font-weight="bold">→ users</tspan> = this column stores a users.user_id · '
  f'<tspan fill="{NAVY}" font-weight="bold">→ departments</tspan> = stores a departments.department_id</text></g>')
a("</svg>")
out = os.path.join(os.path.dirname(os.path.abspath(__file__)), "database-erd.svg")
open(out, "w", encoding="utf-8").write("\n".join(o))
print("written", out, W, H)
