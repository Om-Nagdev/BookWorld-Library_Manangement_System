/**
 * BookWorld - Global JS
 * Handles: loading screen, sidebar toggle, flash message alerts, small UI helpers
 */

document.addEventListener('DOMContentLoaded', function () {

  // ---- Loading screen ----
  const loader = document.getElementById('bw-loader');
  if (loader) {
    window.addEventListener('load', function () {
      setTimeout(() => loader.classList.add('bw-hide'), 300);
    });
    // fallback in case 'load' already fired
    setTimeout(() => loader.classList.add('bw-hide'), 1800);
  }

  // ---- Sidebar toggle (mobile) ----
  const sidebarToggle = document.getElementById('bw-sidebar-toggle');
  const sidebar = document.getElementById('bw-sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 991 && sidebar.classList.contains('show')) {
        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
          sidebar.classList.remove('show');
        }
      }
    });
  }

  // ---- Flash message (server sets window.BW_FLASH via inline script) ----
  if (window.BW_FLASH && typeof Swal !== 'undefined') {
    const f = window.BW_FLASH;
    Swal.fire({
      icon: f.type === 'error' ? 'error' : f.type,
      title: f.type === 'success' ? 'Success' : (f.type === 'error' ? 'Oops!' : 'Notice'),
      text: f.message,
      confirmButtonColor: '#0F2A3F',
      timer: f.type === 'success' ? 2800 : undefined,
      timerProgressBar: f.type === 'success'
    });
  }

  // ---- Generic delete confirmation (SweetAlert2) ----
  document.querySelectorAll('.bw-confirm-delete').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      const form = el.closest('form');
      const itemName = el.getAttribute('data-item') || 'this item';
      Swal.fire({
        title: 'Are you sure?',
        text: `This will permanently remove ${itemName}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#A93F35',
        cancelButtonColor: '#5B6472',
        confirmButtonText: 'Yes, delete it'
      }).then((result) => {
        if (result.isConfirmed) {
          if (form) form.submit();
          else window.location.href = el.getAttribute('href');
        }
      });
    });
  });

  // ---- Generic action confirmation (approve/reject/issue/return) ----
  document.querySelectorAll('.bw-confirm-action').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      const form = el.closest('form');
      const label = el.getAttribute('data-action-label') || 'proceed';
      const icon = el.getAttribute('data-action-icon') || 'question';
      Swal.fire({
        title: 'Confirm action',
        text: `Do you want to ${label}?`,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#2F5233',
        cancelButtonColor: '#5B6472',
        confirmButtonText: 'Confirm'
      }).then((result) => {
        if (result.isConfirmed && form) form.submit();
      });
    });
  });

  // ---- Live search debounce helper ----
  window.bwDebounce = function (fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  };
});
