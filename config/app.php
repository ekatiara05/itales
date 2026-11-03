<?php
/**
 * Konfigurasi Utama Aplikasi - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

// Pengaturan Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Konstanta Aplikasi
define('APP_NAME', 'iTales');
define('APP_TAGLINE', 'Sistem Informasi Logbook Harian SD Siber Labschool');
define('APP_VERSION', '1.0.0');

// Warna Tema iTales
define('COLOR_PRIMARY', '#805538');      // Cokelat utama
define('COLOR_PRIMARY_DARK', '#5F3F2C'); // Cokelat tua
define('COLOR_ACCENT', '#6F8F72');       // Hijau
define('COLOR_ACCENT_LIGHT', '#E6EEE5'); // Hijau muda
define('COLOR_BG', '#F6F1EA');           // Krem
define('COLOR_WHITE', '#FFFFFF');        // Putih

// Pengaturan Session Aman
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan parameter cookie sesi sebelum session_start
    $cookieParams = [
        'lifetime' => 0, // Bertahan sampai browser ditutup
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

/**
 * Menghasilkan Base URL aplikasi secara dinamis.
 * Bekerja baik untuk http://localhost/iTales/ maupun http://localhost/logbook-sd-siber/
 * 
 * @param string $path
 * @return string
 */
function base_url(string $path = ''): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Tentukan direktori dasar relatif terhadap document root
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'), '/');
    $currentDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    
    // Hitung path subfolder
    $subDir = str_replace($docRoot, '', $currentDir);
    $subDir = '/' . trim($subDir, '/');
    if ($subDir === '/') {
        $subDir = '';
    }

    // Jika diakses melalui alias/link seperti /iTales
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (stripos($requestUri, '/iTales') === 0) {
        $subDir = '/iTales';
    }

    $url = $protocol . $host . $subDir;
    if (!empty($path)) {
        $url .= '/' . ltrim($path, '/');
    }
    return $url;
}

/**
 * Escape string HTML untuk mencegah XSS
 * 
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 * 
 * @return string
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Alias helper untuk generate_csrf_token
 * 
 * @return string
 */
function csrf_token(): string {
    return generate_csrf_token();
}

/**
 * Validasi CSRF Token
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message ke session
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 * @return void
 */
function set_flash_message(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Ambil dan hapus flash message dari session
 * 
 * @return array
 */
function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Format tanggal dalam Bahasa Indonesia (contoh: Senin, 19 Mei 2025)
 * 
 * @param int|null $timestamp
 * @return string
 */
function tanggal_indonesia(?int $timestamp = null): string {
    $timestamp = $timestamp ?? time();
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $namaHari = $hari[(int)date('w', $timestamp)];
    $tgl = date('j', $timestamp);
    $namaBulan = $bulan[(int)date('n', $timestamp)];
    $thn = date('Y', $timestamp);
    return "$namaHari, $tgl $namaBulan $thn";
}

