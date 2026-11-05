<?php
// ============================================================
// galeri.php — Serve foto galeri dari database
// URL: galeri.php?id=1
// ============================================================

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit;
}

require_once 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) { serveDefault(); exit; }

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT foto_data, foto_mime FROM galeri WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[GALERI] ' . $e->getMessage());
    serveDefault();
    exit;
}

if (!$row || empty($row['foto_data']) || empty($row['foto_mime'])) {
    serveDefault();
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($row['foto_mime'], $allowed, true)) { serveDefault(); exit; }

$etag = md5('g' . $id . strlen($row['foto_data']));
header('Content-Type: '   . $row['foto_mime']);
header('Content-Length: ' . strlen($row['foto_data']));
header('Cache-Control: private, max-age=86400');
header('ETag: "' . $etag . '"');

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === '"' . $etag . '"') {
    http_response_code(304); exit;
}

echo $row['foto_data'];
exit;

function serveDefault(): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 250">
      <rect width="400" height="250" fill="#1a1f3c"/>
      <text x="50%" y="50%" font-family="sans-serif" font-size="14" fill="rgba(255,255,255,0.3)"
        text-anchor="middle" dominant-baseline="middle">No Image</text>
    </svg>';
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=300');
    echo $svg;
}
