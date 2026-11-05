<?php
session_start();
if (isset($_SESSION['npm'])) { header('Location: index.php'); exit; }
$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars(urldecode($_GET['error']), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Login – IF A Angkatan 2024 | UNMA</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
</head>
<body class="login-page">
  <div class="login-wrap">

    <!-- ===== PANEL KIRI ===== -->
    <div class="login-panel-left">
      <div class="login-logo">
        <img src="images/Lambang_Universitas_Majalengka.png" alt="Logo UNMA" />
        <div class="login-logo-text">
          <strong>ANGKATAN24</strong>
          <span>Universitas Majalengka</span>
        </div>
      </div>
      <h2 class="left-title">Portal<br/><span>IF A 2024</span></h2>
      <p class="left-sub">Selamat datang di portal resmi mahasiswa Teknik Informatika A Angkatan 2024 Universitas Majalengka.</p>
      <div class="left-stats">
        <div class="left-stat"><strong>26</strong><small>Mahasiswa</small></div>
        <div class="left-stat"><strong>S1</strong><small>Jenjang</small></div>
        <div class="left-stat"><strong>2024</strong><small>Angkatan</small></div>
      </div>
    </div>

    <!-- ===== PANEL KANAN (FORM) ===== -->
    <div class="login-panel-right">
      <h2 class="login-heading">Masuk ke Portal</h2>
      <p class="login-hint">Gunakan NPM &amp; password angkatan Anda</p>

      <?php if ($error): ?>
        <div class="error-box">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form action="auth.php" method="post" autocomplete="off">
        <div class="fgroup">
          <label for="npm">Nomor Pokok Mahasiswa (NPM)</label>
          <div class="input-wrap">
            <i class="fa-solid fa-id-card input-icon"></i>
            <input type="text" name="npm" id="npm"
                   placeholder="Contoh: 2414202030"
                   maxlength="20" pattern="[0-9]+"
                   title="NPM hanya angka" required
                   autocomplete="off" />
          </div>
        </div>
        <div class="fgroup">
          <label for="password">Password</label>
          <div class="input-wrap" style="position:relative;">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" name="password" id="password"
                   placeholder="Kata sandi angkatan"
                   maxlength="50" required
                   autocomplete="new-password"
                   style="padding-right:44px;" />
            <button type="button" id="togglePwd"
                    onclick="togglePassword()"
                    aria-label="Tampilkan/sembunyikan password"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                           background:none;border:none;cursor:pointer;
                           color:rgba(255,255,255,0.45);font-size:1rem;padding:4px;
                           transition:color .2s;">
              <i class="fa-solid fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>
        <button class="btn-login" type="submit">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>&nbsp; Masuk Sekarang
        </button>
      </form>

      <div class="login-demo">
        <i class="fa-solid fa-circle-info" style="color:#f5c842;"></i>&nbsp;
        Demo — Website: <strong></strong> &nbsp;|&nbsp;  <strong>ANGKATAN24</strong>
      </div>
    </div>

  </div>

  <script>
    // ---- Toggle show/hide password ----
    function togglePassword() {
      const input = document.getElementById('password');
      const icon  = document.getElementById('toggleIcon');
      const btn   = document.getElementById('togglePwd');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        btn.style.color = 'rgba(255,255,255,0.8)';
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        btn.style.color = 'rgba(255,255,255,0.45)';
      }
    }

    // ---- Cegah browser auto-fill via JS ----
    window.addEventListener('load', () => {
      document.getElementById('npm').value      = '';
      document.getElementById('password').value = '';
    });
  </script>
</body>
</html