(function () {
  const cfg = window.CAMPUSVOICE_SUPABASE || {};
  const ready = cfg.url && cfg.publishableKey &&
    !cfg.url.includes("YOUR-PROJECT-REF") &&
    !cfg.publishableKey.includes("YOUR_SUPABASE_PUBLISHABLE_KEY");

  window.CampusVoiceBackend = {
    ready,
    client: ready && window.supabase
      ? window.supabase.createClient(cfg.url, cfg.publishableKey)
      : null
  };
})();
