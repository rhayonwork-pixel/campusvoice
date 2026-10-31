/* Course / program field: type anything, or open the list and choose — grouped by college.
   Add data-course-picker to any text input. Stored values are kept <= 60 characters (database column size). */
(function () {
  'use strict';

  var COURSES = [
    { college: 'College of Arts and Science (CAS)', programs: [
      'Bachelor of Arts in Journalism',
      'Bachelor of Arts in Political Science',
      'Bachelor of Public Administration',
      'Bachelor of Science in Social Work'
    ] },
    { college: 'College of Criminology', programs: [
      'Bachelor of Science in Criminology'
    ] },
    { college: 'College of Business Administration (CBA)', programs: [
      'BS Business Administration – Financial Management',
      'BS Business Administration – Human Resource Management',
      'BS Business Administration – Marketing Management'
    ] },
    { college: 'College of Accountancy (COA)', programs: [
      'Bachelor of Science in Accountancy (BSA)',
      'Bachelor of Science in Management Accounting (BSMA)'
    ] },
    { college: 'College of Computer Studies (CCS)', programs: [
      'Bachelor of Science in Information Technology (BSIT)'
    ] },
    { college: 'College of Teacher Education (CTE)', programs: [
      'Bachelor in Elementary Education (BEED)',
      'Bachelor in Secondary Education (BSE) – English',
      'Bachelor in Secondary Education (BSE) – Mathematics',
      'Bachelor in Secondary Education (BSE) – Social Studies',
      'Bachelor of Early Childhood Education (BECED)'
    ] }
  ];
  window.CV_COURSES = COURSES;

  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

  function enhance(input) {
    if (input.dataset.cpReady) return;
    input.dataset.cpReady = '1';
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');

    var wrap = el('div', 'cp-wrap');
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    var panel = el('div', 'cp-panel'); panel.setAttribute('role', 'listbox'); panel.hidden = true;
    wrap.appendChild(panel);

    var items = [], active = -1;

    function render() {
      var q = input.value.trim().toLowerCase();
      panel.innerHTML = ''; items = []; active = -1;
      var shown = 0;
      COURSES.forEach(function (g) {
        var collegeHit = q && g.college.toLowerCase().indexOf(q) !== -1;
        var progs = g.programs.filter(function (p) { return !q || collegeHit || p.toLowerCase().indexOf(q) !== -1; });
        if (!progs.length) return;
        panel.appendChild(el('div', 'cp-group', g.college));
        progs.forEach(function (p) {
          var o = el('div', 'cp-opt', p); o.setAttribute('role', 'option'); o.dataset.value = p;
          if (p.toLowerCase() === input.value.trim().toLowerCase()) o.classList.add('chosen');
          panel.appendChild(o); items.push(o); shown++;
        });
      });
      var hint = el('div', 'cp-hint', shown ? 'You can also type your own program.' : (q ? 'No match — your typed text will be used as your program.' : ''));
      if (hint.textContent) panel.appendChild(hint);
    }
    function open() { render(); panel.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function close() { panel.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }
    function choose(v) { input.value = v; close(); input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true })); }
    function highlight(i) {
      items.forEach(function (o) { o.classList.remove('on'); });
      if (i < 0 || i >= items.length) { active = -1; return; }
      active = i; items[i].classList.add('on'); items[i].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('focus', open);
    input.addEventListener('click', function () { if (panel.hidden) open(); });
    input.addEventListener('input', function () { open(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (panel.hidden) open(); highlight(Math.min(active + 1, items.length - 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(Math.max(active - 1, 0)); }
      else if (e.key === 'Enter' && !panel.hidden && active > -1) { e.preventDefault(); choose(items[active].dataset.value); }
      else if (e.key === 'Escape' && !panel.hidden) { e.stopPropagation(); close(); }
      else if (e.key === 'Tab') close();
    });
    // mousedown (not click) so the input does not lose focus before the choice registers
    panel.addEventListener('mousedown', function (e) { var o = e.target.closest('.cp-opt'); if (o) { e.preventDefault(); choose(o.dataset.value); } });
    document.addEventListener('mousedown', function (e) { if (!wrap.contains(e.target)) close(); });
  }

  function init() { Array.prototype.forEach.call(document.querySelectorAll('input[data-course-picker]'), enhance); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
  window.CV_initCoursePickers = init;   // for fields added later
})();
