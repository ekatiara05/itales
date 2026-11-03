<?php
/**
 * Modul Kegiatan Sekolah untuk Guru - iTales
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('guru');
$user = current_user();

$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) $initials = 'G';

$pageTitle = 'Kegiatan Sekolah - Guru iTales';
$activeMenu = 'kegiatan';
$pdo = getDbConnection();

$currentTab = $_GET['tab'] ?? 'mendatang';
if (!in_array($currentTab, ['mendatang', 'selesai'])) $currentTab = 'mendatang';

$countMendatang = (int)$pdo->query("SELECT COUNT(*) FROM kegiatan WHERE status = 'Mendatang'")->fetchColumn();
$countSelesai   = (int)$pdo->query("SELECT COUNT(*) FROM kegiatan WHERE status = 'Selesai'")->fetchColumn();

$filterStatus = ($currentTab === 'selesai') ? 'Selesai' : 'Mendatang';
$stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE status = :st ORDER BY tanggal ASC, id ASC");
$stmt->execute([':st' => $filterStatus]);
$kegiatanList = $stmt->fetchAll();

function format_date_box($dateStr) {
    $timestamp = strtotime($dateStr);
    $day = date('d', $timestamp);
    $months = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN',
        7 => 'JUL', 8 => 'AGU', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
    ];
    $m = (int)date('n', $timestamp);
    return ['day' => $day, 'month' => $months[$m] ?? strtoupper(date('M', $timestamp))];
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
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Kegiatan Sekolah</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= tanggal_indonesia() ?></div>
                <div class="topbar-avatar"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content">
            <div style="margin-bottom: 1.75rem;">
                <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Agenda Kegiatan Sekolah</h2>
                <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Informasi agenda dan kegiatan siswa SD Siber Labschool.</p>
            </div>

            <div class="kegiatan-tabs-nav">
                <a href="<?= base_url('guru/kegiatan.php?tab=mendatang') ?>" class="kegiatan-tab-btn <?= $currentTab === 'mendatang' ? 'active' : '' ?>">
                    <span>Mendatang</span>
                    <?php if ($countMendatang > 0): ?><span class="kegiatan-tab-count"><?= $countMendatang ?></span><?php endif; ?>
                </a>
                <a href="<?= base_url('guru/kegiatan.php?tab=selesai') ?>" class="kegiatan-tab-btn <?= $currentTab === 'selesai' ? 'active' : '' ?>">
                    <span>Selesai</span>
                    <?php if ($countSelesai > 0): ?><span class="kegiatan-tab-count"><?= $countSelesai ?></span><?php endif; ?>
                </a>
            </div>

            <?php if (empty($kegiatanList)): ?>
                <div class="data-card" style="text-align: center; padding: 4rem 2rem; color: #7A6F66;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: #4A382D;">Tidak ada agenda <?= $currentTab === 'selesai' ? 'yang selesai' : 'mendatang' ?></h3>
                </div>
            <?php else: ?>
                <div class="kegiatan-cards-grid">
                    <?php foreach ($kegiatanList as $kg): $dateBox = format_date_box($kg['tanggal']); ?>
                        <div class="kegiatan-card">
                            <div>
                                <div class="kegiatan-card-top">
                                    <div class="date-box-square">
                                        <span class="date-box-number"><?= e($dateBox['day']) ?></span>
                                        <span class="date-box-month-name"><?= e($dateBox['month']) ?></span>
                                    </div>
                                    <span class="badge-status <?= $kg['status'] === 'Mendatang' ? 'badge-status-dipublikasikan' : 'badge-status-draft' ?>">
                                        <?= e($kg['status']) ?>
                                    </span>
                                </div>
                                <h3 class="kegiatan-card-title"><?= e($kg['judul_kegiatan']) ?></h3>
                                <p class="kegiatan-card-desc"><?= e($kg['deskripsi']) ?></p>
                            </div>
                            <div>
                                <div class="kegiatan-card-divider"></div>
                                <div class="kegiatan-meta-list">
                                    <div class="kegiatan-meta-item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span><?= e($kg['waktu']) ?></span>
                                    </div>
                                    <div class="kegiatan-meta-item">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                        <span><?= e($kg['lokasi']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
