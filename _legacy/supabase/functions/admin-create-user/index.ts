import { createClient } from "npm:@supabase/supabase-js@2";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
  "Access-Control-Allow-Methods": "POST, OPTIONS"
};

Deno.serve(async (req) => {
  if (req.method === "OPTIONS") return new Response("ok", { headers: corsHeaders });

  try {
    const supabaseUrl = Deno.env.get("SUPABASE_URL")!;
    const anonKey = Deno.env.get("SUPABASE_ANON_KEY")!;
    const serviceRoleKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;

    const authClient = createClient(supabaseUrl, anonKey, {
      global: { headers: { Authorization: req.headers.get("Authorization") || "" } }
    });
    const adminClient = createClient(supabaseUrl, serviceRoleKey);

    const { data: { user }, error: userError } = await authClient.auth.getUser();
    if (userError || !user) throw new Error("Authentication required.");

    const { data: actor, error: actorError } = await adminClient
      .from("users")
      .select("role")
      .eq("user_id", user.id)
      .single();
    if (actorError || !actor || !["admin", "super_admin"].includes(actor.role)) {
      return new Response(JSON.stringify({ error: "Administrator permission required." }), { status: 403, headers: { ...corsHeaders, "Content-Type": "application/json" } });
    }

    const body = await req.json();
    const allowedRoles = actor.role === "super_admin" ? ["staff", "admin"] : ["staff"];
    if (!allowedRoles.includes(body.role)) {
      return new Response(JSON.stringify({ error: "You are not allowed to create this role." }), { status: 403, headers: { ...corsHeaders, "Content-Type": "application/json" } });
    }

    const { data: created, error: createError } = await adminClient.auth.admin.createUser({
      email: body.email,
      password: body.password,
      email_confirm: false,
      user_metadata: {
        role: body.role,
        first_name: body.first_name,
        middle_initial: body.middle_initial || null,
        last_name: body.last_name,
        staff_id: body.staff_id || null,
        admin_id: body.admin_id || null
      }
    });
    if (createError) throw createError;

    const profile = {
      user_id: created.user.id,
      email: body.email,
      first_name: body.first_name,
      middle_initial: body.middle_initial || null,
      last_name: body.last_name,
      role: body.role,
      staff_id: body.staff_id || null,
      admin_id: body.admin_id || null,
      department_id: body.department_id || null,
      position: body.position || null,
      coverage: body.coverage || null,
      account_status: body.account_status || "active"
    };

    const { error: profileError } = await adminClient.from("users").upsert(profile, { onConflict: "user_id" });
    if (profileError) throw profileError;

    await adminClient.from("activity_logs").insert({
      user_id: user.id,
      action: "CREATE_USER",
      reference_type: "user",
      reference_id: created.user.id,
      details: { role: body.role, email: body.email }
    });

    return new Response(JSON.stringify({ ok: true, user_id: created.user.id }), {
      status: 200,
      headers: { ...corsHeaders, "Content-Type": "application/json" }
    });
  } catch (error) {
    return new Response(JSON.stringify({ error: error.message || "Unable to create user." }), {
      status: 400,
      headers: { ...corsHeaders, "Content-Type": "application/json" }
    });
  }
});
