// ── Toast notifications ──
function showToast(msg, type = 'info', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), duration);
}

// ── Star rating widget ──
function initStarRatings() {
  document.querySelectorAll('.star-rating-widget').forEach(widget => {
    const stars = widget.querySelectorAll('.star-input');
    const input = widget.querySelector('input[type="hidden"]');
    stars.forEach((star, idx) => {
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.classList.toggle('selected', i <= idx));
      });
      star.addEventListener('mouseleave', () => {
        const val = parseInt(input.value) || 0;
        stars.forEach((s, i) => s.classList.toggle('selected', i < val));
      });
      star.addEventListener('click', () => {
        input.value = idx + 1;
        stars.forEach((s, i) => s.classList.toggle('selected', i <= idx));
      });
    });
  });
}

// ── Tabs ──
function initTabs() {
  document.querySelectorAll('[data-tab-group]').forEach(group => {
    const id = group.dataset.tabGroup;
    const buttons = document.querySelectorAll(`[data-tab-target="${id}"]`);
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        const panel = btn.dataset.panel;
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll(`[data-tab-panel="${id}"]`).forEach(p => {
          p.classList.toggle('active', p.dataset.panel === panel);
        });
      });
    });
  });
}

// ── Modals ──
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
  if (e.target.dataset.modalOpen)  openModal(e.target.dataset.modalOpen);
  if (e.target.dataset.modalClose) closeModal(e.target.dataset.modalClose);
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

// ── Price calculator ──
function initPriceCalc() {
  const numInput = document.getElementById('num-people');
  const pricePerPerson = parseFloat(document.getElementById('price-pp')?.value || 0);
  function update() {
    const n = parseInt(numInput?.value) || 1;
    const total = n * pricePerPerson;
    const el = document.getElementById('calc-total');
    if (el) el.textContent = 'R ' + total.toLocaleString('en-ZA', {minimumFractionDigits: 2});
  }
  numInput?.addEventListener('input', update);
  update();
}

// ── Confirm deletes ──
document.addEventListener('click', e => {
  if (e.target.dataset.confirm) {
    if (!confirm(e.target.dataset.confirm)) e.preventDefault();
  }
});

// ── Filters form auto-submit ──
document.querySelectorAll('.filters-bar select').forEach(sel => {
  sel.addEventListener('change', () => sel.closest('form')?.submit());
});

// ── Init on DOM ready ──
document.addEventListener('DOMContentLoaded', () => {
  initStarRatings();
  initTabs();
  initPriceCalc();
});
