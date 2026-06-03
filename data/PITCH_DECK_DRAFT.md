# 🎯 PITCH DECK — TOKO SAKINAH ONLINE
**AMGALA Foundation Final Stage | "STEM Innovation for Real-World Impact"**

> Strategic positioning: Bukan "tugas kuliah jadi e-commerce", tapi **"Solusi inklusif digitalisasi UMKM Indonesia dengan AI body scanner di browser — affordable, scalable, real-impact."**

---

## 📐 Slide 1 — Opening & Personal Motivation

**Title:** Toko Sakinah Online — Digitalisasi UMKM dengan AI Body Scanner

**Tagline (di bawah judul):**
> "Membantu UMKM bersaing di era digital, dengan teknologi yang seharusnya cuma punya brand premium."

**Visual:**
- Foto Toko Sakinah asli (kalau ada) atau logo
- Foto kamu kecil di pojok

**Talking points:**
- "Saya tumbuh melihat toko kecil seperti Toko Sakinah yang berdiri sejak 2010 — masih jualan offline & via WhatsApp di tengah era marketplace."
- "Owner toko bukan tidak mau go-digital, tapi platform yang ada (Shopee/Tokopedia) memotong 5-10% per transaksi & tenggelam di antara seller besar."
- "Saya ingin bangun solusi yang **affordable, mudah, dan punya teknologi setara enterprise** — agar UMKM bisa kompetitif tanpa kehilangan karakternya."

---

## 📐 Slide 2 — Problem Validation (DATA HEAVY)

**Title:** UMKM Indonesia Tertinggal di Era Digital

**Data points (verify ulang sebelum presentasi):**

| Data | Sumber | Insight |
|------|--------|---------|
| **~65 juta UMKM** di Indonesia | BPS 2023 | Tulang punggung ekonomi (60% GDP) |
| **< 30% UMKM go-digital** | KemenkopUKM | Mayoritas belum tersentuh digitalisasi |
| **Rp 50+ triliun** pasar seragam sekolah/tahun | Estimasi dari 55 jt pelajar × Rp 1 jt rata-rata | Pasar besar tapi fragmented |
| **25-40% return rate** baju online | Studi e-commerce fashion | Karena salah ukuran |
| **221.000 jemaah haji/tahun + 1+ jt umroh** | Kemenag | Pasar oleh-oleh haji = recurring tahunan |

**Real cases (specific to Toko Sakinah):**
- Owner Toko Sakinah lose ~30% potential customer karena belum bisa terima order online
- Tahun ajaran baru (Juli) miss demand institusional karena tidak ada platform custom order
- Pelanggan haji minta digital katalog tapi WhatsApp catalog terbatas

**Visual:**
- Pie chart 30% digital vs 70% non-digital
- Bar chart pasar fashion Indonesia
- Foto WhatsApp katalog manual (real pain point)

**Talking points:**
- "Masalahnya bukan UMKM gak mau digital. **Masalahnya cost & kompleksitas.**"
- "Setelah saya wawancara langsung, owner bilang: 'Saya gak ngerti Shopee Seller Center, ribet, fee-nya juga mahal.'"

---

## 📐 Slide 3 — Root Cause Analysis

**Title:** Mengapa Platform Existing Gagal untuk UMKM Niche?

**3 Root Causes:**

### 🔴 Cause 1 — Generic Platform, Lost in Crowd
- Marketplace besar punya **15+ juta seller** → toko kecil tenggelam
- Algoritma favor seller besar dengan budget ads
- Identity brand UMKM hilang di balik UI marketplace

### 🔴 Cause 2 — Belanja Baju Online = Gambling Size
- Konsumen ragu pesan baju karena **tidak tahu muat/tidak**
- Return barang merugikan seller (ongkos kirim 2x + risiko rusak)
- Virtual Try-On premium (Zara, Uniqlo) butuh investasi AI ratusan juta — UMKM tidak mampu

### 🔴 Cause 3 — Custom Order = Chaos Manual
- Pesanan seragam institusional via WhatsApp → tidak terdokumentasi
- Negosiasi harga & spec berulang tanpa platform → miscommunication
- Pembayaran DP manual → trust issue

**Gap analysis:**
> "Tidak ada platform yang sekaligus: niche-focused untuk UMKM + AI try-on affordable + custom order terstruktur."

---

## 📐 Slide 4 — Solution Overview

**Title:** Toko Sakinah Online — 3 Pilar Solusi Terintegrasi

**Visual:** 3 ikon besar berderet

