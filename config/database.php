<?php
/**
 * Konfigurasi Koneksi Database (PDO) - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', 'itales_db');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_PORT') || define('DB_PORT', 3307);
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/**
 * Mendapatkan instance koneksi PDO (Singleton pattern sederhana)
 * 
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error untuk kebutuhan internal debugging
            error_log("Database connection failed: " . $e->getMessage());
            
            // Lemparkan kembali agar caller atau script dapat menampilkan pesan ramah
            throw new PDOException("Gagal terhubung ke database `itales_db`. Pastikan MySQL di XAMPP telah berjalan dan database sudah diimpor. Pesan teknis: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    return $pdo;
}
