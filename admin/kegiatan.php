<?php
/**
 * Modul Kegiatan Sekolah - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi 100% sesuai Figma Revise iTales Kegiatan Sekolah Design
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses
require_login();

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

$pageTitle = 'Kegiatan Sekolah - iTales';
$activeMenu = 'kegiatan';

$pdo = getDbConnection();
$errorMessage = '';

// Handle Tambah Kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_kegiatan') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $judul   = trim($_POST['judul_kegiatan'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? '');
        $waktu   = trim($_POST['waktu'] ?? '');
        $lokasi  = trim($_POST['lokasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status  = trim($_POST['status'] ?? 'Mendatang');

        if (empty($judul) || empty($tanggal) || empty($waktu) || empty($lokasi)) {
            $errorMessage = 'Judul, Tanggal, Waktu, dan Lokasi kegiatan wajib diisi.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO kegiatan (judul_kegiatan, tanggal, waktu, lokasi, deskripsi, status)
                VALUES (:j, :t, :w, :l, :d, :s)
            ");
            $stmt->execute([
                ':j' => $judul,
                ':t' => $tanggal,
                ':w' => $waktu,
                ':l' => $lokasi,
                ':d' => $deskripsi,
                ':s' => $status
            ]);
            set_flash_message('success', 'Kegiatan baru berhasil ditambahkan.');
            header('Location: ' . base_url('admin/kegiatan.php'));
            exit;
        }
    }
}

// Handle Edit Kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_kegiatan') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $id      = (int)($_POST['id'] ?? 0);
        $judul   = trim($_POST['judul_kegiatan'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? '');
        $waktu   = trim($_POST['waktu'] ?? '');
        $lokasi  = trim($_POST['lokasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status  = trim($_POST['status'] ?? 'Mendatang');

        if ($id > 0 && !empty($judul) && !empty($tanggal) && !empty($waktu) && !empty($lokasi)) {
            $stmt = $pdo->prepare("
                UPDATE kegiatan 
                SET judul_kegiatan = :j, tanggal = :t, waktu = :w, lokasi = :l, deskripsi = :d, status = :s
                WHERE id = :id
            ");
            $stmt->execute([
                ':j' => $judul,
                ':t' => $tanggal,
                ':w' => $waktu,
                ':l' => $lokasi,
                ':d' => $deskripsi,
                ':s' => $status,
                ':id' => $id
            ]);
            set_flash_message('success', 'Data kegiatan berhasil diperbarui.');
            header('Location: ' . base_url('admin/kegiatan.php'));
            exit;
        } else {
            $errorMessage = 'Mohon lengkapi seluruh field yang diperlukan.';
        }
    }
}

// Handle Hapus Kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_kegiatan') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Kegiatan berhasil dihapus.');
            header('Location: ' . base_url('admin/kegiatan.php'));
            exit;
        }
    }
}

// Tab Filter: Mendatang atau Selesai
$currentTab = $_GET['tab'] ?? 'mendatang';
if (!in_array($currentTab, ['mendatang', 'selesai'])) {
    $currentTab = 'mendatang';
}

// Hitung total kegiatan per tab
$countMendatangStmt = $pdo->query("SELECT COUNT(*) FROM kegiatan WHERE status = 'Mendatang'");
$countMendatang = (int)$countMendatangStmt->fetchColumn();

$countSelesaiStmt = $pdo->query("SELECT COUNT(*) FROM kegiatan WHERE status = 'Selesai'");
$countSelesai = (int)$countSelesaiStmt->fetchColumn();

// Query daftar kegiatan berdasarkan tab aktif
$filterStatus = ($currentTab === 'selesai') ? 'Selesai' : 'Mendatang';
$stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE status = :st ORDER BY tanggal ASC, id ASC");
$stmt->execute([':st' => $filterStatus]);
$kegiatanList = $stmt->fetchAll();

// Helper untuk format tanggal kotak tanggal
function format_date_box($dateStr) {
    $timestamp = strtotime($dateStr);
    $day = date('d', $timestamp);
    $months = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
    ];
    $m = (int)date('n', $timestamp);
    $month = $months[$m] ?? strtoupper(date('M', $timestamp));
    return ['day' => $day, 'month' => $month];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Kegiatan dan Agenda Sekolah SD Siber Labschool">
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
        .btn-card-edit, .btn-add-primary {
            cursor: pointer !important;
            user-select: none;
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
                <h1 class="page-title">Kegiatan Sekolah</h1>
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
            <!-- Header Section dengan Subtitle & Tombol Tambah Kegiatan -->
            <div class="page-header-action-wrap" style="margin-bottom: 1.75rem; align-items: flex-start;">
                <div>
                    <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Kegiatan Sekolah</h2>
                    <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Kelola agenda dan informasi kegiatan sekolah.</p>
                </div>

                <div>
                    <button type="button" class="btn-add-primary" onclick="openAddKegiatanModal()" style="padding: 10px 22px; font-weight: 700; border-radius: 12px; background-color: var(--color-primary); box-shadow: 0 4px 14px rgba(128, 85, 56, 0.25);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Kegiatan</span>
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

            <!-- Tabs Navigasi: Mendatang vs Selesai Sesuai Desain Figma -->
            <div class="kegiatan-tabs-nav">
                <a href="<?= base_url('kegiatan.php?tab=mendatang') ?>" class="kegiatan-tab-btn <?= $currentTab === 'mendatang' ? 'active' : '' ?>">
                    <span>Mendatang</span>
                    <?php if ($countMendatang > 0): ?>
                        <span class="kegiatan-tab-count"><?= $countMendatang ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= base_url('kegiatan.php?tab=selesai') ?>" class="kegiatan-tab-btn <?= $currentTab === 'selesai' ? 'active' : '' ?>">
                    <span>Selesai</span>
                    <?php if ($countSelesai > 0): ?>
                        <span class="kegiatan-tab-count"><?= $countSelesai ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Grid Card Kegiatan Sesuai Desain Figma -->
            <?php if (empty($kegiatanList)): ?>
                <div class="data-card" style="text-align: center; padding: 4rem 2rem; color: #7A6F66;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #4A382D; margin-bottom: 0.25rem;">Tidak ada kegiatan <?= $currentTab === 'selesai' ? 'yang telah selesai' : 'mendatang' ?></h3>
                    <p style="font-size: 0.9rem;">Klik tombol "+ Tambah Kegiatan" di atas untuk menambahkan agenda baru.</p>
                </div>
            <?php else: ?>
                <div class="kegiatan-cards-grid">
                    <?php foreach ($kegiatanList as $kg): 
                        $dateBox = format_date_box($kg['tanggal']);
                    ?>
                        <div class="kegiatan-card">
                            <div>
                                <!-- Top Baris: Kotak Tanggal & Status Badge -->
                                <div class="kegiatan-card-top">
                                    <div class="date-box-square">
                                        <span class="date-box-number"><?= e($dateBox['day']) ?></span>
                                        <span class="date-box-month-name"><?= e($dateBox['month']) ?></span>
                                    </div>

                                    <?php if ($kg['status'] === 'Mendatang'): ?>
                                        <span class="badge-status badge-status-dipublikasikan" style="padding: 6px 14px; font-weight: 600;">
                                            Mendatang
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-draft" style="padding: 6px 14px; font-weight: 600;">
                                            Selesai
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Judul Kegiatan -->
                                <h3 class="kegiatan-card-title"><?= e($kg['judul_kegiatan']) ?></h3>

                                <!-- Deskripsi Kegiatan -->
                                <p class="kegiatan-card-desc"><?= e($kg['deskripsi']) ?></p>
                            </div>

                            <div>
                                <!-- Garis Pemisah Halus -->
                                <div class="kegiatan-card-divider"></div>

                                <!-- Metadata Waktu & Lokasi -->
                                <div class="kegiatan-meta-list">
                                    <div class="kegiatan-meta-item">
                                        <!-- Ikon Jam -->
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span><?= e($kg['waktu']) ?></span>
                                    </div>

                                    <div class="kegiatan-meta-item">
                                        <!-- Ikon Gedung / Rumah -->
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        </svg>
                                        <span><?= e($kg['lokasi']) ?></span>
                                    </div>
                                </div>

                                <!-- Tombol Edit Kegiatan Sesuai Desain Figma -->
                                <button 
                                    type="button" 
                                    class="btn-card-edit" 
                                    data-kegiatan='<?= htmlspecialchars(json_encode($kg, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                                    onclick="handleEditKegiatanClick(this)"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit Kegiatan</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Tambah Kegiatan -->
<div id="addKegiatanModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalAddTitle">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalAddTitle">Tambah Kegiatan Sekolah</h3>
                <p style="font-size: 0.85rem; color: #8C7F75; margin-top: 2px;">Publikasikan agenda dan acara baru untuk sekolah.</p>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeAddKegiatanModal()">&times;</button>
        </div>

        <form method="POST" action="<?= base_url('admin/kegiatan.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_kegiatan">

            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="addJudul" style="font-weight: 700; color: #4A382D;">Judul Kegiatan <span style="color:#C0392B;">*</span></label>
                    <input type="text" name="judul_kegiatan" id="addJudul" class="form-control" placeholder="Contoh: Pentas Seni Kelas 1-3" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.15rem;">
                    <div class="form-group">
                        <label class="form-label" for="addTanggal" style="font-weight: 700; color: #4A382D;">Tanggal <span style="color:#C0392B;">*</span></label>
                        <input type="date" name="tanggal" id="addTanggal" class="form-control" value="2025-05-25" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="addWaktu" style="font-weight: 700; color: #4A382D;">Waktu <span style="color:#C0392B;">*</span></label>
                        <input type="text" name="waktu" id="addWaktu" class="form-control" placeholder="08.00 - 11.30" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="addLokasi" style="font-weight: 700; color: #4A382D;">Lokasi Acara <span style="color:#C0392B;">*</span></label>
                    <input type="text" name="lokasi" id="addLokasi" class="form-control" placeholder="Contoh: Aula Utama Sekolah" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="addDeskripsi" style="font-weight: 700; color: #4A382D;">Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="addDeskripsi" rows="3" class="form-control" placeholder="Deskripsi ringkas mengenai tujuan dan aktivitas acara..." style="border: 1.5px solid var(--color-border); border-radius: 10px; padding: 12px 14px; width: 100%; font-family: inherit; font-size: 0.9rem;">Kegiatan bersama untuk memperkaya pengalaman belajar dan kebersamaan siswa.</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="addStatus" style="font-weight: 700; color: #4A382D;">Status Agenda</label>
                    <select name="status" id="addStatus" class="form-control" style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                        <option value="Mendatang" selected>Mendatang</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddKegiatanModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 10px; padding: 10px 22px; font-weight: 700;">Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kegiatan -->
<div id="editKegiatanModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalEditTitle">Ubah Data Kegiatan</h3>
                <p style="font-size: 0.85rem; color: #8C7F75; margin-top: 2px;">Perbarui rincian atau ubah status kegiatan menjadi selesai.</p>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeEditKegiatanModal()">&times;</button>
        </div>

        <form method="POST" action="<?= base_url('admin/kegiatan.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="edit_kegiatan">
            <input type="hidden" name="id" id="editKegiatanId">

            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="editJudul" style="font-weight: 700; color: #4A382D;">Judul Kegiatan <span style="color:#C0392B;">*</span></label>
                    <input type="text" name="judul_kegiatan" id="editJudul" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.15rem;">
                    <div class="form-group">
                        <label class="form-label" for="editTanggal" style="font-weight: 700; color: #4A382D;">Tanggal <span style="color:#C0392B;">*</span></label>
                        <input type="date" name="tanggal" id="editTanggal" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editWaktu" style="font-weight: 700; color: #4A382D;">Waktu <span style="color:#C0392B;">*</span></label>
                        <input type="text" name="waktu" id="editWaktu" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="editLokasi" style="font-weight: 700; color: #4A382D;">Lokasi Acara <span style="color:#C0392B;">*</span></label>
                    <input type="text" name="lokasi" id="editLokasi" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" for="editDeskripsi" style="font-weight: 700; color: #4A382D;">Deskripsi Singkat</label>
                    <textarea name="deskripsi" id="editDeskripsi" rows="3" class="form-control" style="border: 1.5px solid var(--color-border); border-radius: 10px; padding: 12px 14px; width: 100%; font-family: inherit; font-size: 0.9rem;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editStatus" style="font-weight: 700; color: #4A382D;">Status Agenda</label>
                    <select name="status" id="editStatus" class="form-control" style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                        <option value="Mendatang">Mendatang</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer" style="justify-content: space-between;">
                <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteKegiatan()" style="color: #C0392B; border: 1px solid #F8B4B4; background: #FFF5F5; border-radius: 10px; padding: 10px 16px; font-weight: 600; cursor: pointer;">Hapus</button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditKegiatanModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 10px; padding: 10px 22px; font-weight: 700;">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus Kegiatan Tersembunyi -->
<form id="deleteKegiatanForm" method="POST" action="<?= base_url('admin/kegiatan.php') ?>" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="delete_kegiatan">
    <input type="hidden" name="id" id="deleteKegiatanId">
</form>

<script>
function openAddKegiatanModal() {
    var modal = document.getElementById('addKegiatanModal');
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
}

function closeAddKegiatanModal() {
    var modal = document.getElementById('addKegiatanModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

function handleEditKegiatanClick(btn) {
    try {
        var raw = btn.getAttribute('data-kegiatan');
        if (!raw) return;
        var item = JSON.parse(raw);
        openEditKegiatanModal(item);
    } catch (e) {
        console.error("Error parsing kegiatan data:", e);
    }
}

function openEditKegiatanModal(item) {
    if (!item) return;
    var modal = document.getElementById('editKegiatanModal');
    if (!modal) return;

    document.getElementById('editKegiatanId').value = item.id || '';
    document.getElementById('deleteKegiatanId').value = item.id || '';
    document.getElementById('editJudul').value = item.judul_kegiatan || '';
    document.getElementById('editTanggal').value = item.tanggal || '';
    document.getElementById('editWaktu').value = item.waktu || '';
    document.getElementById('editLokasi').value = item.lokasi || '';
    document.getElementById('editDeskripsi').value = item.deskripsi || '';
    document.getElementById('editStatus').value = item.status || 'Mendatang';

    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeEditKegiatanModal() {
    var modal = document.getElementById('editKegiatanModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

function confirmDeleteKegiatan() {
    if (confirm('Apakah Anda yakin ingin menghapus agenda kegiatan ini?')) {
        document.getElementById('deleteKegiatanForm').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Event listener cadangan untuk tombol Edit Kegiatan
    document.querySelectorAll('.btn-card-edit').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            handleEditKegiatanClick(this);
        });
    });

    // Tutup modal jika klik overlay luar
    var addModal = document.getElementById('addKegiatanModal');
    var editModal = document.getElementById('editKegiatanModal');
    if (addModal) {
        addModal.addEventListener('click', function(event) {
            if (event.target === addModal) closeAddKegiatanModal();
        });
    }
    if (editModal) {
        editModal.addEventListener('click', function(event) {
            if (event.target === editModal) closeEditKegiatanModal();
        });
    }
});
</script>

</body>
</html>
