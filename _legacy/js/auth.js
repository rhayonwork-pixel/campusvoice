(function () {
    const config = window.CAMPUSVOICE_SUPABASE || {};
    const SUPABASE_URL = config.url;
    const SUPABASE_KEY =
        config.publishableKey ||
        config.key ||
        config.anonKey;

    if (
        !SUPABASE_URL ||
        !SUPABASE_KEY ||
        !window.supabase
    ) {
        console.error(
            "CampusVoice Supabase configuration is missing."
        );
        return;
    }

    const sb = window.supabase.createClient(
        SUPABASE_URL,
        SUPABASE_KEY
    );

    window.campusVoiceSupabase = sb;

    function setMessage(text, type = "error") {
        const el =
            document.getElementById("loginError");

        if (!el) return;

        el.textContent = text;
        el.style.display = "block";
        el.style.color =
            type === "success"
                ? "#247a56"
                : "#b84b52";
    }

    function saveLocalSession(profile, remember) {
        const session = {
            email: profile.email,
            name: [
                profile.first_name,
                profile.middle_initial,
                profile.last_name
            ]
                .filter(Boolean)
                .join(" ") || "CMU User",
            role: profile.role,
            studentId: profile.student_id || null,
            userId: profile.user_id || null
        };

        localStorage.removeItem(
            "campusvoiceUser"
        );

        sessionStorage.removeItem(
            "campusvoiceUser"
        );

        const target =
            remember
                ? localStorage
                : sessionStorage;

        target.setItem(
            "campusvoiceUser",
            JSON.stringify(session)
        );

        return session;
    }

    async function loginWithSupabase(
        email,
        password,
        remember
    ) {
        const { data, error } =
            await sb.auth.signInWithPassword({
                email,
                password
            });

        if (error) {
            throw error;
        }

        if (!data.user) {
            throw new Error(
                "Unable to retrieve your account."
            );
        }

        const {
            data: profile,
            error: profileError
        } = await sb
            .from("users")
            .select(
                "user_id,email,first_name,middle_initial,last_name,role,student_id,account_status"
            )
            .eq("user_id", data.user.id)
            .single();

        if (profileError) {
            await sb.auth.signOut();

            throw new Error(
                "Your account was authenticated, but your CampusVoice profile could not be loaded."
            );
        }

        if (
            profile.account_status !==
            "active"
        ) {
            await sb.auth.signOut();

            throw new Error(
                "Your CampusVoice account is currently inactive. Please contact an administrator."
            );
        }

        saveLocalSession(
            profile,
            remember
        );

        return profile;
    }

    document.addEventListener(
        "DOMContentLoaded",
        () => {
            const form =
                document.getElementById(
                    "loginForm"
                );

            if (!form) return;

            const passwordInput =
                document.getElementById(
                    "password"
                );

            const togglePassword =
                document.getElementById(
                    "togglePassword"
                );

            const remember =
                document.getElementById(
                    "remember"
                ) ||
                document.getElementById(
                    "rememberMe"
                );

            togglePassword?.addEventListener(
                "click",
                () => {
                    const show =
                        passwordInput.type ===
                        "password";

                    passwordInput.type =
                        show
                            ? "text"
                            : "password";

                    togglePassword.textContent =
                        show
                            ? "Hide"
                            : "Show";
                }
            );

            form.addEventListener(
                "submit",
                async e => {
                    e.preventDefault();

                    const emailInput =
                        document.getElementById(
                            "email"
                        ) ||
                        document.getElementById(
                            "identifier"
                        );

                    const email =
                        emailInput?.value
                            .trim()
                            .toLowerCase();

                    const password =
                        passwordInput?.value ||
                        "";

                    if (
                        !email ||
                        !password
                    ) {
                        setMessage(
                            "Please enter your email and password."
                        );
                        return;
                    }

                    const button =
                        form.querySelector(
                            "button[type=submit]"
                        );

                    if (button) {
                        button.disabled =
                            true;

                        button.textContent =
                            "Signing in...";
                    }

                    try {
                        const profile =
                            await loginWithSupabase(
                                email,
                                password,
                                !!remember?.checked
                            );

                        const destination = {
                            student:
                                "student/dashboard.html",

                            staff:
                                "staff/dashboard.html",

                            admin:
                                "admin/dashboard.html",

                            
                        }[
                            profile.role
                        ];

                        if (!destination) {
                            throw new Error(
                                "Your account does not have a valid CampusVoice role."
                            );
                        }

                        window.location.href =
                            destination;

                    } catch (error) {
                        console.error(
                            "CampusVoice login error:",
                            error
                        );

                        let message =
                            error.message ||
                            "Unable to sign in.";

                        if (
                            message
                                .toLowerCase()
                                .includes(
                                    "email not confirmed"
                                )
                        ) {
                            message =
                                "Please confirm your university email before signing in.";
                        }

                        if (
                            message
                                .toLowerCase()
                                .includes(
                                    "invalid login credentials"
                                )
                        ) {
                            message =
                                "Email or password is incorrect.";
                        }

                        setMessage(
                            message
                        );

                    } finally {
                        if (button) {
                            button.disabled =
                                false;

                            button.textContent =
                                "Sign In";
                        }
                    }
                }
            );

            document
                .getElementById(
                    "googleLogin"
                )
                ?.addEventListener(
                    "click",
                    async () => {
                        const {
                            error
                        } =
                            await sb.auth
                                .signInWithOAuth({
                                    provider:
                                        "google",

                                    options: {
                                        redirectTo:
                                            window.location
                                                .origin +
                                            "/CampusVoice_Final_Approved/index.html"
                                    }
                                });

                        if (error) {
                            setMessage(
                                error.message
                            );
                        }
                    }
                );
        }
    );
})();