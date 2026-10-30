(function () {
  const form = document.getElementById("signupForm");
  const message = document.getElementById("formMessage");

  document.querySelectorAll("[data-toggle]").forEach(btn => {
    btn.addEventListener("click", () => {
      const input = document.getElementById(btn.dataset.toggle);
      const showing = input.type === "text";
      input.type = showing ? "password" : "text";
      btn.textContent = showing ? "Show" : "Hide";
    });
  });

  function setError(name, text){
    const el = document.querySelector(`[data-error="${name}"]`);
    if(el) el.textContent = text || "";
  }

  function clearErrors(){
    ["studentId","fullName","email","password","confirmPassword","terms"].forEach(n=>setError(n,""));
    message.textContent="";
    message.className="form-message";
  }

  form?.addEventListener("submit", async e => {
    e.preventDefault();
    clearErrors();

    const studentId = document.getElementById("studentId").value.trim();
    const fullName = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim().toLowerCase();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const terms = document.getElementById("terms").checked;

    let valid = true;
    if(!studentId){ setError("studentId","Student ID is required."); valid=false; }
    if(!fullName){ setError("fullName","Full name is required."); valid=false; }
    if(!email){ setError("email","CMU email is required."); valid=false; }
    else if(!email.endsWith("@cmu.edu.ph")){ setError("email","Please use your CMU email address."); valid=false; }
    if(password.length < 8){ setError("password","Password must be at least 8 characters."); valid=false; }
    if(password !== confirmPassword){ setError("confirmPassword","Passwords do not match."); valid=false; }
    if(!terms){ setError("terms","Please accept the terms to continue."); valid=false; }
    if(!valid) return;

    const backend = window.CampusVoiceBackend;
    if(!backend?.ready){
      message.textContent = "Backend is not configured yet. Add your Supabase URL and publishable key, then try again.";
      message.className = "form-message error";
      return;
    }

    const button = form.querySelector("button[type=submit]");
    if(button){ button.disabled=true; button.textContent="Creating account..."; }

    try {
      const sb = backend.client;
      const names = fullName.split(/\s+/);
      const firstName = names.shift() || "New";
      const lastName = names.pop() || "Student";
      const middleInitial = names.length ? names.map(n => n[0]).join(" ").slice(0, 5) : null;

      const { data, error } = await sb.auth.signUp({
        email,
        password,
        options: {
          data: {
            role: "student",
            student_id: studentId,
            first_name: firstName,
            middle_initial: middleInitial,
            last_name: lastName
          },
          emailRedirectTo: window.location.origin + "/index.html"
        }
      });
      if(error) throw error;
      if(!data.user) throw new Error("The account could not be created.");

      message.textContent = data.session
        ? "Account created successfully. You can now sign in."
        : "Account created. Please check your CMU email to verify your account before signing in.";
      message.className = "form-message success";
      setTimeout(() => window.location.href = "../index.html", 1300);
    } catch(error){
      message.textContent = error.message || "Unable to create the account.";
      message.className = "form-message error";
    } finally {
      if(button){ button.disabled=false; button.textContent="Create Account"; }
    }
  });
})();
