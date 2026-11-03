<?php
/**
 * Modul Semua Logbook - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi 100% sesuai Figma Revise iTales Logbook Design
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi login admin / guru
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

$pageTitle = 'Semua Logbook - iTales';
$activeMenu = 'logbook';

$pdo = getDbConnection();
$errorMessage = '';

// Handle Update Status Logbook via Modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $logbookId = (int)($_POST['logbook_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['Dipublikasikan', 'Menunggu Pemeriksaan', 'Draft', 'Perlu Revisi'];

        if ($logbookId > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $stmt = $pdo->prepare("UPDATE logbook SET status = :st WHERE id = :id");
            $stmt->execute([':st' => $newStatus, ':id' => $logbookId]);
            set_flash_message('success', 'Status logbook berhasil diperbarui.');
            header('Location: ' . base_url('admin/logbook.php'));
            exit;
        } else {
            $errorMessage = 'Status yang dipilih tidak valid.';
        }
    }
}

// Filter Parameters
$searchKeyword = trim($_GET['search'] ?? '');
$tanggalFilter = trim($_GET['tanggal'] ?? 'semua');
$kelasFilter   = trim($_GET['kelas'] ?? 'semua');
$statusFilter  = trim($_GET['status'] ?? 'semua');

$daftarKelas = ['Kelas 1', 'Kelas 2', 'Kelas 3'];
if ($kelasFilter !== 'semua' && !in_array($kelasFilter, $daftarKelas, true)) {
    $kelasFilter = 'semua';
}

// Query data logbook dengan filter
$sql = "SELECT * FROM logbook WHERE nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (nama_siswa LIKE :search OR ringkasan_kegiatan LIKE :search_ringkasan OR nama_guru LIKE :search_guru)";
    $params[':search'] = '%' . $searchKeyword . '%';
    $params[':search_ringkasan'] = '%' . $searchKeyword . '%';
    $params[':search_guru'] = '%' . $searchKeyword . '%';
}

if ($kelasFilter !== 'semua' && !empty($kelasFilter)) {
    $sql .= " AND nama_kelas = :kelas";
    $params[':kelas'] = $kelasFilter;
}

if ($statusFilter !== 'semua' && !empty($statusFilter)) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($tanggalFilter !== 'semua' && !empty($tanggalFilter)) {
    if ($tanggalFilter === 'hari_ini') {
        $sql .= " AND tanggal = CURDATE()";
    } elseif ($tanggalFilter === 'mei_2025') {
        $sql .= " AND tanggal LIKE '2025-05-%'";
    }
}

$sql .= " ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbookList = $stmt->fetchAll();

// Mapping helper untuk nama bulan Indonesia
function format_tanggal_view($dateStr) {
    if (empty($dateStr)) return '-';
    $timestamp = strtotime($dateStr);
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $d = date('j', $timestamp);
    $m = (int)date('n', $timestamp);
    $y = date('Y', $timestamp);
    return $d . ' ' . ($bulanIndo[$m] ?? date('M', $timestamp)) . ' ' . $y;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Semua Logbook Siswa SD Siber Labschool">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/logbook-media.css') ?>">
    <style>
        .modal-overlay.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
        }
        .btn-table-detail {
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
                <h1 class="page-title">Semua Logbook</h1>
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
            <div style="margin-bottom: 1.5rem;">
                <p style="color: #8C7F75; font-size: 0.95rem; margin-top: -0.25rem;">Pantau dan periksa logbook dari seluruh kelas.</p>
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

            <!-- Card Filter Sesuai Desain Figma -->
            <form method="GET" action="<?= base_url('admin/logbook.php') ?>" class="filter-bar-logbook">
                <!-- Input Pencarian -->
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

                <!-- Dropdown Tanggal -->
                <div>
                    <select name="tanggal" class="filter-select" onchange="this.form.submit()">
                        <option value="semua" <?= $tanggalFilter === 'semua' ? 'selected' : '' ?>>Semua tanggal</option>
                        <option value="hari_ini" <?= $tanggalFilter === 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="mei_2025" <?= $tanggalFilter === 'mei_2025' ? 'selected' : '' ?>>Mei 2025</option>
                    </select>
                </div>

                <!-- Dropdown Kelas -->
                <div>
                    <select name="kelas" class="filter-select" onchange="this.form.submit()">
                        <option value="semua" <?= $kelasFilter === 'semua' ? 'selected' : '' ?>>Semua kelas</option>
                        <?php foreach ($daftarKelas as $dk): ?>
                            <option value="<?= e($dk) ?>" <?= $kelasFilter === $dk ? 'selected' : '' ?>><?= e($dk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dropdown Status -->
                <div>
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="semua" <?= $statusFilter === 'semua' ? 'selected' : '' ?>>Semua status</option>
                        <option value="Dipublikasikan" <?= $statusFilter === 'Dipublikasikan' ? 'selected' : '' ?>>Dipublikasikan</option>
                        <option value="Menunggu Pemeriksaan" <?= $statusFilter === 'Menunggu Pemeriksaan' ? 'selected' : '' ?>>Menunggu Pemeriksaan</option>
                        <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Perlu Revisi" <?= $statusFilter === 'Perlu Revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                    </select>
                </div>
            </form>

            <!-- Card Utama Konten Tabel Logbook -->
            <div class="data-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="padding-left: 1.75rem;">TANGGAL</th>
                                <th>NAMA SISWA</th>
                                <th>KELAS</th>
                                <th>GURU</th>
                                <th>RINGKASAN KEGIATAN</th>
                                <th>STATUS</th>
                                <th style="text-align: right; padding-right: 1.75rem;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logbookList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
                                        Tidak ada catatan logbook yang sesuai dengan filter pencarian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logbookList as $item): ?>
                                    <tr>
                                        <!-- Tanggal -->
                                        <td style="padding-left: 1.75rem; color: #5F4E42; font-size: 0.9rem;">
                                            <?= format_tanggal_view($item['tanggal']) ?>
                                        </td>

                                        <!-- Nama Siswa -->
                                        <td>
                                            <strong style="color: #2D231E; font-size: 0.95rem; font-weight: 700;">
                                                <?= e($item['nama_siswa']) ?>
                                            </strong>
                                        </td>

                                        <!-- Kelas -->
                                        <td style="color: #6B5E55; font-size: 0.9rem;">
                                            <?= e($item['nama_kelas']) ?>
                                        </td>

                                        <!-- Guru -->
                                        <td style="color: #5F4E42; font-size: 0.9rem;">
                                            <?= e($item['nama_guru']) ?>
                                        </td>

                                        <!-- Ringkasan Kegiatan -->
                                        <td style="color: #5F4E42; font-size: 0.9rem; max-width: 280px;">
                                            <?= e($item['ringkasan_kegiatan']) ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td>
                                            <?php 
                                            $stClass = 'badge-status-draft';
                                            if ($item['status'] === 'Dipublikasikan') {
                                                $stClass = 'badge-status-dipublikasikan';
                                            } elseif ($item['status'] === 'Menunggu Pemeriksaan') {
                                                $stClass = 'badge-status-pemeriksaan';
                                            } elseif ($item['status'] === 'Perlu Revisi') {
                                                $stClass = 'badge-status-revisi';
                                            }
                                            ?>
                                            <span class="badge-status <?= $stClass ?>">
                                                <?= e($item['status']) ?>
                                            </span>
                                        </td>

                                        <!-- Tombol Aksi Detail -->
                                        <td style="text-align: right; padding-right: 1.75rem;">
                                            <button 
                                                type="button" 
                                                class="btn-table-detail"
                                                data-logbook='<?= htmlspecialchars(json_encode($item, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                                                onclick="handleDetailClick(this)"
                                            >
                                                Detail
                                            </button>
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

<!-- Modal Detail Logbook -->
<div id="detailLogbookModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalDetailTitle">
    <div class="modal-dialog" style="max-width: 580px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalDetailTitle">Detail Catatan Logbook</h3>
                <p style="font-size: 0.85rem; color: #8C7F75; margin-top: 2px;" id="modalDetailSub"></p>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeDetailModal()">&times;</button>
        </div>

        <form method="POST" action="<?= base_url('admin/logbook.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="logbook_id" id="modalDetailId">

            <div class="modal-body">
                <div style="background-color: #FAF6F0; border-radius: 12px; padding: 1.25rem; border: 1px solid #EFE8DF; margin-bottom: 1.25rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
                        <div>
                            <span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Nama Siswa</span>
                            <strong style="color: #2D231E;" id="modalDetailSiswa">-</strong>
                        </div>
                        <div>
                            <span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Kelas</span>
                            <strong style="color: #2D231E;" id="modalDetailKelas">-</strong>
                        </div>
                        <div>
                            <span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Guru Pendamping</span>
                            <span style="color: #2D231E;" id="modalDetailGuru">-</span>
                        </div>
                        <div>
                            <span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Tanggal Kegiatan</span>
                            <span style="color: #2D231E;" id="modalDetailTanggal">-</span>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Ringkasan Kegiatan</label>
                    <div id="modalDetailRingkasan" style="background-color: #FFFFFF; border: 1.5px solid #E8DFD5; border-radius: 8px; padding: 10px 14px; font-size: 0.92rem; font-weight: 600; color: #2D231E;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Detail Observasi / Catatan Lengkap</label>
                    <div id="modalDetailDeskripsi" style="background-color: #FAF8F5; border: 1px solid #E8DFD5; border-radius: 8px; padding: 12px 14px; font-size: 0.88rem; color: #5F4E42; line-height: 1.6; min-height: 80px;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modalDetailStatusSelect" style="font-weight: 700; color: #4A382D;">Perbarui Status Logbook</label>
                    <select name="status" id="modalDetailStatusSelect" class="form-control" style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                        <option value="Dipublikasikan">Dipublikasikan</option>
                        <option value="Menunggu Pemeriksaan">Menunggu Pemeriksaan</option>
                        <option value="Draft">Draft</option>
                        <option value="Perlu Revisi">Perlu Revisi</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
                <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 10px; padding: 10px 20px; font-weight: 600;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('assets/js/logbook-media.js') ?>"></script>
<script>
function handleDetailClick(btn) {
    try {
        var raw = btn.getAttribute('data-logbook');
        if (!raw) return;
        var item = JSON.parse(raw);
        openDetailModal(item);
    } catch (e) {
        console.error("Error parsing logbook data:", e);
    }
}

function openDetailModal(item) {
    if (!item) return;
    var modal = document.getElementById('detailLogbookModal');
    if (!modal) return;

    document.getElementById('modalDetailId').value = item.id || '';
    document.getElementById('modalDetailSiswa').textContent = item.nama_siswa || '-';
    document.getElementById('modalDetailKelas').textContent = item.nama_kelas || '-';
    document.getElementById('modalDetailGuru').textContent = item.nama_guru || '-';
    document.getElementById('modalDetailTanggal').textContent = item.tanggal || '-';
    document.getElementById('modalDetailSub').textContent = 'ID Catatan: #' + (item.id || '') + ' • ' + (item.nama_kelas || '');
    document.getElementById('modalDetailRingkasan').textContent = item.ringkasan_kegiatan || '-';
    window.renderLogbookDetail(document.getElementById('modalDetailDeskripsi'), item.detail_kegiatan || 'Tidak ada catatan tambahan.');

    var statusSelect = document.getElementById('modalDetailStatusSelect');
    if (statusSelect && item.status) {
        statusSelect.value = item.status;
    }

    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeDetailModal() {
    var modal = document.getElementById('detailLogbookModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Event listener cadangan untuk semua tombol Detail
    document.querySelectorAll('.btn-table-detail').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            handleDetailClick(this);
        });
    });

    // Tutup modal ketika klik area background luar
    var modal = document.getElementById('detailLogbookModal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeDetailModal();
            }
        });
    }
});
</script>

</body>
</html>
