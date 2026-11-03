<?php
/**
 * Logout Handler - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

// Hapus sesi pengguna secara menyeluruh
logout_user();

// Mulai sesi baru secara aman untuk menyimpan flash message sukses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash_message('success', 'Anda telah berhasil keluar dari sistem iTales.');

// Alihkan kembali ke halaman login
header("Location: " . base_url('login.php'));
exit;
