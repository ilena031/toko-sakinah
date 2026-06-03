# 🚀 HOSTING GUIDE — Toko Sakinah Online

Panduan deploy ke shared hosting (Niagahoster / Hostinger / Domainesia).

## 📋 Checklist Sebelum Upload

- [ ] Pastikan semua fitur jalan di local (MAMP)
- [ ] Backup database via phpMyAdmin → Export → SQL
- [ ] Sudah punya akun hosting + domain
- [ ] Akun Midtrans (Sandbox dulu, baru Production)

---

## 1️⃣ Persiapan File untuk Upload

### File yang **HARUS** diupload:
```
admin/                  # Panel admin
ai_service/             # (Skip kalau pakai mock — tidak perlu di shared hosting)
assets/                 # CSS, JS, gambar
config/                 # Config files
data/                   # Logo, gambar sample
pelanggan/              # Halaman pelanggan
templates/              # Header/footer
uploads/                # Folder upload (kosongkan, biar di-isi user)
vendor/                 # Composer packages (mPDF, Midtrans, dll)
*.php                   # File root (index, katalog, produk_detail, dll)
.htaccess               # Security
koneksi.php
composer.json
composer.lock
```

### File yang **TIDAK** perlu diupload:
- `Spesifikasi_TokoSakinah_v2.docx`
- `import_produk.php` (dev only)
- `data/products.json` (kalau sudah migrate ke DB)
- `data/migrate_jenjang.sql` (jalankan sekali, tidak perlu upload)
- `.git/`

### Compress jadi ZIP
```bash
cd /Users/syahribanun/Sites/localhost
zip -r toko-sakinah.zip toko-sakinah \
  -x "toko-sakinah/.git/*" \
  -x "toko-sakinah/Spesifikasi_TokoSakinah_v2.docx" \
  -x "toko-sakinah/data/products.json" \
  -x "toko-sakinah/data/migrate_*.sql" \
  -x "toko-sakinah/data/preview-*.png" \
  -x "toko-sakinah/import_produk.php"
```

---

## 2️⃣ Upload ke Hosting

**Cara A — Via cPanel File Manager:**
1. Login cPanel hosting
2. Buka **File Manager** → folder `public_html/` (atau subdomain folder)
3. Upload `toko-sakinah.zip`
4. Klik kanan ZIP → **Extract**
5. Pindahkan isi `toko-sakinah/` ke root `public_html/` (atau biarkan jadi `public_html/toko-sakinah/`)

**Cara B — Via FTP (FileZilla):**
1. Hubungkan ke FTP hosting
2. Drag semua isi folder lokal ke server

---

## 3️⃣ Setup Database di Hosting

1. cPanel → **MySQL Databases**
2. Buat database baru, contoh: `tokoxxxx_sakinah`
3. Buat user baru → assign ke database (centang **ALL PRIVILEGES**)
4. Catat: `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST` (biasanya `localhost`)

### Import data dari local:
1. cPanel → **phpMyAdmin** → pilih database baru
2. Tab **Import** → upload file SQL backup → **Go**
3. Pastikan tabel terisi semua

---

## 4️⃣ Update Konfigurasi

### Edit `koneksi.php`:
```php
<?php
define('DB_HOST', 'localhost');           // Biasanya localhost di hosting
define('DB_USER', 'tokoxxxx_admin');       // Ganti
define('DB_PASS', 'PASSWORD_BARU');        // Ganti
define('DB_NAME', 'tokoxxxx_sakinah');     // Ganti

define('BASE_URL', 'https://domainkamu.com/');  // ← Ganti dengan URL hosting
define('UPLOAD_PATH', __DIR__ . '/uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
```

### Edit `config/config.php` (Midtrans):

Untuk **Production** (jualan beneran):
```php
define('MIDTRANS_SERVER_KEY', 'Mid-server-PRODUCTION_KEY');
define('MIDTRANS_CLIENT_KEY', 'Mid-client-PRODUCTION_KEY');
define('MIDTRANS_IS_PRODUCTION', true);
```

