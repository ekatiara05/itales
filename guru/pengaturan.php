<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('guru');

$user = current_user();
$pdo = getDbConnection();
$pageTitle = 'Pengaturan Akun - iTales';
$activeMenu = 'pengaturan';
$errorMessage = '';

$profileStmt = $pdo->prepare("
    SELECT nama_lengkap, email, no_telepon
    FROM users
    WHERE id = :id AND role = 'guru'
");
$profileStmt->execute([':id' => $user['id']]);
$profile = $profileStmt->fetch();
if (!$profile) {
    http_response_code(404);
    exit('Akun guru tidak ditemukan.');
}

$initials = '';
foreach (array_slice(explode(' ', trim($profile['nama_lengkap'])), 0, 2) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}
$initials = $initials !== '' ? $initials : 'G';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($action) || !is_string($csrfToken) || !verify_csrf_token($csrfToken)) {
        $errorMessage = 'Sesi form telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif ($action === 'save_profile') {
        $name = $_POST['nama_lengkap'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['no_telepon'] ?? '';
        if (!is_string($name) || !is_string($email) || !is_string($phone)) {
            $errorMessage = 'Data profil tidak valid.';
        } else {
            $name = trim($name);
            $email = trim($email);
            $phone = trim($phone);
            if ($name === '' || strlen($name) > 100) {
                $errorMessage = 'Nama wajib diisi dan maksimal 100 karakter.';
            } elseif ($email !== '' && (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $errorMessage = 'Alamat email tidak valid.';
            } elseif (strlen($phone) > 30) {
                $errorMessage = 'Nomor telepon maksimal 30 karakter.';
            } else {
                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
                $emailCheck->execute([':email' => $email !== '' ? $email : null, ':id' => $user['id']]);
                if ($email !== '' && $emailCheck->fetchColumn()) {
                    $errorMessage = 'Email tersebut sudah digunakan akun lain.';
                } else {
                    $updateProfile = $pdo->prepare("
                        UPDATE users
                        SET nama_lengkap = :nama, email = :email, no_telepon = :telepon
                        WHERE id = :id AND role = 'guru'
                    ");
                    $updateProfile->execute([
                        ':nama' => $name,
                        ':email' => $email !== '' ? $email : null,
                        ':telepon' => $phone !== '' ? $phone : null,
                        ':id' => $user['id']
                    ]);
                    $_SESSION['nama_lengkap'] = $name;
                    $_SESSION['email'] = $email;
                    set_flash_message('success', 'Profil berhasil diperbarui.');
                    header('Location: ' . base_url('guru/pengaturan.php'));
                    exit;
                }
            }
            $profile['nama_lengkap'] = $name;
            $profile['email'] = $email;
            $profile['no_telepon'] = $phone;
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['password_lama'] ?? '';
        $newPassword = $_POST['password_baru'] ?? '';
        $confirmation = $_POST['password_konfirmasi'] ?? '';
        if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmation)) {
            $errorMessage = 'Data kata sandi tidak valid.';
        } elseif ($currentPassword === '' || $newPassword === '' || $confirmation === '') {
            $errorMessage = 'Semua kolom kata sandi wajib diisi.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'Kata sandi baru minimal 6 karakter.';
        } elseif ($newPassword !== $confirmation) {
            $errorMessage = 'Konfirmasi kata sandi baru tidak cocok.';
        } else {
            $passwordStmt = $pdo->prepare("SELECT password FROM users WHERE id = :id AND role = 'guru'");
            $passwordStmt->execute([':id' => $user['id']]);
            $currentHash = $passwordStmt->fetchColumn();
            if (!is_string($currentHash) || !password_verify($currentPassword, $currentHash)) {
                $errorMessage = 'Kata sandi saat ini tidak tepat.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updatePassword = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id AND role = 'guru'");
                $updatePassword->execute([':password' => $newHash, ':id' => $user['id']]);
                set_flash_message('success', 'Kata sandi berhasil diperbarui.');
                header('Location: ' . base_url('guru/pengaturan.php'));
                exit;
            }
        }
    } else {
        $errorMessage = 'Permintaan pengaturan tidak dikenali.';
    }
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
    <link rel="stylesheet" href="<?= base_url('assets/css/guru-pengaturan.css') ?>">
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content-wrapper">
        <header class="topbar-header">
            <div class="topbar-left">
                <div class="breadcrumb-text">iTales / Guru</div>
                <h1 class="page-title">Pengaturan</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-date"><?= e(tanggal_indonesia()) ?></div>
                <div class="topbar-avatar" title="<?= e($profile['nama_lengkap']) ?>"><?= e($initials) ?></div>
            </div>
        </header>
        <div class="dashboard-content teacher-settings">
            <p class="teacher-settings-intro">Atur informasi profil dan keamanan akun Guru.</p>
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <section class="teacher-settings-card">
                <div class="teacher-settings-card-heading">
                    <span class="teacher-settings-icon" aria-hidden="true">01</span>
                    <div><h2>Profil Guru</h2><p>Perbarui nama dan informasi kontak akun.</p></div>
                </div>
                <form method="POST" action="<?= base_url('guru/pengaturan.php') ?>" class="teacher-settings-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_profile">
                    <label>Nama lengkap
                        <input type="text" name="nama_lengkap" maxlength="100" value="<?= e($profile['nama_lengkap']) ?>" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" maxlength="100" value="<?= e($profile['email']) ?>" placeholder="nama@sekolah.sch.id">
                    </label>
                    <label>Nomor telepon
                        <input type="tel" name="no_telepon" maxlength="30" value="<?= e($profile['no_telepon']) ?>" placeholder="Nomor yang bisa dihubungi">
                    </label>
                    <button class="btn btn-primary" type="submit">Simpan Profil</button>
                </form>
            </section>

            <section class="teacher-settings-card">
                <div class="teacher-settings-card-heading">
                    <span class="teacher-settings-icon" aria-hidden="true">02</span>
                    <div><h2>Keamanan Akun</h2><p>Gunakan kata sandi yang hanya diketahui oleh Anda.</p></div>
                </div>
                <form method="POST" action="<?= base_url('guru/pengaturan.php') ?>" class="teacher-settings-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_password">
                    <label>Kata sandi saat ini
                        <input type="password" name="password_lama" autocomplete="current-password" required>
                    </label>
                    <label>Kata sandi baru
                        <input type="password" name="password_baru" minlength="6" autocomplete="new-password" required>
                    </label>
                    <label>Konfirmasi kata sandi baru
                        <input type="password" name="password_konfirmasi" minlength="6" autocomplete="new-password" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Ubah Kata Sandi</button>
                </form>
            </section>
        </div>
    </main>
</div>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
