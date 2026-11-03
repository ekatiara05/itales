<?php
/**
 * Modul Logbook Anak untuk Orang Tua - iTales
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('orang_tua');
$user = current_user();

$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) $initials = 'OT';

$pageTitle = 'Logbook Anak - Orang Tua iTales';
$activeMenu = 'logbook';
$pdo = getDbConnection();

// Dapatkan nama anak
$stmtUser = $pdo->prepare("SELECT siswa_terhubung FROM users WHERE id = :id");
$stmtUser->execute([':id' => $user['id']]);
$namaAnak = $stmtUser->fetchColumn();

// Query logbook anak yang sudah dipublikasikan
if (!empty($namaAnak)) {
    $stmt = $pdo->prepare("SELECT * FROM logbook WHERE nama_siswa LIKE :ns AND status = 'Dipublikasikan' ORDER BY tanggal DESC");
    $stmt->execute([':ns' => '%' . $namaAnak . '%']);
} else {
    // Tampilkan logbook yang sudah dipublikasikan
    $stmt = $pdo->prepare("SELECT * FROM logbook WHERE status = 'Dipublikasikan' ORDER BY tanggal DESC");
    $stmt->execute();
}
$logbookList = $stmt->fetchAll();

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/logbook-media.css') ?>">
    <style>
        .modal-overlay.show { display: flex !important; visibility: visible !important; opacity: 1 !important; z-index: 99999 !important; }
        .btn-table-detail { cursor: pointer !important; }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Logbook Harian Anak</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= tanggal_indonesia() ?></div>
                <div class="topbar-avatar"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content">
            <div style="margin-bottom: 1.75rem;">
                <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Catatan Aktivitas Belajar Ananda</h2>
                <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Laporan harian perkembangan dan kegiatan yang dicatat oleh guru kelas.</p>
            </div>

            <div class="data-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="padding-left: 1.75rem;">TANGGAL</th>
                                <th>NAMA SISWA</th>
                                <th>KELAS</th>
                                <th>GURU PENDAMPING</th>
                                <th>RINGKASAN KEGIATAN</th>
                                <th>STATUS</th>
                                <th style="text-align: right; padding-right: 1.75rem;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logbookList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
                                        Belum ada catatan logbook yang dipublikasikan untuk ananda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logbookList as $item): ?>
                                    <tr>
                                        <td style="padding-left: 1.75rem; color: #5F4E42; font-size: 0.9rem;"><?= format_tanggal_view($item['tanggal']) ?></td>
                                        <td><strong style="color: #2D231E;"><?= e($item['nama_siswa']) ?></strong></td>
                                        <td style="color: #6B5E55;"><?= e($item['nama_kelas']) ?></td>
                                        <td style="color: #5F4E42;"><?= e($item['nama_guru']) ?></td>
                                        <td style="color: #5F4E42;"><?= e($item['ringkasan_kegiatan']) ?></td>
                                        <td><span class="badge-status badge-status-dipublikasikan"><?= e($item['status']) ?></span></td>
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
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Guru Pendamping</span><span style="color: #2D231E;" id="modalDetailGuru">-</span></div>
                    <div><span style="color: #8C786A; display: block; font-size: 0.78rem; text-transform: uppercase; font-weight: 700;">Tanggal</span><span style="color: #2D231E;" id="modalDetailTanggal">-</span></div>
                </div>
            </div>
            <div style="margin-bottom: 1rem;"><label style="font-weight: 700; color: #4A382D; display: block; margin-bottom: 4px;">Ringkasan Kegiatan</label><div id="modalDetailRingkasan" style="background-color: #FFFFFF; border: 1.5px solid #E8DFD5; border-radius: 8px; padding: 10px 14px; font-weight: 600;"></div></div>
            <div style="margin-bottom: 1.25rem;"><label style="font-weight: 700; color: #4A382D; display: block; margin-bottom: 4px;">Detail Observasi / Catatan Guru</label><div id="modalDetailDeskripsi" style="background-color: #FAF8F5; border: 1px solid #E8DFD5; border-radius: 8px; padding: 12px 14px; font-size: 0.88rem; line-height: 1.6;"></div></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/logbook-media.js') ?>"></script>
<script>
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
