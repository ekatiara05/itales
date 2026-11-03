<?php
/**
 * Modul Data Siswa - iTales
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

$pageTitle = 'Data Siswa - iTales';
$activeMenu = 'siswa';

$pdo = getDbConnection();
$errorMessage = '';

$daftarKelas = ['Kelas 1', 'Kelas 2', 'Kelas 3'];

// Handle Tambah Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_siswa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $namaSiswa    = trim($_POST['nama_siswa'] ?? '');
        $nis          = trim($_POST['nis'] ?? '');
        $namaKelas    = trim($_POST['nama_kelas'] ?? '');
        $namaOrangTua = trim($_POST['nama_orang_tua'] ?? '');
        $status       = $_POST['status'] ?? 'aktif';

        if (empty($namaSiswa) || !in_array($namaKelas, $daftarKelas, true)) {
            $errorMessage = 'Nama siswa wajib diisi dan kelas harus Kelas 1, 2, atau 3.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO siswa (nis, nama_siswa, nama_kelas, nama_orang_tua, status)
                VALUES (:nis, :ns, :nk, :no, :s)
            ");
            $stmt->execute([
                ':nis' => !empty($nis) ? $nis : null,
                ':ns'  => $namaSiswa,
                ':nk'  => $namaKelas,
                ':no'  => !empty($namaOrangTua) ? $namaOrangTua : null,
                ':s'   => $status === 'nonaktif' ? 'nonaktif' : 'aktif'
            ]);
            set_flash_message('success', "Data siswa $namaSiswa berhasil ditambahkan.");
            header("Location: " . base_url('admin/siswa.php'));
            exit;
        }
    }
}

// Handle Update Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_siswa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $id           = (int)($_POST['siswa_id'] ?? 0);
        $namaSiswa    = trim($_POST['nama_siswa'] ?? '');
        $nis          = trim($_POST['nis'] ?? '');
        $namaKelas    = trim($_POST['nama_kelas'] ?? '');
        $namaOrangTua = trim($_POST['nama_orang_tua'] ?? '');
        $status       = $_POST['status'] ?? 'aktif';

        if ($id <= 0 || empty($namaSiswa) || !in_array($namaKelas, $daftarKelas, true)) {
            $errorMessage = 'Data siswa tidak valid.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE siswa 
                SET nama_siswa = :ns, nis = :nis, nama_kelas = :nk, nama_orang_tua = :no, status = :s
                WHERE id = :id
            ");
            $stmt->execute([
                ':ns'  => $namaSiswa,
                ':nis' => !empty($nis) ? $nis : null,
                ':nk'  => $namaKelas,
                ':no'  => !empty($namaOrangTua) ? $namaOrangTua : null,
                ':s'   => $status === 'nonaktif' ? 'nonaktif' : 'aktif',
                ':id'  => $id
            ]);
            set_flash_message('success', "Data siswa $namaSiswa berhasil diperbarui.");
            header("Location: " . base_url('admin/siswa.php'));
            exit;
        }
    }
}

// Handle Hapus Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_siswa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $id = (int)($_POST['siswa_id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare("DELETE FROM siswa WHERE id = :id");
            $del->execute([':id' => $id]);
            set_flash_message('success', "Data siswa berhasil dihapus.");
            header("Location: " . base_url('admin/siswa.php'));
            exit;
        }
    }
}

// Filter Pencarian & Status
$searchKeyword = trim($_GET['search'] ?? '');
$statusFilter  = trim($_GET['status'] ?? 'semua');

$sql = "SELECT id, nis, nama_siswa, nama_kelas, nama_orang_tua, status FROM siswa
        WHERE nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (nama_siswa LIKE :s OR nama_kelas LIKE :s OR nama_orang_tua LIKE :s)";
    $params[':s'] = "%$searchKeyword%";
}

if (!empty($statusFilter) && $statusFilter !== 'semua') {
    $sql .= " AND status = :st";
    $params[':st'] = $statusFilter;
}

$sql .= " ORDER BY nama_kelas ASC, nama_siswa ASC";
$queryStmt = $pdo->prepare($sql);
$queryStmt->execute($params);
$siswaList = $queryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Kelola Data Siswa SD Siber Labschool">
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
                <h1 class="page-title">Data Siswa</h1>
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
            <!-- Header Halaman & Tombol Tambah Siswa -->
            <div class="page-header-action-wrap">
                <div>
                    <h2 class="section-main-heading">Data Siswa</h2>
                    <p class="section-sub-heading">Kelola data siswa dan akun orang tua yang terhubung.</p>
                </div>

                <div>
                    <button type="button" class="btn-add-primary" onclick="openAddModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Siswa</span>
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
                            <line x1="12" y1="8" x2="12" y2="12"></line>
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
                <form method="GET" action="<?= base_url('admin/siswa.php') ?>" class="filter-bar">
                    <div class="search-input-wrap">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Cari nama siswa..." 
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

                <!-- Tabel Data Siswa -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NAMA SISWA</th>
                                <th>KELAS</th>
                                <th>ORANG TUA TERHUBUNG</th>
                                <th>STATUS DATA</th>
                                <th style="text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaList)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
                                        Tidak ada data siswa yang ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($siswaList as $s): 
                                    $sWords = explode(' ', trim($s['nama_siswa']));
                                    $sInit = '';
                                    foreach (array_slice($sWords, 0, 2) as $w) {
                                        $sInit .= strtoupper(substr($w, 0, 1));
                                    }
                                    if (empty($sInit)) $sInit = 'S';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="teacher-cell">
                                                <div class="teacher-avatar" style="background-color: #E6EEE5; color: #3C6040;">
                                                    <?= e($sInit) ?>
                                                </div>
                                                <span class="teacher-name"><?= e($s['nama_siswa']) ?></span>
                                            </div>
                                        </td>
                                        <td class="cell-muted">
                                            <strong><?= e($s['nama_kelas']) ?></strong>
                                        </td>
                                        <td class="cell-muted">
                                            <?= !empty($s['nama_orang_tua']) ? e($s['nama_orang_tua']) : '<span style="color:#999;">-</span>' ?>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] === 'aktif'): ?>
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
                                                    title="Ubah Data Siswa"
                                                    onclick='openEditModal(<?= json_encode($s) ?>)'
                                                >
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Tombol Opsi Lainnya -->
                                                <div class="dropdown-action-container">
                                                    <button 
                                                        type="button" 
                                                        class="action-btn-circle" 
                                                        title="Opsi Lainnya"
                                                        onclick="toggleDropdownMenu(event, 'dropdown-<?= $s['id'] ?>')"
                                                    >
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="1"></circle>
                                                            <circle cx="19" cy="12" r="1"></circle>
                                                            <circle cx="5" cy="12" r="1"></circle>
                                                        </svg>
                                                    </button>
                                                    <div id="dropdown-<?= $s['id'] ?>" class="dropdown-action-menu">
                                                        <form method="POST" action="<?= base_url('admin/siswa.php') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                            <input type="hidden" name="action" value="delete_siswa">
                                                            <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
                                                            <button type="submit" class="dropdown-item-danger">
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                </svg>
                                                                Hapus Siswa
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

<!-- Modal Tambah Siswa -->
<div id="modalAddSiswa" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Data Siswa</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/siswa.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="add_siswa">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_nama">Nama Lengkap Siswa *</label>
                    <input type="text" id="add_nama" name="nama_siswa" class="form-input" placeholder="Contoh: Raka Pratama" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_nis">NIS / Nomor Induk Siswa</label>
                    <input type="text" id="add_nis" name="nis" class="form-input" placeholder="Contoh: 1001">
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_kelas">Kelas *</label>
                    <select id="add_kelas" name="nama_kelas" class="form-input" required>
                        <?php foreach ($daftarKelas as $dk): ?>
                            <option value="<?= e($dk) ?>"><?= e($dk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_ortu">Nama Orang Tua Terhubung</label>
                    <input type="text" id="add_ortu" name="nama_orang_tua" class="form-input" placeholder="Contoh: Nadia Rahma">
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_status">Status Data</label>
                    <select id="add_status" name="status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Data Siswa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Siswa -->
<div id="modalEditSiswa" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Ubah Data Siswa</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/siswa.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="edit_siswa">
            <input type="hidden" id="edit_siswa_id" name="siswa_id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_nama">Nama Lengkap Siswa *</label>
                    <input type="text" id="edit_nama" name="nama_siswa" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_nis">NIS</label>
                    <input type="text" id="edit_nis" name="nis" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_kelas">Kelas *</label>
                    <select id="edit_kelas" name="nama_kelas" class="form-input" required>
                        <?php foreach ($daftarKelas as $dk): ?>
                            <option value="<?= e($dk) ?>"><?= e($dk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_ortu">Nama Orang Tua Terhubung</label>
                    <input type="text" id="edit_ortu" name="nama_orang_tua" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Status Data</label>
                    <select id="edit_status" name="status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
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
    document.getElementById('modalAddSiswa').classList.add('show');
}
function closeAddModal() {
    document.getElementById('modalAddSiswa').classList.remove('show');
}

function openEditModal(siswa) {
    document.getElementById('edit_siswa_id').value = siswa.id;
    document.getElementById('edit_nama').value = siswa.nama_siswa;
    document.getElementById('edit_nis').value = siswa.nis || '';
    document.getElementById('edit_kelas').value = siswa.nama_kelas;
    document.getElementById('edit_ortu').value = siswa.nama_orang_tua || '';
    document.getElementById('edit_status').value = siswa.status;
    document.getElementById('modalEditSiswa').classList.add('show');
}
function closeEditModal() {
    document.getElementById('modalEditSiswa').classList.remove('show');
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

document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown-action-menu').forEach(el => el.classList.remove('show'));
});
</script>
</body>
</html>
