<?php
/**
 * Modul Logbook Guru - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Guru
require_role('guru');

$user = current_user();

$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'G';
}

$pageTitle = 'Logbook Siswa - Guru iTales';
$activeMenu = 'logbook';

$pdo = getDbConnection();
$errorMessage = '';

// Handle Tambah Catatan Logbook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_logbook') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa.';
    } else {
        $siswa     = trim($_POST['nama_siswa'] ?? '');
        $kelas     = trim($_POST['nama_kelas'] ?? '');
        $tanggal   = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $ringkasan = trim($_POST['ringkasan_kegiatan'] ?? '');
        $detail    = trim($_POST['detail_kegiatan'] ?? '');
        $status    = trim($_POST['status'] ?? 'Menunggu Pemeriksaan');

        if (empty($siswa) || !in_array($kelas, ['Kelas 1', 'Kelas 2', 'Kelas 3'], true) || empty($ringkasan)) {
            $errorMessage = 'Nama Siswa, Kelas, dan Ringkasan Kegiatan wajib diisi.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO logbook (tanggal, nama_siswa, nama_kelas, nama_guru, ringkasan_kegiatan, detail_kegiatan, status)
                VALUES (:t, :s, :k, :g, :r, :d, :st)
            ");
            $stmt->execute([
                ':t'  => $tanggal,
                ':s'  => $siswa,
                ':k'  => $kelas,
                ':g'  => $user['nama_lengkap'],
                ':r'  => $ringkasan,
                ':d'  => $detail,
                ':st' => $status
            ]);
            set_flash_message('success', 'Catatan logbook berhasil ditambahkan.');
            header('Location: ' . base_url('guru/logbook.php'));
            exit;
        }
    }
}

// Deteksi Wali Kelas
$waliKelas = '';
if (!empty($user['tanggung_jawab'])) {
    if (preg_match('/(Kelas\s+[0-9A-Za-z]+)/i', $user['tanggung_jawab'], $matches)) {
        $requestedClass = preg_replace('/\s+/', ' ', trim($matches[1]));
        if (in_array($requestedClass, ['Kelas 1', 'Kelas 2', 'Kelas 3'], true)) {
            $waliKelas = $requestedClass;
        }
    }
}
$cleanName = trim(str_replace(['Ibu ', 'Pak ', 'Bpk ', 'S.Pd.', 'M.Pd.', 'S.Pd', 'M.Pd', ','], '', $user['nama_lengkap']));
$firstName = explode(' ', $cleanName)[0];

// Filter
$searchKeyword = trim($_GET['search'] ?? '');
$statusFilter  = trim($_GET['status'] ?? 'semua');

$sql = "SELECT * FROM logbook
        WHERE nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
        AND (nama_kelas = :k OR nama_guru LIKE :ng)";
$params = [':k' => $waliKelas, ':ng' => '%' . $firstName . '%'];

if (!empty($searchKeyword)) {
    $sql .= " AND (nama_siswa LIKE :search OR ringkasan_kegiatan LIKE :search_ringkasan)";
    $params[':search'] = '%' . $searchKeyword . '%';
    $params[':search_ringkasan'] = '%' . $searchKeyword . '%';
}

if ($statusFilter !== 'semua' && !empty($statusFilter)) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbookList = $stmt->fetchAll();

// Daftar siswa & kelas untuk dropdown tambah
$daftarSiswa = $pdo->query("SELECT nama_siswa, nama_kelas FROM siswa
    WHERE status = 'aktif' AND nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
    ORDER BY nama_siswa ASC")->fetchAll();
$daftarKelas = $pdo->query("SELECT nama_kelas FROM kelas
    WHERE status = 'aktif' AND nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
    ORDER BY nama_kelas ASC")->fetchAll(PDO::FETCH_COLUMN);

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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/logbook-media.css') ?>">
    <style>
        .modal-overlay.show { display: flex !important; visibility: visible !important; opacity: 1 !important; z-index: 99999 !important; }
        .btn-table-detail, .btn-add-primary { cursor: pointer !important; }
    </style>
</head>
<body class="dashboard-body">

<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Logbook Siswa</h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date"><?= tanggal_indonesia() ?></div>
                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?>"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="page-header-action-wrap" style="margin-bottom: 1.5rem; align-items: flex-start;">
                <div>
                    <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Logbook Harian</h2>
                    <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Catat perkembangan, kegiatan belajar, dan observasi harian siswa.</p>
                </div>

                <div>
                    <button type="button" class="btn-add-primary" onclick="openAddModal()" style="padding: 10px 22px; font-weight: 700; border-radius: 12px; background-color: var(--color-primary); box-shadow: 0 4px 14px rgba(128, 85, 56, 0.25);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>+ Catat Logbook</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert"><div><?= e($errorMessage) ?></div></div>
            <?php endif; ?>

            <?php foreach (get_flash_messages() as $type => $messages): foreach ($messages as $msg): ?>
                <div class="alert alert-<?= e($type) ?>" role="alert"><div><?= e($msg) ?></div></div>
            <?php endforeach; endforeach; ?>

            <!-- Filter -->
            <form method="GET" action="<?= base_url('guru/logbook.php') ?>" class="filter-bar-logbook">
                <div class="search-input-wrap">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" name="search" class="search-input" placeholder="Cari nama siswa..." value="<?= e($searchKeyword) ?>">
                </div>

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

            <!-- Tabel Logbook -->
            <div class="data-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="padding-left: 1.75rem;">TANGGAL</th>
                                <th>NAMA SISWA</th>
                                <th>KELAS</th>
                                <th>RINGKASAN KEGIATAN</th>
                                <th>STATUS</th>
                                <th style="text-align: right; padding-right: 1.75rem;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logbookList)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
                                        Belum ada catatan logbook yang ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logbookList as $item): 
                                    $stClass = 'badge-status-draft';
                                    if ($item['status'] === 'Dipublikasikan') $stClass = 'badge-status-dipublikasikan';
                                    elseif ($item['status'] === 'Menunggu Pemeriksaan') $stClass = 'badge-status-pemeriksaan';
                                    elseif ($item['status'] === 'Perlu Revisi') $stClass = 'badge-status-revisi';
                                ?>
                                    <tr>
                                        <td style="padding-left: 1.75rem; color: #5F4E42; font-size: 0.9rem;"><?= format_tanggal_view($item['tanggal']) ?></td>
                                        <td><strong style="color: #2D231E;"><?= e($item['nama_siswa']) ?></strong></td>
                                        <td style="color: #6B5E55;"><?= e($item['nama_kelas']) ?></td>
                                        <td style="color: #5F4E42;"><?= e($item['ringkasan_kegiatan']) ?></td>
                                        <td><span class="badge-status <?= $stClass ?>"><?= e($item['status']) ?></span></td>
                                        <td style="text-align: right; padding-right: 1.75rem;">
                                            <button type="button" class="btn-table-detail" data-logbook='<?= htmlspecialchars(json_encode($item, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>' onclick="handleDetailClick(this)">
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

<!-- Modal Tambah Logbook -->
<div id="addLogbookModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width: 580px;">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Catatan Logbook</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('guru/logbook.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_logbook">

            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1.15rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: #4A382D;">Nama Siswa *</label>
                        <select name="nama_siswa" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($daftarSiswa as $ds): ?>
                                <option value="<?= e($ds['nama_siswa']) ?>"><?= e($ds['nama_siswa']) ?> (<?= e($ds['nama_kelas']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; color: #4A382D;">Kelas *</label>
                        <select name="nama_kelas" class="form-control" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                            <?php foreach ($daftarKelas as $dk): ?>
                                <option value="<?= e($dk) ?>"><?= e($dk) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Tanggal *</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Ringkasan Kegiatan *</label>
                    <input type="text" name="ringkasan_kegiatan" class="form-control" placeholder="Contoh: Eksperimen perubahan wujud benda" required style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 14px; width: 100%;">
                </div>

                <div class="form-group" style="margin-bottom: 1.15rem;">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Detail Observasi / Catatan Lengkap</label>
                    <textarea name="detail_kegiatan" rows="3" class="form-control" placeholder="Tuliskan catatan observasi belajar siswa hari ini..." style="border: 1.5px solid var(--color-border); border-radius: 10px; padding: 12px 14px; width: 100%; font-family: inherit; font-size: 0.9rem;"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: #4A382D;">Status</label>
                    <select name="status" class="form-control" style="border: 1.5px solid var(--color-border); border-radius: 10px; height: 44px; padding: 0 12px; width: 100%;">
                        <option value="Menunggu Pemeriksaan" selected>Menunggu Pemeriksaan</option>
                        <option value="Draft">Draft</option>
                        <option value="Dipublikasikan">Dipublikasikan</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; border-radius: 10px; padding: 10px 22px; font-weight: 700;">Simpan Logbook</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Logbook -->
<div id="detailLogbookModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width: 580px;">
        <div class="modal-header">
            <h3 class="modal-title">Detail Catatan Logbook</h3>
            <button type="button" class="modal-close-btn" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background-color: #FAF6F0; border-radius: 12px; padding: 1.25rem; border: 1px solid #EFE8DF; margin-bottom: 1.25rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Nama Siswa</span><strong style="color: #2D231E;" id="modalDetailSiswa">-</strong></div>
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Kelas</span><strong style="color: #2D231E;" id="modalDetailKelas">-</strong></div>
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Guru</span><span style="color: #2D231E;" id="modalDetailGuru">-</span></div>
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Tanggal</span><span style="color: #2D231E;" id="modalDetailTanggal">-</span></div>
                </div>
            </div>
            <div style="margin-bottom: 1rem;"><label style="font-weight: 700; color: #4A382D; display: block; margin-bottom: 4px;">Ringkasan Kegiatan</label><div id="modalDetailRingkasan" style="background-color: #FFFFFF; border: 1.5px solid #E8DFD5; border-radius: 8px; padding: 10px 14px; font-weight: 600;"></div></div>
            <div style="margin-bottom: 1.25rem;"><label style="font-weight: 700; color: #4A382D; display: block; margin-bottom: 4px;">Detail Observasi</label><div id="modalDetailDeskripsi" style="background-color: #FAF8F5; border: 1px solid #E8DFD5; border-radius: 8px; padding: 12px 14px; font-size: 0.88rem; line-height: 1.6;"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/logbook-media.js') ?>"></script>
<script>
function openAddModal() {
    var modal = document.getElementById('addLogbookModal');
    if (modal) { modal.classList.add('show'); modal.style.display = 'flex'; }
}
function closeAddModal() {
    var modal = document.getElementById('addLogbookModal');
    if (modal) { modal.classList.remove('show'); modal.style.display = 'none'; }
}
function handleDetailClick(btn) {
    try {
        var raw = btn.getAttribute('data-logbook');
        var item = JSON.parse(raw);
        document.getElementById('modalDetailSiswa').textContent = item.nama_siswa || '-';
        document.getElementById('modalDetailKelas').textContent = item.nama_kelas || '-';
        document.getElementById('modalDetailGuru').textContent = item.nama_guru || '-';
        document.getElementById('modalDetailTanggal').textContent = item.tanggal || '-';
        document.getElementById('modalDetailRingkasan').textContent = item.ringkasan_kegiatan || '-';
        window.renderLogbookDetail(document.getElementById('modalDetailDeskripsi'), item.detail_kegiatan || '-');
        var modal = document.getElementById('detailLogbookModal');
        if (modal) { modal.classList.add('show'); modal.style.display = 'flex'; }
    } catch(e) { console.error(e); }
}
function closeDetailModal() {
    var modal = document.getElementById('detailLogbookModal');
    if (modal) { modal.classList.remove('show'); modal.style.display = 'none'; }
}
</script>
</body>
</html>
