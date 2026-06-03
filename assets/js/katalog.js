/**
 * KATALOG.JS — Product Detail Page Scripts
 * Size/gender selection, price update, gallery, qty controls
 */

document.addEventListener('DOMContentLoaded', function () {

  // ── Product Data (embedded from PHP) ─────────────────────────
  const priceBySize = window.productPriceBySize || {};
  const stockBySize = window.productStockBySize || {};

  // ── Size Selection ─────────────────────────────────────────
  const sizeBtns = document.querySelectorAll('.size-btn:not(.out-of-stock)');
  const priceEl = document.getElementById('detail-price');
  const stockEl = document.getElementById('stock-display');
  const sizeInput = document.getElementById('selected-size');

  sizeBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      sizeBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const size = this.dataset.size;
      if (sizeInput) sizeInput.value = size;

      // Update price
      if (priceBySize[size] && priceEl) {
        priceEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(priceBySize[size]);
      }

      // Update stock display
      if (stockBySize[size] !== undefined && stockEl) {
        const stok = stockBySize[size];
        if (stok > 10) {
          stockEl.innerHTML = 'Stok: <span class="stock-number">' + stok + '</span>';
        } else if (stok > 0) {
          stockEl.innerHTML = 'Stok: <span class="stock-low">' + stok + ' tersisa</span>';
        } else {
          stockEl.innerHTML = '<span class="stock-low">Habis</span>';
        }
      }

      // Reset qty to 1
      const qtyInput = document.getElementById('qty-input');
      if (qtyInput) qtyInput.value = 1;
    });
  });

  // ── Gender Selection ───────────────────────────────────────
  const genderBtns = document.querySelectorAll('.gender-btn');
  const genderInput = document.getElementById('selected-gender');

  genderBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      genderBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      if (genderInput) genderInput.value = this.dataset.gender;
    });
  });

  // ── Quantity Controls ──────────────────────────────────────
  const qtyInput = document.getElementById('qty-input');
  const btnMinus = document.getElementById('qty-minus');
  const btnPlus = document.getElementById('qty-plus');

  if (btnMinus) {
    btnMinus.addEventListener('click', function () {
      let val = parseInt(qtyInput.value) || 1;
      if (val > 1) qtyInput.value = val - 1;
    });
  }

  if (btnPlus) {
    btnPlus.addEventListener('click', function () {
      let val = parseInt(qtyInput.value) || 1;
      const activeSize = document.querySelector('.size-btn.active');
      const maxStok = activeSize ? (stockBySize[activeSize.dataset.size] || 99) : 99;
      if (val < maxStok) qtyInput.value = val + 1;
    });
  }

  if (qtyInput) {
    qtyInput.addEventListener('change', function () {
      let val = parseInt(this.value) || 1;
      if (val < 1) val = 1;
      const activeSize = document.querySelector('.size-btn.active');
      const maxStok = activeSize ? (stockBySize[activeSize.dataset.size] || 99) : 99;
      if (val > maxStok) val = maxStok;
      this.value = val;
    });
  }

  // ── Gallery Thumbnail Click ────────────────────────────────
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainImg = document.getElementById('gallery-main-img');

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', function () {
      thumbs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      if (mainImg) {
        mainImg.src = this.dataset.src;
        mainImg.style.opacity = '0';
        setTimeout(() => { mainImg.style.opacity = '1'; }, 50);
      }
    });
  });

  // ── Add to Cart (AJAX) ─────────────────────────────────────
  const addCartForm = document.getElementById('add-cart-form');
  if (addCartForm) {
    addCartForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      // Validate size selected
      if (!formData.get('ukuran')) {
        showToast('Pilih ukuran terlebih dahulu', 'error');
        return;
      }

      // Tandai loading state pada tombol Add to Cart
      const btnAdd = this.querySelector('.btn-add-cart');
      const btnAddHtml = btnAdd ? btnAdd.innerHTML : '';
      if (btnAdd) {
        btnAdd.disabled = true;
        btnAdd.innerHTML = '<i class="bi bi-arrow-clockwise spinning"></i> Menambahkan...';
      }

      fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showToast(data.message || 'Berhasil ditambahkan ke keranjang!', 'success');
            if (data.cart_count !== undefined) {
              updateCartBadge(data.cart_count);
            }
          } else {
            showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
          }
        })
        .catch((err) => {
          console.error('Add to cart error:', err);
          showToast('Terjadi kesalahan: ' + (err.message || 'koneksi gagal'), 'error');
        })
        .finally(() => {
          if (btnAdd) {
            btnAdd.disabled = false;
            btnAdd.innerHTML = btnAddHtml;
          }
        });
    });
  }

  // ── Beli Sekarang (add to cart → redirect checkout) ────────
  const btnBuyNow = document.getElementById('btn-buy-now');
  if (btnBuyNow && addCartForm) {
    btnBuyNow.addEventListener('click', function () {
      const formData = new FormData(addCartForm);
      if (!formData.get('ukuran')) {
        showToast('Pilih ukuran terlebih dahulu', 'error');
        return;
      }

      const original = this.innerHTML;
      this.disabled = true;
      this.innerHTML = '<i class="bi bi-arrow-clockwise spinning"></i> Menambahkan...';

      fetch(addCartForm.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Langsung ke checkout
            window.location.href = window.location.pathname.split('/produk_detail.php')[0] + '/pelanggan/checkout.php';
          } else {
            showToast(data.message || 'Gagal menambahkan ke keranjang', 'error');
            this.disabled = false;
            this.innerHTML = original;
          }
        })
        .catch(err => {
          console.error('Buy now error:', err);
          showToast('Terjadi kesalahan: ' + (err.message || 'koneksi gagal'), 'error');
          this.disabled = false;
          this.innerHTML = original;
        });
    });
  }

  // Auto-select first size
  const firstSize = document.querySelector('.size-btn:not(.out-of-stock)');
  if (firstSize) firstSize.click();

  // Auto-select first gender
  const firstGender = document.querySelector('.gender-btn');
  if (firstGender) firstGender.click();

  // ── Rating star picker untuk form ulasan ──
  const ratingStars = document.querySelectorAll('.rating-star');
  const ratingInput = document.getElementById('rating-input');
  if (ratingStars.length && ratingInput) {
    function applyRating(val) {
      ratingStars.forEach((s, i) => {
        const icon = s.querySelector('i');
        if (i < val) {
          s.classList.add('active');
          icon.classList.remove('bi-star');
          icon.classList.add('bi-star-fill');
        } else {
          s.classList.remove('active');
          icon.classList.add('bi-star');
          icon.classList.remove('bi-star-fill');
        }
      });
    }
    applyRating(parseInt(ratingInput.value) || 5);
    ratingStars.forEach(star => {
      star.addEventListener('mouseenter', () => applyRating(parseInt(star.dataset.val)));
      star.addEventListener('click', () => {
        ratingInput.value = star.dataset.val;
        applyRating(parseInt(star.dataset.val));
      });
    });
    const ratingPick = document.getElementById('rating-pick');
    if (ratingPick) {
      ratingPick.addEventListener('mouseleave', () => applyRating(parseInt(ratingInput.value) || 5));
    }
  }

});
