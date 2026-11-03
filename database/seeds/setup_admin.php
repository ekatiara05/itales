<?php
/**
 * Inisialisasi Akun Admin Pertama - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * 
 * Mekanisme Keamanan:
 * 1. Hanya dapat digunakan jika BELUM ada akun dengan role 'admin' di database.
 * 2. Begitu akun Admin pertama dibuat, halaman ini otomatis terkunci selamanya.
 * 3. Dilindungi validasi CSRF, verifikasi konfirmasi sandi, dan enkripsi password_hash().
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$errorMessage   = '';
$successMessage = '';
$isLocked       = false;
$dbConnected    = true;
$dbErrorDetails = '';

// Nilai input form awal
$usernameValue = '';
$namaValue     = '';
$emailValue    = '';

try {
    $pdo = getDbConnection();

    // Periksa apakah tabel users sudah ada dan apakah sudah ada akun Admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = (int)$stmt->fetchColumn();

    if ($adminCount > 0) {
        $isLocked = true;
    }
} catch (PDOException $e) {
    $dbConnected = false;
    $dbErrorDetails = $e->getMessage();
}

// Proses pembuatan akun jika belum terkunci dan metode POST
if ($dbConnected && !$isLocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken       = $_POST['csrf_token'] ?? '';
    $username        = trim($_POST['username'] ?? '');
    $namaLengkap     = trim($_POST['nama_lengkap'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $usernameValue = $username;
    $namaValue     = $namaLengkap;
    $emailValue    = $email;

    // Validasi token CSRF
    if (!verify_csrf_token($csrfToken)) {
        $errorMessage = 'Sesi form Anda telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (empty($username) || empty($namaLengkap) || empty($password) || empty($passwordConfirm)) {
        $errorMessage = 'Semua kolom bertanda bintang (*) wajib diisi.';
    } elseif (strlen($username) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errorMessage = 'Username minimal 4 karakter dan hanya boleh berisi huruf, angka, serta underscore (_).';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Kata sandi minimal 8 karakter demi keamanan sistem.';
    } elseif ($password !== $passwordConfirm) {
        $errorMessage = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
    } else {
        try {
            // Periksa ulang apakah akun admin sudah terisi (pencegahan race condition)
            $checkStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            if ((int)$checkStmt->fetchColumn() > 0) {
                $isLocked = true;
                $errorMessage = 'Akun Administrator sudah dibuat sebelumnya. Form ini sekarang dinonaktifkan.';
            } else {
                // Periksa apakah username atau email sudah ada di tabel users
                $userCheck = $pdo->prepare("SELECT id FROM users WHERE username = :u OR (email IS NOT NULL AND email = :e) LIMIT 1");
                $userCheck->execute([':u' => $username, ':e' => $email]);
                if ($userCheck->fetch()) {
                    $errorMessage = 'Username atau Email tersebut sudah terdaftar.';
                } else {
                    // Enkripsi kata sandi menggunakan BCRYPT
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                    // Insert akun Admin pertama
                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (username, password, nama_lengkap, email, role, status)
                        VALUES (:username, :password, :nama_lengkap, :email, 'admin', 'aktif')
                    ");
                    $insertStmt->execute([
                        ':username'     => $username,
                        ':password'     => $passwordHash,
                        ':nama_lengkap' => $namaLengkap,
                        ':email'        => !empty($email) ? $email : null
                    ]);

                    set_flash_message('success', 'Akun Administrator utama berhasil dibuat! Silakan masuk menggunakan akun baru Anda.');
                    header("Location: " . base_url('login.php'));
                    exit;
                }
            }
        } catch (PDOException $e) {
            $errorMessage = 'Gagal menyimpan data akun: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Setup Admin Pertama';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-header">
            <div class="brand-badge">Inisialisasi Sistem</div>
            <h1 class="auth-title">Setup Admin</h1>
            <p class="auth-subtitle">Pembuatan Akun Administrator Utama iTales</p>
        </div>

        <?php if (!$dbConnected): ?>
            <div class="alert alert-danger" role="alert">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <strong>Koneksi Database Gagal!</strong><br>
                    Pastikan MySQL di XAMPP telah dinyalakan dan database <code>itales_db</code> sudah diimpor melalui phpMyAdmin.<br>
                    <small style="margin-top: 5px; display: block; opacity: 0.8;"><?= e($dbErrorDetails) ?></small>
                </div>
            </div>
            <a href="<?= base_url('setup_admin.php') ?>" class="btn btn-secondary btn-block">Coba Sambungkan Ulang</a>

        <?php elseif ($isLocked): ?>
            <div class="alert alert-warning" role="alert" style="display: block; text-align: center; padding: 1.5rem;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-warning); margin-bottom: 0.75rem;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: #744210;">Setup Telah Dikunci</h3>
                <p style="font-size: 0.9rem; color: #744210; margin-bottom: 1.25rem;">
                    Akun Administrator utama sudah terdaftar di sistem. Demi keamanan, form pembuatan akun admin awal ini dinonaktifkan secara otomatis.
                </p>
                <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-block">Buka Halaman Login</a>
            </div>

        <?php else: ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div><?= e($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                Silakan lengkapi formulir di bawah ini untuk membuat akun Administrator pertama. Formulir ini hanya dapat diisi satu kali saat instalasi awal.
            </p>

            <form method="POST" action="<?= base_url('setup_admin.php') ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

                <div class="form-group">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                    <input 
                        type="text" 
                        id="nama_lengkap" 
                        name="nama_lengkap" 
                        class="form-input" 
                        placeholder="Contoh: Administrator SD Siber" 
                        value="<?= e($namaValue) ?>" 
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Contoh: admin" 
                        value="<?= e($usernameValue) ?>" 
                        required
                    >
                    <div class="form-text">Gunakan huruf kecil, angka, atau garis bawah (minimal 4 karakter).</div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email (Opsional)</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="admin@sdsiber.sch.id" 
                        value="<?= e($emailValue) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi *</label>
                    <div class="form-input-wrap">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Minimal 8 karakter" 
                            required
                        >
                        <button 
                            type="button" 
                            class="password-toggle-btn" 
                            data-target="password" 
                            aria-label="Lihat Sandi"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Konfirmasi Kata Sandi *</label>
                    <div class="form-input-wrap">
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="form-input" 
                            placeholder="Ketik ulang kata sandi" 
                            required
                        >
                        <button 
                            type="button" 
                            class="password-toggle-btn" 
                            data-target="password_confirm" 
                            aria-label="Lihat Sandi"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 1.75rem;">
                    <button type="submit" class="btn btn-primary btn-block">
                        Simpan Akun Administrator
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
