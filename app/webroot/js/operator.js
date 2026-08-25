(function () {
  'use strict';

  var STORAGE_KEY = 'operator-sidebar-collapsed';
  var MOBILE_BREAKPOINT = 1024;

  var shell = document.getElementById('operator-shell');
  var toggle = document.getElementById('operator-sidebar-toggle');

  if (!shell || !toggle) {
    return;
  }

  function applyCollapsed(collapsed) {
    shell.classList.toggle('sidebar-collapsed', collapsed);
  }

  var saved = window.localStorage.getItem(STORAGE_KEY);
  if (saved === 'true' || saved === 'false') {
    applyCollapsed(saved === 'true');
  } else {
    applyCollapsed(window.innerWidth < MOBILE_BREAKPOINT);
  }

  toggle.addEventListener('click', function () {
    var collapsed = !shell.classList.contains('sidebar-collapsed');
    applyCollapsed(collapsed);
    window.localStorage.setItem(STORAGE_KEY, String(collapsed));
  });
})();
