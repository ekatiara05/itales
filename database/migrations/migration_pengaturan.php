<?php
/**
 * Migration & Seeder Pengaturan - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();

    // 1. Buat Tabel pengaturan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pengaturan` (
            `kunci` VARCHAR(50) PRIMARY KEY,
            `nilai` TEXT NOT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Data Pengaturan Awal Sesuai Figma
    $defaultSettings = [
        'nama_aplikasi'      => 'iTales',
        'nama_sekolah'       => 'SD Siber Labschool',
        'nama_lengkap_sistem'=> 'iTales — Sistem Informasi Logbook Harian SD Siber Labschool',
        'tagline'            => 'Every Day Has a Story.'
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `pengaturan` (kunci, nilai)
        VALUES (:kunci, :nilai)
        ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
    ");

    foreach ($defaultSettings as $k => $v) {
        $stmt->execute([':kunci' => $k, ':nilai' => $v]);
    }

    echo "MIGRATION_PENGATURAN_SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
