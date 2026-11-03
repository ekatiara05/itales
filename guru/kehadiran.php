<?php
/**
 * Halaman Kehadiran Siswa - Guru iTales
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('guru');

$user = current_user();
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}
if ($initials === '') {
    $initials = 'G';
}

$pageTitle = 'Kehadiran Siswa - iTales';
$activeMenu = 'kehadiran';
$pdo = getDbConnection();
$errorMessage = '';
$showNewStudentModal = false;
$newStudentValues = [
    'nama_siswa' => '',
    'status' => 'Hadir',
    'waktu_datang' => '',
    'keterangan' => ''
];
$attendanceStatuses = ['Hadir', 'Izin', 'Sakit', 'Alpa'];

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

$requestedClass = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['nama_kelas'] ?? '')
    : ($_GET['nama_kelas'] ?? '');
$requestedClass = is_string($requestedClass) ? trim($requestedClass) : '';
$selectedClass = in_array($requestedClass, $daftarKelas, true)
    ? $requestedClass
    : (in_array($assignedClass, $daftarKelas, true) ? $assignedClass : ($daftarKelas[0] ?? ''));

$requestedDate = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['tanggal'] ?? '')
    : ($_GET['tanggal'] ?? date('Y-m-d'));
$selectedDate = is_string($requestedDate) ? trim($requestedDate) : '';
$validDate = DateTime::createFromFormat('!Y-m-d', $selectedDate);
$dateIsValid = $validDate !== false && $validDate->format('Y-m-d') === $selectedDate;
if (!$dateIsValid) {
    $selectedDate = date('Y-m-d');
    $validDate = DateTime::createFromFormat('!Y-m-d', $selectedDate);
    $errorMessage = 'Tanggal yang dipilih tidak valid. Tanggal hari ini ditampilkan.';
}

$stmtStudents = $pdo->prepare("
    SELECT id, nama_siswa, nama_kelas
    FROM siswa
    WHERE nama_kelas = :kelas AND status = 'aktif'
    ORDER BY nama_siswa ASC
");
$stmtStudents->execute([':kelas' => $selectedClass]);
$allStudents = $stmtStudents->fetchAll();
$studentsById = [];
foreach ($allStudents as $student) {
    $studentsById[(string)$student['id']] = $student;
}

$stmtAttendance = $pdo->prepare("
    SELECT s.id, s.nama_siswa, s.nama_kelas, attendance.keterangan, attendance.status,
           TIME_FORMAT(attendance.waktu_datang, '%H:%i') AS waktu_datang
    FROM kehadiran attendance
    INNER JOIN siswa s ON s.id = attendance.siswa_id
    WHERE attendance.tanggal = :tanggal AND attendance.nama_kelas = :kelas
          AND s.status = 'aktif' AND s.nama_kelas = :student_class
    ORDER BY s.nama_siswa ASC
");
$stmtAttendance->execute([
    ':tanggal' => $selectedDate,
    ':kelas' => $selectedClass,
    ':student_class' => $selectedClass
]);
$siswaList = $stmtAttendance->fetchAll();
$displayedStudentIds = array_fill_keys(array_map(
    static function ($student) {
        return (string)$student['id'];
    },
    $siswaList
), true);
$studentsAvailableToAdd = array_values(array_filter(
    $allStudents,
    static function ($student) use ($displayedStudentIds) {
        return !isset($displayedStudentIds[(string)$student['id']]);
    }
));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_student_attendance') {
    $showNewStudentModal = true;
    foreach ($newStudentValues as $field => $default) {
        $postedValue = $_POST[$field] ?? $default;
        $newStudentValues[$field] = is_string($postedValue) ? trim($postedValue) : $default;
    }

    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !verify_csrf_token($csrfToken)) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (!in_array($selectedClass, $daftarKelas, true)) {
        $errorMessage = 'Pilih kelas yang tersedia.';
    } elseif (!$dateIsValid) {
        $errorMessage = 'Tanggal kehadiran tidak valid.';
    } elseif ($newStudentValues['nama_siswa'] === '' || strlen($newStudentValues['nama_siswa']) > 100) {
        $errorMessage = 'Nama siswa wajib diisi dan maksimal 100 karakter.';
    } elseif (!in_array($newStudentValues['status'], $attendanceStatuses, true)) {
        $errorMessage = 'Pilih status kehadiran yang tersedia.';
    } elseif ($newStudentValues['waktu_datang'] !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $newStudentValues['waktu_datang'])) {
        $errorMessage = 'Format waktu datang tidak valid.';
    } elseif (strlen($newStudentValues['keterangan']) > 255) {
        $errorMessage = 'Keterangan maksimal 255 karakter.';
    } else {
        $duplicateStudent = $pdo->prepare("
            SELECT id FROM siswa
            WHERE nama_kelas = :kelas AND nama_siswa = :nama AND status = 'aktif'
            LIMIT 1
        ");
        $duplicateStudent->execute([
            ':kelas' => $selectedClass,
            ':nama' => $newStudentValues['nama_siswa']
        ]);
        if ($duplicateStudent->fetchColumn()) {
            $errorMessage = 'Nama siswa ini sudah terdaftar di kelas tersebut. Pilih dari daftar siswa yang tersedia.';
        } else {
            try {
                $pdo->beginTransaction();
                $insertStudent = $pdo->prepare("
                    INSERT INTO siswa (nama_siswa, nama_kelas, status)
                    VALUES (:nama, :kelas, 'aktif')
                ");
                $insertStudent->execute([
                    ':nama' => $newStudentValues['nama_siswa'],
                    ':kelas' => $selectedClass
                ]);
                $studentId = (int)$pdo->lastInsertId();

                $insertAttendance = $pdo->prepare("
                    INSERT INTO kehadiran
                        (tanggal, siswa_id, nama_kelas, status, waktu_datang, keterangan, dicatat_oleh)
                    VALUES
                        (:tanggal, :siswa_id, :kelas, :status, :waktu_datang, :keterangan, :dicatat_oleh)
                ");
                $insertAttendance->execute([
                    ':tanggal' => $selectedDate,
                    ':siswa_id' => $studentId,
                    ':kelas' => $selectedClass,
                    ':status' => $newStudentValues['status'],
                    ':waktu_datang' => $newStudentValues['status'] === 'Hadir' && $newStudentValues['waktu_datang'] !== ''
                        ? $newStudentValues['waktu_datang']
                        : null,
                    ':keterangan' => $newStudentValues['keterangan'] !== '' ? $newStudentValues['keterangan'] : null,
                    ':dicatat_oleh' => (int)$user['id']
                ]);
                $pdo->commit();

                set_flash_message('success', 'Siswa ' . $newStudentValues['nama_siswa'] . ' berhasil ditambahkan dan kehadirannya dicatat.');
                header('Location: ' . base_url('guru/kehadiran.php') . '?nama_kelas=' . rawurlencode($selectedClass) . '&tanggal=' . rawurlencode($selectedDate));
                exit;
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Gagal menambahkan siswa dari halaman kehadiran: ' . $exception->getMessage());
                $errorMessage = 'Siswa dan kehadirannya gagal disimpan. Periksa database dan coba kembali.';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $attendanceInput = $_POST['kehadiran'] ?? null;
    if (!is_string($csrfToken) || !verify_csrf_token($csrfToken)) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (!in_array($selectedClass, $daftarKelas, true)) {
        $errorMessage = 'Pilih kelas yang tersedia.';
    } elseif (!$dateIsValid) {
        $errorMessage = 'Tanggal kehadiran tidak valid.';
    } elseif (!is_array($attendanceInput) || $attendanceInput === []) {
        $errorMessage = 'Tidak ada data kehadiran untuk disimpan.';
    } else {
        $attendanceError = '';
        $validatedRows = [];
        foreach ($attendanceInput as $studentId => $values) {
            $studentId = (string)$studentId;
            if (!isset($studentsById[$studentId]) || !is_array($values)) {
                $attendanceError = 'Data siswa yang dikirim tidak sesuai dengan kelas terpilih.';
                break;
            }

            $status = $values['status'] ?? '';
            $arrivalTime = $values['waktu_datang'] ?? '';
            $note = $values['keterangan'] ?? '';
            if (!is_string($status) || !is_string($arrivalTime) || !is_string($note)) {
                $attendanceError = 'Data kehadiran tidak valid.';
                break;
            }
            $status = trim($status);
            $arrivalTime = trim($arrivalTime);
            $note = trim($note);
            if ($status !== '' && !in_array($status, $attendanceStatuses, true)) {
                $attendanceError = 'Pilih status kehadiran yang tersedia.';
                break;
            }
            if ($arrivalTime !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $arrivalTime)) {
                $attendanceError = 'Format waktu datang tidak valid.';
                break;
            }
            if (strlen($note) > 255) {
                $attendanceError = 'Keterangan maksimal 255 karakter.';
                break;
            }
            $validatedRows[] = [
                'student_id' => (int)$studentId,
                'status' => $status,
                'arrival_time' => $status === 'Hadir' && $arrivalTime !== '' ? $arrivalTime : null,
                'note' => $note !== '' ? $note : null
            ];
        }

        $markedRows = array_filter($validatedRows, static function ($row) {
            return $row['status'] !== '';
        });
        if ($attendanceError !== '') {
            $errorMessage = $attendanceError;
        } elseif ($markedRows === []) {
            $errorMessage = 'Pilih status kehadiran setidaknya untuk satu siswa.';
        } else {
            try {
                $pdo->beginTransaction();
                $deleteStatement = $pdo->prepare("
                    DELETE FROM kehadiran
                    WHERE tanggal = :tanggal AND siswa_id = :siswa_id AND nama_kelas = :kelas
                ");
                $saveStatement = $pdo->prepare("
                    INSERT INTO kehadiran
                        (tanggal, siswa_id, nama_kelas, status, waktu_datang, keterangan, dicatat_oleh)
                    VALUES
                        (:tanggal, :siswa_id, :kelas, :status, :waktu_datang, :keterangan, :dicatat_oleh)
                    ON DUPLICATE KEY UPDATE
                        nama_kelas = VALUES(nama_kelas),
                        status = VALUES(status),
                        waktu_datang = VALUES(waktu_datang),
                        keterangan = VALUES(keterangan),
                        dicatat_oleh = VALUES(dicatat_oleh)
                ");
                foreach ($validatedRows as $row) {
                    if ($row['status'] === '') {
                        $deleteStatement->execute([
                            ':tanggal' => $selectedDate,
                            ':siswa_id' => $row['student_id'],
                            ':kelas' => $selectedClass
                        ]);
                        continue;
                    }
                    $saveStatement->execute([
                        ':tanggal' => $selectedDate,
                        ':siswa_id' => $row['student_id'],
                        ':kelas' => $selectedClass,
                        ':status' => $row['status'],
                        ':waktu_datang' => $row['arrival_time'],
                        ':keterangan' => $row['note'],
                        ':dicatat_oleh' => (int)$user['id']
                    ]);
                }
                $pdo->commit();
                set_flash_message('success', 'Kehadiran ' . $selectedClass . ' berhasil disimpan.');
                header('Location: ' . base_url('guru/kehadiran.php') . '?nama_kelas=' . rawurlencode($selectedClass) . '&tanggal=' . rawurlencode($selectedDate));
                exit;
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Gagal menyimpan kehadiran guru: ' . $exception->getMessage());
                $errorMessage = 'Kehadiran gagal disimpan. Periksa database dan coba kembali.';
            }
        }
    }
}

$statusCounts = array_fill_keys($attendanceStatuses, 0);
$stmtStatusCounts = $pdo->prepare("
    SELECT status, COUNT(*) AS total
    FROM kehadiran
    WHERE nama_kelas = :kelas AND tanggal = :tanggal
    GROUP BY status
");
$stmtStatusCounts->execute([
    ':kelas' => $selectedClass,
    ':tanggal' => $selectedDate
]);
foreach ($stmtStatusCounts->fetchAll() as $statusCount) {
    $statusCounts[$statusCount['status']] = (int)$statusCount['total'];
}

$flashMessages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="theme-color" content="#805538">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/guru-kehadiran.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/guru-kehadiran.css') ?>">
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / Guru</div>
                <h1 class="page-title">Kehadiran</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= e(tanggal_indonesia()) ?></div>
                <div class="topbar-avatar" title="<?= e($user['nama_lengkap']) ?>"><?= e($initials) ?></div>
            </div>
        </header>

        <div class="dashboard-content attendance-content">
            <div class="attendance-intro">
                <div>
                    <h2>Pantau Kehadiran Siswa</h2>
                    <p>Catat kehadiran <?= e($selectedClass) ?> untuk <?= e(tanggal_indonesia($validDate->getTimestamp())) ?>.</p>
                </div>
                <form method="GET" action="<?= base_url('guru/kehadiran.php') ?>" class="attendance-filter">
                    <label class="attendance-sr-only" for="filter-kelas">Pilih kelas</label>
                    <select id="filter-kelas" name="nama_kelas" required <?= empty($daftarKelas) ? 'disabled' : '' ?>>
                        <?php foreach ($daftarKelas as $namaKelas): ?>
                            <option value="<?= e($namaKelas) ?>" <?= $selectedClass === $namaKelas ? 'selected' : '' ?>><?= e($namaKelas) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="attendance-sr-only" for="filter-tanggal">Pilih tanggal</label>
                    <input type="date" id="filter-tanggal" name="tanggal" value="<?= e($selectedDate) ?>" required>
                    <button class="attendance-button attendance-button-secondary" type="submit">Tampilkan</button>
                </form>
            </div>

            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= e($type === 'success' ? 'success' : 'danger') ?>" role="status"><?= e($message) ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <section class="attendance-stat-grid" aria-label="Ringkasan kehadiran">
                <?php foreach ($attendanceStatuses as $status): ?>
                    <article class="attendance-stat-card">
                        <strong><?= e($statusCounts[$status]) ?></strong>
                        <span><?= e($status) ?></span>
                    </article>
                <?php endforeach; ?>
            </section>

            <form method="POST" action="<?= base_url('guru/kehadiran.php') ?>" class="attendance-table-card">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="nama_kelas" value="<?= e($selectedClass) ?>">
                <input type="hidden" name="tanggal" value="<?= e($selectedDate) ?>">
                <div class="attendance-add-row">
                    <label class="attendance-sr-only" for="attendance-student-picker">Pilih siswa untuk ditambahkan</label>
                    <select id="attendance-student-picker" <?= empty($studentsAvailableToAdd) ? 'disabled' : '' ?>>
                        <option value="">
                            <?= empty($allStudents)
                                ? 'Belum ada siswa terdaftar di kelas ini'
                                : (empty($studentsAvailableToAdd) ? 'Semua siswa kelas ini sudah ditambahkan' : '-- Pilih siswa --') ?>
                        </option>
                        <?php foreach ($studentsAvailableToAdd as $student): ?>
                            <option value="<?= (int)$student['id'] ?>"><?= e($student['nama_siswa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="attendance-button attendance-button-secondary" id="add-attendance-student" type="button" <?= empty($studentsAvailableToAdd) ? 'disabled' : '' ?>>
                        Tambahkan siswa terdaftar
                    </button>
                    <button class="attendance-button attendance-button-primary" id="open-new-student-modal" type="button">
                        + Tambah Kehadiran
                    </button>
                </div>
                <div class="attendance-table-scroll">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th scope="col">Nama Siswa</th>
                                <th scope="col">Status</th>
                                <th scope="col">Waktu Datang</th>
                                <th scope="col">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-rows">
                            <?php foreach ($siswaList as $siswa): ?>
                                <?php
                                    $studentId = (int)$siswa['id'];
                                    $studentInitials = '';
                                    foreach (array_slice(explode(' ', trim($siswa['nama_siswa'])), 0, 2) as $part) {
                                        $studentInitials .= strtoupper(substr($part, 0, 1));
                                    }
                                ?>
                                <tr data-student-id="<?= $studentId ?>">
                                    <th scope="row">
                                        <span class="attendance-student">
                                            <span class="attendance-student-avatar"><?= e($studentInitials) ?></span>
                                            <span><?= e($siswa['nama_siswa']) ?></span>
                                        </span>
                                    </th>
                                    <td>
                                        <label class="attendance-sr-only" for="status-<?= $studentId ?>">Status <?= e($siswa['nama_siswa']) ?></label>
                                        <select id="status-<?= $studentId ?>" name="kehadiran[<?= $studentId ?>][status]" class="attendance-status-select">
                                            <?php foreach ($attendanceStatuses as $status): ?>
                                                <option value="<?= e($status) ?>" <?= $siswa['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <label class="attendance-sr-only" for="time-<?= $studentId ?>">Waktu datang <?= e($siswa['nama_siswa']) ?></label>
                                        <input id="time-<?= $studentId ?>" type="time" name="kehadiran[<?= $studentId ?>][waktu_datang]" value="<?= e($siswa['waktu_datang'] ?? '') ?>" class="attendance-time-input">
                                    </td>
                                    <td>
                                        <label class="attendance-sr-only" for="note-<?= $studentId ?>">Keterangan <?= e($siswa['nama_siswa']) ?></label>
                                        <input id="note-<?= $studentId ?>" type="text" name="kehadiran[<?= $studentId ?>][keterangan]" value="<?= e($siswa['keterangan'] ?? '') ?>" maxlength="255" class="attendance-note-input" placeholder="Tambahkan keterangan">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="attendance-table-footer" id="attendance-footer" <?= empty($siswaList) ? 'hidden' : '' ?>>
                    <span>Perubahan status disimpan untuk tanggal dan kelas yang dipilih.</span>
                    <button class="attendance-button attendance-button-primary" type="submit">Simpan Kehadiran</button>
                </div>
                <p class="attendance-empty" id="attendance-empty" <?= !empty($siswaList) ? 'hidden' : '' ?>>
                    <?php if (empty($allStudents)): ?>
                        Belum ada siswa aktif di kelas ini. Minta Admin menambahkan siswa ke Data Siswa terlebih dahulu.
                    <?php else: ?>
                        Belum ada siswa yang ditambahkan untuk dicatat. Pilih siswa, lalu klik <strong>Tambah Kehadiran</strong>.
                    <?php endif; ?>
                </p>
            </form>
        </div>
    </main>
</div>
<div class="attendance-modal-overlay<?= $showNewStudentModal ? ' show' : '' ?>" id="new-student-modal" <?= $showNewStudentModal ? '' : 'hidden' ?>>
    <section class="attendance-modal" role="dialog" aria-modal="true" aria-labelledby="new-student-title">
        <div class="attendance-modal-heading">
            <div>
                <h2 id="new-student-title">Tambah Kehadiran Siswa</h2>
                <p>Masukkan siswa baru dan catat kehadirannya untuk <?= e($selectedClass) ?>.</p>
            </div>
            <button type="button" class="attendance-modal-close" id="close-new-student-modal" aria-label="Tutup">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('guru/kehadiran.php') ?>" class="attendance-modal-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_student_attendance">
            <input type="hidden" name="nama_kelas" value="<?= e($selectedClass) ?>">
            <input type="hidden" name="tanggal" value="<?= e($selectedDate) ?>">
            <label for="new-student-name">Nama siswa</label>
            <input id="new-student-name" name="nama_siswa" type="text" maxlength="100" value="<?= e($newStudentValues['nama_siswa']) ?>" placeholder="Masukkan nama lengkap siswa" required>
            <label for="new-student-status">Status kehadiran</label>
            <select id="new-student-status" name="status" required>
                <?php foreach ($attendanceStatuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= $newStudentValues['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="new-student-time">Waktu datang <span>(opsional)</span></label>
            <input id="new-student-time" name="waktu_datang" type="time" value="<?= e($newStudentValues['waktu_datang']) ?>">
            <label for="new-student-note">Keterangan <span>(opsional)</span></label>
            <input id="new-student-note" name="keterangan" type="text" maxlength="255" value="<?= e($newStudentValues['keterangan']) ?>" placeholder="Tambahkan keterangan jika perlu">
            <div class="attendance-modal-actions">
                <button type="button" class="attendance-button attendance-button-secondary" id="cancel-new-student-modal">Batal</button>
                <button type="submit" class="attendance-button attendance-button-primary">Simpan Kehadiran</button>
            </div>
        </form>
    </section>
</div>
<script>
(() => {
    const picker = document.getElementById('attendance-student-picker');
    const addButton = document.getElementById('add-attendance-student');
    const newStudentButton = document.getElementById('open-new-student-modal');
    const rows = document.getElementById('attendance-rows');
    const footer = document.getElementById('attendance-footer');
    const emptyMessage = document.getElementById('attendance-empty');
    const newStudentModal = document.getElementById('new-student-modal');

    if (!picker || !addButton || !newStudentButton || !rows || !footer || !emptyMessage || !newStudentModal) {
        return;
    }

    const closeModal = () => {
        newStudentModal.classList.remove('show');
        newStudentModal.hidden = true;
    };

    newStudentButton.addEventListener('click', () => {
        newStudentModal.hidden = false;
        newStudentModal.classList.add('show');
        document.getElementById('new-student-name').focus();
    });
    document.getElementById('close-new-student-modal').addEventListener('click', closeModal);
    document.getElementById('cancel-new-student-modal').addEventListener('click', closeModal);
    newStudentModal.addEventListener('click', (event) => {
        if (event.target === newStudentModal) {
            closeModal();
        }
    });

    const updateListState = () => {
        const hasRows = rows.querySelectorAll('tr[data-student-id]').length > 0;
        footer.hidden = !hasRows;
        emptyMessage.hidden = hasRows;
    };

    addButton.addEventListener('click', () => {
        const option = picker.selectedOptions[0];
        const studentId = picker.value;
        if (!option || !studentId) {
            picker.focus();
            return;
        }

        const studentName = option.textContent.trim();
        const initials = studentName.split(/\s+/).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('');
        const row = document.createElement('tr');
        row.dataset.studentId = studentId;

        const nameCell = document.createElement('th');
        nameCell.scope = 'row';
        const student = document.createElement('span');
        student.className = 'attendance-student';
        const avatar = document.createElement('span');
        avatar.className = 'attendance-student-avatar';
        avatar.textContent = initials;
        const name = document.createElement('span');
        name.textContent = studentName;
        student.append(avatar, name);
        nameCell.append(student);

        const statusCell = document.createElement('td');
        const statusLabel = document.createElement('label');
        statusLabel.className = 'attendance-sr-only';
        statusLabel.htmlFor = `status-${studentId}`;
        statusLabel.textContent = `Status ${studentName}`;
        const status = document.createElement('select');
        status.id = `status-${studentId}`;
        status.name = `kehadiran[${studentId}][status]`;
        status.className = 'attendance-status-select';
        status.required = true;
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Pilih status';
        placeholder.selected = true;
        status.append(placeholder);
        ['Hadir', 'Izin', 'Sakit', 'Alpa'].forEach((value) => {
            const statusOption = document.createElement('option');
            statusOption.value = value;
            statusOption.textContent = value;
            status.append(statusOption);
        });
        statusCell.append(statusLabel, status);

        const timeCell = document.createElement('td');
        const timeLabel = document.createElement('label');
        timeLabel.className = 'attendance-sr-only';
        timeLabel.htmlFor = `time-${studentId}`;
        timeLabel.textContent = `Waktu datang ${studentName}`;
        const time = document.createElement('input');
        time.id = `time-${studentId}`;
        time.type = 'time';
        time.name = `kehadiran[${studentId}][waktu_datang]`;
        time.className = 'attendance-time-input';
        timeCell.append(timeLabel, time);

        const noteCell = document.createElement('td');
        const noteLabel = document.createElement('label');
        noteLabel.className = 'attendance-sr-only';
        noteLabel.htmlFor = `note-${studentId}`;
        noteLabel.textContent = `Keterangan ${studentName}`;
        const note = document.createElement('input');
        note.id = `note-${studentId}`;
        note.type = 'text';
        note.name = `kehadiran[${studentId}][keterangan]`;
        note.maxLength = 255;
        note.className = 'attendance-note-input';
        note.placeholder = 'Tambahkan keterangan';
        noteCell.append(noteLabel, note);

        row.append(nameCell, statusCell, timeCell, noteCell);
        rows.append(row);
        option.remove();
        picker.selectedIndex = 0;
        updateListState();
        status.focus();
    });

    updateListState();
})();
</script>
</body>
</html>
