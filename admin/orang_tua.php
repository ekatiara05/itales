<?php
/**
 * Modul Data Orang Tua - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi sesuai antarmuka Figma iTales
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Admin
require_role('admin');

$user = current_user();

// Inisial avatar admin
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'AS';
}

$pageTitle = 'Data Orang Tua - iTales';
$activeMenu = 'orang_tua';

$pdo = getDbConnection();
$errorMessage = '';
$successMessage = '';

// Handle Penambahan Orang Tua Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_orang_tua') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $namaLengkap    = trim($_POST['nama_lengkap'] ?? '');
        $username       = trim($_POST['username'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $kontak         = trim($_POST['no_telepon'] ?? '');
        $siswaTerhubung = trim($_POST['siswa_terhubung'] ?? '');
        $password       = $_POST['password'] ?? '';
        $status         = $_POST['status'] ?? 'aktif';

        if (empty($namaLengkap) || empty($username) || empty($password)) {
            $errorMessage = 'Nama Orang Tua, Username, dan Kata Sandi wajib diisi.';
        } elseif (strlen($username) < 3) {
            $errorMessage = 'Username minimal 3 karakter.';
        } else {
            // Periksa duplikasi username
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
            $checkStmt->execute([':u' => $username]);
            if ($checkStmt->fetch()) {
                $errorMessage = "Username '$username' sudah terdaftar.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role, siswa_terhubung, status)
                    VALUES (:u, :p, :n, :e, :tel, 'orang_tua', :sis, :s)
                ");
                $insertStmt->execute([
                    ':u'   => $username,
                    ':p'   => $hash,
                    ':n'   => $namaLengkap,
                    ':e'   => !empty($email) ? $email : null,
                    ':tel' => !empty($kontak) ? $kontak : null,
                    ':sis' => !empty($siswaTerhubung) ? $siswaTerhubung : null,
                    ':s'   => $status === 'nonaktif' ? 'nonaktif' : 'aktif'
                ]);
                set_flash_message('success', "Akun orang tua $namaLengkap berhasil ditambahkan.");
                header("Location: " . base_url('admin/orang_tua.php'));
                exit;
            }
        }
    }
}

// Handle Update Orang Tua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_orang_tua') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $id             = (int)($_POST['user_id'] ?? 0);
        $namaLengkap    = trim($_POST['nama_lengkap'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $kontak         = trim($_POST['no_telepon'] ?? '');
        $siswaTerhubung = trim($_POST['siswa_terhubung'] ?? '');
        $password       = $_POST['password'] ?? '';
        $status         = $_POST['status'] ?? 'aktif';

        if ($id <= 0 || empty($namaLengkap)) {
            $errorMessage = 'Data orang tua tidak valid.';
        } else {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET nama_lengkap = :n, email = :e, no_telepon = :tel, siswa_terhubung = :sis, status = :s, password = :p
                    WHERE id = :id AND role = 'orang_tua'
                ");
                $updateStmt->execute([
                    ':n'   => $namaLengkap,
                    ':e'   => !empty($email) ? $email : null,
                    ':tel' => !empty($kontak) ? $kontak : null,
                    ':sis' => !empty($siswaTerhubung) ? $siswaTerhubung : null,
                    ':s'   => $status === 'nonaktif' ? 'nonaktif' : 'aktif',
                    ':p'   => $hash,
                    ':id'  => $id
                ]);
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET nama_lengkap = :n, email = :e, no_telepon = :tel, siswa_terhubung = :sis, status = :s
                    WHERE id = :id AND role = 'orang_tua'
                ");
                $updateStmt->execute([
                    ':n'   => $namaLengkap,
                    ':e'   => !empty($email) ? $email : null,
                    ':tel' => !empty($kontak) ? $kontak : null,
                    ':sis' => !empty($siswaTerhubung) ? $siswaTerhubung : null,
                    ':s'   => $status === 'nonaktif' ? 'nonaktif' : 'aktif',
                    ':id'  => $id
                ]);
            }
            set_flash_message('success', "Data orang tua $namaLengkap berhasil diperbarui.");
            header("Location: " . base_url('admin/orang_tua.php'));
            exit;
        }
    }
}

// Handle Hapus Orang Tua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_orang_tua') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $delStmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'orang_tua'");
            $delStmt->execute([':id' => $id]);
            set_flash_message('success', "Data orang tua berhasil dihapus.");
            header("Location: " . base_url('admin/orang_tua.php'));
            exit;
        }
    }
}

// Filter Pencarian & Status
$searchKeyword = trim($_GET['search'] ?? '');
$statusFilter  = trim($_GET['status'] ?? 'semua');

$sql = "SELECT id, username, nama_lengkap, email, no_telepon, role, siswa_terhubung, status FROM users WHERE role = 'orang_tua'";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (nama_lengkap LIKE :s OR username LIKE :s OR email LIKE :s OR no_telepon LIKE :s OR siswa_terhubung LIKE :s)";
    $params[':s'] = "%$searchKeyword%";
}

if (!empty($statusFilter) && $statusFilter !== 'semua') {
    $sql .= " AND status = :st";
    $params[':st'] = $statusFilter;
}

$sql .= " ORDER BY nama_lengkap ASC";
$queryStmt = $pdo->prepare($sql);
$queryStmt->execute($params);
$ortuList = $queryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Kelola Data Orang Tua Siswa SD Siber Labschool">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
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
                <h1 class="page-title">Data Orang Tua</h1>
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
            <!-- Header Halaman & Tombol Tambah Orang Tua -->
            <div class="page-header-action-wrap">
                <div>
                    <h2 class="section-main-heading">Kelola Data Orang Tua</h2>
                    <p class="section-sub-heading">Atur akun dan hubungan orang tua dengan siswa.</p>
                </div>

                <div>
                    <button type="button" class="btn-add-primary" onclick="openAddModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Orang Tua</span>
                    </button>
                </div>
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
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="8"></line>
                        </svg>
                        <div><?= e($msg) ?></div>
                    </div>
            <?php 
                endforeach;
            endforeach; 
            ?>

            <!-- Card Utama Konten Tabel -->
            <div class="data-card">
                <!-- Baris Filter dan Pencarian -->
                <form method="GET" action="<?= base_url('admin/orang_tua.php') ?>" class="filter-bar">
                    <div class="search-input-wrap">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Cari data..." 
                            value="<?= e($searchKeyword) ?>"
                        >
                    </div>

                    <div class="filter-select-wrap">
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="semua" <?= $statusFilter === 'semua' ? 'selected' : '' ?>>Semua status</option>
                            <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $statusFilter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </form>

                <!-- Tabel Data Orang Tua -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NAMA ORANG TUA</th>
                                <th>USERNAME</th>
                                <th>KONTAK</th>
                                <th>SISWA TERHUBUNG</th>
                                <th>STATUS AKUN</th>
                                <th style="text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ortuList)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
                                        Tidak ada data orang tua yang ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ortuList as $o): 
                                    // Ambil inisial orang tua
                                    $oWords = explode(' ', trim($o['nama_lengkap']));
                                    $oInit = '';
                                    foreach (array_slice($oWords, 0, 2) as $w) {
                                        $oInit .= strtoupper(substr($w, 0, 1));
                                    }
                                    if (empty($oInit)) $oInit = 'OT';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="teacher-cell">
                                                <div class="teacher-avatar" style="background-color: #F4EBE2; color: #7A5338;">
                                                    <?= e($oInit) ?>
                                                </div>
                                                <span class="teacher-name"><?= e($o['nama_lengkap']) ?></span>
                                            </div>
                                        </td>
                                        <td class="cell-muted"><?= e($o['username']) ?></td>
                                        <td class="cell-muted"><?= !empty($o['no_telepon']) ? e($o['no_telepon']) : '-' ?></td>
                                        <td>
                                            <strong><?= !empty($o['siswa_terhubung']) ? e($o['siswa_terhubung']) : '<span style="color:#999; font-weight:normal;">Belum terhubung</span>' ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($o['status'] === 'aktif'): ?>
                                                <span class="status-badge-active">
                                                    <span class="dot-active">●</span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge-inactive">
                                                    <span class="dot-inactive">●</span> Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="action-buttons-wrap">
                                                <!-- Tombol Edit -->
                                                <button 
                                                    type="button" 
                                                    class="action-btn-circle" 
                                                    title="Ubah Data Orang Tua"
                                                    onclick='openEditModal(<?= json_encode($o) ?>)'
                                                >
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Tombol Opsi Lainnya / Dropdown Menu -->
                                                <div class="dropdown-action-container">
                                                    <button 
                                                        type="button" 
                                                        class="action-btn-circle" 
                                                        title="Opsi Lainnya"
                                                        onclick="toggleDropdownMenu(event, 'dropdown-<?= $o['id'] ?>')"
                                                    >
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="1"></circle>
                                                            <circle cx="19" cy="12" r="1"></circle>
                                                            <circle cx="5" cy="12" r="1"></circle>
                                                        </svg>
                                                    </button>
                                                    <div id="dropdown-<?= $o['id'] ?>" class="dropdown-action-menu">
                                                        <form method="POST" action="<?= base_url('admin/orang_tua.php') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun orang tua ini?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                            <input type="hidden" name="action" value="delete_orang_tua">
                                                            <input type="hidden" name="user_id" value="<?= $o['id'] ?>">
                                                            <button type="submit" class="dropdown-item-danger">
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                </svg>
                                                                Hapus Orang Tua
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ==========================================================
     Modal Tambah Orang Tua
     ========================================================== -->
<div id="modalAddOrtu" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Akun Orang Tua</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/orang_tua.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="add_orang_tua">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_nama">Nama Lengkap Orang Tua *</label>
                    <input type="text" id="add_nama" name="nama_lengkap" class="form-input" placeholder="Contoh: Nadia Rahma" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_username">Username *</label>
                    <input type="text" id="add_username" name="username" class="form-input" placeholder="Contoh: nadia.r" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_telepon">No. Kontak / WhatsApp *</label>
                    <input type="text" id="add_telepon" name="no_telepon" class="form-input" placeholder="Contoh: 0812••••7821" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_siswa">Nama Siswa Terhubung *</label>
                    <input type="text" id="add_siswa" name="siswa_terhubung" class="form-input" placeholder="Contoh: Raka Pratama" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_email">Alamat Email (Opsional)</label>
                    <input type="email" id="add_email" name="email" class="form-input" placeholder="orangtua@gmail.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_password">Kata Sandi Awal *</label>
                    <input type="password" id="add_password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_status">Status Akun</label>
                    <select id="add_status" name="status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Akun Orang Tua</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================================
     Modal Ubah (Edit) Orang Tua
     ========================================================== -->
<div id="modalEditOrtu" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Ubah Data Orang Tua</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/orang_tua.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="edit_orang_tua">
            <input type="hidden" id="edit_user_id" name="user_id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_nama">Nama Lengkap Orang Tua *</label>
                    <input type="text" id="edit_nama" name="nama_lengkap" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_username">Username</label>
                    <input type="text" id="edit_username" class="form-input" disabled style="background: #f0f0f0; cursor: not-allowed;">
                    <small class="form-text">Username akun tidak dapat diubah.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_telepon">No. Kontak / WhatsApp *</label>
                    <input type="text" id="edit_telepon" name="no_telepon" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_siswa">Nama Siswa Terhubung *</label>
                    <input type="text" id="edit_siswa" name="siswa_terhubung" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_email">Alamat Email</label>
                    <input type="email" id="edit_email" name="email" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Status Akun</label>
                    <select id="edit_status" name="status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_password">Kata Sandi Baru (Opsional)</label>
                    <input type="password" id="edit_password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah sandi">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script>
function openAddModal() {
    document.getElementById('modalAddOrtu').classList.add('show');
}
function closeAddModal() {
    document.getElementById('modalAddOrtu').classList.remove('show');
}

function openEditModal(ortu) {
    document.getElementById('edit_user_id').value = ortu.id;
    document.getElementById('edit_nama').value = ortu.nama_lengkap;
    document.getElementById('edit_username').value = ortu.username;
    document.getElementById('edit_email').value = ortu.email || '';
    document.getElementById('edit_telepon').value = ortu.no_telepon || '';
    document.getElementById('edit_siswa').value = ortu.siswa_terhubung || '';
    document.getElementById('edit_status').value = ortu.status;
    document.getElementById('edit_password').value = '';
    document.getElementById('modalEditOrtu').classList.add('show');
}
function closeEditModal() {
    document.getElementById('modalEditOrtu').classList.remove('show');
}

function toggleDropdownMenu(e, id) {
    e.stopPropagation();
    document.querySelectorAll('.dropdown-action-menu').forEach(el => {
        if (el.id !== id) el.classList.remove('show');
    });
    const target = document.getElementById(id);
    if (target) {
        target.classList.toggle('show');
    }
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown-action-menu').forEach(el => el.classList.remove('show'));
});
</script>
</body>
</html>
