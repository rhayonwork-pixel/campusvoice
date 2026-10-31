/* Theme: light | dark | system. Loaded in <head> (before paint) so there is no flash.
   "system" follows the device setting and switches live when the device switches. Saved per browser. */
(function () {
  'use strict';
  var KEY = 'cv_theme';
  var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function saved() {
    try { var v = localStorage.getItem(KEY); return v === 'light' || v === 'dark' ? v : 'system'; } catch (e) { return 'system'; }
  }
  function apply() {
    var pref = saved();
    var dark = pref === 'dark' || (pref === 'system' && media && media.matches);
    var root = document.documentElement;
    root.setAttribute('data-theme', dark ? 'dark' : 'light');
    root.setAttribute('data-theme-pref', pref);
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', dark ? '#0c1424' : '#0f2a5e');
    document.dispatchEvent(new CustomEvent('cv:theme', { detail: { pref: pref, dark: dark } }));
  }
  apply();
  if (media) { if (media.addEventListener) media.addEventListener('change', apply); else if (media.addListener) media.addListener(apply); }
  document.addEventListener('DOMContentLoaded', apply);

  // printed pages are always light, whatever theme is on screen
  window.addEventListener('beforeprint', function () { document.documentElement.setAttribute('data-theme', 'light'); });
  window.addEventListener('afterprint', apply);

  window.cvTheme = {
    get: saved,
    set: function (v) { try { localStorage.setItem(KEY, v); } catch (e) { /* private mode: still applies for this page */ } apply(); }
  };
})();
