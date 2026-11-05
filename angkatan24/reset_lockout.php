<?php
// ============================================================
// reset_lockout.php — ALL-IN-ONE FIX
// 1. Reset session lockout
// 2. Update hash password di database
// 3. Verifikasi koneksi & hash
// HAPUS FILE INI setelah selesai digunakan!
// ============================================================
session_start();

// ---- 1. Reset session lockout ----
$_SESSION['login_attempts'] = 0;
$_SESSION['lockout_until']  = 0;
session_write_close();

// ---- 2. Generate hash baru & update DB ----
$password_plain = 'angkatan24';
$hash_baru      = password_hash($password_plain, PASSWORD_BCRYPT, ['cost' => 10]);

$db_ok     = false;
$db_error  = '';
$rows_updated = 0;
$hash_verify  = false;

try {
    require_once __DIR__ . '/config.php';
    $pdo = getDB();

    // Update semua user dengan hash baru
    $stmt = $pdo->prepare(
        "UPDATE `users`
         SET `password`  = ?,
             `is_active` = 1
         WHERE `npm` IN ('2414202050','2414101050','2414101076','2414101006')"
    );
    $stmt->execute([$hash_baru]);
    $rows_updated = $stmt->rowCount();

    // Hapus login_logs agar brute-force terhapus
    $pdo->exec("DELETE FROM `login_logs`");

    // Verifikasi: ambil hash dari DB dan cek
    $row = $pdo->query("SELECT password FROM users WHERE npm='2414202050' LIMIT 1")->fetch();
    if ($row) {
        $hash_verify = password_verify($password_plain, $row['password']);
    }

    $db_ok = true;
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Fix Login – IF A 2024</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,sans-serif;background:#0d1024;color:#fff;
         min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
          border-radius:16px;padding:36px 42px;max-width:460px;width:100%}
    h1{font-size:1.3rem;margin-bottom:6px}
    .sub{color:rgba(255,255,255,.4);font-size:.85rem;margin-bottom:24px}
    .row{display:flex;align-items:center;gap:12px;padding:10px 0;
         border-bottom:1px solid rgba(255,255,255,.07)}
    .row:last-of-type{border:none}
    .icon{width:28px;text-align:center;font-size:1.1rem}
    .ok  {color:#4ade80}
    .fail{color:#f87171}
    .label{font-size:.9rem;flex:1}
    .val{font-size:.8rem;color:rgba(255,255,255,.4);font-family:monospace}
    .err-box{margin-top:16px;padding:12px 16px;background:rgba(248,113,113,.1);
             border:1px solid #f87171;border-radius:8px;font-size:.82rem;color:#f87171;
             word-break:break-all}
    .btn{display:block;margin-top:24px;padding:12px;border-radius:10px;
         background:#d4af37;color:#000;text-align:center;text-decoration:none;
         font-weight:700;font-size:.95rem}
    .btn:hover{background:#f0cc50}
    .warn{margin-top:14px;font-size:.75rem;color:#f87171;text-align:center}
    .info{margin-top:18px;background:rgba(255,255,255,.04);border-radius:8px;
          padding:12px 16px;font-size:.82rem;line-height:1.7}
    .info code{background:rgba(255,255,255,.1);border-radius:4px;padding:1px 5px;
               font-family:monospace;color:#fbbf24}
  </style>
</head>
<body>
<div class="card">
  <h1>🔧 Fix Login Portal IF A 2024</h1>
  <p class="sub">Memperbaiki hash password &amp; session lockout secara otomatis</p>

  <div class="row">
    <span class="icon <?= $db_ok ? 'ok' : 'fail' ?>">
      <?= $db_ok ? '✓' : '✗' ?>
    </span>
    <span class="label">Koneksi Database</span>
    <span class="val"><?= $db_ok ? 'OK' : 'GAGAL' ?></span>
  </div>

  <div class="row">
    <span class="icon ok">✓</span>
    <span class="label">Session Lockout Reset</span>
    <span class="val">Cleared</span>
  </div>

  <div class="row">
    <span class="icon <?= $rows_updated > 0 ? 'ok' : 'fail' ?>">
      <?= $rows_updated > 0 ? '✓' : '✗' ?>
    </span>
    <span class="label">Password Diupdate</span>
    <span class="val"><?= $rows_updated ?> baris</span>
  </div>

  <div class="row">
    <span class="icon <?= $hash_verify ? 'ok' : 'fail' ?>">
      <?= $hash_verify ? '✓' : '✗' ?>
    </span>
    <span class="label">Verifikasi Hash</span>
    <span class="val"><?= $hash_verify ? 'password_verify() PASS' : 'GAGAL' ?></span>
  </div>

  <?php if ($db_error): ?>
  <div class="err-box">❌ DB Error: <?= htmlspecialchars($db_error) ?></div>
  <?php endif; ?>

  <?php if ($db_ok && $hash_verify): ?>
  <div class="info">
    Silakan login dengan:<br>
    NPM Admin &nbsp;&nbsp;: <code>2414202050</code><br>
    NPM Mahasiswa: <code>2414101050</code><br>
    Password &nbsp;&nbsp;&nbsp;&nbsp;: <code>angkatan24</code>
  </div>
  <a class="btn" href="login.php">→ Klik di sini untuk Login</a>
  <?php else: ?>
  <div class="err-box" style="margin-top:12px">
    Perbaikan gagal. Pastikan MySQL sudah berjalan dan database <strong>angkatan24</strong> sudah diimport.
  </div>
  <?php endif; ?>

  <p class="warn">⚠️ Hapus file <strong>reset_lockout.php</strong> setelah berhasil login!</p>
</div>
</body>
</html>
