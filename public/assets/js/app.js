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
      const formId = el.getAttribute('form');
      const form = formId ? document.getElementById(formId) : el.closest('form');
      form?.requestSubmit ? form.requestSubmit() : form?.submit();
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

  initSortableTables();
});

function initSortableTables() {
  document.querySelectorAll('.table-wrap table, main table').forEach((table) => {
    if (table.dataset.sortableInit === '1') {
      return;
    }
    const thead = table.tHead;
    const tbody = table.tBodies[0];
    if (!thead || !tbody || tbody.rows.length === 0) {
      return;
    }

    const headerRow = thead.rows[0];
    if (!headerRow) {
      return;
    }

    table.dataset.sortableInit = '1';
    table.classList.add('table--sortable');

    Array.from(headerRow.cells).forEach((th, columnIndex) => {
      const label = (th.textContent || '').trim();
      if (!label || th.hasAttribute('data-nosort')) {
        return;
      }

      th.classList.add('th-sortable');
      th.setAttribute('tabindex', '0');
      th.setAttribute('role', 'button');
      th.setAttribute('aria-label', `Ordenar por ${label}`);
      th.title = `Ordenar por ${label}`;

      const activate = () => sortTableByColumn(table, columnIndex, th);
      th.addEventListener('click', activate);
      th.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          activate();
        }
      });
    });
  });
}

function sortTableByColumn(table, columnIndex, th) {
  const tbody = table.tBodies[0];
  if (!tbody) {
    return;
  }

  const current = th.getAttribute('aria-sort');
  const direction = current === 'ascending' ? 'desc' : 'asc';

  Array.from(table.tHead.rows[0].cells).forEach((cell) => {
    cell.removeAttribute('aria-sort');
    cell.classList.remove('is-sorted-asc', 'is-sorted-desc');
  });
  th.setAttribute('aria-sort', direction === 'asc' ? 'ascending' : 'descending');
  th.classList.add(direction === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');

  const rows = Array.from(tbody.rows);
  const collator = new Intl.Collator('es', { numeric: true, sensitivity: 'base' });

  rows.sort((rowA, rowB) => {
    const a = cellSortValue(rowA.cells[columnIndex]);
    const b = cellSortValue(rowB.cells[columnIndex]);
    let cmp = 0;

    if (a.type === 'number' && b.type === 'number') {
      cmp = a.value - b.value;
    } else if (a.type === 'date' && b.type === 'date') {
      cmp = a.value - b.value;
    } else {
      cmp = collator.compare(String(a.value), String(b.value));
    }

    if (cmp === 0) {
      return 0;
    }
    return direction === 'asc' ? cmp : -cmp;
  });

  const frag = document.createDocumentFragment();
  rows.forEach((row) => frag.appendChild(row));
  tbody.appendChild(frag);
}

function cellSortValue(cell) {
  if (!cell) {
    return { type: 'string', value: '' };
  }

  if (cell.dataset.sort !== undefined) {
    return parseSortToken(cell.dataset.sort);
  }

  const select = cell.querySelector('select');
  if (select) {
    const selected = select.options[select.selectedIndex];
    return parseSortToken((selected && selected.textContent) || select.value || '');
  }

  const clone = cell.cloneNode(true);
  clone.querySelectorAll('script, style, .sr-only').forEach((el) => el.remove());
  const text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
  return parseSortToken(text);
}

function parseSortToken(raw) {
  const text = String(raw || '').trim();
  if (text === '' || text === '—') {
    return { type: 'string', value: '' };
  }

  const normalized = text
    .replace(/\s/g, '')
    .replace(/\$/g, '')
    .replace(/%/g, '');

  // 1.234,56 or 1234,56 (es-AR) / 1,234.56
  let numeric = normalized;
  if (/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(normalized) || /^-?\d+,\d+$/.test(normalized)) {
    numeric = normalized.replace(/\./g, '').replace(',', '.');
  } else if (/^-?\d{1,3}(,\d{3})+(\.\d+)?$/.test(normalized)) {
    numeric = normalized.replace(/,/g, '');
  }

  if (/^-?\d+(\.\d+)?$/.test(numeric)) {
    return { type: 'number', value: Number(numeric) };
  }

  const dateMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}(?::\d{2})?)?/);
  if (dateMatch) {
    const ts = Date.parse(text.replace(' ', 'T'));
    if (!Number.isNaN(ts)) {
      return { type: 'date', value: ts };
    }
  }

  const dmy = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
  if (dmy) {
    const ts = Date.parse(`${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`);
    if (!Number.isNaN(ts)) {
      return { type: 'date', value: ts };
    }
  }

  return { type: 'string', value: text.toLocaleLowerCase('es') };
}
