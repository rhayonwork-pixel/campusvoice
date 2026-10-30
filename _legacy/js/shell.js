
const savedUser = JSON.parse(
    localStorage.getItem("campusvoiceUser") ||
    sessionStorage.getItem("campusvoiceUser") ||
    "null"
);

const currentRole = savedUser?.role || "student";

const role = currentRole === "super_admin"
    ? "admin"
    : currentRole;

const roleLabels = {
    student: "Student",
    staff: "Staff",
    admin: "Administrator"
};

const roleLabel = roleLabels[role] || "User";

const nav = {
    student: [
        ["dashboard.html", "Dashboard", "⌂"],
        ["report.html", "Report a Concern", "＋"],
        ["concerns.html", "My Concerns", "▤"],
        ["help.html", "Help & Support", "?"]
    ],

    staff: [
        ["dashboard.html", "Dashboard", "⌂"],
        ["concerns.html", "My Assignments", "▤"],
        ["notifications.html", "Notifications", "◌"],
        ["help.html", "Help & Support", "?"]
    ],

    admin: [
        ["dashboard.html", "Dashboard", "grid"],
        ["concerns.html", "Concern Management", "clipboard"],
        ["users.html", "Users & Staff", "users"],
        ["categories.html", "Campus Data", "map"],
        ["reports.html", "Reports", "chart"],
        ["activity.html", "System Activity", "activity"],
        ["help.html", "Help & Support", "help"],
        ["settings.html", "System Settings", "settings"]
    ]
};

