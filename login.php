<?php
/**
 * Halaman Login - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Desain presisi sesuai antarmuka Figma iTales
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

// Jika pengguna sudah dalam keadaan login, alihkan ke dashboard sesuai role
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'admin';
    if ($role === 'guru') {
        header("Location: " . base_url('guru/dashboard.php'));
    } elseif ($role === 'orang_tua') {
        header("Location: " . base_url('orang_tua/dashboard.php'));
    } else {
        header("Location: " . base_url('admin/dashboard.php'));
    }
    exit;
}

$errorMessage = '';
$identifierValue = '';

// Inisialisasi Captcha Matematika Sederhana jika belum ada
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(3, 9);
    $_SESSION['captcha_num2'] = rand(2, 8);
    $_SESSION['captcha_result'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
}

// Proses form saat metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $identifier     = trim($_POST['identifier'] ?? '');
    $password       = $_POST['password'] ?? '';
    $captchaAnswer  = trim($_POST['captcha'] ?? '');
    $identifierValue = $identifier;

    // 1. Validasi token CSRF
    if (!verify_csrf_token($submittedToken)) {
        $errorMessage = 'Sesi form Anda telah kedaluwarsa. Silakan coba kembali.';
    } elseif (empty($identifier) || empty($password)) {
        $errorMessage = 'Username dan Kata Sandi wajib diisi.';
    } elseif ($captchaAnswer === '' || (int)$captchaAnswer !== (int)($_SESSION['captcha_result'] ?? -999)) {
        $errorMessage = 'Jawaban verifikasi keamanan belum tepat. Silakan hitung kembali.';
        // Acak ulang captcha jika salah
        $_SESSION['captcha_num1'] = rand(3, 9);
        $_SESSION['captcha_num2'] = rand(2, 8);
        $_SESSION['captcha_result'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
    } else {
        // 2. Eksekusi autentikasi akun
        $result = login_user($identifier, $password);
        if ($result['success']) {
            // Bersihkan captcha dari session
            unset($_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_result']);
            
            // Pengalihan ke dashboard berbasis role
            $role = $_SESSION['role'] ?? 'admin';
            if ($role === 'guru') {
                header("Location: " . base_url('guru/dashboard.php'));
            } elseif ($role === 'orang_tua') {
                header("Location: " . base_url('orang_tua/dashboard.php'));
            } else {
                header("Location: " . base_url('admin/dashboard.php'));
            }
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    }
}

$num1 = $_SESSION['captcha_num1'];
$num2 = $_SESSION['captcha_num2'];
$pageTitle = 'Masuk ke iTales';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    
    <!-- Meta SEO & Theme -->
    <meta name="description" content="Sistem Informasi Logbook Harian SD Siber Labschool">
    <meta name="theme-color" content="#805538">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
</head>
<body class="login-page">

<div class="login-split-page">
    <a class="login-home-link" href="<?= base_url('index.php') ?>">← Kembali ke beranda</a>
    <div class="login-banner-side">
        <div class="banner-brand-header">
            <div class="brand-icon-box">
                <span aria-hidden="true">iT</span>
            </div>
            <div class="login-brand-copy">
                <span class="brand-title">iTales</span>
                <span class="login-brand-sub">INTEGRATED TALES OF LEARNING</span>
            </div>
        </div>

        <div class="banner-center-content">
            <div class="banner-pill-tag">
                <span aria-hidden="true">✦</span>
                SISTEM INFORMASI SEKOLAH
            </div>
            <h1 class="banner-heading">
                Satu ruang untuk setiap <span>cerita belajar.</span>
            </h1>
            <p class="banner-desc">
                Menghubungkan sekolah, guru, dan orang tua dalam perjalanan tumbuh anak setiap hari.
            </p>
        </div>

        <div class="banner-quote">
            “Every Day Has a Story.”
        </div>
    </div>

    <div class="login-form-side">
        <div class="login-form-inner">
            <span class="form-top-badge">SELAMAT DATANG</span>
            <h2 class="form-main-title">Welcome to iTales</h2>
            <p class="form-main-subtitle">
                Masuk untuk melihat kegiatan dan cerita belajar anak di SD Siber Labschool.
            </p>

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

            <?php 
            $flashMessages = get_flash_messages();
            foreach ($flashMessages as $type => $messages): 
                foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= e($type) ?>" role="alert">
                        <div><?= e($msg) ?></div>
                    </div>
            <?php 
                endforeach;
            endforeach; 
            ?>

            <form method="POST" action="<?= base_url('login.php') ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">

                <div class="login-field">
                    <label for="identifier">Username</label>
                    <div class="login-input-wrap">
                        <input 
                            type="text" 
                            id="identifier" 
                            name="identifier" 
                            class="login-input" 
                            placeholder="Masukkan username" 
                            value="<?= e($identifierValue) ?>" 
                            required 
                            autocomplete="username"
                            autofocus
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="login-input-wrap">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="login-input" 
                            placeholder="Masukkan password" 
                            required 
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            class="login-eye-btn password-toggle-btn" 
                            data-target="password" 
                            aria-label="Lihat Password"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-field">
                    <label for="captcha">Verifikasi keamanan</label>
                    <div class="captcha-box">
                        <div class="captcha-question">
                            Berapakah hasil <strong><?= $num1 ?> + <?= $num2 ?></strong>?
                        </div>
                        <input 
                            type="number" 
                            id="captcha" 
                            name="captcha" 
                            class="captcha-input" 
                            placeholder="Jawaban" 
                            required 
                            autocomplete="off"
                        >
                    </div>
                    <div class="captcha-helper">
                        Jawab pertanyaan sederhana untuk melanjutkan.
                    </div>
                </div>

                <button type="submit" class="btn-login-submit">
                    <span>Masuk ke iTales</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