### 🛒 Pilar 1 — E-Commerce Lengkap
- Katalog responsive, payment via Midtrans (GoPay/QRIS/VA/Kartu)
- **1% fee** (vs 5-10% marketplace besar)
- Branded — toko punya identitasnya sendiri

### 📷 Pilar 2 — AI Body Scanner (INOVASI UTAMA)
- Live webcam scan tubuh dengan MediaPipe Pose (Google)
- **Estimasi size per jenjang sekolah Indonesia** (TK/SD/SMP/SMA/Dewasa)
- 100% client-side — **zero AI server cost**
- Output: rekomendasi size + perbandingan dengan stok produk

### 📋 Pilar 3 — Custom Order Platform
- Form pengajuan + upload referensi desain
- Chat konsultasi real-time admin ↔ pelanggan
- Penawaran resmi terstruktur + DP via Midtrans
- Tracking progress produksi

**Differentiator:**
- ✨ Body scanner dengan **jenjang sekolah Indonesia** = produk pertama di pasar
- ✨ Niche focused: seragam sekolah + perlengkapan haji = sengaja **bukan untuk semua**
- ✨ Affordable: Rp 99rb/bulan vs investasi marketplace puluhan juta

---

## 📐 Slide 5 — STEM / Technical Application

**Title:** STEM Applied: AI di Browser, Database Terstruktur, Secure Payment

**Mathematics & Science:**
- **Computer Vision** — MediaPipe Pose detect 33 landmark tubuh real-time
- **Anthropometric calculation** — shoulder width → chest circumference via ratio 2.4×
- **Camera calibration** — face width reference (14.5 cm avg) → cm/pixel conversion
- **Statistical smoothing** — moving average 30 frame untuk stabilkan measurement

**Engineering:**
- 9-table relational database (3NF normalized)
- Client-side AI = **0 GPU server cost** (vs hosted ML $100+/month)
- Payment webhook dengan SHA-512 signature verification
- File upload MIME validation + .htaccess block PHP execution

**Technology Stack:**
```
Frontend  : HTML5 + CSS3 + JavaScript + MediaPipe Pose (CDN)
Backend   : PHP 8 Native + Composer
Database  : MySQL 8 (utf8mb4)
Payment   : Midtrans Snap (GoPay/QRIS/VA/CC)
AI Engine : MediaPipe Pose (Google, WebAssembly)
Hosting   : Hostinger Business + SSL Let's Encrypt
Tools     : mPDF (cetak), PhpSpreadsheet (export)
```

**Visual:** Architecture diagram (browser ↔ PHP ↔ MySQL + Midtrans + MediaPipe)

---

## 📐 Slide 6 — Prototype / System Flow

**Title:** Live Demo & Real-World Implementation

**KEY: Sudah deploy real → https://tokosakinah.com**

**Visual:** 4 screenshot besar:
1. Landing page mobile
2. **Body scanner in action** (dengan dots di shoulder/hip + measurement label) ← INI HIGHLIGHT
3. Checkout dengan Midtrans Snap popup
4. Admin dashboard dengan stats

**System Flow Diagram:**
```
Pelanggan → Browse Katalog → Pilih Produk
                                 ↓
                       Klik "Virtual Try-On"
                                 ↓
              Webcam → MediaPipe Pose → 33 Landmarks
                                 ↓
                Calculate Body Dimensions (cm)
                                 ↓
              Match dengan Size Chart Jenjang
                                 ↓
           Rekomendasi: "Cocok / Kebesaran / Kekecilan"
                                 ↓
                    Add to Cart → Checkout
                                 ↓
                        Midtrans Payment
                                 ↓
              Webhook → Status Update → Admin Process
```

**Talking point:** "Bukan mockup, bukan slide screenshot — **live URL, real database, real payment flow.**"

---

## 📐 Slide 7 — Feasibility & Execution Plan

**Title:** From Idea to Production in 14 Days

**Sudah Eksekusi:**
| Phase | Duration | Output |
|-------|----------|--------|
| Hari 1-7 | 1 minggu | Foundation: DB, auth, katalog, detail |
| Hari 8-11 | 4 hari | Cart, checkout, Midtrans, custom order, PDF, try-on |
| Hari 12-13 | 2 hari | Admin panel lengkap |
| Hari 14 | 1 hari | Polish + deploy |
| **TOTAL** | **14 hari** | **Live di production** |

**Resource Allocation:**
- 👤 **1 developer** (saya, solo)
- 💰 **Total cost <Rp 500rb** (domain + hosting 1 bulan)
- 🛠️ **Tools open source** (PHP, MySQL, MediaPipe = free)
- ⏱️ **~4-6 jam/hari** consistent execution

