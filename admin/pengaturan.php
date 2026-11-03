<?php
/**
 * Modul Pengaturan Sistem - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi 100% sesuai Figma Revise iTales Pengaturan Design
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Admin
require_role('admin');

$user = current_user();

// Inisial avatar
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'AS';
}

$pageTitle = 'Pengaturan - iTales';
$activeMenu = 'pengaturan';

$pdo = getDbConnection();
$errorMessage = '';

// Handle Simpan Identitas Aplikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $namaAplikasi     = trim($_POST['nama_aplikasi'] ?? 'iTales');
        $namaSekolah      = trim($_POST['nama_sekolah'] ?? 'SD Siber Labschool');
        $namaLengkapSistem= trim($_POST['nama_lengkap_sistem'] ?? '');
        $tagline          = trim($_POST['tagline'] ?? '');

        $settings = [
            'nama_aplikasi'       => $namaAplikasi,
            'nama_sekolah'        => $namaSekolah,
            'nama_lengkap_sistem' => $namaLengkapSistem,
            'tagline'             => $tagline
        ];

        $stmt = $pdo->prepare("
            INSERT INTO pengaturan (kunci, nilai)
            VALUES (:k, :v)
            ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
        ");

        foreach ($settings as $k => $v) {
            $stmt->execute([':k' => $k, ':v' => $v]);
        }

        set_flash_message('success', 'Identitas aplikasi berhasil diperbarui.');
        header('Location: ' . base_url('admin/pengaturan.php'));
        exit;
    }
}

// Handle Ubah Password Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $passLama   = $_POST['password_lama'] ?? '';
        $passBaru   = $_POST['password_baru'] ?? '';
        $passKonf   = $_POST['password_konfirmasi'] ?? '';

        if (empty($passLama) || empty($passBaru) || empty($passKonf)) {
            $errorMessage = 'Seluruh field kata sandi wajib diisi.';
        } elseif ($passBaru !== $passKonf) {
            $errorMessage = 'Konfirmasi kata sandi baru tidak cocok.';
        } elseif (strlen($passBaru) < 6) {
            $errorMessage = 'Kata sandi baru minimal 6 karakter.';
        } else {
            // Verifikasi password lama
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($passLama, $hash)) {
                $errorMessage = 'Kata sandi saat ini tidak tepat.';
            } else {
                $newHash = password_hash($passBaru, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                $updateStmt->execute([':p' => $newHash, ':id' => $user['id']]);

                set_flash_message('success', 'Kata sandi admin berhasil diperbarui.');
                header('Location: ' . base_url('admin/pengaturan.php'));
                exit;
            }
        }
    }
}

// Ambil data pengaturan tersimpan
$settingsRows = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR);
$namaAplikasiValue       = $settingsRows['nama_aplikasi'] ?? 'iTales';
$namaSekolahValue        = $settingsRows['nama_sekolah'] ?? 'SD Siber Labschool';
$namaLengkapSistemValue  = $settingsRows['nama_lengkap_sistem'] ?? 'iTales — Sistem Informasi Logbook Harian SD Siber Labschool';
$taglineValue            = $settingsRows['tagline'] ?? 'Every Day Has a Story.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Pengaturan Identitas Sistem iTales">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
    <style>
        .modal-overlay.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
        }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">
    <!-- Sidebar Navigasi -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Konten Utama -->
    <main class="main-content-wrapper">
        <!-- Topbar Header -->
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Pengaturan</h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <?= tanggal_indonesia() ?>
                </div>

                <button type="button" class="topbar-icon-btn" aria-label="Notifikasi">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <span class="notification-badge-dot"></span>
                </button>

                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?>">
                    <?= e($initials) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Subtitle Halaman Sesuai Desain Figma -->
            <div style="margin-bottom: 2rem;">
                <p style="color: #8C7F75; font-size: 0.95rem; margin-top: -0.25rem;">Kelola identitas dasar aplikasi dan sekolah.</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div><?= e($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <?php 
            $flashMessages = get_flash_messages();
            foreach ($flashMessages as $type => $messages): 
                foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= e($type) ?>" role="alert">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="8"></line>
                        </svg>
                        <div><?= e($msg) ?></div>
                    </div>
            <?php 
                endforeach;
            endforeach; 
            ?>

            <!-- Layout 2 Kolom Sesuai Desain Figma -->
            <div class="settings-grid">
                <!-- Kolom Kiri: Form Identitas Aplikasi -->
                <div class="settings-card">
                    <h3 class="settings-card-title">Identitas Aplikasi</h3>
                    <p class="settings-card-subtitle">Informasi yang tampil pada sistem</p>

                    <form method="POST" action="<?= base_url('admin/pengaturan.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="save_settings">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <div>
                                <label class="menu-field-label">Nama aplikasi</label>
                                <input 
                                    type="text" 
                                    name="nama_aplikasi" 
                                    class="settings-input" 
                                    value="<?= e($namaAplikasiValue) ?>" 
                                    required
                                >
                            </div>
                            <div>
                                <label class="menu-field-label">Nama sekolah</label>
                                <input 
                                    type="text" 
                                    name="nama_sekolah" 
                                    class="settings-input" 
                                    value="<?= e($namaSekolahValue) ?>" 
                                    required
                                >
                            </div>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label class="menu-field-label">Nama lengkap sistem</label>
                            <input 
                                type="text" 
                                name="nama_lengkap_sistem" 
                                class="settings-input" 
                                value="<?= e($namaLengkapSistemValue) ?>" 
                                required
                            >
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <label class="menu-field-label">Tagline</label>
                            <input 
                                type="text" 
                                name="tagline" 
                                class="settings-input" 
                                value="<?= e($taglineValue) ?>"
                            >
                        </div>

                        <div style="border-top: 1.5px solid #F0EAE1; padding-top: 1.5rem; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 12px; padding: 12px 28px; font-weight: 700; font-size: 0.95rem; box-shadow: 0 4px 14px rgba(128, 85, 56, 0.25);">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Kolom Kanan: Informasi Akun Admin -->
                <div class="settings-card">
                    <h3 class="settings-card-title">Akun Admin</h3>
                    <p class="settings-card-subtitle">Informasi akun pengelola</p>

                    <div class="admin-profile-box">
                        <div class="admin-avatar-box">
                            <?= e($initials) ?>
                        </div>
                        <div>
                            <div class="admin-info-name"><?= e($user['nama_lengkap']) ?></div>
                            <div class="admin-info-email"><?= e($user['email'] ?? 'admin@sd-siber.sch.id') ?></div>
                            <span class="badge-status badge-status-dipublikasikan" style="font-size: 0.76rem; padding: 4px 10px;">
                                Aktif
                            </span>
                        </div>
                    </div>

                    <button type="button" class="btn-change-password" onclick="openPasswordModal()">
                        Ubah Password
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Ubah Password Admin -->
<div id="passwordModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalPassTitle">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalPassTitle">Ubah Kata Sandi Admin</h3>
                <p style="font-size: 0.85rem; color: #8C7F75; margin-top: 2px;">Pastikan kata sandi baru kuat dan aman.</p>
            </div>
            <button type="button" class="modal-close-btn" onclick="closePasswordModal()">&times;</button>
        </div>

        <form method="POST" action="<?= base_url('admin/pengaturan.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="change_password">

            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="passLama" style="font-weight: 700; color: #4A382D;">Kata Sandi Saat Ini <span style="color:#C0392B;">*</span></label>
                    <input type="password" name="password_lama" id="passLama" class="form-control" placeholder="Masukkan kata sandi saat ini" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="passBaru" style="font-weight: 700; color: #4A382D;">Kata Sandi Baru <span style="color:#C0392B;">*</span></label>
                    <input type="password" name="password_baru" id="passBaru" class="form-control" placeholder="Minimal 6 karakter" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div class="form-group">
                    <label class="form-label" for="passKonf" style="font-weight: 700; color: #4A382D;">Ulangi Kata Sandi Baru <span style="color:#C0392B;">*</span></label>
                    <input type="password" name="password_konfirmasi" id="passKonf" class="form-control" placeholder="Ketik ulang kata sandi baru" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 10px; padding: 10px 22px; font-weight: 700;">Simpan Kata Sandi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal() {
    var modal = document.getElementById('passwordModal');
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
}

function closePasswordModal() {
    var modal = document.getElementById('passwordModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

window.addEventListener('click', function(event) {
    var modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        closePasswordModal();
    }
});
</script>

</body>
</html>
