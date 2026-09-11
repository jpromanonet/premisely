document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (event) => {
      const message = el.getAttribute('data-confirm') || '¿Confirmás?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-auto-submit]').forEach((el) => {
    el.addEventListener('change', () => {
      el.closest('form')?.submit();
    });
  });

  const sidebar = document.getElementById('app-sidebar');
  const scrollKey = 'premisely.sidebarScroll';
  if (sidebar) {
    const saved = sessionStorage.getItem(scrollKey);
    if (saved !== null) {
      const y = parseInt(saved, 10);
      if (!Number.isNaN(y)) {
        sidebar.scrollTop = y;
      }
    }

    const active = sidebar.querySelector('.nav a.active');
    if (active) {
      const sidebarRect = sidebar.getBoundingClientRect();
      const activeRect = active.getBoundingClientRect();
      const outOfView =
        activeRect.top < sidebarRect.top + 8 ||
        activeRect.bottom > sidebarRect.bottom - 8;
      if (outOfView) {
        active.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      }
    }

    const persist = () => {
      sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
    };
    sidebar.addEventListener('scroll', persist, { passive: true });
    sidebar.querySelectorAll('a[href]').forEach((link) => {
      link.addEventListener('click', persist);
    });
  }
});
