/* ─────────────────────────────────────────────
 * TRYON.JS — Live Body Scanner dengan MediaPipe Pose
 * - getUserMedia → video
 * - MediaPipe Pose → 33 landmark tubuh
 * - Hitung lebar bahu + panjang torso → estimasi dada → rekomendasi size
 * - Animasi scanner: lingkaran pulsing di titik-titik tubuh
 * ───────────────────────────────────────────── */

(function () {
  'use strict';

  const BASE = window.location.pathname.split('/pelanggan/')[0] + '/';

  // ── Elements ────────────────────────────────
  const video       = document.getElementById('webcam');
  const canvas      = document.getElementById('overlay');
  const ctx         = canvas.getContext('2d');
  const btnStart    = document.getElementById('btn-start-camera');
  const btnCapture  = document.getElementById('btn-capture');
  const btnStop     = document.getElementById('btn-stop');
  const prestart    = document.getElementById('scanner-prestart');
  const loading     = document.getElementById('scanner-loading');
  const statusBadge = document.getElementById('scanner-status');
  const hud         = document.getElementById('scanner-hud');
  const hudShoulder = document.getElementById('hud-shoulder');
  const hudTorso    = document.getElementById('hud-torso');
  const hudChest    = document.getElementById('hud-chest');
  const sizeArea    = document.getElementById('size-result-area');
  const stageEl     = document.getElementById('scanner-stage');
  const productCards= document.querySelectorAll('.tryon-product-card');

  // ── State ───────────────────────────────────
  let pose = null;
  let camera = null;
  let stream = null;
  let lastLandmarks = null;
  let measureBuffer = { shoulder: [], torso: [], chest: [] };
  const BUFFER_SIZE = 30;       // moving average dari 30 frame
  let stableFrames = 0;         // counter frame stabil
  let frameCount = 0;
  let selectedProduct = {
    id: null, nama: null, foto: null,
    jenjang: null,
    availSizes: [],   // size yang ada stok
    allSizes: [],     // semua size produk (termasuk stok 0)
  };

  // ── Size chart per JENJANG (lingkar dada cm) ──────
  // SAMA label (S/M/L/XL/XXL) tapi chest range BEDA per jenjang.
  // Hard cap di chest_max XXL agar out-of-range bisa detect (mis. dewasa pilih baju TK).
  const SIZE_CHARTS = {
    tk: {
      label: 'TK (4–6 tahun)',
      chart: [
        { size: 'S',   chest_min: 0,  chest_max: 52, desc: 'TK Small',       panjang_estimasi: '36 cm' },
        { size: 'M',   chest_min: 52, chest_max: 56, desc: 'TK Medium',      panjang_estimasi: '38 cm' },
        { size: 'L',   chest_min: 56, chest_max: 60, desc: 'TK Large',       panjang_estimasi: '40 cm' },
        { size: 'XL',  chest_min: 60, chest_max: 64, desc: 'TK Extra Large', panjang_estimasi: '42 cm' },
        { size: 'XXL', chest_min: 64, chest_max: 72, desc: 'TK Double XL',   panjang_estimasi: '44 cm' },
      ],
    },
    sd_kecil: {
      label: 'SD Kelas 1–3 (6–9 tahun)',
      chart: [
        { size: 'S',   chest_min: 0,  chest_max: 64, desc: 'SD Small',       panjang_estimasi: '44 cm' },
        { size: 'M',   chest_min: 64, chest_max: 68, desc: 'SD Medium',      panjang_estimasi: '46 cm' },
        { size: 'L',   chest_min: 68, chest_max: 72, desc: 'SD Large',       panjang_estimasi: '48 cm' },
        { size: 'XL',  chest_min: 72, chest_max: 76, desc: 'SD Extra Large', panjang_estimasi: '50 cm' },
        { size: 'XXL', chest_min: 76, chest_max: 84, desc: 'SD Double XL',   panjang_estimasi: '52 cm' },
      ],
    },
    sd_besar: {
      label: 'SD Kelas 4–6 (9–12 tahun)',
      chart: [
        { size: 'S',   chest_min: 0,  chest_max: 72, desc: 'SD Small',       panjang_estimasi: '52 cm' },
        { size: 'M',   chest_min: 72, chest_max: 76, desc: 'SD Medium',      panjang_estimasi: '54 cm' },
        { size: 'L',   chest_min: 76, chest_max: 80, desc: 'SD Large',       panjang_estimasi: '58 cm' },
        { size: 'XL',  chest_min: 80, chest_max: 84, desc: 'SD Extra Large', panjang_estimasi: '60 cm' },
        { size: 'XXL', chest_min: 84, chest_max: 92, desc: 'SD Double XL',   panjang_estimasi: '62 cm' },
      ],
    },
    smp: {
      label: 'SMP (12–15 tahun)',
      chart: [
        { size: 'S',   chest_min: 0,  chest_max: 84, desc: 'SMP Small',       panjang_estimasi: '60 cm' },
        { size: 'M',   chest_min: 84, chest_max: 88, desc: 'SMP Medium',      panjang_estimasi: '62 cm' },
        { size: 'L',   chest_min: 88, chest_max: 92, desc: 'SMP Large',       panjang_estimasi: '64 cm' },
        { size: 'XL',  chest_min: 92, chest_max: 96, desc: 'SMP Extra Large', panjang_estimasi: '66 cm' },
        { size: 'XXL', chest_min: 96, chest_max: 104, desc: 'SMP Double XL',  panjang_estimasi: '68 cm' },
      ],
    },
    sma: {
      label: 'SMA (15–18 tahun)',
      chart: [
        { size: 'S',   chest_min: 0,   chest_max: 92,  desc: 'SMA Small',       panjang_estimasi: '66 cm' },
        { size: 'M',   chest_min: 92,  chest_max: 96,  desc: 'SMA Medium',      panjang_estimasi: '68 cm' },
        { size: 'L',   chest_min: 96,  chest_max: 100, desc: 'SMA Large',       panjang_estimasi: '70 cm' },
        { size: 'XL',  chest_min: 100, chest_max: 104, desc: 'SMA Extra Large', panjang_estimasi: '72 cm' },
        { size: 'XXL', chest_min: 104, chest_max: 112, desc: 'SMA Double XL',   panjang_estimasi: '74 cm' },
      ],
    },
    dewasa: {
      label: 'Dewasa (18+ tahun)',
      chart: [
        { size: 'S',   chest_min: 0,   chest_max: 96,  desc: 'Small',       panjang_estimasi: '66 cm' },
        { size: 'M',   chest_min: 96,  chest_max: 104, desc: 'Medium',      panjang_estimasi: '70 cm' },
        { size: 'L',   chest_min: 104, chest_max: 112, desc: 'Large',       panjang_estimasi: '72 cm' },
        { size: 'XL',  chest_min: 112, chest_max: 120, desc: 'Extra Large', panjang_estimasi: '74 cm' },
        { size: 'XXL', chest_min: 120, chest_max: 200, desc: 'Double XL',   panjang_estimasi: '76 cm' },
      ],
    },
  };

  // Default jenjang. Disinkronkan dengan tombol .jenjang-opt.active di HTML.
  let currentJenjang = localStorage.getItem('tryon_jenjang') || 'sma';

  // Asumsi rata-rata lebar wajah orang dewasa = 14.5cm (eye-to-eye distance ≈ 6.3cm)
  // Pakai distance antara ear → wajah width estimasi
  const AVG_FACE_WIDTH_CM = 14.5;
  const SHOULDER_TO_CHEST_RATIO = 2.4;  // chest circumference ≈ shoulder_breadth × 2.4

  // MediaPipe landmark indices
  const LM = {
    NOSE: 0,
    LEFT_EYE: 2, RIGHT_EYE: 5,
    LEFT_EAR: 7, RIGHT_EAR: 8,
    LEFT_SHOULDER: 11, RIGHT_SHOULDER: 12,
    LEFT_ELBOW: 13, RIGHT_ELBOW: 14,
    LEFT_HIP: 23, RIGHT_HIP: 24,
  };

  // Titik-titik scanner (yang dianimasikan)
  const SCAN_POINTS = [
    { key: 'LEFT_SHOULDER',  label: 'Bahu Kiri' },
    { key: 'RIGHT_SHOULDER', label: 'Bahu Kanan'},
    { key: 'LEFT_HIP',       label: 'Pinggul Kiri' },
    { key: 'RIGHT_HIP',      label: 'Pinggul Kanan'},
  ];

  // ── Helpers ─────────────────────────────────
  function setStatus(text, type) {
    statusBadge.className = 'scanner-status ' + (type || 'idle');
    statusBadge.innerHTML = '<i class="bi bi-circle-fill"></i> ' + text;
  }

  function dist(a, b, scaleX, scaleY) {
    const dx = (a.x - b.x) * scaleX;
    const dy = (a.y - b.y) * scaleY;
    return Math.sqrt(dx*dx + dy*dy);
  }

  function avgPoint(a, b) {
    return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
  }

  function pushBuffer(buf, val) {
    buf.push(val);
    if (buf.length > BUFFER_SIZE) buf.shift();
  }

  function avg(arr) {
    if (!arr.length) return 0;
    return arr.reduce((s, v) => s + v, 0) / arr.length;
  }

  function findSize(chestCm, jenjang) {
    const chart = SIZE_CHARTS[jenjang || currentJenjang].chart;
    // Return null kalau di luar range (badan terlalu kecil ATAU terlalu besar untuk jenjang ini)
    return chart.find(s => chestCm >= s.chest_min && chestCm < s.chest_max) || null;
  }
  // Get min & max chest yang reasonable untuk jenjang
  function jenjangChestRange(jenjang) {
    const chart = SIZE_CHARTS[jenjang || currentJenjang].chart;
    return { min: chart[0].chest_min, max: chart[chart.length - 1].chest_max };
  }
  function currentJenjangLabel() {
    return SIZE_CHARTS[currentJenjang].label;
  }
  function jenjangLabelByKey(key) {
    return SIZE_CHARTS[key] ? SIZE_CHARTS[key].label : key;
  }

  // Hitung index size dalam chart agar bisa bandingkan "lebih besar / lebih kecil"
  function sizeIndexInChart(sizeName, jenjang) {
    const chart = SIZE_CHARTS[jenjang || currentJenjang].chart;
    return chart.findIndex(s => s.size === sizeName);
  }

  // ── Compare badan user dengan produk yang dipilih ──
  // Return: {
  //   status: 'no_product' | 'fits' | 'fits_no_stock' | 'too_big' | 'too_small' | 'wrong_jenjang'
  //   idealSize, productSizes, message, suggestion
  // }
  function compareWithProduct(chestCm) {
    const ideal = findSize(chestCm);  // pakai currentJenjang (= jenjang produk kalau sudah dipilih)
    const range = jenjangChestRange(currentJenjang);

    // CASE: badan kamu out-of-range untuk jenjang ini (mis. dewasa pilih TK)
    if (!ideal) {
      const tooBig = chestCm >= range.max;
      return {
        status: 'out_of_jenjang',
        tooBig: tooBig,
        chestCm: chestCm,
        rangeMax: range.max,
        productSizes: selectedProduct.allSizes,
        availSizes:  selectedProduct.availSizes,
      };
    }

    if (!selectedProduct.id) {
      return { status: 'no_product', idealSize: ideal, productSizes: [] };
    }

    const allSz   = selectedProduct.allSizes;
    const availSz = selectedProduct.availSizes;
    const idealName = ideal.size;

    // Apakah jenjang user (dari currentJenjang yang skrg = jenjang produk) match dengan produk?
    // Selalu match karena kita force currentJenjang = produk.jenjang saat pilih produk.
    // Tapi kalau produk tidak punya jenjang (NULL), kita pakai jenjang dari selector.

    if (availSz.includes(idealName)) {
      return {
        status: 'fits',
        idealSize: ideal,
        productSizes: allSz,
        availSizes: availSz,
      };
    }

    if (allSz.includes(idealName)) {
      // Size ada di produk tapi stok habis
      return {
        status: 'fits_no_stock',
        idealSize: ideal,
        productSizes: allSz,
        availSizes: availSz,
      };
    }

    // Bandingkan posisi ideal vs range produk
    const idealIdx = sizeIndexInChart(idealName);
    if (idealIdx < 0) {
      return { status: 'wrong_jenjang', idealSize: ideal, productSizes: allSz, availSizes: availSz };
    }
    const productIdxs = allSz.map(s => sizeIndexInChart(s)).filter(i => i >= 0);
    if (productIdxs.length === 0) {
      return { status: 'wrong_jenjang', idealSize: ideal, productSizes: allSz, availSizes: availSz };
    }
    const minProductIdx = Math.min(...productIdxs);
    const maxProductIdx = Math.max(...productIdxs);

    if (idealIdx > maxProductIdx) {
      // Badan user lebih besar dari size terbesar produk
      return {
        status: 'too_small',  // produk terlalu kecil
        idealSize: ideal,
        productSizes: allSz,
        availSizes: availSz,
      };
    }
    if (idealIdx < minProductIdx) {
      // Badan user lebih kecil dari size terkecil produk
      return {
        status: 'too_big',  // produk terlalu besar
        idealSize: ideal,
        productSizes: allSz,
        availSizes: availSz,
      };
    }
    return { status: 'wrong_jenjang', idealSize: ideal, productSizes: allSz, availSizes: availSz };
  }

  // ── Setup MediaPipe Pose ────────────────────
  function initPose() {
    pose = new Pose({
      locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`,
    });
    pose.setOptions({
      modelComplexity: 1,
      smoothLandmarks: true,
      enableSegmentation: false,
      minDetectionConfidence: 0.6,
      minTrackingConfidence: 0.6,
    });
    pose.onResults(onPoseResults);
  }

  // ── Loop kamera ─────────────────────────────
  async function startCamera() {
    setStatus('Memuat AI model...', 'loading');
    prestart.style.display = 'none';
    loading.style.display = 'flex';

    try {
      if (!pose) initPose();
      await pose.initialize();

      stream = await navigator.mediaDevices.getUserMedia({
        video: { width: 640, height: 480, facingMode: 'user' },
        audio: false,
      });
      video.srcObject = stream;
      await video.play();

      // Set canvas size = video size
      canvas.width  = video.videoWidth;
      canvas.height = video.videoHeight;

      // Pakai MediaPipe camera utils untuk loop
      camera = new Camera(video, {
        onFrame: async () => { await pose.send({ image: video }); },
        width: 640,
        height: 480,
      });
      camera.start();

      loading.style.display = 'none';
      hud.style.display = 'block';
      btnCapture.disabled = false;
      setStatus('Scanning aktif', 'active');
    } catch (err) {
      console.error('Camera error:', err);
      loading.style.display = 'none';
      prestart.style.display = 'flex';
      setStatus('Gagal akses kamera', 'error');
      alert('Tidak bisa mengakses kamera. Pastikan kamu memberi izin kamera di browser.\n\nError: ' + err.message);
    }
  }

  function stopCamera() {
    if (camera) { camera.stop(); camera = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    video.srcObject = null;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hud.style.display = 'none';
    btnCapture.disabled = true;
    prestart.style.display = 'flex';
    setStatus('Kamera off', 'idle');
    measureBuffer = { shoulder: [], torso: [], chest: [] };
    stableFrames = 0;
  }

  // ── Pose Results ────────────────────────────
  function onPoseResults(results) {
    frameCount++;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Gambar video frame
    ctx.save();
    // Mirror horizontal supaya seperti cermin
    ctx.scale(-1, 1);
    ctx.translate(-canvas.width, 0);
    ctx.drawImage(results.image, 0, 0, canvas.width, canvas.height);
    ctx.restore();

    if (!results.poseLandmarks) {
      setStatus('Tubuh tidak terdeteksi — geser ke tengah', 'warning');
      stableFrames = 0;
      return;
    }

    lastLandmarks = results.poseLandmarks;
    const lm = lastLandmarks;
    const W = canvas.width;
    const H = canvas.height;

    // Validasi: shoulder & hip kelihatan
    const reqLm = [LM.LEFT_SHOULDER, LM.RIGHT_SHOULDER, LM.LEFT_HIP, LM.RIGHT_HIP];
    const visible = reqLm.every(i => lm[i] && lm[i].visibility > 0.6);
    if (!visible) {
      setStatus('Mundur sedikit — tubuh sebagian terpotong', 'warning');
      drawSkeletonOnly(lm, W, H);
      stableFrames = 0;
      return;
    }

    // ── Hitung dimensi ──────────────────────
    // Reference: jarak antar telinga = lebar wajah ≈ AVG_FACE_WIDTH_CM
    const leftEar  = lm[LM.LEFT_EAR];
    const rightEar = lm[LM.RIGHT_EAR];
    const faceWidthPx = dist(leftEar, rightEar, W, H);

    if (faceWidthPx < 20) {
      setStatus('Wajah tidak terdeteksi cukup baik', 'warning');
      drawSkeletonOnly(lm, W, H);
      return;
    }

    const cmPerPx = AVG_FACE_WIDTH_CM / faceWidthPx;

    const shoulderPx = dist(lm[LM.LEFT_SHOULDER], lm[LM.RIGHT_SHOULDER], W, H);
    const shoulderCm = shoulderPx * cmPerPx;

    const midShoulder = avgPoint(lm[LM.LEFT_SHOULDER], lm[LM.RIGHT_SHOULDER]);
    const midHip      = avgPoint(lm[LM.LEFT_HIP], lm[LM.RIGHT_HIP]);
    const torsoPx     = dist(midShoulder, midHip, W, H);
    const torsoCm     = torsoPx * cmPerPx;

    const chestCircCm = shoulderCm * SHOULDER_TO_CHEST_RATIO;

    pushBuffer(measureBuffer.shoulder, shoulderCm);
    pushBuffer(measureBuffer.torso,    torsoCm);
    pushBuffer(measureBuffer.chest,    chestCircCm);

    const avgShoulder = avg(measureBuffer.shoulder);
    const avgTorso    = avg(measureBuffer.torso);
    const avgChest    = avg(measureBuffer.chest);

    // ── Update HUD ──────────────────────────
    hudShoulder.textContent = avgShoulder.toFixed(1) + ' cm';
    hudTorso.textContent    = avgTorso.toFixed(1)    + ' cm';
    hudChest.textContent    = avgChest.toFixed(1)    + ' cm';

    // ── Draw scanner overlay ────────────────
    drawScannerOverlay(lm, W, H, avgChest, avgShoulder, avgTorso);

    // ── Sudah stabil (cukup data)? ──────────
    if (measureBuffer.chest.length >= BUFFER_SIZE) {
      stableFrames++;
      if (stableFrames >= 10) {
        setStatus('Scan stabil ✓ siap capture', 'active');
        updateSizeRecommendation(avgChest, avgTorso);
      } else {
        setStatus('Menstabilkan pengukuran...', 'loading');
      }
    } else {
      setStatus('Mengumpulkan data... ' + measureBuffer.chest.length + '/' + BUFFER_SIZE, 'loading');
    }
  }

  function drawSkeletonOnly(lm, W, H) {
    // Garis dasar saat data masih kurang
    ctx.strokeStyle = 'rgba(233,30,99,0.5)';
    ctx.lineWidth = 2;
    const pairs = [
      [LM.LEFT_SHOULDER, LM.RIGHT_SHOULDER],
      [LM.LEFT_SHOULDER, LM.LEFT_HIP],
      [LM.RIGHT_SHOULDER, LM.RIGHT_HIP],
      [LM.LEFT_HIP, LM.RIGHT_HIP],
    ];
    pairs.forEach(([a, b]) => {
      if (lm[a] && lm[b]) {
        ctx.beginPath();
        ctx.moveTo((1 - lm[a].x) * W, lm[a].y * H);
        ctx.lineTo((1 - lm[b].x) * W, lm[b].y * H);
        ctx.stroke();
      }
    });
  }

  function drawScannerOverlay(lm, W, H, chestCm, shoulderCm, torsoCm) {
    // Mirror x karena video di-mirror
    const px = (l) => (1 - l.x) * W;
    const py = (l) => l.y * H;

    // ── Skeleton frame (gradien neon) ──
    const skeletonGrad = ctx.createLinearGradient(0, 0, W, H);
    skeletonGrad.addColorStop(0, '#E91E63');
    skeletonGrad.addColorStop(1, '#FF80AB');
    ctx.strokeStyle = skeletonGrad;
    ctx.lineWidth = 3;
    ctx.shadowColor = '#FF4081';
    ctx.shadowBlur = 8;

    const skeletonPairs = [
      [LM.LEFT_SHOULDER, LM.RIGHT_SHOULDER],
      [LM.LEFT_SHOULDER, LM.LEFT_HIP],
      [LM.RIGHT_SHOULDER, LM.RIGHT_HIP],
      [LM.LEFT_HIP, LM.RIGHT_HIP],
      [LM.LEFT_SHOULDER, LM.LEFT_ELBOW],
      [LM.RIGHT_SHOULDER, LM.RIGHT_ELBOW],
    ];
    skeletonPairs.forEach(([a, b]) => {
      if (lm[a] && lm[b]) {
        ctx.beginPath();
        ctx.moveTo(px(lm[a]), py(lm[a]));
        ctx.lineTo(px(lm[b]), py(lm[b]));
        ctx.stroke();
      }
    });
    ctx.shadowBlur = 0;

    // ── Animated scan points ──
    const pulse = (Math.sin(frameCount * 0.15) + 1) / 2; // 0..1
    SCAN_POINTS.forEach((sp, idx) => {
      const l = lm[LM[sp.key]];
      if (!l) return;
      const x = px(l);
      const y = py(l);

      // Outer pulse ring
      const radius = 18 + pulse * 12;
      ctx.beginPath();
      ctx.arc(x, y, radius, 0, Math.PI * 2);
      ctx.strokeStyle = `rgba(233,30,99,${0.8 - pulse * 0.5})`;
      ctx.lineWidth = 2;
      ctx.stroke();

      // Inner glowing dot
      ctx.beginPath();
      ctx.arc(x, y, 7, 0, Math.PI * 2);
      ctx.fillStyle = '#FF4081';
      ctx.shadowColor = '#FF4081';
      ctx.shadowBlur = 16;
      ctx.fill();
      ctx.shadowBlur = 0;

      // Center white dot
      ctx.beginPath();
      ctx.arc(x, y, 3, 0, Math.PI * 2);
      ctx.fillStyle = '#FFFFFF';
      ctx.fill();
    });

    // ── Measurement labels ──
    // Garis bahu dengan label cm
    const ls = lm[LM.LEFT_SHOULDER];
    const rs = lm[LM.RIGHT_SHOULDER];
    if (ls && rs) {
      const midX = (px(ls) + px(rs)) / 2;
      const midY = (py(ls) + py(rs)) / 2 - 20;
      drawLabel(midX, midY, shoulderCm.toFixed(1) + ' cm', '#FF4081');
    }

    // Garis torso vertikal dengan label
    const midSh = avgPoint(lm[LM.LEFT_SHOULDER], lm[LM.RIGHT_SHOULDER]);
    const midHp = avgPoint(lm[LM.LEFT_HIP], lm[LM.RIGHT_HIP]);
    const torsoX = px(midSh) + 30;
    const torsoY = (py(midSh) + py(midHp)) / 2;
    drawLabel(torsoX + 20, torsoY, torsoCm.toFixed(1) + ' cm', '#FF4081');

    // Tampilkan estimasi size di pojok (atau '?' kalau out of range)
    const ideal = findSize(chestCm);
    const recommendedSize = ideal ? ideal.size : '?';
    drawBigLabel(W - 110, 50, recommendedSize, 'rgba(0,0,0,0.7)');
  }

  function drawLabel(x, y, text, color) {
    ctx.font = 'bold 13px Poppins, sans-serif';
    const w = ctx.measureText(text).width + 14;
    const h = 22;
    ctx.fillStyle = 'rgba(0,0,0,0.7)';
    ctx.fillRect(x - w/2, y - h/2, w, h);
    ctx.fillStyle = color || '#FFFFFF';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x, y);
  }

  function drawBigLabel(x, y, text, bg) {
    ctx.font = 'bold 28px Poppins, sans-serif';
    const w = 90;
    const h = 70;
    ctx.fillStyle = bg;
    ctx.fillRect(x, y - h/2, w, h);
    ctx.fillStyle = '#FFD740';
    ctx.font = 'bold 11px Poppins, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('SIZE', x + w/2, y - 14);
    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 30px Poppins, sans-serif';
    ctx.fillText(text, x + w/2, y + 14);
  }

  // ── Update Size Result Card ─────────────────
  let lastSizeKey = null;
  function updateSizeRecommendation(chestCm, torsoCm) {
    const cmp = compareWithProduct(chestCm);
    const idealKey = cmp.idealSize ? cmp.idealSize.size : 'na';
    const key = currentJenjang + '|' + cmp.status + '|' + idealKey + '|' + (selectedProduct.id || 'none');
    if (lastSizeKey === key) return;
    lastSizeKey = key;

    let verdictHtml = '';

    if (cmp.status === 'out_of_jenjang') {
      // Badan kamu lebih besar/kecil dari jenjang ini secara keseluruhan
      const arah = cmp.tooBig ? 'jauh lebih besar' : 'jauh lebih kecil';
      verdictHtml = `
        <div class="size-verdict verdict-wrong">
          <i class="bi bi-x-octagon-fill"></i> Tidak ada baju yang cocok
        </div>
        <div class="size-big-badge muted">—</div>
        <div class="size-label">Tidak ada ukuran sesuai</div>
        <div class="size-explanation">
          Badan kamu (lingkar dada <strong>${chestCm.toFixed(1)} cm</strong>) ${arah}
          dari range jenjang <strong>${currentJenjangLabel()}</strong>.
          <br><br>
          ${cmp.tooBig
            ? 'Coba ganti ke jenjang yang <strong>lebih tinggi</strong> (SMP / SMA / Dewasa).'
            : 'Coba ganti ke jenjang yang <strong>lebih rendah</strong> (TK / SD).'}
        </div>
        ${renderProductSizes(cmp)}
      `;
    } else if (cmp.status === 'no_product') {
      // Tidak ada produk dipilih → mode rekomendasi umum
      verdictHtml = `
        <div class="size-big-badge">${cmp.idealSize.size}</div>
        <div class="size-label">${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          Estimasi panjang baju ~ ${cmp.idealSize.panjang_estimasi}
        </div>
        <div class="size-call-to-pick">
          <i class="bi bi-arrow-right-circle"></i>
          Pilih produk di bawah untuk lihat apakah size kamu tersedia.
        </div>
      `;
    } else if (cmp.status === 'fits') {
      verdictHtml = `
        <div class="size-verdict verdict-fit">
          <i class="bi bi-check-circle-fill"></i> Cocok!
        </div>
        <div class="size-big-badge">${cmp.idealSize.size}</div>
        <div class="size-label">${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          ✓ Tersedia di produk ini, stok ready
        </div>
        ${renderProductSizes(cmp)}
      `;
    } else if (cmp.status === 'fits_no_stock') {
      verdictHtml = `
        <div class="size-verdict verdict-warning">
          <i class="bi bi-exclamation-circle-fill"></i> Cocok tapi stok habis
        </div>
        <div class="size-big-badge">${cmp.idealSize.size}</div>
        <div class="size-label">${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          Size kamu ada di produk ini tapi <strong>stok lagi habis</strong>. Coba size terdekat di bawah.
        </div>
        ${renderProductSizes(cmp)}
      `;
    } else if (cmp.status === 'too_big') {
      verdictHtml = `
        <div class="size-verdict verdict-wrong">
          <i class="bi bi-x-circle-fill"></i> Kebesaran
        </div>
        <div class="size-big-badge muted">${cmp.idealSize.size}</div>
        <div class="size-label">Ukuran ideal kamu: ${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          Baju ini <strong>kebesaran</strong> untuk badan kamu. Ukuran terkecil di produk ini = <strong>${cmp.productSizes[0]}</strong>, sedangkan kamu lebih cocok size <strong>${cmp.idealSize.size}</strong>.
        </div>
        ${renderProductSizes(cmp)}
      `;
    } else if (cmp.status === 'too_small') {
      verdictHtml = `
        <div class="size-verdict verdict-wrong">
          <i class="bi bi-x-circle-fill"></i> Kekecilan
        </div>
        <div class="size-big-badge muted">${cmp.idealSize.size}</div>
        <div class="size-label">Ukuran ideal kamu: ${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          Baju ini <strong>kekecilan</strong> untuk badan kamu. Size terbesar di produk = <strong>${cmp.productSizes[cmp.productSizes.length-1]}</strong>, tapi kamu butuh size <strong>${cmp.idealSize.size}</strong>.
        </div>
        ${renderProductSizes(cmp)}
      `;
    } else if (cmp.status === 'wrong_jenjang') {
      verdictHtml = `
        <div class="size-verdict verdict-wrong">
          <i class="bi bi-x-circle-fill"></i> Tidak ada size yang cocok
        </div>
        <div class="size-big-badge muted">${cmp.idealSize.size}</div>
        <div class="size-label">${cmp.idealSize.desc}</div>
        <div class="size-explanation">
          Tidak ada ukuran yang pas untuk badan kamu di produk ini.
          <br><br>
          Cari produk untuk <strong>${currentJenjangLabel()}</strong> di katalog.
        </div>
        ${renderProductSizes(cmp)}
      `;
    }

    sizeArea.innerHTML = `
      <div class="size-result">
        <div class="size-jenjang-pill">
          <i class="bi bi-mortarboard"></i> ${currentJenjangLabel()}
          ${selectedProduct.id ? '<small style="margin-left:6px;color:var(--gray-500);">· dari produk</small>' : ''}
        </div>
        ${verdictHtml}
        <ul class="size-detail-list">
          <li><span>Lebar Bahu</span><strong>${avg(measureBuffer.shoulder).toFixed(1)} cm</strong></li>
          <li><span>Tinggi Torso</span><strong>${torsoCm.toFixed(1)} cm</strong></li>
          <li><span>Est. Lingkar Dada</span><strong>${chestCm.toFixed(1)} cm</strong></li>
        </ul>
        <div class="size-disclaimer">
          <i class="bi bi-info-circle"></i> Estimasi otomatis ±2cm. Cek size chart produk untuk pasti.
        </div>
      </div>
    `;
  }

  function renderProductSizes(cmp) {
    if (!selectedProduct.id || !cmp.productSizes || !cmp.productSizes.length) return '';
    const idealSizeName = cmp.idealSize ? cmp.idealSize.size : null;
    const html = cmp.productSizes.map(sz => {
      const inStock = cmp.availSizes && cmp.availSizes.includes(sz);
      const isIdeal = sz === idealSizeName;
      let cls = 'sz-chip';
      if (isIdeal) cls += ' chip-ideal';
      if (!inStock) cls += ' chip-empty';
      return `<span class="${cls}">${sz}${!inStock ? ' <small>habis</small>' : ''}</span>`;
    }).join('');
    return `
      <div class="sz-chips-row">
        <small>Size tersedia di produk:</small>
        <div class="sz-chips">${html}</div>
      </div>`;
  }

  // ── Capture & Save ──────────────────────────
  btnCapture.addEventListener('click', async function () {
    if (!lastLandmarks || measureBuffer.chest.length < BUFFER_SIZE) {
      alert('Tunggu sampai scan stabil dulu (~3 detik)');
      return;
    }

    btnCapture.disabled = true;
    btnCapture.innerHTML = '<i class="bi bi-arrow-clockwise spinning me-2"></i>Menyimpan...';

    // Capture frame dari canvas (sudah ada overlay scanner)
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

    const avgChest    = avg(measureBuffer.chest);
    const avgShoulder = avg(measureBuffer.shoulder);
    const avgTorso    = avg(measureBuffer.torso);
    const sizeData    = findSize(avgChest) || { size: 'OUT', desc: 'Out of range jenjang ini' };

    const payload = new FormData();
    payload.append('image_data', dataUrl);
    payload.append('id_produk',  selectedProduct.id || 0);
    payload.append('chest_cm',   avgChest.toFixed(2));
    payload.append('shoulder_cm',avgShoulder.toFixed(2));
    payload.append('torso_cm',   avgTorso.toFixed(2));
    payload.append('size',       sizeData.size);
    payload.append('jenjang',    currentJenjang);
    payload.append('jenjang_label', currentJenjangLabel());

    try {
      const r = await fetch(BASE + 'pelanggan/tryon_process.php', {
        method: 'POST',
        body: payload,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      });
      const data = await r.json();
      if (data.success) {
        showCaptureResult(data, sizeData, avgChest, avgShoulder, avgTorso);
      } else {
        alert(data.message || 'Gagal menyimpan capture.');
      }
    } catch (err) {
      console.error('Capture error:', err);
      alert('Gagal: ' + err.message);
    } finally {
      btnCapture.disabled = false;
      btnCapture.innerHTML = '<i class="bi bi-camera-fill me-2"></i>Capture & Simpan';
    }
  });

  function showCaptureResult(data, sizeData, chest, shoulder, torso) {
    sizeArea.innerHTML = `
      <div class="size-result captured">
        <div class="captured-banner">
          <i class="bi bi-check-circle-fill"></i> Tersimpan!
        </div>
        <div class="size-jenjang-pill">
          <i class="bi bi-mortarboard"></i> ${currentJenjangLabel()}
        </div>
        <div class="size-big-badge">${sizeData.size}</div>
        <div class="size-label">${sizeData.desc}</div>
        <img src="${data.foto_hasil}" alt="Hasil capture" style="width:100%;border-radius:8px;margin:10px 0;">
        <ul class="size-detail-list">
          <li><span>Lebar Bahu</span><strong>${shoulder.toFixed(1)} cm</strong></li>
          <li><span>Tinggi Torso</span><strong>${torso.toFixed(1)} cm</strong></li>
          <li><span>Est. Lingkar Dada</span><strong>${chest.toFixed(1)} cm</strong></li>
        </ul>
        <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
          <a href="${data.foto_hasil}" download="tryon.jpg"
             class="btn-back-cart" style="flex:1;text-align:center;text-decoration:none;border:1px solid var(--gray-300);padding:8px;border-radius:8px;font-size:12px;">
            <i class="bi bi-download"></i> Download
          </a>
          ${selectedProduct.id ? `
          <a href="${BASE}produk_detail.php?id=${selectedProduct.id}"
             class="btn-pink" style="flex:1;text-align:center;text-decoration:none;padding:8px;border-radius:8px;font-size:12px;background:var(--pink-primary);color:#fff;">
            <i class="bi bi-cart-plus"></i> Lihat Produk
          </a>
          ` : ''}
        </div>
      </div>
    `;
  }

  // ── Jenjang selector ─────────────────────
  const jenjangBtns = document.querySelectorAll('.jenjang-opt');
  // Restore selection from localStorage
  jenjangBtns.forEach(b => {
    b.classList.toggle('active', b.dataset.jenjang === currentJenjang);
    b.addEventListener('click', function () {
      jenjangBtns.forEach(x => x.classList.remove('active'));
      this.classList.add('active');
      currentJenjang = this.dataset.jenjang;
      localStorage.setItem('tryon_jenjang', currentJenjang);
      lastSizeKey = null;  // paksa re-render rekomendasi
      // Kalau sedang scanning aktif, langsung update rekomendasi
      if (measureBuffer.chest.length >= BUFFER_SIZE) {
        updateSizeRecommendation(avg(measureBuffer.chest), avg(measureBuffer.torso));
      }
    });
  });

  // ── Event handlers ──────────────────────────
  btnStart.addEventListener('click', startCamera);
  btnStop .addEventListener('click', stopCamera);

  function applyProductSelection(card) {
    const jenjang = card.dataset.jenjang || '';
    selectedProduct = {
      id:         card.dataset.id,
      nama:       card.dataset.name,
      foto:       card.dataset.foto,
      jenjang:    jenjang,
      availSizes: (card.dataset.sizes     || '').split(',').filter(Boolean),
      allSizes:   (card.dataset.allSizes  || '').split(',').filter(Boolean),
    };

    // Sinkronkan jenjang ke produk (kalau produk punya jenjang)
    if (jenjang && SIZE_CHARTS[jenjang]) {
      currentJenjang = jenjang;
      localStorage.setItem('tryon_jenjang', jenjang);
      jenjangBtns.forEach(b => b.classList.toggle('active', b.dataset.jenjang === jenjang));
    }
    lastSizeKey = null;
    if (measureBuffer.chest.length >= BUFFER_SIZE) {
      updateSizeRecommendation(avg(measureBuffer.chest), avg(measureBuffer.torso));
    }
  }

  productCards.forEach(card => {
    card.addEventListener('click', function () {
      productCards.forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
      applyProductSelection(this);
    });
  });

  // Auto-pilih kalau ada produk dari URL (?id=...)
  const firstSelected = document.querySelector('.tryon-product-card.selected');
  if (firstSelected) applyProductSelection(firstSelected);

  // Cleanup saat halaman ditutup
  window.addEventListener('beforeunload', stopCamera);
})();
