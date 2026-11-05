<?php
// ============================================================
// index.php — Halaman Utama Portal IF A 2024
// ============================================================
session_start();

// ---- Guard: harus login ----
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$is_admin = (($_SESSION['role'] ?? '') === 'admin');

// ============================================================
// HANDLE POST (admin only)
// ============================================================
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo    = getDB();

    // ---- Helper: validasi & baca upload foto ----
    // Mengembalikan ['data'=>..., 'mime'=>...] atau null jika tidak ada file / gagal.
    // Karena foto disimpan sebagai BLOB di DB, menimpa kolom foto_data/foto_mime
    // secara otomatis menghapus foto lama — tidak ada file fisik yang perlu dihapus.
    function readUploadedPhoto(array $file, int $maxBytes = 5 * 1024 * 1024): ?array {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] > $maxBytes) return null;

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed, true)) return null;

        return ['data' => file_get_contents($file['tmp_name']), 'mime' => $mime];
    }

    // ---- Edit item galeri ----
    if ($action === 'edit_galeri') {
        $gid   = filter_input(INPUT_POST, 'gid', FILTER_VALIDATE_INT);
        $judul = htmlspecialchars(strip_tags(trim($_POST['judul'] ?? '')), ENT_QUOTES, 'UTF-8');
        $icon  = preg_replace('/[^a-z0-9\-]/', '', $_POST['icon'] ?? 'fa-camera');

        if ($gid && strlen($judul) > 0) {
            $foto = readUploadedPhoto($_FILES['foto'] ?? []);

            if ($foto !== null) {
                // Foto baru diunggah → timpa foto_data & foto_mime lama di DB
                // (foto lama otomatis hilang karena kolom BLOB di-UPDATE)
                $stmt = $pdo->prepare(
                    "UPDATE galeri SET judul=?, icon=?, foto_data=?, foto_mime=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([$judul, $icon, $foto['data'], $foto['mime'], $gid]);
            } else {
                // Tidak ada foto baru → hanya update teks, foto lama tetap
                $stmt = $pdo->prepare(
                    "UPDATE galeri SET judul=?, icon=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([$judul, $icon, $gid]);
            }
        }

    // ---- Tambah item galeri ----
    } elseif ($action === 'add_galeri') {
        $judul = htmlspecialchars(strip_tags(trim($_POST['judul'] ?? '')), ENT_QUOTES, 'UTF-8');
        $icon  = preg_replace('/[^a-z0-9\-]/', '', $_POST['icon'] ?? 'fa-camera');

        if (strlen($judul) > 0) {
            $foto = readUploadedPhoto($_FILES['foto'] ?? []);
            $stmt = $pdo->prepare(
                "INSERT INTO galeri (judul, icon, foto_data, foto_mime) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$judul, $icon, $foto['data'] ?? null, $foto['mime'] ?? null]);
        }

    // ---- Hapus item galeri ----
    } elseif ($action === 'delete_galeri') {
        $gid = filter_input(INPUT_POST, 'gid', FILTER_VALIDATE_INT);
        if ($gid) {
            $pdo->prepare("DELETE FROM galeri WHERE id=?")->execute([$gid]);
        }
    }

    header('Location: index.php#free-course');
    exit;
}

// ============================================================
// AMBIL DATA GALERI
// ============================================================
try {
    $pdo    = getDB();
    $galeri = $pdo->query(
        "SELECT id, judul, icon,
                CASE WHEN foto_data IS NOT NULL AND foto_data != '' THEN 1 ELSE 0 END AS has_foto
         FROM galeri ORDER BY urutan ASC, id ASC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('[GALERI LIST] ' . $e->getMessage());
    $galeri = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Portal resmi Teknik Informatika A Angkatan 2024 Universitas Majalengka." />
  <title>IF A 2024 – Universitas Majalengka</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
</head>
<body>

  <!-- ===== NAVBAR ===== -->
  <nav id="mainNav">
    <div class="wrapper">
      <div class="logo">
        <img src="images/Lambang_Universitas_Majalengka.png" alt="Logo UNMA" />
        <div class="logo-text">
          Teknik Informatika A
          <small>UNMA · Angkatan 2024</small>
        </div>
      </div>

      <!-- Hamburger (mobile) -->
      <button class="menu-toggle" id="menuToggle"
              aria-label="Buka/tutup menu" aria-expanded="false" aria-controls="navMenu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="menu" id="navMenu">
        <ul>
          <li><a href="#tentang-saya">Tentang IF A</a></li>
          <li><a href="mahasiswa.php">MHS IF A</a></li>
          <?php if ($is_admin): ?>
          <li>
            <span class="nav-role-badge">
              <i class="fa-solid fa-shield-halved"></i> Admin
            </span>
          </li>
          <?php endif; ?>
          <li>
            <a href="logout.php" class="btn btn-outline" style="padding:8px 18px;font-size:0.85rem;">
              <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ===== HERO ===== -->
  <header>
    <div class="hero-content">
      <div class="hero-badge">
        <i class="fa-solid fa-graduation-cap"></i> Angkatan 2024
      </div>
      <h1 class="hero-title">
        Teknik<br/><span>Informatika A</span>
      </h1>
      <p class="hero-sub">
        Universitas Majalengka · Fakultas Teknik<br/>
        We Don't Just Use Technology — <em>We Build It.</em>
      </p>
      <div class="hero-actions">
        <a href="#tentang-saya" class="btn btn-gold">
          <i class="fa-solid fa-circle-info"></i> Tentang Kami
        </a>
        <a href="mahasiswa.php" class="btn btn-outline">
          <i class="fa-solid fa-users"></i> Lihat Mahasiswa
        </a>
      </div>
    </div>
    <div class="scroll-down">
      <i class="fa-solid fa-chevron-down"></i>
      <span>SCROLL</span>
    </div>
  </header>

  <!-- ===== STATISTIK ===== -->
  <section id="statistic">
    <div class="wrapper">
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
          <div class="stat-number">S1</div>
          <div class="stat-label">Jenjang Studi</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
          <div class="stat-number">26</div>
          <div class="stat-label">Jumlah Mahasiswa</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fa-brands fa-instagram"></i></div>
          <div class="stat-number">34</div>
          <div class="stat-label">Followers IG</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
          <div class="stat-number">2024</div>
          <div class="stat-label">Tahun Masuk</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== TENTANG ===== -->
  <section id="tentang-saya">
    <div class="wrapper">
      <div class="text-center">
        <span class="section-label"><i class="fa-solid fa-star"></i>&nbsp; Tentang Kami</span>
        <h2 class="section-title">Teknik <span>Informatika A</span></h2>
        <p class="section-sub">Dibentuk oleh logika, disiplin, dan semangat inovasi untuk masa depan digital Indonesia.</p>
      </div>
      <div class="about-grid">
        <div class="about-img-wrap">
          <img src="images/logo angkatan.png" alt="Logo Teknik UNMA" />
        </div>
        <div>
          <p class="about-desc">
            <span class="about-highlight">Informatika A – Universitas Majalengka</span> adalah kumpulan mahasiswa yang
            dibentuk oleh logika, disiplin, dan semangat inovasi. Melalui teknologi, kami belajar tidak hanya
            memecahkan masalah, tetapi juga menciptakan solusi yang berdampak bagi masyarakat.
          </p>
          <p class="about-desc">
            Kami berkomitmen untuk terus berkembang, berintegritas, dan siap menghadapi tantangan era digital.
            Bersama teknik informatika, kita ciptakan kemajuan bangsa Indonesia.
          </p>
          <div class="social-links">
            <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== ALBUM MEMORY ===== -->
  <section id="free-course">
    <div class="wrapper">
      <div class="text-center">
        <span class="section-label"><i class="fa-solid fa-images"></i>&nbsp; Galeri</span>
        <h2 class="section-title">Album <span>Memory</span></h2>
        <p class="section-sub">Kenangan bersama anak Informatika A 2024.</p>
      </div>

      <?php if ($is_admin): ?>
      <div class="admin-toolbar">
        <span class="admin-label"><i class="fa-solid fa-shield-halved"></i> Mode Admin</span>
        <button class="btn-admin-add" onclick="openAddGaleri()">
          <i class="fa-solid fa-plus"></i> Tambah Foto
        </button>
      </div>
      <?php endif; ?>

      <div class="gallery-grid">
        <?php foreach ($galeri as $g):
          $gid     = (int)$g['id'];
          $judul   = htmlspecialchars($g['judul'], ENT_QUOTES, 'UTF-8');
          $icon    = htmlspecialchars($g['icon'],  ENT_QUOTES, 'UTF-8');
          $img_src = $g['has_foto'] ? "galeri.php?id={$gid}" : "images/default-gallery.svg";
        ?>
        <div class="gallery-card">
          <img src="<?php echo $img_src; ?>" class="gallery-card-img" alt="<?php echo $judul; ?>" loading="lazy" />
          <div class="gallery-card-body">
            <span><i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $judul; ?></span>
            <div class="gallery-card-actions">
              <a href="<?php echo $img_src; ?>" class="btn btn-gold"
                 style="padding:8px 18px;font-size:0.8rem;" target="_blank">Lihat</a>
              <?php if ($is_admin): ?>
              <button class="btn-admin-edit"
                onclick="openEditGaleri(<?php echo $gid; ?>, '<?php echo $judul; ?>', '<?php echo $icon; ?>')">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn-admin-del" onclick="deleteGaleri(<?php echo $gid; ?>)">
                <i class="fa-solid fa-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($galeri)): ?>
        <p style="color:rgba(255,255,255,0.4);text-align:center;grid-column:1/-1;padding:40px 0;">
          Belum ada foto. <?php echo $is_admin ? 'Klik "Tambah Foto" untuk memulai.' : ''; ?>
        </p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== FOOTER ===== -->
  <footer>
    <div class="wrapper">
      <div class="footer-inner">
        <div class="footer-logo">
          <img src="images/Lambang_Universitas_Majalengka.png" alt="UNMA" />
        </div>
        <p class="footer-text">&copy; 2025 <span>Teknik Informatika A</span> – Universitas Majalengka. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- ===== MODALS (admin only) ===== -->
  <?php if ($is_admin): ?>

  <!-- Modal: Edit Galeri -->
  <div id="modalEditGaleri" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="editGaleriTitle">
    <div class="modal-box">
      <h3 class="modal-title" id="editGaleriTitle">
        <i class="fa-solid fa-image" style="color:var(--accent);"></i> Edit Foto Galeri
      </h3>
      <p class="modal-sub">Ubah judul, ikon, atau ganti foto. Foto lama otomatis terganti.</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_galeri" />
        <input type="hidden" name="gid"    id="editGaleriId" />
        <div class="mfgroup">
          <label for="editGaleriJudul">Judul / Caption</label>
          <input type="text" name="judul" id="editGaleriJudul" maxlength="150" required />
        </div>
        <div class="mfgroup">
          <label for="editGaleriIcon">Icon (Font Awesome class)</label>
          <input type="text" name="icon" id="editGaleriIcon" maxlength="30" placeholder="fa-camera" />
          <small>Contoh: fa-camera, fa-user, fa-image</small>
        </div>
        <div class="mfgroup">
          <label for="editGaleriFoto">Ganti Foto (Opsional)</label>
          <input type="file" name="foto" id="editGaleriFoto" accept="image/jpeg,image/png,image/gif,image/webp" />
          <small>
            <i class="fa-solid fa-database" style="color:var(--accent);"></i>
            Foto lama <strong>otomatis terhapus</strong> dan diganti. Maks 5 MB.
          </small>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModalGaleri()">Batal</button>
          <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Tambah Galeri -->
  <div id="modalAddGaleri" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="addGaleriTitle">
    <div class="modal-box">
      <h3 class="modal-title" id="addGaleriTitle">
        <i class="fa-solid fa-plus-circle" style="color:var(--accent);"></i> Tambah Foto Galeri
      </h3>
      <p class="modal-sub">Upload foto baru ke Album Memory.</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_galeri" />
        <div class="mfgroup">
          <label for="addGaleriJudul">Judul / Caption</label>
          <input type="text" name="judul" id="addGaleriJudul" maxlength="150" required />
        </div>
        <div class="mfgroup">
          <label for="addGaleriIcon">Icon (Font Awesome class)</label>
          <input type="text" name="icon" id="addGaleriIcon" maxlength="30" placeholder="fa-camera" value="fa-camera" />
        </div>
        <div class="mfgroup">
          <label for="addGaleriFoto">Foto</label>
          <input type="file" name="foto" id="addGaleriFoto" accept="image/jpeg,image/png,image/gif,image/webp" />
          <small>
            <i class="fa-solid fa-database" style="color:var(--accent);"></i>
            Disimpan di database. Maks 5 MB.
          </small>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeAddGaleri()">Batal</button>
          <button type="submit" class="btn-save"><i class="fa-solid fa-plus"></i> Tambah</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Form hapus (hidden, di-submit via JS) -->
  <form id="formDeleteGaleri" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_galeri" />
    <input type="hidden" name="gid"    id="deleteGaleriId" />
  </form>

  <?php endif; ?>

  <!-- ===== SCRIPT ===== -->
  <script>
    // ---- Navbar: scroll effect ----
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
      nav.style.background = window.scrollY > 50
        ? 'rgba(13,16,36,0.98)'
        : 'rgba(13,16,36,0.85)';
    });

    // ---- Navbar: hamburger toggle ----
    const toggle = document.getElementById('menuToggle');
    const menu   = document.getElementById('navMenu');

    toggle.addEventListener('click', () => {
      const isOpen = menu.classList.toggle('open');
      toggle.classList.toggle('open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen);
    });

    // Tutup menu saat link diklik (navigasi smooth)
    document.querySelectorAll('#navMenu a').forEach(a => {
      a.addEventListener('click', () => {
        menu.classList.remove('open');
        toggle.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });

    <?php if ($is_admin): ?>
    // ============================================================
    // MODAL HELPERS (admin only)
    // ============================================================

    /** Buka modal edit galeri dan isi field */
    function openEditGaleri(id, judul, icon) {
      document.getElementById('editGaleriId').value    = id;
      document.getElementById('editGaleriJudul').value = judul;
      document.getElementById('editGaleriIcon').value  = icon;
      document.getElementById('editGaleriFoto').value  = '';   // reset input file
      showModal('modalEditGaleri');
    }
    function closeModalGaleri() { hideModal('modalEditGaleri'); }

    /** Buka modal tambah galeri */
    function openAddGaleri() {
      document.getElementById('addGaleriJudul').value = '';
      document.getElementById('addGaleriFoto').value  = '';
      showModal('modalAddGaleri');
    }
    function closeAddGaleri() { hideModal('modalAddGaleri'); }

    /** Konfirmasi & submit hapus galeri */
    function deleteGaleri(id) {
      if (!confirm('Hapus foto ini dari galeri?')) return;
      document.getElementById('deleteGaleriId').value = id;
      document.getElementById('formDeleteGaleri').submit();
    }

    // ---- Utilitas modal ----
    function showModal(id) {
      document.getElementById(id).style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
    function hideModal(id) {
      document.getElementById(id).style.display = 'none';
      document.body.style.overflow = '';
    }

    // Tutup modal saat klik backdrop
    ['modalEditGaleri', 'modalAddGaleri'].forEach(mid => {
      const el = document.getElementById(mid);
      if (!el) return;
      el.addEventListener('click', e => { if (e.target === el) hideModal(mid); });
    });

    // Tutup modal saat tekan Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        ['modalEditGaleri', 'modalAddGaleri'].forEach(hideModal);
        document.body.style.overflow = '';
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>
