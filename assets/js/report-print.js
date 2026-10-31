/* Printable report: print button, and keep "custom dates" and "preset period" from fighting each other. */
(function () {
  'use strict';
  var btn = document.getElementById('printNow');
  if (btn) btn.addEventListener('click', function () { window.print(); });
  var from = document.querySelector('input[name=from]'), to = document.querySelector('input[name=to]'), days = document.getElementById('days');
  function sync() { if (days) days.disabled = !!(from && to && from.value && to.value); }
  [from, to].forEach(function (el) { if (el) el.addEventListener('input', sync); });
  sync();
  // an unchecked "detailed list" must still be sent, so the page can tell "off" from "default on"
  var form = document.querySelector('form.toolbar-bar'), chk = form && form.querySelector('input[name=list]');
  if (form && chk) form.addEventListener('submit', function () { if (!chk.checked) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'list'; h.value = '0'; form.appendChild(h); } });
})();
