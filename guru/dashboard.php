<?php
/**
 * Dashboard Guru (Wali Kelas) - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi sesuai Figma & kebutuhan Wali Kelas
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Hanya Guru
require_role('guru');

$user = current_user();

// Inisial Nama untuk Avatar
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'AP';
}

$pageTitle = 'Dashboard Guru - iTales';
$activeMenu = 'dashboard';

$pdo = getDbConnection();

// Ambil kelas yang ditugaskan kepada guru dari profil akunnya.
$waliKelas = '';
if (preg_match('/Kelas\s+[0-9A-Za-z]+/i', $user['tanggung_jawab'] ?? '', $matches)) {
    $requestedClass = preg_replace('/\s+/', ' ', trim($matches[0]));
    if (in_array($requestedClass, ['Kelas 1', 'Kelas 2', 'Kelas 3'], true)) {
        $waliKelas = $requestedClass;
    }
}
$labelKelas = $waliKelas !== '' ? $waliKelas : 'Kelas belum ditentukan';

$logbookScope = "nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3') AND nama_guru = :guru";
$logbookParams = [':guru' => $user['nama_lengkap']];
if ($waliKelas !== '') {
    $logbookScope = "nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3') AND (nama_kelas = :kelas OR nama_guru = :guru)";
    $logbookParams[':kelas'] = $waliKelas;
}

$stmtSiswaCount = $pdo->prepare('SELECT COUNT(*) FROM siswa WHERE nama_kelas = :kelas');
$stmtSiswaCount->execute([':kelas' => $waliKelas]);
$totalSiswa = $waliKelas !== '' ? (int)$stmtSiswaCount->fetchColumn() : 0;

$stmtTotalLogbook = $pdo->prepare("SELECT COUNT(*) FROM logbook WHERE $logbookScope");
$stmtTotalLogbook->execute($logbookParams);
$totalLogbook = (int)$stmtTotalLogbook->fetchColumn();

$stmtStatusCounts = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM logbook
    WHERE $logbookScope
    GROUP BY status
");
$stmtStatusCounts->execute($logbookParams);
$statusCounts = $stmtStatusCounts->fetchAll(PDO::FETCH_KEY_PAIR);
$totalDraft = (int)($statusCounts['Draft'] ?? 0);
$totalPub = (int)($statusCounts['Dipublikasikan'] ?? 0);

$stmtRecent = $pdo->prepare("
    SELECT id, tanggal, nama_siswa, nama_kelas, ringkasan_kegiatan, detail_kegiatan, status
    FROM logbook
    WHERE $logbookScope
    ORDER BY tanggal DESC, id DESC
    LIMIT 6
");
$stmtRecent->execute($logbookParams);
$recentLogbooks = $stmtRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <meta name="description" content="Dashboard Guru Wali Kelas iTales SD Siber Labschool">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        /* Gaya Khusus Dashboard Guru Sesuai Figma */
        .guru-welcome-card {
            background-color: #EBF3ED;
            border: 1.5px solid #D5E5D8;
            border-radius: 20px;
            padding: 2rem 2.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .guru-welcome-left {
            max-width: 520px;
        }

        .guru-date-tag {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #3C6040;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }

        .guru-welcome-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: #2D231E;
            margin: 0 0 0.4rem 0;
            letter-spacing: -0.5px;
        }

        .guru-welcome-subtitle {
            font-size: 0.98rem;
            color: #6A5C52;
            margin: 0 0 1.5rem 0;
            line-height: 1.5;
        }

        .btn-add-logbook-hero {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #805538;
            color: #FFFFFF !important;
            text-decoration: none;
            padding: 0.75rem 1.65rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(128, 85, 56, 0.25);
            transition: all 0.25s ease;
        }

        .btn-add-logbook-hero:hover {
            background-color: #5F3F2C;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(128, 85, 56, 0.35);
        }

        .guru-quote-box {
            background: #FFFFFF;
            border: 1px solid #E5EBE6;
            border-radius: 16px;
            padding: 1.25rem 1.65rem;
            max-width: 380px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 4px 16px rgba(60, 96, 64, 0.05);
        }

        .guru-quote-sparkle {
            color: #3C6040;
            font-size: 1.25rem;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .guru-quote-text {
            font-size: 0.88rem;
            font-style: italic;
            color: #4A3E36;
            line-height: 1.55;
            margin: 0;
        }

        /* 4 Stat Cards Row */
        .guru-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .guru-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .guru-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .guru-stat-card {
            background-color: #FFFFFF;
            border: 1.5px solid #EFEAE3;
            border-radius: 16px;
            padding: 1.35rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(95, 63, 44, 0.03);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .guru-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(95, 63, 44, 0.06);
            border-color: #E2D7CC;
        }

        .guru-stat-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-wrap-beige {
            background-color: #FAF4ED;
            color: #805538;
        }

        .icon-wrap-green {
            background-color: #E6EEE5;
            color: #3C6040;
        }

        .icon-wrap-orange {
            background-color: #FEF3EB;
            color: #B56228;
        }

        .guru-stat-info {
            display: flex;
            flex-direction: column;
        }

        .guru-stat-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #8C7F75;
            margin-bottom: 2px;
        }

        .guru-stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #2D231E;
            line-height: 1.15;
            margin-bottom: 2px;
        }

        .guru-stat-sub {
            font-size: 0.78rem;
            color: #9C8F85;
            font-weight: 500;
        }

        /* Logbook Terbaru (Tampilan Penuh) */
        .logbook-recent-card {
            background-color: #FFFFFF;
            border: 1.5px solid #EFEAE3;
            border-radius: 20px;
            padding: 1.75rem 2rem;
            box-shadow: 0 4px 18px rgba(95, 63, 44, 0.03);
        }

        .recent-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #F2EDE8;
        }

        .recent-header-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #2D231E;
            margin: 0 0 2px 0;
        }

        .recent-header-sub {
            font-size: 0.86rem;
            color: #8C7F75;
            margin: 0;
        }

        .recent-link-all {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #805538;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .recent-link-all:hover {
            color: #5F3F2C;
            text-decoration: underline;
        }

        /* List Items */
        .logbook-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 0;
            border-bottom: 1px solid #F6F1EA;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .logbook-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .student-meta-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 280px;
        }

        .student-avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #FAF4ED;
            color: #805538;
            border: 1.5px solid #EFE8DF;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .student-name-box {
            display: flex;
            flex-direction: column;
        }

        .student-name-text {
            font-size: 1rem;
            font-weight: 700;
            color: #2D231E;
            margin-bottom: 2px;
        }

        .student-class-date {
            font-size: 0.8rem;
            color: #8C7F75;
        }

        .logbook-activity-center {
            flex: 1;
            min-width: 250px;
        }

        .activity-summary-text {
            font-size: 0.92rem;
            font-weight: 600;
            color: #4A3E36;
            margin-bottom: 2px;
        }

        .activity-desc-snippet {
            font-size: 0.82rem;
            color: #8C7F75;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 420px;
        }

        .logbook-action-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/css/guru-dashboard.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/guru-dashboard.css') ?>">
