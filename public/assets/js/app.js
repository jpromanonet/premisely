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
});
