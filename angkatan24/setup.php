<?php
// ============================================================
// setup.php — Script setup database SEKALI PAKAI
// Jalankan sekali di browser: http://localhost/angkatan24/setup.php
// HAPUS file ini setelah selesai!
// ============================================================

// Proteksi: hanya bisa dijalankan dari localhost
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip   = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($client_ip, $allowed_ips, true)) {
    http_response_code(403);
    die('Akses ditolak.');
}

require_once 'config.php';

$errors   = [];
$messages = [];

// ============================================================
// 1. Generate hash baru untuk password 'angkatan24'
// ============================================================
$plain_password = 'angkatan24';
$hash           = password_hash($plain_password, PASSWORD_BCRYPT, ['cost' => 12]);
$messages[]     = "✅ Hash password '<strong>{$plain_password}</strong>' berhasil dibuat.";

// ============================================================
// 2. Import SQL schema
// ============================================================
$sql_file = __DIR__ . '/database.sql';
if (!file_exists($sql_file)) {
    $errors[] = "File database.sql tidak ditemukan.";
} else {
    try {
        $pdo = getDB();
        $sql = file_get_contents($sql_file);

        // Hapus komentar & jalankan per-statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => strlen($s) > 3
        );

        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
        $messages[] = "✅ Schema database berhasil diimport.";
    } catch (PDOException $e) {
        $errors[] = "❌ Error SQL: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

// ============================================================
// 3. Update hash di tabel users dengan hash fresh
// ============================================================
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE 1");
    $stmt->execute([$hash]);
    $rows = $stmt->rowCount();
    $messages[] = "✅ Password hash diperbarui untuk {$rows} user.";
} catch (PDOException $e) {
    $errors[] = "❌ Gagal update hash: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// ============================================================
// Tampilkan hasil
// ============================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Setup Database – IF A 2024</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #0d1024; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #1a1f3c; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 40px; max-width: 620px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
    h1 { font-size: 1.5rem; color: #f5c842; margin-bottom: 8px; }
    .subtitle { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 30px; }
    .msg  { padding: 12px 16px; border-radius: 10px; margin-bottom: 12px; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); font-size: 0.9rem; }
    .err  { background: rgba(220,38,38,0.12); border: 1px solid rgba(220,38,38,0.35); color: #fca5a5; }
    .hash-box { margin-top: 24px; background: rgba(255,255,255,0.05); border: 1px dashed rgba(255,255,255,0.15); border-radius: 10px; padding: 16px; font-family: monospace; font-size: 0.8rem; color: #a5b4fc; word-break: break-all; }
    .hash-label { font-size: 0.75rem; color: rgba(255,255,255,0.4); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.1em; }
    .warning { margin-top: 28px; padding: 16px; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.35); border-radius: 10px; color: #fcd34d; font-size: 0.88rem; line-height: 1.7; }
    .warning strong { display: block; font-size: 1rem; margin-bottom: 4px; }
    .btn { display: inline-block; margin-top: 24px; padding: 12px 28px; background: linear-gradient(135deg, #4f8ef7, #7b5cfa); color: #fff; border-radius: 50px; font-weight: 700; font-size: 0.9rem; text-decoration: none; }
  </style>
</head>
<body>
  <div class="card">
    <h1>⚙️ Setup Database</h1>
    <p class="subtitle">Portal IF A 2024 – Universitas Majalengka</p>

    <?php foreach ($messages as $m): ?>
      <div class="msg"><?php echo $m; ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $e): ?>
      <div class="msg err"><?php echo $e; ?></div>
    <?php endforeach; ?>

    <?php if (empty($errors)): ?>
    <div class="hash-box">
      <div class="hash-label">Hash Password "angkatan24"</div>
      <?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <div class="warning">
      <strong>⚠️ PENTING – Hapus file ini sekarang!</strong>
      File <code>setup.php</code> adalah celah keamanan jika dibiarkan di server.
      Hapus setelah setup selesai, atau akses akan terus terbuka.
    </div>

    <a href="login.php" class="btn">🚀 Ke Halaman Login</a>
    <?php endif; ?>
  </div>
</body>
</html>
