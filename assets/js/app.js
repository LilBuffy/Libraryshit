// ============================================================
// Library Management System — Modern UI JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── Sidebar toggle ──────────────────────────────────────
  const toggle   = document.getElementById('sidebarToggle');
  const sidebar  = document.getElementById('sidebar');

  // Overlay
  const overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  document.body.appendChild(overlay);

  function openSidebar() {
    sidebar && sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar && sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (toggle) toggle.addEventListener('click', function () {
    sidebar && sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  overlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  // ── Auto-dismiss alerts ────────────────────────────────
  document.querySelectorAll('.custom-alert').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s ease, transform .4s ease';
      el.style.opacity    = '0';
      el.style.transform  = 'translateY(-6px)';
      setTimeout(function () { el && el.remove(); }, 400);
    }, 5000);
  });

  // ── Stat card counter animation ────────────────────────
  const observerOpts = { threshold: 0.1 };
  const statObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const raw    = el.textContent.replace(/,/g, '').trim();
      const target = parseInt(raw, 10);
      if (isNaN(target) || target === 0) return;

      let start    = 0;
      const dur    = 800;
      const step   = 16;
      const inc    = target / (dur / step);
      const timer  = setInterval(function () {
        start += inc;
        if (start >= target) {
          el.textContent = target.toLocaleString();
          clearInterval(timer);
        } else {
          el.textContent = Math.floor(start).toLocaleString();
        }
      }, step);

      statObserver.unobserve(el);
    });
  }, observerOpts);

  document.querySelectorAll('.stat-value, .ms-num').forEach(function (el) {
    statObserver.observe(el);
  });

  // ── Due date auto-fill: borrow_date + 14 days ─────────
  const borrowDateEl = document.querySelector('input[name="borrow_date"]');
  const dueDateEl    = document.getElementById('dueDateInput')
                    || document.querySelector('input[name="due_date"]');

  if (borrowDateEl && dueDateEl) {
    borrowDateEl.addEventListener('change', function () {
      const d = new Date(this.value);
      if (!isNaN(d.getTime())) {
        d.setDate(d.getDate() + 14);
        dueDateEl.value = d.toISOString().split('T')[0];
        dueDateEl.style.borderColor = '#22c55e';
        setTimeout(() => dueDateEl.style.borderColor = '', 1200);
      }
    });
  }

  // ── Book number: auto uppercase ────────────────────────
  const bnInput = document.querySelector('input[name="book_number"]');
  if (bnInput) {
    bnInput.addEventListener('input', function () {
      const pos   = this.selectionStart;
      this.value  = this.value.toUpperCase();
      this.setSelectionRange(pos, pos);
    });
  }

  // ── Search: Enter to submit, Escape to clear ──────────
  document.querySelectorAll('.search-input').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const form = input.closest('form');
        if (form) form.submit();
      }
      if (e.key === 'Escape') {
        this.value = '';
        const form = input.closest('form');
        if (form) form.submit();
      }
    });
  });

  // ── Search term highlight in table ────────────────────
  const searchEl = document.querySelector('.search-input');
  if (searchEl && searchEl.value.trim().length > 1) {
    const term  = searchEl.value.trim();
    const regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    document.querySelectorAll('.data-table tbody td').forEach(function (td) {
      if (!td.children.length && td.textContent.trim()) {
        td.innerHTML = td.textContent.replace(
          regex,
          '<mark style="background:#fef08a;color:#713f12;padding:0 2px;border-radius:2px;font-weight:600;">$1</mark>'
        );
      }
    });
  }

  // ── Staggered table row animations ────────────────────
  const tableRows = document.querySelectorAll('.data-table tbody tr');
  tableRows.forEach(function (row, i) {
    row.style.opacity   = '0';
    row.style.transform = 'translateY(8px)';
    setTimeout(function () {
      row.style.transition = 'opacity .25s ease, transform .25s ease';
      row.style.opacity    = '1';
      row.style.transform  = 'translateY(0)';
    }, i * 30);
  });

  // ── Stagger section cards ──────────────────────────────
  document.querySelectorAll('.section-card').forEach(function (card, i) {
    card.style.animationDelay = (i * 0.06) + 's';
  });

  // ── Form submit: disable to prevent double-submit ─────
  document.querySelectorAll('.form-custom').forEach(function (form) {
    form.addEventListener('submit', function () {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Processing...';
        btn.style.opacity = '.75';
        setTimeout(function () {
          btn.disabled  = false;
          btn.innerHTML = orig;
          btn.style.opacity = '1';
        }, 8000);
      }
    });
  });

  // ── Input focus ring glow ──────────────────────────────
  document.querySelectorAll('.form-ctrl').forEach(function (inp) {
    inp.addEventListener('focus', function () {
      this.parentElement && this.parentElement.classList.add('input-focused');
    });
    inp.addEventListener('blur', function () {
      this.parentElement && this.parentElement.classList.remove('input-focused');
    });
  });

  // ── Tooltip on truncated text ──────────────────────────
  document.querySelectorAll('.data-table td').forEach(function (td) {
    if (td.scrollWidth > td.clientWidth) {
      td.title = td.textContent.trim();
    }
  });

  // ── Quick btn ripple effect ────────────────────────────
  document.querySelectorAll('.quick-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const rect   = btn.getBoundingClientRect();
      const ripple = document.createElement('span');
      const size   = Math.max(rect.width, rect.height);
      ripple.style.cssText = `
        position:absolute;width:${size}px;height:${size}px;
        left:${e.clientX - rect.left - size/2}px;
        top:${e.clientY - rect.top - size/2}px;
        background:rgba(255,255,255,.15);border-radius:50%;
        transform:scale(0);animation:ripple .5s ease-out;
        pointer-events:none;z-index:2;
      `;
      btn.appendChild(ripple);
      setTimeout(() => ripple.remove(), 500);
    });
  });

  // ── Ripple keyframe injection ──────────────────────────
  if (!document.getElementById('ripple-style')) {
    const s = document.createElement('style');
    s.id = 'ripple-style';
    s.textContent = `@keyframes ripple{to{transform:scale(2.5);opacity:0;}}`;
    document.head.appendChild(s);
  }

  // ── Active nav item from URL ───────────────────────────
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(function (item) {
    if (item.getAttribute('href') && path.includes(item.getAttribute('href').split('?')[0])) {
      item.classList.add('active');
    }
  });

  // ── Tooltip for action buttons ─────────────────────────
  document.querySelectorAll('.btn-action[title]').forEach(function (btn) {
    btn.addEventListener('mouseenter', function () {
      const tip = document.createElement('div');
      tip.className = '__tooltip';
      tip.textContent = btn.title;
      tip.style.cssText = `
        position:fixed;background:#111;color:#fff;font-size:11px;
        padding:4px 9px;border-radius:5px;pointer-events:none;z-index:9999;
        white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,.2);
        font-family:'Inter',Arial,sans-serif;letter-spacing:.2px;
      `;
      document.body.appendChild(tip);
      const r = btn.getBoundingClientRect();
      tip.style.left = (r.left + r.width/2 - tip.offsetWidth/2) + 'px';
      tip.style.top  = (r.top - tip.offsetHeight - 6) + 'px';
      btn._tooltip = tip;
    });

    btn.addEventListener('mouseleave', function () {
      if (btn._tooltip) { btn._tooltip.remove(); btn._tooltip = null; }
    });
  });

  // ── Dashboard mini chart (activity line) ──────────────
  const chartCanvas = document.getElementById('activityChart');
  if (chartCanvas && window.Chart) {
    const ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: window._chartLabels || [],
        datasets: [{
          label: 'Borrows',
          data: window._chartBorrows || [],
          borderColor: '#111',
          backgroundColor: 'rgba(0,0,0,0.05)',
          borderWidth: 2,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#111',
          pointRadius: 4,
          pointHoverRadius: 6,
        },{
          label: 'Returns',
          data: window._chartReturns || [],
          borderColor: '#16a34a',
          backgroundColor: 'rgba(22,163,74,0.05)',
          borderWidth: 2,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#16a34a',
          pointRadius: 4,
          pointHoverRadius: 6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              font: { family: 'Inter', size: 11 },
              color: '#666',
              boxWidth: 12,
              padding: 16
            }
          },
          tooltip: {
            backgroundColor: '#111',
            titleFont: { family: 'Inter', size: 12 },
            bodyFont:  { family: 'Inter', size: 12 },
            padding: 10,
            cornerRadius: 8,
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { family: 'Inter', size: 11 }, color: '#999' }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: {
              font: { family: 'Inter', size: 11 },
              color: '#999',
              stepSize: 1,
              precision: 0
            }
          }
        }
      }
    });
  }

});
