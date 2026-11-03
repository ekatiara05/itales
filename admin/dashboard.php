<?php
/**
 * Dashboard Utama Admin - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi sesuai antarmuka Figma iTales
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Pastikan hanya pengguna yang login yang dapat mengakses
require_login();

$user = current_user();

// Ambil jumlah logbook per kelas dan total seluruh catatan.
$pdo = getDbConnection();
$logbookSummaryStmt = $pdo->query("
    SELECT
        COUNT(CASE WHEN nama_kelas = 'Kelas 1' THEN 1 END) AS kelas_1,
        COUNT(CASE WHEN nama_kelas = 'Kelas 2' THEN 1 END) AS kelas_2,
        COUNT(CASE WHEN nama_kelas = 'Kelas 3' THEN 1 END) AS kelas_3,
        COUNT(*) AS total
    FROM logbook
");
$logbookSummary = $logbookSummaryStmt->fetch();

// Inisial avatar
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'AS';
}

$pageTitle = 'Dashboard - iTales';
$activeMenu = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Dashboard Sistem Informasi Logbook Harian SD Siber Labschool">
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

    <!-- Konten Utama Dashboard -->
    <main class="main-content-wrapper">
        <!-- Topbar Header -->
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Dashboard</h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <?= tanggal_indonesia() ?>
                </div>

                <!-- Notifikasi Bell -->
                <button type="button" class="topbar-icon-btn" aria-label="Notifikasi">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    <span class="notification-badge-dot"></span>
                </button>

                <!-- Avatar User -->
                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?>">
                    <?= e($initials) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Banner Utama Ringkasan Sekolah -->
            <section class="welcome-banner">
                <div class="welcome-banner-left">
                    <span class="banner-tag">RINGKASAN SEKOLAH</span>
                    <h2 class="banner-title">Selamat datang, <?= e($user['nama_lengkap']) ?></h2>
                    <p class="banner-subtitle">Pantau aktivitas dan kelola kebutuhan iTales hari ini.</p>
                </div>

                <div class="welcome-banner-right">
                    <div class="banner-date-card">
                        <div class="date-card-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="date-card-text">
                            <span class="date-label">Hari ini</span>
                            <span class="date-value"><?= date('d M Y') ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ringkasan Logbook -->
            <section class="logbook-summary-panel">
                <h3 class="panel-tag">RINGKASAN LOGBOOK</h3>

                <div class="logbook-summary-grid">
                    <div class="logbook-card">
                        <div class="logbook-card-icon icon-green">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Logbook Kelas 1</span>
                            <div class="logbook-card-number"><?= number_format((int)$logbookSummary['kelas_1'], 0, ',', '.') ?></div>
                            <span class="logbook-card-sub">Catatan tercatat</span>
                        </div>
                    </div>

                    <div class="logbook-card">
                        <div class="logbook-card-icon icon-brown">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Logbook Kelas 2</span>
                            <div class="logbook-card-number"><?= number_format((int)$logbookSummary['kelas_2'], 0, ',', '.') ?></div>
                            <span class="logbook-card-sub">Catatan tercatat</span>
                        </div>
                    </div>

                    <div class="logbook-card">
                        <div class="logbook-card-icon icon-green">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Logbook Kelas 3</span>
                            <div class="logbook-card-number"><?= number_format((int)$logbookSummary['kelas_3'], 0, ',', '.') ?></div>
                            <span class="logbook-card-sub">Catatan tercatat</span>
                        </div>
                    </div>

                    <div class="logbook-card logbook-card-total">
                        <div class="logbook-card-icon icon-white">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H8v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                                <path d="M8 2h4v20H8"/>
                                <path d="M12 2h3.5A2.5 2.5 0 0 1 18 4.5v15a2.5 2.5 0 0 1-2.5 2.5H12"/>
                                <path d="m18 6 3 13"/>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Total Logbook</span>
                            <div class="logbook-card-number"><?= number_format((int)$logbookSummary['total'], 0, ',', '.') ?></div>
                            <span class="logbook-card-sub">Semua kelas</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bagian Akses Cepat -->
            <section class="quick-access-section">
                <div class="section-heading-wrap">
                    <h3 class="section-title">Akses Cepat</h3>
                    <p class="section-desc">Kelola kebutuhan utama dengan lebih ringkas.</p>
                </div>

                <div class="quick-access-grid">
                    <!-- Aksi 1: Tambah Data Siswa -->
                    <a href="<?= base_url('admin/siswa.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Tambah Data Siswa</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Aksi 2: Tambah Akun Guru -->
                    <a href="<?= base_url('admin/guru.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Tambah Akun Guru</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Aksi 3: Tambah Akun Orang Tua -->
                    <a href="<?= base_url('admin/orang_tua.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Tambah Akun Orang Tua</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Aksi 4: Lihat Semua Logbook -->
                    <a href="<?= base_url('admin/logbook.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Lihat Semua Logbook</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Aksi 5: Kelola Kegiatan -->
                    <a href="<?= base_url('admin/kegiatan.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Kelola Kegiatan</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Aksi 6: Kelola Menu Makanan -->
                    <a href="<?= base_url('admin/menu_makanan.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 2v20"/>
                                    <path d="m21 15-3-3-3 3"/>
                                    <path d="M6 2v7a3 3 0 0 0 3 3v10"/>
                                    <path d="M3 2v4a3 3 0 0 0 3 3"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Kelola Menu Makanan</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