</head>
<body class="dashboard-body guru-dashboard">

<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <!-- Topbar Sesuai Figma -->
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / Guru</div>
                <h1 class="page-title">Dashboard</h1>
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

                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?> (<?= e($labelKelas) ?>)">
                    <?= e($initials) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            <!-- 1. Hero Welcome Card Sesuai Figma -->
            <section class="guru-welcome-card">
                <div class="guru-welcome-left">
                    <span class="guru-date-tag">RINGKASAN KELAS</span>
                    <h2 class="guru-welcome-title">Selamat datang, Ibu Eka</h2>
                    <p class="guru-welcome-subtitle">Pantau aktivitas dan kelola logbook <?= e($labelKelas) ?> hari ini.</p>
                    <a href="<?= base_url('guru/tambah_logbook.php') ?>" class="btn-add-logbook-hero" id="btn-add-logbook-top">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Tambah Logbook</span>
                    </a>
                </div>
                <div class="guru-date-card">
                    <span class="guru-date-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                            <path d="M16 2v4M8 2v4M3 10h18"></path>
                        </svg>
                    </span>
                    <span><small>Hari ini</small><strong><?= date('d M Y') ?></strong></span>
                </div>
            </section>

            <section class="logbook-summary-panel" aria-labelledby="guru-summary-title">
                <h2 class="panel-tag" id="guru-summary-title">RINGKASAN LOGBOOK</h2>
                <div class="logbook-summary-grid guru-summary-grid">
                    <article class="logbook-card">
                        <div class="logbook-card-icon icon-green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                    </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Jumlah Siswa</span>
                            <span class="logbook-card-number"><?= $totalSiswa ?></span>
                            <span class="logbook-card-sub"><?= e($labelKelas) ?></span>
                        </div>
                    </article>
                    <article class="logbook-card">
                        <div class="logbook-card-icon icon-brown">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Masih Draft</span>
                            <span class="logbook-card-number"><?= $totalDraft ?></span>
                            <span class="logbook-card-sub">Perlu dilengkapi</span>
                        </div>
                    </article>
                    <article class="logbook-card">
                        <div class="logbook-card-icon icon-green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Dipublikasikan</span>
                            <span class="logbook-card-number"><?= $totalPub ?></span>
                            <span class="logbook-card-sub">Dapat dilihat orang tua</span>
                        </div>
                    </article>
                    <article class="logbook-card logbook-card-total">
                        <div class="logbook-card-icon icon-white">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <div class="logbook-card-info">
                            <span class="logbook-card-title">Total Logbook</span>
                            <span class="logbook-card-number"><?= $totalLogbook ?></span>
                            <span class="logbook-card-sub">Semua catatan kelas</span>
                        </div>
                    </article>
                </div>
            </section>

            <!-- 3. Logbook Terbaru (Tampilan Penuh) -->
            <section class="logbook-recent-card">
                <div class="recent-header-row">
                    <div>
                        <h3 class="recent-header-title">Logbook Terbaru</h3>
                        <p class="recent-header-sub">Logbook terbaru untuk <?= e($labelKelas) ?></p>
                    </div>
                    <a href="<?= base_url('guru/logbook.php') ?>" class="recent-link-all">
                        <span>Lihat riwayat</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>

                <div class="recent-list-container">
                    <?php if (empty($recentLogbooks)): ?>
                        <div class="recent-empty">Belum ada logbook untuk <?= e($labelKelas) ?>. Tambahkan catatan pertama Anda.</div>
                    <?php endif; ?>
                    <?php foreach ($recentLogbooks as $lb): 
                        // Inisial Siswa
                        $sWords = explode(' ', trim($lb['nama_siswa']));
                        $sInit = '';
                        foreach (array_slice($sWords, 0, 2) as $sw) {
                            $sInit .= strtoupper(substr($sw, 0, 1));
                        }
                        
                        // Badge Status Class
                        $statusLower = strtolower($lb['status']);
                        $statusClass = 'badge-status-draft';
                        if (strpos($statusLower, 'publikasi') !== false) {
                            $statusClass = 'badge-status-dipublikasikan';
                        } elseif (strpos($statusLower, 'periksa') !== false || strpos($statusLower, 'tinjau') !== false) {
                            $statusClass = 'badge-status-pemeriksaan';
                        } elseif (strpos($statusLower, 'revisi') !== false) {
                            $statusClass = 'badge-status-revisi';
                        }
                    ?>
                        <div class="logbook-item-row">
                            <div class="student-meta-left">
                                <div class="student-avatar-circle"><?= e($sInit) ?></div>
                                <div class="student-name-box">
                                    <span class="student-name-text"><?= e($lb['nama_siswa']) ?></span>
                                    <span class="student-class-date"><?= e($lb['nama_kelas']) ?> • <?= date('d M Y', strtotime($lb['tanggal'])) ?></span>
                                </div>
                            </div>

                            <div class="logbook-activity-center">
                                <div class="activity-summary-text"><?= e($lb['ringkasan_kegiatan']) ?></div>
                                <div class="activity-desc-snippet"><?= e($lb['detail_kegiatan'] ?? '-') ?></div>
                            </div>

                            <div class="logbook-action-right">
                                <span class="badge-status <?= $statusClass ?>">
                                    <?= e($lb['status']) ?>
                                </span>
                                <a href="<?= base_url('guru/logbook.php?id=' . $lb['id']) ?>" class="btn-table-detail" style="text-decoration: none;">
                                    Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>
    </main>
</div>

</body>
</html>
