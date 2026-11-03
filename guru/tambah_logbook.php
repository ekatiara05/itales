<?php
/**
 * Modul Tambah Logbook Siswa - Guru (Wali Kelas)
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Lokasi: guru/tambah_logbook.php
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

$pageTitle = 'Tambah Logbook Siswa - iTales';
$activeMenu = 'tambah_logbook';

$pdo = getDbConnection();
$errorMessage = '';

// Guru dapat memilih salah satu kelas aktif dan hanya siswa aktif di kelas tersebut.
$daftarKelas = $pdo->query("
    SELECT nama_kelas
    FROM kelas
    WHERE status = 'aktif' AND nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
    ORDER BY nama_kelas ASC
")->fetchAll(PDO::FETCH_COLUMN);
$assignedClass = '';
if (preg_match('/Kelas\s+[0-9A-Za-z]+/i', $user['tanggung_jawab'] ?? '', $matches)) {
    $assignedClass = $matches[0];
}
$requestedClass = $_GET['kelas'] ?? '';
$requestedClass = is_string($requestedClass) ? trim($requestedClass) : '';
$selectedClass = in_array($requestedClass, $daftarKelas, true)
    ? $requestedClass
    : (in_array($assignedClass, $daftarKelas, true) ? $assignedClass : ($daftarKelas[0] ?? ''));

$stmtSiswa = $pdo->query("
    SELECT s.nama_siswa, s.nama_kelas
    FROM siswa s
    INNER JOIN kelas k ON k.nama_kelas = s.nama_kelas AND k.status = 'aktif'
    WHERE s.status = 'aktif' AND s.nama_kelas IN ('Kelas 1', 'Kelas 2', 'Kelas 3')
    ORDER BY s.nama_kelas ASC, s.nama_siswa ASC
");
$daftarSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);
$selectedSiswa = $_GET['siswa'] ?? '';
$selectedSiswa = is_string($selectedSiswa) ? trim($selectedSiswa) : '';
foreach ($daftarSiswa as $siswaRow) {
    if ($siswaRow['nama_siswa'] === $selectedSiswa) {
        $selectedClass = $siswaRow['nama_kelas'];
        break;
    }
}
$tanggalValue = date('Y-m-d');
$formValues = [
    'nama_siswa' => $selectedSiswa,
    'pembelajaran' => '',
    'praktik_ibadah' => '',
    'ekskul' => '',
    'catatan_anak' => '',
    'dokumentasi' => ''
];

// Handle Submit Tambah Logbook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !verify_csrf_token($csrfToken)) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        foreach ($formValues as $field => $value) {
            $postedValue = $_POST[$field] ?? $value;
            $formValues[$field] = is_string($postedValue) ? trim($postedValue) : '';
        }
        $postedDate = $_POST['tanggal'] ?? '';
        $tanggalValue = is_string($postedDate) ? trim($postedDate) : '';
        $postedClass = $_POST['nama_kelas'] ?? '';
        $selectedClass = is_string($postedClass) ? trim($postedClass) : '';
        $siswa = $formValues['nama_siswa'];
        $activitySections = [
            'Pembelajaran' => $formValues['pembelajaran'],
            'Praktik Ibadah' => $formValues['praktik_ibadah'],
            'Ekskul' => $formValues['ekskul'],
            'Catatan Anak' => $formValues['catatan_anak'],
            'Dokumentasi' => $formValues['dokumentasi']
        ];
        $completedSections = array_filter($activitySections, static function ($value) {
            return $value !== '';
        });
        $firstSection = array_key_first($completedSections);
        $ringkasan = '';
        if ($firstSection !== null) {
            $fullSummary = $firstSection . ': ' . $completedSections[$firstSection];
            $ringkasan = preg_match('/^.{0,220}/us', $fullSummary, $summaryMatch) === 1
                ? $summaryMatch[0]
                : $firstSection;
        }
        $detail = implode("\n\n", array_map(static function ($section, $value) {
            return $section . ":\n" . $value;
        }, array_keys($completedSections), array_values($completedSections)));

        $studentIsInClass = false;
        foreach ($daftarSiswa as $siswaRow) {
            if ($siswaRow['nama_kelas'] === $selectedClass && $siswaRow['nama_siswa'] === $siswa) {
                $studentIsInClass = true;
                break;
            }
        }
        $validDate = DateTime::createFromFormat('Y-m-d', $tanggalValue);

        $pendingUploads = [];
        $mediaError = '';
        $totalUploadSize = 0;
        $maxTotalUploadSize = 35 * 1024 * 1024;
        $upload = $_FILES['dokumentasi_media'] ?? null;
        $allowedMediaTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov'
        ];
        if ($upload !== null) {
            if (!is_array($upload['name'] ?? null) || !is_array($upload['tmp_name'] ?? null) || !is_array($upload['error'] ?? null) || !is_array($upload['size'] ?? null)) {
                $mediaError = 'Data unggahan dokumentasi tidak valid.';
            } elseif (count($upload['name']) > 10) {
                $mediaError = 'Pilih maksimal 10 file foto atau video.';
            } else {
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($fileInfo === false) {
                    $mediaError = 'Pemeriksaan tipe file tidak tersedia di server.';
                } else {
                    foreach ($upload['name'] as $index => $originalName) {
                        $uploadError = $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                        if ($uploadError === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        if ($uploadError !== UPLOAD_ERR_OK) {
                            $mediaError = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                                ? 'Ukuran file melebihi batas unggahan server.'
                                : 'Salah satu file dokumentasi gagal diunggah. Silakan coba lagi.';
                            break;
                        }
                        $temporaryPath = $upload['tmp_name'][$index] ?? '';
                        $fileSize = $upload['size'][$index] ?? 0;
                        if (!is_string($temporaryPath) || !is_int($fileSize) || $fileSize < 1 || $fileSize > 20 * 1024 * 1024 || !is_uploaded_file($temporaryPath)) {
                            $mediaError = 'Setiap file dokumentasi harus berukuran maksimal 20 MB.';
                            break;
                        }
                        $totalUploadSize += $fileSize;
                        if ($totalUploadSize > $maxTotalUploadSize) {
                            $mediaError = 'Total ukuran semua file dokumentasi maksimal 35 MB.';
                            break;
                        }
                        $mimeType = finfo_file($fileInfo, $temporaryPath);
                        if (!is_string($mimeType) || !isset($allowedMediaTypes[$mimeType])) {
                            $mediaError = 'Gunakan foto JPG, PNG, GIF, WEBP atau video MP4, WEBM, MOV.';
                            break;
                        }
                        if (str_starts_with($mimeType, 'image/') && @getimagesize($temporaryPath) === false) {
                            $mediaError = 'Salah satu file foto tidak dapat dibaca.';
                            break;
                        }
                        $pendingUploads[] = [
                            'temporary_path' => $temporaryPath,
                            'extension' => $allowedMediaTypes[$mimeType]
                        ];
                    }
                    finfo_close($fileInfo);
                }
            }
        }

        if ($selectedClass === '' || !in_array($selectedClass, $daftarKelas, true)) {
            $errorMessage = 'Pilih kelas yang tersedia.';
        } elseif (!$studentIsInClass) {
            $errorMessage = 'Pilih siswa aktif yang terdaftar di kelas tersebut.';
        } elseif (!$validDate || $validDate->format('Y-m-d') !== $tanggalValue) {
            $errorMessage = 'Tanggal observasi tidak valid.';
        } elseif ($mediaError !== '') {
            $errorMessage = $mediaError;
        } elseif (empty($completedSections) && empty($pendingUploads)) {
            $errorMessage = 'Isi setidaknya satu catatan kegiatan atau lampirkan foto/video.';
        } else {
            $uploadDirectory = __DIR__ . '/../uploads/logbook';
            $savedFiles = [];
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                $errorMessage = 'Folder penyimpanan dokumentasi tidak dapat dibuat.';
            } else {
                foreach ($pendingUploads as $pendingUpload) {
                    $fileName = bin2hex(random_bytes(16)) . '.' . $pendingUpload['extension'];
                    $targetPath = $uploadDirectory . '/' . $fileName;
                    if (!move_uploaded_file($pendingUpload['temporary_path'], $targetPath)) {
                        $errorMessage = 'File dokumentasi gagal disimpan. Silakan coba lagi.';
                        break;
                    }
                    $savedFiles[] = [
                        'path' => $targetPath,
                        'url' => base_url('uploads/logbook/' . $fileName)
                    ];
                }
            }

            if ($errorMessage !== '') {
                foreach ($savedFiles as $savedFile) {
                    if (is_file($savedFile['path'])) {
                        unlink($savedFile['path']);
                    }
                }
            } else {
                $firstSection = array_key_first($completedSections);
                if ($firstSection === null && !empty($savedFiles)) {
                    $firstSection = 'Dokumentasi';
                    $ringkasan = 'Dokumentasi logbook';
                }
                foreach ($savedFiles as $savedFile) {
                    $detail .= ($detail !== '' ? "\n\n" : '') . 'Lampiran: ' . $savedFile['url'];
                }

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO logbook (tanggal, nama_siswa, nama_kelas, nama_guru, ringkasan_kegiatan, detail_kegiatan, status)
                        VALUES (:t, :s, :k, :g, :r, :d, :st)
                    ");
                    $stmt->execute([
                        ':t'  => $tanggalValue,
                        ':s'  => $siswa,
                        ':k'  => $selectedClass,
                        ':g'  => $user['nama_lengkap'],
                        ':r'  => $ringkasan,
                        ':d'  => $detail,
                        ':st' => 'Dipublikasikan'
                    ]);
                } catch (PDOException $exception) {
                    foreach ($savedFiles as $savedFile) {
                        if (is_file($savedFile['path'])) {
                            unlink($savedFile['path']);
                        }
                    }
                    error_log('Gagal menyimpan logbook guru: ' . $exception->getMessage());
                    $errorMessage = 'Logbook gagal disimpan. Silakan coba lagi.';
                }

                if ($errorMessage === '') {
                    set_flash_message('success', 'Catatan logbook untuk ' . $siswa . ' berhasil disimpan!');
                    header('Location: ' . base_url('guru/dashboard.php'));
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <meta name="description" content="Form Tambah Logbook Siswa iTales">
    <meta name="theme-color" content="#805538">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/guru-logbook-form.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/guru-logbook-form.css') ?>">
</head>
<body class="dashboard-body guru-logbook-form-page">

<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / Guru / Logbook</div>
                <h1 class="page-title">Tambah Logbook</h1>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-date">
                    <?= tanggal_indonesia() ?>
                </div>

                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?> (Guru)">
                    <?= e($initials) ?>
                </div>
            </div>
        </header>

        <div class="dashboard-content guru-logbook-content">
            <div class="logbook-form-heading">
                <div>
                    <p class="logbook-form-eyebrow">CERITA BELAJAR SISWA</p>
                    <h2>Tambah Logbook</h2>
                    <p>Lengkapi catatan harian siswa dalam beberapa langkah sederhana.</p>
                </div>
                <a href="<?= base_url('guru/dashboard.php') ?>" class="logbook-back-link">← Kembali ke Dashboard</a>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger logbook-form-alert" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <?php if (empty($daftarKelas)): ?>
                <div class="alert alert-warning logbook-form-alert" role="alert">
                    Belum ada kelas aktif. Minta Admin mengaktifkan kelas terlebih dahulu.
                </div>
            <?php elseif (empty($daftarSiswa)): ?>
                <div class="alert alert-warning logbook-form-alert" role="alert">
                    Belum ada siswa aktif. Minta Admin menambahkan siswa ke kelas yang tersedia terlebih dahulu.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="<?= base_url('guru/tambah_logbook.php') ?>" class="logbook-wizard" id="logbook-wizard">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <aside class="wizard-sidebar">
                    <div class="wizard-school-mark"><span>iT</span><strong>iTales</strong></div>
                    <p class="wizard-sidebar-label">LANGKAH PENGISIAN</p>
                    <ol class="wizard-steps" aria-label="Langkah pengisian logbook">
                        <li><button type="button" class="wizard-step is-active" data-step-target="0" aria-current="step"><span>1</span><span>Informasi</span></button></li>
                        <li><button type="button" class="wizard-step" data-step-target="1"><span>2</span><span>Pembelajaran</span></button></li>
                        <li><button type="button" class="wizard-step" data-step-target="2"><span>3</span><span>Praktik Ibadah</span></button></li>
                        <li><button type="button" class="wizard-step" data-step-target="3"><span>4</span><span>Ekskul</span></button></li>
                        <li><button type="button" class="wizard-step" data-step-target="4"><span>5</span><span>Catatan Anak</span></button></li>
                        <li><button type="button" class="wizard-step" data-step-target="5"><span>6</span><span>Dokumentasi</span></button></li>
                    </ol>
                    <p class="wizard-sidebar-note">Catatan Anda membantu orang tua mengikuti cerita belajar anak.</p>
                </aside>

                <div class="wizard-main">
                    <div class="wizard-main-heading">
                        <span class="wizard-progress-label" id="wizard-progress-label">LANGKAH 1 DARI 6</span>
                        <h3 id="wizard-panel-title">Informasi</h3>
                        <p id="wizard-panel-description">Tentukan tanggal dan siswa yang akan dibuatkan catatan.</p>
                    </div>

                    <div class="wizard-panels">
                        <section class="wizard-panel is-active" data-step="0" aria-labelledby="wizard-panel-title">
                            <div class="wizard-field-grid">
                                <div class="wizard-field">
                                    <label for="tanggal">Hari / Tanggal</label>
                                    <input type="date" id="tanggal" name="tanggal" value="<?= e($tanggalValue) ?>" aria-required="true">
                                </div>
                                <div class="wizard-field">
                                    <label for="nama_kelas">Kelas</label>
                                    <select id="nama_kelas" name="nama_kelas" required <?= empty($daftarKelas) ? 'disabled' : '' ?>>
                                        <option value="">Pilih kelas</option>
                                        <?php foreach ($daftarKelas as $namaKelas): ?>
                                            <option value="<?= e($namaKelas) ?>" <?= $selectedClass === $namaKelas ? 'selected' : '' ?>><?= e($namaKelas) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wizard-field wizard-field-wide">
                                    <label for="nama_siswa">Nama Siswa</label>
                                    <select id="nama_siswa" name="nama_siswa" required <?= empty($daftarSiswa) ? 'disabled' : '' ?>>
                                        <option value="">Pilih siswa</option>
                                        <?php foreach ($daftarSiswa as $siswaRow): ?>
                                            <option value="<?= e($siswaRow['nama_siswa']) ?>" data-class="<?= e($siswaRow['nama_kelas']) ?>" <?= $formValues['nama_siswa'] === $siswaRow['nama_siswa'] && $selectedClass === $siswaRow['nama_kelas'] ? 'selected' : '' ?>><?= e($siswaRow['nama_siswa']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="wizard-panel" data-step="1" aria-labelledby="wizard-panel-title" hidden>
                            <div class="wizard-field">
                                <label for="pembelajaran">Kegiatan Pembelajaran</label>
                                <textarea id="pembelajaran" name="pembelajaran" rows="7" placeholder="Contoh: materi yang dipelajari, partisipasi siswa, dan hasil kegiatan..."><?= e($formValues['pembelajaran']) ?></textarea>
                                <small>Ceritakan pengalaman belajar siswa hari ini.</small>
                            </div>
                        </section>

                        <section class="wizard-panel" data-step="2" aria-labelledby="wizard-panel-title" hidden>
                            <div class="wizard-field">
                                <label for="praktik_ibadah">Praktik Ibadah</label>
                                <textarea id="praktik_ibadah" name="praktik_ibadah" rows="7" placeholder="Catat kegiatan pembiasaan ibadah dan partisipasi siswa..."><?= e($formValues['praktik_ibadah']) ?></textarea>
                                <small>Bagian ini boleh dikosongkan jika tidak ada kegiatan.</small>
                            </div>
                        </section>

                        <section class="wizard-panel" data-step="3" aria-labelledby="wizard-panel-title" hidden>
                            <div class="wizard-field">
                                <label for="ekskul">Ekstrakurikuler</label>
                                <textarea id="ekskul" name="ekskul" rows="7" placeholder="Tuliskan kegiatan minat dan bakat yang diikuti siswa..."><?= e($formValues['ekskul']) ?></textarea>
                                <small>Bagian ini boleh dikosongkan jika tidak ada kegiatan.</small>
                            </div>
                        </section>

                        <section class="wizard-panel" data-step="4" aria-labelledby="wizard-panel-title" hidden>
                            <div class="wizard-field">
                                <label for="catatan_anak">Catatan Anak</label>
                                <textarea id="catatan_anak" name="catatan_anak" rows="7" placeholder="Tambahkan perkembangan, apresiasi, atau hal yang perlu diperhatikan..."><?= e($formValues['catatan_anak']) ?></textarea>
                                <small>Catatan yang baik membantu keluarga memahami perkembangan anak.</small>
                            </div>
                        </section>

                        <section class="wizard-panel" data-step="5" aria-labelledby="wizard-panel-title" hidden>
                            <div class="wizard-field-grid">
                                <div class="wizard-field wizard-field-wide">
                                    <label for="dokumentasi_media">Foto dan Video</label>
                                    <input type="file" id="dokumentasi_media" name="dokumentasi_media[]" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime" multiple>
                                    <small>Pilih hingga 10 foto atau video sekaligus. Format foto JPG, PNG, GIF, WEBP dan video MP4, WEBM, MOV; maksimal 20 MB per file dan 35 MB total.</small>
                                    <div class="wizard-media-preview" id="wizard-media-preview" aria-live="polite"></div>
                                </div>
                                <div class="wizard-field wizard-field-wide">
                                    <label for="dokumentasi">Keterangan Dokumentasi (opsional)</label>
                                    <textarea id="dokumentasi" name="dokumentasi" rows="4" placeholder="Tambahkan keterangan untuk foto atau video..."><?= e($formValues['dokumentasi']) ?></textarea>
                                </div>
                            </div>
                            <div class="wizard-review" id="wizard-review">
                                <strong>Ringkasan sebelum disimpan</strong>
                                <span>Pilih siswa dan isi setidaknya satu catatan kegiatan.</span>
                            </div>
                        </section>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="wizard-button wizard-button-secondary" id="wizard-previous" hidden>Kembali</button>
                        <span class="wizard-footer-spacer"></span>
                        <button type="button" class="wizard-button wizard-button-primary" id="wizard-next">Selanjutnya <span aria-hidden="true">→</span></button>
                        <button type="submit" class="wizard-button wizard-button-primary" id="wizard-submit" hidden>Simpan Logbook <span aria-hidden="true">✓</span></button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="<?= base_url('assets/js/guru-logbook-wizard.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/guru-logbook-wizard.js') ?>"></script>
</body>
</html>
