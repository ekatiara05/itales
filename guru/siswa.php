<?php
/**
 * Modul Daftar Siswa Kelas (Wali Kelas) - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Lokasi: guru/siswa.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi hak akses: Hanya Guru
require_role('guru');

$user = current_user();

// Inisial avatar guru
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'G';
}

$pageTitle = 'Daftar Siswa Kelas - iTales';
$activeMenu = 'siswa';

$pdo = getDbConnection();

// Deteksi Wali Kelas dari akun; hanya kelas 1-3 yang ditampilkan.
$waliKelas = '';
if (!empty($user['tanggung_jawab'])) {
    if (preg_match('/(Kelas\s+[0-9A-Za-z]+)/i', $user['tanggung_jawab'], $matches)) {
        $requestedClass = preg_replace('/\s+/', ' ', trim($matches[1]));
        if (in_array($requestedClass, ['Kelas 1', 'Kelas 2', 'Kelas 3'], true)) {
            $waliKelas = $requestedClass;
        }
    }
}

// Pencarian Siswa
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM siswa WHERE nama_kelas = :kelas";
$params = [':kelas' => $waliKelas];

if (!empty($search)) {
    $sql .= " AND (nama_siswa LIKE :s OR nis LIKE :s OR nama_orang_tua LIKE :s)";
    $params[':s'] = "%$search%";
}

$sql .= " ORDER BY nama_siswa ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswaList = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <meta name="description" content="Daftar Siswa Tanggung Jawab Wali Kelas iTales">
    <meta name="theme-color" content="#805538">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= time() ?>">
</head>
<body class="dashboard-body">

<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / Guru / <?= e($waliKelas) ?></div>
                <h1 class="page-title">Tanggung Jawab Siswa <?= e($waliKelas) ?></h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <?= tanggal_indonesia() ?>
                </div>

                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?> (<?= e($waliKelas) ?>)">
                    <?= e($initials) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="page-header-action-wrap" style="margin-bottom: 1.75rem;">
                <div>
                    <h2 class="section-main-heading">Tanggung Jawab Siswa <?= e($waliKelas) ?></h2>
                    <p class="section-sub-heading">Total <?= count($siswaList) ?> siswa terdaftar dalam rombel binaan Anda.</p>
                </div>

                <a href="<?= base_url('guru/tambah_logbook.php') ?>" class="btn-add-primary" style="text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>+ Tambah Logbook</span>
                </a>
            </div>

            <!-- Card Utama Data Siswa -->
            <div class="data-card">
                <!-- Filter & Search Bar -->
                <form method="GET" action="<?= base_url('guru/siswa.php') ?>" class="filter-bar">
                    <div class="search-input-wrap">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Cari siswa berdasarkan nama atau NIS..." 
                            value="<?= e($search) ?>"
                        >
                    </div>

                    <?php if (!empty($search)): ?>
                        <a href="<?= base_url('guru/siswa.php') ?>" class="btn-table-detail" style="text-decoration: none;">
                            Reset Pencarian
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Tabel Data Siswa -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Lengkap Siswa</th>
                                <th>Kelas</th>
                                <th>Orang Tua / Wali</th>
                                <th>Status</th>
                                <th style="text-align: right;">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaList)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2.5rem; color: #8C8075;">
                                        <?= $waliKelas !== '' ? 'Tidak ada data siswa ditemukan untuk ' . e($waliKelas) . '.' : 'Akun ini belum ditetapkan ke Kelas 1, 2, atau 3.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($siswaList as $s): 
                                    $sWords = explode(' ', trim($s['nama_siswa']));
                                    $sInit = '';
                                    foreach (array_slice($sWords, 0, 2) as $sw) {
                                        $sInit .= strtoupper(substr($sw, 0, 1));
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight: 700; color: #6F4E37;">
                                                <?= e($s['nis'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="teacher-cell">
                                                <div class="teacher-avatar"><?= e($sInit) ?></div>
                                                <span class="teacher-name"><?= e($s['nama_siswa']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: #4A3E36;">
                                                <?= e($s['nama_kelas']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="cell-muted">
                                                <?= e($s['nama_orang_tua'] ?? 'Belum terhubung') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge-active">
                                                <span class="dot-active">•</span> Aktif
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="<?= base_url('guru/tambah_logbook.php?siswa=' . urlencode($s['nama_siswa'])) ?>" class="btn-table-detail" style="text-decoration: none;" title="Buat logbook untuk siswa ini">
                                                + Buat Logbook
                                            </a>
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

</body>
</html>
