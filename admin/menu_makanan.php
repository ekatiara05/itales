<?php
/**
 * Modul Menu Makanan Mingguan - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi 100% sesuai Figma Revise iTales Menu Makanan Design
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

$pageTitle = 'Menu Makanan - iTales';
$activeMenu = 'menu_makanan';

$pdo = getDbConnection();
$errorMessage = '';

// Pengaturan Navigasi Minggu (Default minggu Mei 2025 sesuai desain Figma: 19 - 23 Mei 2025)
$baseMonday = '2025-05-19';
$weekOffset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$startOfWeekTimestamp = strtotime("$baseMonday +$weekOffset week");
$endOfWeekTimestamp   = strtotime("+4 days", $startOfWeekTimestamp);

// Array nama hari & bulan bahasa Indonesia
$namaHariIndo = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat'
];

$namaBulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$startDay   = date('j', $startOfWeekTimestamp);
$endDay     = date('j', $endOfWeekTimestamp);
$startMonth = $namaBulanIndo[(int)date('n', $startOfWeekTimestamp)] ?? date('M', $startOfWeekTimestamp);
$endMonth   = $namaBulanIndo[(int)date('n', $endOfWeekTimestamp)] ?? date('M', $endOfWeekTimestamp);
$year       = date('Y', $startOfWeekTimestamp);

if ($startMonth === $endMonth) {
    $weekRangeLabel = "$startDay – $endDay $startMonth $year";
} else {
    $weekRangeLabel = "$startDay $startMonth – $endDay $endMonth $year";
}

// Handle Simpan Menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_menu') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $menusPost = $_POST['menu'] ?? [];
        if (is_array($menusPost)) {
            $stmt = $pdo->prepare("
                INSERT INTO menu_makanan (hari, tanggal, menu_utama, pelengkap)
                VALUES (:hari, :tanggal, :menu_utama, :pelengkap)
                ON DUPLICATE KEY UPDATE 
                    menu_utama = VALUES(menu_utama),
                    pelengkap = VALUES(pelengkap)
            ");

            foreach ($menusPost as $date => $data) {
                $hariName = $data['hari'] ?? 'Senin';
                $menuUtama = trim($data['menu_utama'] ?? '');
                $pelengkap = trim($data['pelengkap'] ?? '');

                $stmt->execute([
                    ':hari'       => $hariName,
                    ':tanggal'    => $date,
                    ':menu_utama' => $menuUtama,
                    ':pelengkap'  => $pelengkap
                ]);
            }

            set_flash_message('success', 'Menu makan siang mingguan berhasil disimpan.');
            header('Location: ' . base_url('admin/menu_makanan.php?offset=' . $weekOffset));
            exit;
        }
    }
}

// 5 Hari Kerja (Senin - Jumat) untuk minggu yang aktif
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
        'menu_utama'  => '',
        'pelengkap'   => ''
    ];
}

// Ambil data menu yang tersimpan di database untuk 5 hari ini
$datesList = array_keys($days);
$placeholders = implode(',', array_fill(0, count($datesList), '?'));
$stmt = $pdo->prepare("SELECT * FROM menu_makanan WHERE tanggal IN ($placeholders)");
$stmt->execute($datesList);
$savedMenus = $stmt->fetchAll();

foreach ($savedMenus as $sm) {
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
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Menu Makan Siang Sehat SD Siber Labschool">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
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
                <h1 class="page-title">Menu Makanan</h1>
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
            <form method="POST" action="<?= base_url('admin/menu_makanan.php?offset=' . $weekOffset) ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_menu">

                <!-- Header Subtitle & Tombol Simpan Menu Sesuai Desain Figma -->
                <div class="page-header-action-wrap" style="margin-bottom: 1.75rem; align-items: flex-start;">
                    <div>
                        <h2 class="section-main-heading" style="font-size: 1.65rem; margin-bottom: 0.35rem;">Menu Makanan Mingguan</h2>
                        <p class="section-sub-heading" style="color: #7A6F66; font-size: 0.95rem;">Susun menu makan siang sehat untuk siswa.</p>
                    </div>

                    <div>
                        <button type="submit" class="btn-save-menu">
                            <!-- Ikon Centang Sesuai Figma -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Simpan Menu</span>
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

                <!-- Card Navigasi Minggu Aktif Sesuai Desain Figma -->
                <div class="week-selector-card">
                    <div class="week-nav-wrap">
                        <a href="<?= base_url('admin/menu_makanan.php?offset=' . ($weekOffset - 1)) ?>" class="week-nav-btn" title="Minggu Sebelumnya">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>

                        <div class="week-info-center">
                            <span class="week-info-label">Minggu aktif</span>
                            <div class="week-info-range"><?= e($weekRangeLabel) ?></div>
                        </div>

                        <a href="<?= base_url('admin/menu_makanan.php?offset=' . ($weekOffset + 1)) ?>" class="week-nav-btn" title="Minggu Berikutnya">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Grid 5 Kolom Menu Makan Siang Harian (Senin s/d Jumat) -->
                <div class="menu-grid-5">
                    <?php foreach ($days as $dateKey => $item): ?>
                        <div class="menu-day-card <?= $item['is_today'] ? 'active-today' : '' ?>">
                            <div>
                                <!-- Header Hari & Tanggal -->
                                <div class="menu-day-header">
                                    <div>
                                        <div class="menu-day-name"><?= e($item['hari']) ?></div>
                                        <div class="menu-day-date"><?= e($item['label_tgl']) ?></div>
                                    </div>

                                    <?php if ($item['is_today']): ?>
                                        <div class="badge-today">Hari Ini</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Ilustrasi Lembut Sendok & Garpu -->
                                <div class="menu-ill-box">
                                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 2v20"/>
                                        <path d="m21 15-3-3-3 3"/>
                                        <path d="M6 2v7a3 3 0 0 0 3 3v10"/>
                                        <path d="M3 2v4a3 3 0 0 0 3 3"/>
                                    </svg>
                                </div>

                                <input type="hidden" name="menu[<?= $dateKey ?>][hari]" value="<?= e($item['hari']) ?>">

                                <!-- Input Menu Utama -->
                                <div>
                                    <label class="menu-field-label">Menu utama</label>
                                    <input 
                                        type="text" 
                                        name="menu[<?= $dateKey ?>][menu_utama]" 
                                        class="menu-input-utama" 
                                        placeholder="Contoh: Nasi ayam teriyaki" 
                                        value="<?= e($item['menu_utama']) ?>"
                                    >
                                </div>

                                <!-- Input Pelengkap & Keterangan -->
                                <div>
                                    <label class="menu-field-label">Pelengkap & keterangan</label>
                                    <textarea 
                                        name="menu[<?= $dateKey ?>][pelengkap]" 
                                        class="menu-textarea-pelengkap" 
                                        placeholder="Contoh: Sayur bening • Buah • Air mineral"
                                    ><?= e($item['pelengkap']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
