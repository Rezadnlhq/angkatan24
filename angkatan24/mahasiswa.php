<?php
// ============================================================
// mahasiswa.php — Dashboard Data Mahasiswa IF A 2024
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
// HANDLE POST: Edit mahasiswa (hanya admin)
// ============================================================
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id   = filter_input(INPUT_POST, 'mhs_id', FILTER_VALIDATE_INT);
    $nama = htmlspecialchars(strip_tags(trim($_POST['nama'] ?? '')), ENT_QUOTES, 'UTF-8');
    $npm  = preg_replace('/\D/', '', $_POST['npm'] ?? '');
    $bio  = htmlspecialchars(strip_tags(trim($_POST['bio']  ?? '')), ENT_QUOTES, 'UTF-8');

    if ($id && strlen($nama) > 0 && strlen($npm) >= 6) {
        try {
            $pdo = getDB();

            // ---- Proses foto upload ----
            // Foto disimpan sebagai BLOB di kolom foto_data.
            // Ketika foto baru diunggah, kolom tersebut di-UPDATE sehingga
            // foto lama OTOMATIS TERTIMPA — tidak ada file fisik yang perlu dihapus.
            $foto_data = null;
            $foto_mime = null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK
                && $_FILES['foto']['size'] <= 3 * 1024 * 1024) {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                finfo_close($finfo);

                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($mime, $allowed, true)) {
                    $foto_data = file_get_contents($_FILES['foto']['tmp_name']);
                    $foto_mime = $mime;
                }
            }

            if ($foto_data !== null) {
                // Ada foto baru → timpa foto lama di DB
                $stmt = $pdo->prepare(
                    "UPDATE mahasiswa SET nama=?, npm=?, bio=?, foto_data=?, foto_mime=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([$nama, $npm, $bio, $foto_data, $foto_mime, $id]);
            } else {
                // Tidak ada foto baru → hanya update data teks
                $stmt = $pdo->prepare(
                    "UPDATE mahasiswa SET nama=?, npm=?, bio=?, updated_at=NOW() WHERE id=?"
                );
                $stmt->execute([$nama, $npm, $bio, $id]);
            }

        } catch (PDOException $e) {
            error_log('[MHS EDIT] ' . $e->getMessage());
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ============================================================
// AMBIL DATA MAHASISWA (tanpa BLOB agar ringan)
// ============================================================
try {
    $pdo  = getDB();
    $stmt = $pdo->query(
        "SELECT id, npm, nama, bio,
                CASE WHEN foto_data IS NOT NULL AND foto_data != '' THEN 1 ELSE 0 END AS has_foto
         FROM mahasiswa ORDER BY id ASC"
    );
    $list = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('[MHS LIST] ' . $e->getMessage());
    $list = [];
}

/** Helper: kembalikan URL foto atau default avatar */
function fotoUrl(int $id, int $has_foto): string {
    return $has_foto ? 'foto.php?id=' . $id : 'images/default-profile.svg';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Data mahasiswa Teknik Informatika A Angkatan 2024 Universitas Majalengka." />
  <title>Dashboard Mahasiswa – IF A 2024 | UNMA</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
</head>
<body>

  <!-- ===== NAVBAR ===== -->
  <nav id="mainNav">
    <div class="wrapper">
      <div class="logo">
        <img src="images/Lambang_Universitas_Majalengka.png" alt="Logo UNMA" />
        <div class="logo-text">Teknik Informatika A <small>UNMA · Angkatan 2024</small></div>
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
          <li><a href="index.php">Home</a></li>
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

  <!-- ===== DASH HEADER ===== -->
  <div class="dash-header">
    <div class="wrapper">
      <div class="welcome-badge"><i class="fa-solid fa-door-open"></i> Dashboard Mahasiswa</div>
      <h1>Selamat Datang!</h1>
      <p>Halaman data mahasiswa Teknik Informatika A Angkatan 2024</p>
      <div class="npm-tag">
        <i class="fa-solid fa-id-card"></i>
        NPM: <?php echo htmlspecialchars($_SESSION['npm'], ENT_QUOTES, 'UTF-8'); ?>
        &nbsp;·&nbsp;
        <i class="fa-solid fa-shield-halved"></i>
        <?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'mahasiswa', ENT_QUOTES, 'UTF-8')); ?>
      </div>
    </div>
  </div>

  <!-- ===== KARTU MAHASISWA ===== -->
  <section class="section-dash">
    <div class="wrapper">
      <div class="text-center">
        <span class="section-label"><i class="fa-solid fa-users"></i>&nbsp; Anggota</span>
        <h2 class="section-title">Mahasiswa <span>IF A 2024</span></h2>
      </div>
      <div class="cards-grid">
        <?php foreach ($list as $mhs):
          $mhs_id    = (int)$mhs['id'];
          $foto      = fotoUrl($mhs_id, (int)$mhs['has_foto']);
          $bio       = htmlspecialchars($mhs['bio'],  ENT_QUOTES, 'UTF-8');
          $nama_safe = htmlspecialchars($mhs['nama'], ENT_QUOTES, 'UTF-8');
          $npm_safe  = htmlspecialchars($mhs['npm'],  ENT_QUOTES, 'UTF-8');
        ?>
        <div class="mhs-card">
          <img src="<?php echo $foto; ?>" class="mhs-avatar" alt="Foto <?php echo $nama_safe; ?>" />
          <div class="mhs-name"><?php echo $nama_safe; ?></div>
          <div class="mhs-npm"><?php echo $npm_safe; ?></div>
          <div class="mhs-bio">"<?php echo $bio; ?>"</div>
          <?php if ($is_admin): ?>
          <button class="btn-edit-card"
            onclick="openModal(<?php echo $mhs_id; ?>, '<?php echo $npm_safe; ?>', '<?php echo $nama_safe; ?>', '<?php echo $bio; ?>')">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== TABEL DATA LENGKAP ===== -->
  <section class="table-section">
    <div class="wrapper">
      <div class="table-card">
        <div class="table-head-bar">
          <h3><i class="fa-solid fa-table-list"></i> Data Lengkap Mahasiswa</h3>
          <span class="table-count"><?php echo count($list); ?> Mahasiswa</span>
        </div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th style="text-align:center;">No</th>
                <th>Nama Lengkap</th>
                <th>NPM</th>
                <th>Bio / Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($list as $mhs): ?>
              <tr>
                <td class="td-no"><?php echo $no++; ?></td>
                <td class="td-nama"><?php echo htmlspecialchars($mhs['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="td-npm"><?php echo htmlspecialchars($mhs['npm'],  ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="td-bio"><?php echo htmlspecialchars($mhs['bio'],  ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== FOOTER ===== -->
  <footer>
    <div class="wrapper">
      <div class="footer-inner">
        <div class="footer-logo"><img src="images/Lambang_Universitas_Majalengka.png" alt="UNMA" /></div>
        <p class="footer-text">&copy; 2025 <span>Teknik Informatika A</span> – Universitas Majalengka.</p>
      </div>
    </div>
  </footer>

  <!-- ===== MODAL EDIT (admin only) ===== -->
  <?php if ($is_admin): ?>
  <div id="editModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal-box">
      <h3 class="modal-title" id="editModalTitle">
        <i class="fa-solid fa-user-pen" style="color:var(--accent);"></i> Edit Data Mahasiswa
      </h3>
      <p class="modal-sub">Ubah informasi profil di bawah ini.</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action"  value="edit" />
        <input type="hidden" name="mhs_id"  id="editId" />
        <div class="mfgroup">
          <label for="editNama">Nama Lengkap</label>
          <input type="text" name="nama" id="editNama" maxlength="150" required />
        </div>
        <div class="mfgroup">
          <label for="editNpm">NPM</label>
          <input type="text" name="npm" id="editNpm" maxlength="20" pattern="[0-9]+" required />
        </div>
        <div class="mfgroup">
          <label for="editBio">Bio / Status</label>
          <input type="text" name="bio" id="editBio" maxlength="255" placeholder="Contoh: Hobi futsal" />
        </div>
        <div class="mfgroup">
          <label for="editFoto">Upload Foto Baru (Opsional)</label>
          <input type="file" name="foto" id="editFoto" accept="image/jpeg,image/png,image/gif,image/webp" />
          <small>
            <i class="fa-solid fa-database" style="color:var(--accent);"></i>
            Foto lama <strong>otomatis terhapus</strong> dan diganti. Format: JPG, PNG, GIF, WebP. Maks 3 MB.
          </small>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
          <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== SCRIPT ===== -->
  <script>
    // ---- Navbar: scroll effect ----
    const nav    = document.getElementById('mainNav');
    const toggle = document.getElementById('menuToggle');
    const menu   = document.getElementById('navMenu');

    window.addEventListener('scroll', () => {
      nav.style.background = window.scrollY > 30
        ? 'rgba(13,16,36,0.98)'
        : 'rgba(13,16,36,0.85)';
    });

    // ---- Hamburger toggle ----
    toggle.addEventListener('click', () => {
      const isOpen = menu.classList.toggle('open');
      toggle.classList.toggle('open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen);
    });

    document.querySelectorAll('#navMenu a').forEach(a => {
      a.addEventListener('click', () => {
        menu.classList.remove('open');
        toggle.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });

    <?php if ($is_admin): ?>
    // ============================================================
    // MODAL EDIT MAHASISWA (admin only)
    // ============================================================

    /** Buka modal dan isi field dengan data mahasiswa */
    function openModal(id, npm, nama, bio) {
      document.getElementById('editId').value   = id;
      document.getElementById('editNpm').value  = npm;
      document.getElementById('editNama').value = nama;
      document.getElementById('editBio').value  = bio;
      document.getElementById('editFoto').value = '';   // reset input file
      document.getElementById('editModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
      document.body.style.overflow = '';
    }

    // Tutup saat klik backdrop
    document.getElementById('editModal').addEventListener('click', e => {
      if (e.target === e.currentTarget) closeModal();
    });

    // Tutup saat tekan Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeModal();
    });
    <?php endif; ?>
  </script>
</body>
</html>