**Roadmap 12 Bulan:**
| Bulan | Milestone |
|-------|-----------|
| 1-2 | User testing dengan Toko Sakinah real, iterate UI/UX |
| 3-4 | Onboard 5 toko pilot di Palembang & Surabaya |
| 5-6 | Multi-tenant architecture, self-service signup |
| 7-9 | Marketing push, content creator collab |
| 10-12 | Scale ke 100 toko, raise seed funding |

**Risk Mitigation:**
- 🛡️ Bug? → Soft launch ke 1 toko dulu, hardening 30 hari
- 💸 Funding stuck? → Bootstrap mode dengan subscription revenue Rp 99k × 10 toko = Rp 990k/bln cover hosting
- 👥 No traction? → Pivot ke white-label SaaS untuk koperasi/asosiasi UMKM

---

## 📐 Slide 8 — Business & Financial Projection

**Title:** Sustainable Business Model untuk UMKM

**Revenue Streams (multiple):**

| Stream | Pricing | Note |
|--------|---------|------|
| **SaaS subscription** | Rp 99rb/bulan/toko | Core revenue |
| **Transaction fee** | 1% per order | Trailing on volume |
| **Custom branding** | Rp 5 jt one-time | Customization, logo, color |
| **Premium AI Upgrade** | Rp 199rb/bulan | AR overlay, full body fit |
| **Marketplace listing** | Rp 500rb/year | Toko aggregator |

**Cost Structure (per toko, bulanan):**
- Hosting share: Rp 25rb (multi-tenant VPS)
- Midtrans fee: 2.9% (passed to consumer)
- Customer support: Rp 20rb (proportional)
- **Margin per toko: Rp 54rb (54%)**

**Financial Projection Year 1:**

| Bulan | Toko Aktif | MRR (Rp) | Profit Margin |
|-------|-----------|----------|---------------|
| 3 | 5 | 495.000 | 270.000 |
| 6 | 25 | 2.475.000 | 1.350.000 |
| 9 | 60 | 5.940.000 | 3.240.000 |
| 12 | 100 | 9.900.000 | 5.400.000 |

**Year 1 Total Revenue:** ~Rp 60 juta
**Break-even point:** Bulan ke-7 (15 toko aktif)

**Year 2 Target:** 500 toko × Rp 99rb = Rp 49.5 jt/bulan MRR → valuation **Rp 2-5 M**

**Market Size:**
- TAM: 65 juta UMKM × 1% adoption = **650.000 potensi customer**
- SAM: 100.000 UMKM fashion + retail
- SOM: 1.000 UMKM Year 2 (0.1% capture)

---

## 📐 Slide 9 — Social Impact, Scalability & Leadership Reflection

**Title:** Beyond Business — Inclusive Digital Economy

**Social Impact:**

🌍 **UMKM Empowerment**
- Setiap toko di-onboard = 1-3 lapangan kerja baru (admin, packer, kurir)
- Owner UMKM 50+ tahun terbantu adapt teknologi
- Income UMKM naik 20-40% dari digital channel

🎓 **Education Affordability**
- Body scanner = orangtua hemat baju yang salah size
- Estimasi save Rp 100rb-200rb per anak per tahun ajaran

🕌 **Islamic Community Impact**
- Toko niche perlengkapan haji ter-digitalisasi
- Pasar 1+ juta jemaah/tahun jadi accessible

**Scalability:**

📈 **Vertical Expansion**
- Year 1: Seragam + oleh-oleh haji
- Year 2: Fashion muslim, kosmetik halal, makanan kering
- Year 3: All-vertical UMKM fashion

🌏 **Geographic Expansion**
- Year 1-2: 5 kota besar Indonesia
- Year 3+: Malaysia, Brunei (pasar muslim, kultur seragam serupa)

🤖 **Technology Expansion**
- AR overlay baju (Year 2)
- Voice ordering Bahasa Indonesia (Year 2)
- Predictive stock dengan ML (Year 3)

**Leadership Reflection:**

✅ **Solo full-stack** dalam 14 hari = bukti execution capability
✅ **Real deployment** (bukan slide demo) = bukti ownership
✅ **Iterative learning** — fix bug login CSS specificity, debug 500 error, dll
✅ **Strategic thinking** — pilih niche bukan all-vertical = focus
✅ **User-first** — wawancara owner UMKM real before code

