/* ─────────────────────────────────────────────
 * CHECKOUT.JS — Update total ongkir & total bayar
 * ───────────────────────────────────────────── */

(function () {
  'use strict';

  const radios     = document.querySelectorAll('input[name="metode_pengiriman"]');
  const elSubtotal = document.getElementById('sum-subtotal');
  const elOngkir   = document.getElementById('sum-ongkir');
  const elTotal    = document.getElementById('sum-total');
  const hidOngkir  = document.getElementById('hidden-ongkir');
  const hidTotal   = document.getElementById('hidden-total');
  const form       = document.getElementById('checkout-form');
  const btnSubmit  = document.getElementById('btn-checkout-submit');

  function fmt(n) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
  }

  function recalculate() {
    const subtotal = parseInt(elSubtotal.dataset.value) || 0;
    const checked  = document.querySelector('input[name="metode_pengiriman"]:checked');
    const ongkir   = checked ? parseInt(checked.dataset.ongkir) || 0 : 0;
    const total    = subtotal + ongkir;

    if (elOngkir) {
      elOngkir.textContent  = ongkir > 0 ? fmt(ongkir) : 'GRATIS';
      elOngkir.dataset.value = ongkir;
    }
    if (elTotal)   elTotal.textContent  = fmt(total);
    if (hidOngkir) hidOngkir.value      = ongkir;
    if (hidTotal)  hidTotal.value       = total;

    // Highlight option
    document.querySelectorAll('.shipping-option').forEach(opt => {
      const radio = opt.querySelector('input[type="radio"]');
      opt.classList.toggle('selected', radio && radio.checked);
    });
  }

  radios.forEach(r => r.addEventListener('change', recalculate));
  recalculate();

  // Loading state on submit
  if (form) {
    form.addEventListener('submit', function () {
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="bi bi-arrow-clockwise spinning me-2"></i>Memproses...';
      }
    });
  }
})();
