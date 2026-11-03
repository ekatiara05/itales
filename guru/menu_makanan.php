<?php
/**
 * Modul Menu Makanan untuk Guru - iTales
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

$pageTitle = 'Menu Makanan - Guru iTales';
$activeMenu = 'menu_makanan';
$pdo = getDbConnection();

$baseMonday = '2025-05-19';
$weekOffset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$startOfWeekTimestamp = strtotime("$baseMonday +$weekOffset week");
$endOfWeekTimestamp   = strtotime("+4 days", $startOfWeekTimestamp);

$namaHariIndo = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat'];
$namaBulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

$startDay   = date('j', $startOfWeekTimestamp);
$endDay     = date('j', $endOfWeekTimestamp);
$startMonth = $namaBulanIndo[(int)date('n', $startOfWeekTimestamp)] ?? date('M', $startOfWeekTimestamp);
$endMonth   = $namaBulanIndo[(int)date('n', $endOfWeekTimestamp)] ?? date('M', $endOfWeekTimestamp);
$year       = date('Y', $startOfWeekTimestamp);
$weekRangeLabel = ($startMonth === $endMonth) ? "$startDay – $endDay $startMonth $year" : "$startDay $startMonth – $endDay $endMonth $year";

$days = [];
for ($i = 0; $i < 5; $i++) {
    $dayTs = strtotime("+$i days", $startOfWeekTimestamp);
    $dateKey = date('Y-m-d', $dayTs);
    $dayEng = date('l', $dayTs);
    $dayIndo = $namaHariIndo[$dayEng] ?? $dayEng;
    $dayNum = date('j', $dayTs);
    $monthIndo = $namaBulanIndo[(int)date('n', $dayTs)] ?? date('M', $dayTs);

    $days[$dateKey] = [
        'tanggal'     => $dateKey,
        'hari'        => $dayIndo,
        'label_tgl'   => "$dayNum $monthIndo",
        'is_today'    => ($dateKey === '2025-05-19' || $dateKey === date('Y-m-d')),
        'menu_utama'  => 'Belum diisi',
        'pelengkap'   => '-'
    ];
}

$datesList = array_keys($days);
$placeholders = implode(',', array_fill(0, count($datesList), '?'));
$stmt = $pdo->prepare("SELECT * FROM menu_makanan WHERE tanggal IN ($placeholders)");
$stmt->execute($datesList);
foreach ($stmt->fetchAll() as $sm) {
    if (isset($days[$sm['tanggal']])) {
        $days[$sm['tanggal']]['menu_utama'] = $sm['menu_utama'];
        $days[$sm['tanggal']]['pelengkap']  = $sm['pelengkap'];
    }
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
                <h1 class="page-title">Menu Makanan</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= tanggal_indonesia() ?></div>
                <div class="topbar-avatar"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content">
            <div style="margin-bottom: 1.75rem;">
                <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Menu Makanan Mingguan</h2>
                <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Jadwal makan siang sehat siswa SD Siber Labschool.</p>
            </div>

            <div class="week-selector-card">
                <div class="week-nav-wrap">
                    <a href="<?= base_url('guru/menu_makanan.php?offset=' . ($weekOffset - 1)) ?>" class="week-nav-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></a>
                    <div class="week-info-center">
                        <span class="week-info-label">Minggu aktif</span>
                        <div class="week-info-range"><?= e($weekRangeLabel) ?></div>
                    </div>
                    <a href="<?= base_url('guru/menu_makanan.php?offset=' . ($weekOffset + 1)) ?>" class="week-nav-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
                </div>
            </div>

            <div class="menu-grid-5">
                <?php foreach ($days as $item): ?>
                    <div class="menu-day-card <?= $item['is_today'] ? 'active-today' : '' ?>">
                        <div>
                            <div class="menu-day-header">
                                <div>
                                    <div class="menu-day-name"><?= e($item['hari']) ?></div>
                                    <div class="menu-day-date"><?= e($item['label_tgl']) ?></div>
                                </div>
                                <?php if ($item['is_today']): ?><div class="badge-today">Hari Ini</div><?php endif; ?>
                            </div>

                            <div class="menu-ill-box">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2v20"/><path d="m21 15-3-3-3 3"/><path d="M6 2v7a3 3 0 0 0 3 3v10"/><path d="M3 2v4a3 3 0 0 0 3 3"/></svg>
                            </div>

                            <div style="margin-bottom: 1.15rem;">
                                <span class="menu-field-label">Menu utama</span>
                                <div style="font-weight: 700; color: #2D231E; font-size: 0.95rem; background: #FAF7F2; padding: 10px 12px; border-radius: 10px; border: 1.5px solid #EFE8DF;">
                                    <?= e($item['menu_utama']) ?>
                                </div>
                            </div>

                            <div>
                                <span class="menu-field-label">Pelengkap & keterangan</span>
                                <div style="font-size: 0.88rem; color: #554C44; background: #FAF7F2; padding: 10px 12px; border-radius: 10px; border: 1.5px solid #EFE8DF; min-height: 80px; line-height: 1.5;">
                                    <?= e($item['pelengkap']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
