/**
 * MAIN.JS — Toko Sakinah Global Scripts
 */

document.addEventListener('DOMContentLoaded', function () {

  // ── Search Form ──────────────────────────────────────────────
  const searchForm = document.getElementById('search-form');
  const searchInput = document.getElementById('search-input');

  if (searchForm && searchInput) {
    searchForm.addEventListener('submit', function (e) {
      const q = searchInput.value.trim();
      if (!q) {
        e.preventDefault();
        searchInput.focus();
      }
    });
  }

  // ── Sticky Header Shadow + Category Nav Position ─────────────
  const mainHeader = document.getElementById('main-header');
  const categoryNav = document.getElementById('category-nav');

  function setCategoryNavTop() {
    if (mainHeader && categoryNav) {
      const headerH = mainHeader.offsetHeight;
      categoryNav.style.top = headerH + 'px';
    }
  }

  setCategoryNavTop();
  window.addEventListener('resize', setCategoryNavTop);

  if (mainHeader) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 10) {
        mainHeader.style.boxShadow = '0 4px 20px rgba(0,0,0,0.08)';
      } else {
        mainHeader.style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)';
      }
    }, { passive: true });
  }

  // ── Cart Badge Update ───────────────────────────────────────
  window.updateCartBadge = function (count) {
    const badge = document.getElementById('cart-badge');
    const navKeranjang = document.getElementById('nav-keranjang');

    if (count > 0) {
      if (badge) {
        badge.textContent = count;
        badge.style.animation = 'none';
        badge.offsetHeight; // trigger reflow
        badge.style.animation = 'badgePop 0.3s ease';
      } else if (navKeranjang) {
        const newBadge = document.createElement('span');
        newBadge.className = 'cart-badge';
        newBadge.id = 'cart-badge';
        newBadge.textContent = count;
        navKeranjang.appendChild(newBadge);
      }
    } else {
      if (badge) badge.remove();
    }
  };

  // ── Format Rupiah ───────────────────────────────────────────
  window.formatRupiah = function (angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
  };

  // ── Toast Notification ──────────────────────────────────────
  window.showToast = function (message, type = 'success') {
    // Remove existing toast
    const existing = document.getElementById('ts-toast');
    if (existing) existing.remove();

    const iconMap = {
      success: 'bi-check-circle-fill',
      error: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill'
    };
    const bgMap = {
      success: '#43A047',
      error: '#E53935',
      info: '#1976D2'
    };

    const toast = document.createElement('div');
    toast.id = 'ts-toast';
    toast.innerHTML = `<i class="bi ${iconMap[type] || iconMap.info}"></i> ${message}`;
    Object.assign(toast.style, {
      position: 'fixed',
      bottom: '24px',
      right: '24px',
      background: bgMap[type] || bgMap.info,
      color: '#fff',
      padding: '14px 24px',
      borderRadius: '10px',
      fontSize: '13.5px',
      fontFamily: "'Inter', sans-serif",
      fontWeight: '500',
      boxShadow: '0 8px 30px rgba(0,0,0,0.15)',
      zIndex: '9999',
      display: 'flex',
      alignItems: 'center',
      gap: '10px',
      opacity: '0',
      transform: 'translateY(20px)',
      transition: 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)'
    });

    document.body.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
    });

    // Auto-dismiss
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 400);
    }, 3000);
  };

  // ── Smooth Scroll for Anchor Links ──────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Animate Elements on Scroll (Intersection Observer) ─────
  const animateEls = document.querySelectorAll('.animate-on-scroll');
  if (animateEls.length > 0) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-fade-in');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    animateEls.forEach(el => observer.observe(el));
  }

});
