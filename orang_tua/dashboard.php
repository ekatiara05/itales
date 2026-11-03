<?php
/**
 * Dashboard Orang Tua - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Orang Tua
require_role('orang_tua');

$user = current_user();

$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'OT';
}

$pageTitle = 'Dashboard Orang Tua - iTales';
$activeMenu = 'dashboard';

$pdo = getDbConnection();

// Ambil info siswa yang terhubung dengan akun orang tua ini
$stmtUser = $pdo->prepare("SELECT siswa_terhubung FROM users WHERE id = :id");
$stmtUser->execute([':id' => $user['id']]);
$namaAnak = $stmtUser->fetchColumn();
if (empty($namaAnak)) {
    // Coba cari dari tabel siswa berdasarkan nama orang tua
    $stmtSiswa = $pdo->prepare("SELECT nama_siswa, nama_kelas FROM siswa WHERE nama_orang_tua LIKE :no LIMIT 1");
    $stmtSiswa->execute([':no' => '%' . $user['nama_lengkap'] . '%']);
    $siswaRow = $stmtSiswa->fetch();
    $namaAnak = $siswaRow['nama_siswa'] ?? 'Siswa Labschool';
    $kelasAnak = $siswaRow['nama_kelas'] ?? 'Kelas 1';
} else {
    $stmtSiswa = $pdo->prepare("SELECT nama_kelas FROM siswa WHERE nama_siswa = :ns LIMIT 1");
    $stmtSiswa->execute([':ns' => $namaAnak]);
    $kelasAnak = $stmtSiswa->fetchColumn() ?: 'Kelas 1';
}

// Logbook terbaru anak
$stmtLb = $pdo->prepare("SELECT * FROM logbook WHERE (nama_siswa LIKE :ns OR :ns2 = '') AND status = 'Dipublikasikan' ORDER BY tanggal DESC LIMIT 1");
$stmtLb->execute([':ns' => '%' . $namaAnak . '%', ':ns2' => $namaAnak]);
$logbookTerbaru = $stmtLb->fetch();

$stmtAgenda = $pdo->prepare("SELECT id, judul_kegiatan, tanggal, waktu FROM kegiatan WHERE status = 'Mendatang' ORDER BY tanggal ASC, id ASC LIMIT 4");
$stmtAgenda->execute();
$agendaMendatang = $stmtAgenda->fetchAll();

$bulanAgenda = [
    1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MEI', 6 => 'JUN',
    7 => 'JUL', 8 => 'AGU', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
];
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
</head>
<body class="dashboard-body parent-dashboard">

<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / <?= e($user['role_label']) ?></div>
                <h1 class="page-title">Dashboard Orang Tua</h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date"><?= tanggal_indonesia() ?></div>
                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?>"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Welcome Banner -->
            <section class="welcome-banner">
                <div class="welcome-banner-left">
                    <span class="banner-tag">PORTAL ORANG TUA</span>
                    <h2 class="banner-title">Selamat datang, <?= e($user['nama_lengkap']) ?></h2>
                    <p class="banner-subtitle">Pantau aktivitas, logbook belajar, dan jadwal sekolah ananda <strong><?= e($namaAnak) ?></strong> (<?= e($kelasAnak) ?>).</p>
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

            <section class="parent-dashboard-grid" aria-label="Ringkasan dashboard">
                <article class="data-card parent-logbook-card">
                    <div class="parent-card-heading">
                        <div>
                            <span class="parent-card-eyebrow">LOGBOOK TERBARU</span>
                            <h2 class="parent-card-title">Cerita belajar ananda</h2>
                        </div>
                        <?php if ($logbookTerbaru): ?>
                            <span class="badge-status badge-status-dipublikasikan">Dipublikasikan</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($logbookTerbaru): ?>
                        <h3 class="parent-logbook-summary"><?= e($logbookTerbaru['ringkasan_kegiatan']) ?></h3>
                        <p class="parent-logbook-detail"><?= e($logbookTerbaru['detail_kegiatan'] ?: 'Belum ada detail kegiatan.') ?></p>
                        <div class="parent-logbook-meta">
                            <span><?= e(date('d M Y', strtotime($logbookTerbaru['tanggal']))) ?></span>
                            <span>Guru: <strong><?= e($logbookTerbaru['nama_guru']) ?></strong></span>
                        </div>
                    <?php else: ?>
                        <p class="parent-empty-state">Belum ada logbook yang dipublikasikan untuk ananda.</p>
                    <?php endif; ?>

                    <a href="<?= base_url('orang_tua/logbook.php') ?>" class="parent-section-link">
                        Lihat logbook lengkap
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </article>

                <aside class="data-card parent-agenda-card">
                    <div class="parent-card-heading">
                        <div>
                            <h2 class="parent-card-title">Kegiatan mendatang</h2>
                            <p class="parent-card-description">Agenda sekolah yang akan datang</p>
                        </div>
                    </div>

                    <?php if (!empty($agendaMendatang)): ?>
                        <ul class="parent-agenda-list">
                            <?php foreach ($agendaMendatang as $agenda): ?>
                                <?php
                                $tanggalAgenda = strtotime($agenda['tanggal']);
                                $nomorBulanAgenda = (int) date('n', $tanggalAgenda);
                                ?>
                                <li class="parent-agenda-item">
                                    <div class="parent-agenda-date">
                                        <span><?= e(date('d', $tanggalAgenda)) ?></span>
                                        <small><?= e($bulanAgenda[$nomorBulanAgenda] ?? '') ?></small>
                                    </div>
                                    <div class="parent-agenda-info">
                                        <strong><?= e($agenda['judul_kegiatan']) ?></strong>
                                        <span><?= e($agenda['waktu']) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="parent-empty-state">Belum ada agenda sekolah mendatang.</p>
                    <?php endif; ?>

                    <a href="<?= base_url('orang_tua/kegiatan.php') ?>" class="parent-section-link">
                        Lihat semua kegiatan
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </aside>
            </section>

            <section class="quick-access-section parent-quick-access">
                <div class="section-heading-wrap">
                    <h2 class="section-title">Jelajahi informasi sekolah</h2>
                    <p class="section-desc">Pantau perkembangan ananda secara transparan.</p>
                </div>

                <div class="quick-access-grid">
                    <a href="<?= base_url('orang_tua/logbook.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Lihat Logbook Harian Anak</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </a>

                    <a href="<?= base_url('orang_tua/kegiatan.php') ?>" class="quick-card">
                        <div class="quick-card-left">
                            <div class="quick-icon stat-green">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <span class="quick-card-title">Agenda & Kegiatan Sekolah</span>
                        </div>
                        <div class="quick-chevron">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </div>
                    </a>

                </div>
            </section>
        </div>
    </main>
</div>

</body>
</html>