**Personal growth:**
> "Saya belajar bahwa **shipping > perfection**. Better: live URL imperfect, than slide deck flawless tapi tidak ada produk."

---

## 📐 Slide 10 — Closing

**Title:** Teknologi Tinggi, Akses Inklusif

**Hero stats:**
- 🔢 **14 hari** development
- 📁 **27 file PHP + 4500+ lines**
- 🗃️ **9 database tables**
- 🌐 **Live di tokosakinah.com**
- 🤖 **AI body scanner di browser** (zero server cost)
- 💰 **<Rp 500rb total dev cost**

**Closing statement:**
> *"Toko Sakinah Online membuktikan: **teknologi tinggi seperti AI body scanner & payment gateway tidak harus eksklusif untuk brand premium**. Dengan engineering yang tepat dan empati terhadap UMKM, kita bisa demokratisasi akses teknologi — supaya toko kecil pun bisa bersaing di era digital."*

**Visual penutup:**
- QR code → https://tokosakinah.com
- Logo Amgala (kanan bawah)
- Foto kamu + nama + role
- Contact: email, GitHub, LinkedIn

**Call to action:**
> *"Mari demokratisasi teknologi untuk 65 juta UMKM Indonesia."*

---

## 🎨 DESIGN GUIDELINES

**Tools rekomendasi:**
1. **Canva Pro** (gratis trial) — paling cepat, banyak template pitch
2. **Figma** — kalau mau custom design profesional
3. **Google Slides** — gratis, gampang collaborate
4. **PowerPoint** — kalau familiar

**Color palette (sesuai brand Toko Sakinah):**
- Primary: **#E91E63** (pink)
- Secondary: **#1E88E5** (biru)
- Accent: **#FFD740** (kuning emas, untuk highlight CTA)
- Background: **#FAFAFA** (off-white)
- Text: **#212121** (hitam soft)

**Typography:**
- Heading: **Poppins Bold** atau **Inter Bold**
- Body: **Inter** atau **Roboto**

**Layout tips:**
- Maks **3 bullet point per slide** (lebih dari itu jadi wall of text)
- Gunakan **angka besar** untuk stats (mis. "65 juta UMKM" font 72pt)
- Setiap slide minimal punya **1 visual** (icon, chart, screenshot, atau diagram)
- Konsisten dengan icon library (Bootstrap Icons / Font Awesome)
- Slide 4-6 (Solution, STEM, Prototype) — **screenshot real product wajib**

---

## 🎤 PRESENTATION TIPS

**Durasi 1 slide ≈ 1 menit** (asumsi 10 menit total presentasi).

**Per slide structure (per 60 detik):**
- 10 detik: Hook (statement provokatif)
- 30 detik: Substansi (poin utama)
- 20 detik: Transisi ke slide berikutnya

**Tone:**
- Bukan tugas kuliah → ini **pitch business**
- Confident, not arrogant
- Storytelling, bukan reading slide
- Tunjukkan **passion untuk masalah** (bukan cuma solusi)

**Q&A Anticipation (prep jawaban untuk pertanyaan ini):**

❓ "Apa bedanya dengan Shopee/Tokopedia?"
✅ Niche-focused untuk UMKM tertentu + body scanner = differentiator. Marketplace besar punya economies of scale yang kita tidak compete head-to-head.

❓ "Apakah AI body scanner-mu akurat?"
✅ Estimasi ±2 cm berdasarkan kalibrasi lebar wajah. Bukan 100% akurat, tapi cukup untuk recommend size. Akurasi bisa ditingkatkan dengan kalibrasi tinggi user (roadmap Year 2).

❓ "Bagaimana validasi market?"
✅ Sudah deploy ke produksi & ready for Toko Sakinah real onboard. Validasi qualitative dari owner UMKM via interview. Year 1 plan = 100 toko pilot untuk validate quantitatively.

❓ "Bagaimana monetize kalau UMKM sensitif harga?"
✅ Free trial 1 bulan + freemium tier (Rp 0 untuk <50 produk + Toko Sakinah branding kecil). Premium Rp 99rb/bulan untuk yang serious.

❓ "Kalau saingan besar (Shopee/Tokopedia) buat fitur try-on, gimana?"
✅ Mereka tidak akan invest di niche kecil seperti seragam sekolah Indonesia. Kalau pun iya, kita sudah punya database UMKM + relasi pelanggan + branding niche.

❓ "Apakah ini scalable secara teknis?"
✅ Stack PHP + MySQL adalah commodity, scaling horizontal mudah. AI client-side = no GPU server cost. Bottleneck di customer support, bukan teknis.
