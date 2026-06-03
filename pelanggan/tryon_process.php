<?php
/**
 * TRYON_PROCESS.PHP — Save capture try-on dari MediaPipe scanner
 * Input: base64 image dataURL, dimensions (shoulder, torso, chest), size recommended
 * Action: simpan foto → tryon_log → return URL
 */

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/auth_check.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid.']);
    exit;
}

$user_id        = (int) $_SESSION['user_id'];
$id_produk      = (int) ($_POST['id_produk']      ?? 0);
$image_data     = (string)($_POST['image_data']    ?? '');
$chest_cm       = (float)($_POST['chest_cm']       ?? 0);
$shoulder_cm    = (float)($_POST['shoulder_cm']    ?? 0);
$torso_cm       = (float)($_POST['torso_cm']       ?? 0);
$size           = trim($_POST['size']              ?? '');
$jenjang        = trim($_POST['jenjang']           ?? '');
$jenjang_label  = trim($_POST['jenjang_label']     ?? '');

if (!$image_data || !str_starts_with($image_data, 'data:image/')) {
    echo json_encode(['success' => false, 'message' => 'Image data tidak valid.']);
    exit;
}

// Parse dataURL: "data:image/jpeg;base64,..."
if (!preg_match('/^data:image\/(jpeg|png);base64,(.+)$/', $image_data, $matches)) {
    echo json_encode(['success' => false, 'message' => 'Format image tidak didukung.']);
    exit;
}
$ext     = $matches[1] === 'png' ? 'png' : 'jpg';
$decoded = base64_decode($matches[2], true);
if ($decoded === false || strlen($decoded) < 100) {
    echo json_encode(['success' => false, 'message' => 'Gagal decode image.']);
    exit;
}

if (strlen($decoded) > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Image terlalu besar.']);
    exit;
}

// Save ke uploads/tryon/result/
$dir = UPLOAD_PATH . 'tryon/result';
if (!is_dir($dir)) @mkdir($dir, 0775, true);

$ts   = time();
$rnd  = bin2hex(random_bytes(4));
$name = "scan_{$user_id}_{$ts}_{$rnd}.{$ext}";
$path = $dir . '/' . $name;

if (!file_put_contents($path, $decoded)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file.']);
    exit;
}
$rel = 'uploads/tryon/result/' . $name;

// Validasi id_produk (opsional, boleh 0 = scan tanpa produk spesifik)
if ($id_produk) {
    $stmt = $conn->prepare("SELECT id_produk FROM produk WHERE id_produk = ?");
    $stmt->bind_param('i', $id_produk);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) $id_produk = 0;
    $stmt->close();
}

// Insert tryon_log
// foto_input dipakai untuk simpan size summary (JSON), foto_hasil = path file
$summary = json_encode([
    'size'           => $size,
    'jenjang'        => $jenjang,
    'jenjang_label'  => $jenjang_label,
    'chest_cm'       => $chest_cm,
    'shoulder_cm'    => $shoulder_cm,
    'torso_cm'       => $torso_cm,
]);
$status = 'success';

$stmt = $conn->prepare(
    "INSERT INTO tryon_log (user_id, id_produk, foto_input, foto_hasil, status)
     VALUES (?, ?, ?, ?, ?)"
);
// id_produk boleh 0; kalau schema FK strict, set NULL
if ($id_produk === 0) {
    $stmt_null = $conn->prepare(
        "INSERT INTO tryon_log (user_id, id_produk, foto_input, foto_hasil, status)
         VALUES (?, NULL, ?, ?, ?)"
    );
    $stmt_null->bind_param('isss', $user_id, $summary, $rel, $status);
    $stmt_null->execute();
    $id_log = $stmt_null->insert_id;
    $stmt_null->close();
} else {
    $stmt->bind_param('iisss', $user_id, $id_produk, $summary, $rel, $status);
    $stmt->execute();
    $id_log = $stmt->insert_id;
}
$stmt->close();

echo json_encode([
    'success'    => true,
    'message'    => 'Capture tersimpan.',
    'id_log'     => $id_log,
    'foto_hasil' => BASE_URL . $rel,
    'size'       => $size,
    'chest_cm'   => $chest_cm,
    'shoulder_cm'=> $shoulder_cm,
    'torso_cm'   => $torso_cm,
]);
