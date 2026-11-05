<?php
// ============================================================
// foto.php — Serve foto mahasiswa langsung dari database
// URL: foto.php?id=5
// ============================================================

session_start();

// Proteksi: harus login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Kembalikan default avatar jika tidak login
    header('Location: images/default-profile.svg');
    exit;
}

require_once 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    serveDefault();
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT foto_data, foto_mime FROM mahasiswa WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[FOTO] ' . $e->getMessage());
    serveDefault();
    exit;
}

// Jika tidak ada data BLOB, kembalikan default
if (!$row || empty($row['foto_data']) || empty($row['foto_mime'])) {
    serveDefault();
    exit;
}

// Validasi MIME agar hanya gambar yang bisa dikeluarkan
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mime = $row['foto_mime'];
if (!in_array($mime, $allowed_mime, true)) {
    serveDefault();
    exit;
}

// Cache headers (gambar jarang berubah)
$etag = md5($id . strlen($row['foto_data']));
header('Content-Type: '    . $mime);
header('Content-Length: '  . strlen($row['foto_data']));
header('Cache-Control: private, max-age=86400');
header('ETag: "' . $etag . '"');

// 304 Not Modified jika ETag cocok
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

echo $row['foto_data'];
exit;

// ---- Helper: serve default avatar ----
function serveDefault(): void {
    $path = __DIR__ . '/images/default-profile.svg';
    if (file_exists($path)) {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=3600');
        readfile($path);
    } else {
        http_response_code(404);
    }
}