Untuk **Sandbox** (testing dulu):
```php
define('MIDTRANS_IS_PRODUCTION', false);
// Server key & client key tetap pakai yang Sandbox
```

---

## 5️⃣ Setting Permission Folder

Lewat File Manager / FTP, set permission:

| Folder | Permission |
|--------|------------|
| `uploads/` | **755** atau **775** (writable) |
| `uploads/produk/` | **755** atau **775** |
| `uploads/tryon/` | **755** atau **775** |
| `uploads/custom_referensi/` | **755** atau **775** |
| `uploads/bukti_bayar/` | **755** atau **775** |
| `uploads/logs/` | **755** atau **775** |
| File `.php` | **644** |

---

## 6️⃣ Setup Webhook Midtrans

1. Login [dashboard.midtrans.com](https://dashboard.midtrans.com) (atau sandbox)
2. **Settings** → **Configuration** → **Payment Notification URL**
3. Isi: `https://domainkamu.com/midtrans_webhook.php`
4. **Finish Redirect URL**: `https://domainkamu.com/pelanggan/pembayaran_status.php`
5. Save

### Test webhook:
- Dashboard Midtrans → **Transactions** → pilih order test → Approve/Cancel
- Cek `uploads/logs/midtrans_webhook.log` di server

---

## 7️⃣ HTTPS (SSL)

Webcam (Virtual Try-On) **WAJIB HTTPS** di production. Localhost dikecualikan.

- Aktifkan **Free SSL (Let's Encrypt)** di cPanel
- Atau **AutoSSL** kalau pakai Niagahoster/Hostinger
- Force redirect HTTP → HTTPS di `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 8️⃣ Bikin Admin User di Hosting

Setelah deploy, masuk phpMyAdmin → SQL → jalankan:

```sql
-- Ganti email & nama. Password default: admin123
INSERT INTO users (nama, email, password, no_hp, role)
VALUES (
  'Admin Toko Sakinah',
  'admin@tokosakinah.com',
  -- Pakai password_hash result dari local
  '$2y$10$...',
  '081234567890',
  'admin'
);
```

Hash password generate dari local:
```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
```

---

## 9️⃣ Final Test

- [ ] Buka URL hosting → landing page tampil
- [ ] Login sebagai admin → masuk panel
- [ ] Tambah produk via admin
- [ ] Logout, register user baru
- [ ] Belanja → bayar via Midtrans → cek webhook log
- [ ] Test Virtual Try-On (butuh HTTPS)
- [ ] Cetak PDF bukti pesanan

---

## 🔧 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **500 Error** | Cek error log cPanel: Error Log. Biasanya permission/PHP version |
| **DB connection fail** | Re-cek DB_USER/DB_PASS/DB_NAME di koneksi.php |
| **Foto produk tidak muncul** | Cek permission folder `uploads/` jadi 755, dan path di DB pakai forward slash |
| **Webcam Try-On tidak nyala** | Pastikan SSL aktif (HTTPS) |
| **Composer error** | Upload manual folder `vendor/` dari local |
| **Mid-server-XXX 401 error** | Server key salah / belum aktif di Midtrans dashboard |
| **Webhook tidak masuk** | Cek URL webhook benar, file `.htaccess` tidak block POST ke webhook |

---

## 🎯 Hosting Recommendation

| Hosting | Paket | Harga / bulan | Note |
|---------|-------|---------------|------|
| **Niagahoster** | Bayi | Rp 10rb–25rb | Indonesia, support PHP 8, MySQL, gratis SSL |
| **Hostinger** | Premium | Rp 30rb | Indonesia friendly, bagus untuk EAS |
| **Domainesia** | Lite | Rp 15rb | Indonesia, support penuh |
| **InfinityFree** | Free | Rp 0 | Gratis tapi limit, OK untuk demo |

Untuk **demo EAS**: InfinityFree atau Niagahoster Bayi cukup.
