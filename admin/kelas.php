<?php
/**
 * Modul Data Kelas - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi sesuai antarmuka Figma iTales (Dibatasi Kelas 1 - 3)
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

$pageTitle = 'Data Kelas - iTales';
$activeMenu = 'kelas';

$pdo = getDbConnection();
$errorMessage = '';
$daftarKelas = ['Kelas 1', 'Kelas 2', 'Kelas 3'];

// Ambil daftar guru aktif untuk pilihan guru penanggung jawab
$guruStmt = $pdo->query("SELECT nama_lengkap FROM users WHERE role = 'guru' AND status = 'aktif' ORDER BY nama_lengkap ASC");
$daftarGuru = $guruStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Tambah Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_kelas') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $namaKelas   = trim($_POST['nama_kelas'] ?? '');
        $tingkat     = trim($_POST['tingkat'] ?? '');
        $guruNama    = trim($_POST['guru_nama'] ?? '');
        $jumlahSiswa = (int)($_POST['jumlah_siswa'] ?? 0);
        $status      = $_POST['status'] ?? 'aktif';

        if (!in_array($namaKelas, $daftarKelas, true) || $tingkat !== $namaKelas) {
            $errorMessage = 'Data kelas hanya boleh Kelas 1, Kelas 2, atau Kelas 3.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO kelas (nama_kelas, tingkat, guru_nama, jumlah_siswa, status)
                VALUES (:nk, :t, :g, :j, :s)
            ");
            $stmt->execute([
                ':nk' => $namaKelas,
                ':t'  => $tingkat,
                ':g'  => !empty($guruNama) ? $guruNama : null,
                ':j'  => $jumlahSiswa,
                ':s'  => $status === 'nonaktif' ? 'nonaktif' : 'aktif'
            ]);
            set_flash_message('success', "Data $namaKelas berhasil ditambahkan.");
            header("Location: " . base_url('admin/kelas.php'));
            exit;
        }
    }
}

// Handle Update Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_kelas') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $id          = (int)($_POST['kelas_id'] ?? 0);
        $namaKelas   = trim($_POST['nama_kelas'] ?? '');
        $tingkat     = trim($_POST['tingkat'] ?? '');
        $guruNama    = trim($_POST['guru_nama'] ?? '');
        $jumlahSiswa = (int)($_POST['jumlah_siswa'] ?? 0);
        $status      = $_POST['status'] ?? 'aktif';

        if ($id <= 0 || !in_array($namaKelas, $daftarKelas, true) || $tingkat !== $namaKelas) {
            $errorMessage = 'Data kelas tidak valid. Gunakan hanya Kelas 1, Kelas 2, atau Kelas 3.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE kelas 
                SET nama_kelas = :nk, tingkat = :t, guru_nama = :g, jumlah_siswa = :j, status = :s
                WHERE id = :id
            ");
            $stmt->execute([
                ':nk' => $namaKelas,
                ':t'  => $tingkat,
                ':g'  => !empty($guruNama) ? $guruNama : null,
                ':j'  => $jumlahSiswa,
                ':s'  => $status === 'nonaktif' ? 'nonaktif' : 'aktif',
                ':id' => $id
            ]);
            set_flash_message('success', "Data $namaKelas berhasil diperbarui.");
            header("Location: " . base_url('admin/kelas.php'));
            exit;
        }
    }
}

// Handle Hapus Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_kelas') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $id = (int)($_POST['kelas_id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare("
                DELETE FROM kelas
                WHERE id = :id AND nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
            ");
            $del->execute([':id' => $id]);
            set_flash_message('success', "Data kelas berhasil dihapus.");
            header("Location: " . base_url('admin/kelas.php'));
            exit;
        }
    }
}

// Filter Pencarian & Status
$searchKeyword = trim($_GET['search'] ?? '');
$statusFilter  = trim($_GET['status'] ?? 'semua');

$sql = "SELECT id, nama_kelas, tingkat, guru_nama, jumlah_siswa, status FROM kelas
        WHERE nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (nama_kelas LIKE :s OR tingkat LIKE :s OR guru_nama LIKE :s)";
    $params[':s'] = "%$searchKeyword%";
}

if (!empty($statusFilter) && $statusFilter !== 'semua') {
    $sql .= " AND status = :st";
    $params[':st'] = $statusFilter;
}

$sql .= " ORDER BY nama_kelas ASC";
$queryStmt = $pdo->prepare($sql);
$queryStmt->execute($params);
$kelasList = $queryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Kelola Data Kelas SD Siber Labschool">
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
                <h1 class="page-title">Data Kelas</h1>
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
            <!-- Header Halaman & Tombol Tambah Kelas -->
            <div class="page-header-action-wrap">
                <div>
                    <h2 class="section-main-heading">Data Kelas</h2>
                    <p class="section-sub-heading">Atur wali kelas dan pembagian siswa.</p>
                </div>

                <div>
                    <button type="button" class="btn-add-primary" onclick="openAddModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Kelas</span>
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
                <form method="GET" action="<?= base_url('admin/kelas.php') ?>" class="filter-bar">
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

                <!-- Tabel Data Kelas -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NAMA KELAS</th>
                                <th>TINGKAT</th>
                                <th>GURU BERTANGGUNG JAWAB</th>
                                <th>JUMLAH SISWA</th>
                                <th style="text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kelasList)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2.5rem 1rem; color: var(--color-text-muted);">
                                        Tidak ada data kelas yang ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kelasList as $k): 
                                    // Generate short code e.g. K1, K2, K3
                                    preg_match('/\d+/', $k['nama_kelas'], $numMatch);
                                    $kCode = !empty($numMatch[0]) ? 'K' . $numMatch[0] : 'K';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="teacher-cell">
                                                <div class="teacher-avatar" style="background-color: #E6EEE5; color: #3C6040; border-radius: 8px;">
                                                    <?= e($kCode) ?>
                                                </div>
                                                <span class="teacher-name"><?= e($k['nama_kelas']) ?></span>
                                            </div>
                                        </td>
                                        <td class="cell-muted"><?= e($k['tingkat']) ?></td>
                                        <td class="cell-muted"><?= !empty($k['guru_nama']) ? e($k['guru_nama']) : '<span style="color:#999;">Belum ditentukan</span>' ?></td>
                                        <td class="cell-muted"><?= (int)$k['jumlah_siswa'] ?> siswa</td>
                                        <td style="text-align: right;">
                                            <div class="action-buttons-wrap">
                                                <!-- Tombol Edit -->
                                                <button 
                                                    type="button" 
                                                    class="action-btn-circle" 
                                                    title="Ubah Data Kelas"
                                                    onclick='openEditModal(<?= json_encode($k) ?>)'
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
                                                        onclick="toggleDropdownMenu(event, 'dropdown-<?= $k['id'] ?>')"
                                                    >
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="1"></circle>
                                                            <circle cx="19" cy="12" r="1"></circle>
                                                            <circle cx="5" cy="12" r="1"></circle>
                                                        </svg>
                                                    </button>
                                                    <div id="dropdown-<?= $k['id'] ?>" class="dropdown-action-menu">
                                                        <form method="POST" action="<?= base_url('admin/kelas.php') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kelas ini?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                                            <input type="hidden" name="action" value="delete_kelas">
                                                            <input type="hidden" name="kelas_id" value="<?= $k['id'] ?>">
                                                            <button type="submit" class="dropdown-item-danger">
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                </svg>
                                                                Hapus Kelas
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

<!-- Modal Tambah Kelas -->
<div id="modalAddKelas" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Data Kelas</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/kelas.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="add_kelas">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_nama_kelas">Nama Kelas *</label>
                    <input type="text" id="add_nama_kelas" name="nama_kelas" class="form-input" placeholder="Contoh: Kelas 1" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_tingkat">Tingkat *</label>
                    <select id="add_tingkat" name="tingkat" class="form-input" required>
                        <option value="Kelas 1">Kelas 1</option>
                        <option value="Kelas 2">Kelas 2</option>
                        <option value="Kelas 3">Kelas 3</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_guru">Guru Penanggung Jawab / Wali Kelas</label>
                    <select id="add_guru" name="guru_nama" class="form-input">
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($daftarGuru as $dg): ?>
                            <option value="<?= e($dg) ?>"><?= e($dg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_jumlah">Jumlah Siswa</label>
                    <input type="number" id="add_jumlah" name="jumlah_siswa" class="form-input" value="0" min="0">
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_status">Status Kelas</label>
                    <select id="add_status" name="status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kelas</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Kelas -->
<div id="modalEditKelas" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Ubah Data Kelas</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('admin/kelas.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="edit_kelas">
            <input type="hidden" id="edit_kelas_id" name="kelas_id" value="">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_nama_kelas">Nama Kelas *</label>
                    <input type="text" id="edit_nama_kelas" name="nama_kelas" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_tingkat">Tingkat *</label>
                    <select id="edit_tingkat" name="tingkat" class="form-input" required>
                        <option value="Kelas 1">Kelas 1</option>
                        <option value="Kelas 2">Kelas 2</option>
                        <option value="Kelas 3">Kelas 3</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_guru">Guru Penanggung Jawab / Wali Kelas</label>
                    <select id="edit_guru" name="guru_nama" class="form-input">
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($daftarGuru as $dg): ?>
                            <option value="<?= e($dg) ?>"><?= e($dg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_jumlah">Jumlah Siswa</label>
                    <input type="number" id="edit_jumlah" name="jumlah_siswa" class="form-input" min="0">
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Status Kelas</label>
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
    document.getElementById('modalAddKelas').classList.add('show');
}
function closeAddModal() {
    document.getElementById('modalAddKelas').classList.remove('show');
}

function openEditModal(k) {
    document.getElementById('edit_kelas_id').value = k.id;
    document.getElementById('edit_nama_kelas').value = k.nama_kelas;
    document.getElementById('edit_tingkat').value = k.tingkat;
    document.getElementById('edit_guru').value = k.guru_nama || '';
    document.getElementById('edit_jumlah').value = k.jumlah_siswa;
    document.getElementById('edit_status').value = k.status;
    document.getElementById('modalEditKelas').classList.add('show');
}
function closeEditModal() {
    document.getElementById('modalEditKelas').classList.remove('show');
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
