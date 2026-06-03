/* ─────────────────────────────────────────────
 * KERANJANG.JS — Logic untuk halaman keranjang
 * - Qty +/-  (debounced)
 * - Hapus item (dengan konfirmasi)
 * - Kosongkan cart
 * - Update badge cart di header tanpa reload
 * ───────────────────────────────────────────── */

(function () {
  'use strict';

  const BASE = window.location.pathname.split('/pelanggan/')[0] + '/';
  const ENDPOINT_UPDATE = BASE + 'pelanggan/update_cart.php';
  const ENDPOINT_REMOVE = BASE + 'pelanggan/remove_cart.php';

  const debounceTimers = {};

  // ── Helpers ───────────────────────────────────
  function showAlert(type, message) {
    // Hapus alert lama
    document.querySelectorAll('.cart-alert.js-alert').forEach(el => el.remove());

    const alert = document.createElement('div');
    alert.className = 'cart-alert js-alert ' + (type === 'success' ? 'alert-success' : 'alert-danger');
    alert.innerHTML = `<i class="bi bi-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;

    const wrapper = document.querySelector('.cart-header');
    if (wrapper) wrapper.insertAdjacentElement('afterend', alert);

    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 3000);
  }

  function updateBadge(count) {
    const badge = document.getElementById('cart-badge');
    const navKeranjang = document.getElementById('nav-keranjang');
    if (count > 0) {
      if (badge) {
        badge.textContent = count;
      } else if (navKeranjang) {
        const span = document.createElement('span');
        span.className = 'cart-badge';
        span.id = 'cart-badge';
        span.textContent = count;
        navKeranjang.appendChild(span);
      }
    } else if (badge) {
      badge.remove();
    }
  }

  function updateSummary(totals) {
    const elItems    = document.getElementById('summary-items');
    const elSubtotal = document.getElementById('summary-subtotal');
    const elTotal    = document.getElementById('summary-total');
    if (elItems && totals.items !== undefined)
      elItems.textContent = totals.items + ' pcs';
    if (elSubtotal && totals.subtotal_formatted)
      elSubtotal.textContent = totals.subtotal_formatted;
    if (elTotal && totals.subtotal_formatted)
      elTotal.textContent = totals.subtotal_formatted;
  }

  function updateItemSubtotal(key, formatted) {
    const el = document.querySelector(`.cart-item-subtotal[data-key="${CSS.escape(key)}"]`);
    if (el) el.textContent = formatted;
  }

  // ── Update Qty (debounced 350ms) ──────────────
  function updateQty(key, jumlah, inputEl) {
    if (debounceTimers[key]) clearTimeout(debounceTimers[key]);
    debounceTimers[key] = setTimeout(() => {
      const fd = new FormData();
      fd.append('cart_key', key);
      fd.append('jumlah', jumlah);

      fetch(ENDPOINT_UPDATE, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            updateItemSubtotal(key, data.item.subtotal_formatted);
            updateSummary(data.totals);
            updateBadge(data.cart_count);
          } else {
            showAlert('danger', data.message);
            // Reset input ke nilai semula
            if (inputEl) {
              const max = parseInt(inputEl.getAttribute('max')) || 1;
              inputEl.value = Math.min(parseInt(inputEl.value), max);
            }
          }
        })
        .catch(() => showAlert('danger', 'Gagal menghubungi server.'));
    }, 350);
  }

  // ── Qty buttons & input ───────────────────────
  document.addEventListener('click', function (e) {
    const minus = e.target.closest('.qty-minus');
    const plus  = e.target.closest('.qty-plus');
    const removeBtn = e.target.closest('.cart-item-remove');
    const clearBtn  = e.target.closest('#btn-clear-cart');

    if (minus || plus) {
      const key = (minus || plus).dataset.key;
      const input = document.querySelector(`.qty-input[data-key="${CSS.escape(key)}"]`);
      if (!input) return;
      let val = parseInt(input.value) || 1;
      const max = parseInt(input.getAttribute('max')) || 999;
      val = minus ? val - 1 : val + 1;
      val = Math.max(1, Math.min(max, val));
      input.value = val;
      updateQty(key, val, input);
    }

    if (removeBtn) {
      const key = removeBtn.dataset.key;
      if (!confirm('Yakin mau hapus item ini dari keranjang?')) return;

      const fd = new FormData();
      fd.append('cart_key', key);
      fetch(ENDPOINT_REMOVE, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            const item = document.querySelector(`.cart-item[data-key="${CSS.escape(key)}"]`);
            if (item) {
              item.classList.add('removing');
              setTimeout(() => {
                item.remove();
                updateSummary(data.totals);
                updateBadge(data.cart_count);
                if (data.cart_empty) location.reload();
              }, 250);
            }
          } else {
            showAlert('danger', data.message);
          }
        })
        .catch(() => showAlert('danger', 'Gagal menghubungi server.'));
    }

    if (clearBtn) {
      if (!confirm('Yakin mau kosongkan seluruh keranjang?')) return;
      const fd = new FormData();
      fd.append('action', 'clear');
      fetch(ENDPOINT_REMOVE, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            updateBadge(0);
            location.reload();
          }
        })
        .catch(() => showAlert('danger', 'Gagal menghubungi server.'));
    }
  });

  // Input ketik manual qty
  document.addEventListener('input', function (e) {
    const input = e.target.closest('.qty-input');
    if (!input) return;
    const key = input.dataset.key;
    let val = parseInt(input.value) || 1;
    const max = parseInt(input.getAttribute('max')) || 999;
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
    updateQty(key, val, input);
  });
})();
