
ALTER TABLE produk
ADD COLUMN jenjang VARCHAR(20) NULL DEFAULT NULL
AFTER subcategory;

-- Step 2: Auto-fill jenjang berdasarkan subcategory & nama produk
-- (Urutan penting — yang lebih spesifik dulu)

-- TK
UPDATE produk
SET jenjang = 'tk'
WHERE subcategory LIKE '%TK%'
   OR LOWER(nama_produk) LIKE '%tk%'
   OR LOWER(nama_produk) LIKE '%paud%';

-- SD Besar (default untuk SD karena baju SD lebih banyak dipakai kelas 4-6)
UPDATE produk
SET jenjang = 'sd_besar'
WHERE jenjang IS NULL
  AND (subcategory LIKE '%SD%' OR LOWER(nama_produk) LIKE '%sd%');

-- SMP
UPDATE produk
SET jenjang = 'smp'
WHERE jenjang IS NULL
  AND (subcategory LIKE '%SMP%' OR LOWER(nama_produk) LIKE '%smp%');

-- SMA
UPDATE produk
SET jenjang = 'sma'
WHERE jenjang IS NULL
  AND (subcategory LIKE '%SMA%' OR LOWER(nama_produk) LIKE '%sma%');

-- Pramuka & seragam universal (default SMA karena sizes S,M,L,XL,XXL paling cocok)
UPDATE produk
SET jenjang = 'sma'
WHERE jenjang IS NULL
  AND id_kategori = (SELECT id_kategori FROM (SELECT id_kategori FROM kategori WHERE slug='baju-sekolah') AS k);

-- Oleh-oleh haji & lain-lain → null tetap (tidak butuh jenjang)

-- Step 3: Verifikasi hasil
SELECT id_produk, nama_produk, subcategory, jenjang, ukuran
FROM produk
ORDER BY jenjang, subcategory;