function esc(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function navIcon(name) {
    const paths = {
        grid: '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',

        clipboard: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6M9 10h6M9 14h6M9 18h3"/>',

        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',

        map: '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',

        chart: '<path d="M3 3v18h18"/><path d="m7 14 4-4 4 3 5-7"/>',

        activity: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',

        bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',

        user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',

        help: '<circle cx="12" cy="12" r="9"/><path d="M9.6 9a2.5 2.5 0 1 1 4.3 1.7c-1.1 1-1.9 1.3-1.9 3M12 17.5h.01"/>',

        settings:
            '<circle cx="12" cy="12" r="3"/>' +
            '<path d="M19.4 15 a1.7 1.7 0 0 0 .1-1.9 l1.4-1.1 l-1.4-2.4 l-1.7.6 a8 8 0 0 0-1.6-.9 l-.3-1.8 h-2.8 l-.3 1.8 a8 8 0 0 0-1.6.9 l-1.7-.6 l-1.4 2.4 l1.4 1.1 a8 8 0 0 0 0 1.9 l-1.4 1.1 l1.4 2.4 l1.7-.6 a8 8 0 0 0 1.6.9 l.3 1.8 h2.8 l.3-1.8 a8 8 0 0 0 1.6-.9 l1.7.6 l1.4-2.4 l-1.4-1.1 Z"/>'
    };

    if (!paths[name]) {
        return `<span aria-hidden="true">${esc(name)}</span>`;
    }

    return `<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${paths[name]}</svg>`;
}

function adminWorkspaceTabs(active) {
    if (role !== "admin") return "";

    const concernPages = ["concerns.html", "assignments.html"];
    const campusPages = ["categories.html", "locations.html"];

    let tabs = [];

    if (concernPages.includes(active)) {
        tabs = [
            ["concerns.html", "All Concerns", "clipboard"],
            ["assignments.html", "Staff Assignments", "users"]
        ];
    }

    if (campusPages.includes(active)) {
        tabs = [
            ["categories.html", "Categories", "grid"],
            ["locations.html", "Locations", "map"]
        ];
    }

    if (!tabs.length) return "";

    return `
        <nav class="workspace-tabs" aria-label="Workspace sections">
            ${tabs.map(([href, label, icon]) => `
                <a
                    class="workspace-tab ${active === href ? "active" : ""}"
                    href="${href}"
                >
                    <span>${navIcon(icon)}</span>
                    ${label}
                </a>
            `).join("")}
        </nav>
    `;
}

function cvName() {
    if (!savedUser) return "CMU User";
    return savedUser.name || "CMU User";
}

function cvInitial() {
    const name = cvName().trim();
    return name ? name.charAt(0).toUpperCase() : "U";
}

function cvPhoto() {
    return savedUser?.profilePicture || "";
}

function avatarMarkup(cls = "avatar", size = "") {
    const photo = cvPhoto();

    if (photo) {
        return `
            <span class="${cls} ${size}" style="overflow:hidden">
                <img src="${esc(photo)}" alt="Profile picture">
            </span>
        `;
    }

    return `<span class="${cls} ${size}">${esc(cvInitial())}</span>`;
}

function shell(active, title, subtitle, content, extra = "") {
    const currentNav = nav[role] || nav.student;

    document.body.innerHTML = `
        <div class="app">

            <button
                class="sidebar-overlay"
                id="sidebarOverlay"
                type="button"
                aria-label="Close navigation menu"
                tabindex="-1"
            ></button>

            <aside class="sidebar" id="mainSidebar">
                <div class="brand">
                    <img src="../assets/cmu-logo.png" alt="CMU">
                    <div>
                        <b>CampusVoice</b>
                        <small>${esc(roleLabel)} Portal</small>
                    </div>
                </div>

                <div class="nav-group">
                    <div class="nav-label">
                        ${role === "admin"
                            ? "OVERVIEW"
                            : role === "staff"
                                ? "WORKSPACE"
                                : "CAMPUSVOICE"}
                    </div>

                    ${currentNav.map(item => `
                        <a
                            class="nav-item ${
                                active === item[0] ||
                                (role === "admin" &&
                                    item[0] === "concerns.html" &&
                                    active === "assignments.html") ||
                                (role === "admin" &&
                                    item[0] === "categories.html" &&
                                    active === "locations.html")
                                    ? "active"
                                    : ""
                            }"
                            href="${item[0]}"
                        >
                            <span class="nav-icon">${navIcon(item[2])}</span>
                            ${item[1]}
                        </a>
                    `).join("")}
                </div>

                <div class="sidebar-bottom">
                    <button class="logout" id="logoutBtn" type="button">
                        ↪ Logout
                    </button>
                </div>
            </aside>

            <main class="main">
                
<header class="topbar">
    <button
        class="mobile-menu-toggle"
        id="mobileMenuToggle"
        type="button"
        aria-label="Open navigation menu"
        aria-controls="mainSidebar"
        aria-expanded="false"
    >☰</button>

    <div class="topbar-heading">
        <div class="kicker">${esc(roleLabel).toUpperCase()} PORTAL</div>
        <h1>${esc(title)}</h1>
        <p class="subtitle">${esc(subtitle)}</p>
    </div>

    <button
        class="header-notification"
        id="headerNotification"
        type="button"
        aria-label="Open notifications"
        title="Notifications"
    >
        ${navIcon("bell")}
    </button>

    <button class="user-menu" id="profileMenu" type="button">
        ${avatarMarkup("avatar")}
        <span>
            <b>${esc(cvName())}</b>
            <small>${esc(roleLabel)}</small>
        </span>
    </button>
</header>


                ${adminWorkspaceTabs(active)}

                <section class="content">
                    ${content}
                </section>

                ${extra}
            </main>
        </div>

        <div class="toast"></div>
    `;

    // Sidebar menu controls
    const appShell = document.querySelector(".app");
    const menuToggle = document.getElementById("mobileMenuToggle");
    const menuOverlay = document.getElementById("sidebarOverlay");
    const sidebar = document.getElementById("mainSidebar");

    function closeSidebar() {
        if (!appShell || !menuToggle || !menuOverlay) return;

        appShell.classList.remove("sidebar-open");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Open navigation menu");
        menuOverlay.setAttribute("tabindex", "-1");
    }

    if (appShell && menuToggle && menuOverlay && sidebar) {
        menuToggle.addEventListener("click", () => {
            const isOpen = !appShell.classList.contains("sidebar-open");

            appShell.classList.toggle("sidebar-open", isOpen);
            menuToggle.setAttribute("aria-expanded", String(isOpen));
            menuToggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );

            menuOverlay.setAttribute("tabindex", isOpen ? "0" : "-1");
        });

        menuOverlay.addEventListener("click", closeSidebar);

        document.addEventListener("keydown", event => {
            if (event.key === "Escape") closeSidebar();
        });

        sidebar.querySelectorAll(".nav-item").forEach(link => {
            link.addEventListener("click", closeSidebar);
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth > 720) closeSidebar();
        });
    }

    // Logout
    const logoutButton = document.getElementById("logoutBtn");

    if (logoutButton) {
        logoutButton.onclick = async () => {
            localStorage.removeItem("campusvoiceUser");
            sessionStorage.removeItem("campusvoiceUser");

            if (window.supabase && window.CAMPUSVOICE_SUPABASE) {
                try {
                    const config = window.CAMPUSVOICE_SUPABASE;
                    const client = window.supabase.createClient(
                        config.url,
                        config.publishableKey
                    );

                    await client.auth.signOut();
                } catch (error) {
                    console.error("Logout error:", error);
                }
            }

            window.location.href = "../index.html";
        };
    }

    // Profile
    const profileMenu = document.getElementById("profileMenu");

    if (profileMenu) {
        profileMenu.onclick = () => {
            window.location.href = "profile.html";
        };
    }

    // Notifications
    const headerNotification = document.getElementById("headerNotification");

    if (headerNotification) {
        headerNotification.onclick = () => {
            window.location.href = "notifications.html";
        };
    }
}

function modal(title, body, buttons = "") {
    const element = document.createElement("div");
    element.className = "modal-backdrop show";

    element.innerHTML = `
        <div class="modal">
            <div class="modal-head">
                <h3>${esc(title)}</h3>
                <button class="close" type="button">×</button>
            </div>

            ${body}

            ${buttons ? `<div class="form-actions">${buttons}</div>` : ""}
        </div>
    `;

    document.body.appendChild(element);

    element.querySelector(".close").onclick = () => {
        element.remove();
    };

    element.addEventListener("click", event => {
        if (event.target === element) {
            element.remove();
        }
    });

    return element;
}
