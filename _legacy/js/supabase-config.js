/* CampusVoice Supabase configuration
   Replace ONLY these two values after creating your Supabase project.
   Never put a service-role/secret key here.
*/
window.CAMPUSVOICE_SUPABASE = {
  url: "https://fzuufaqilespuxdulpai.supabase.co",
  publishableKey: "sb_publishable_ZA0kUmyCLXwwcvaE4OgZvQ_-KPWP2vq"
};
if (window.supabase && window.CAMPUSVOICE_SUPABASE) {
    window.campusVoiceSupabase = window.supabase.createClient(
        window.CAMPUSVOICE_SUPABASE.url,
        window.CAMPUSVOICE_SUPABASE.publishableKey
    );
}