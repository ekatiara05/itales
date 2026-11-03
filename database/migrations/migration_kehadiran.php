<?php
/**
 * Migration Tabel Kehadiran Siswa - iTales
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDbConnection();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kehadiran` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tanggal` DATE NOT NULL,
            `siswa_id` INT UNSIGNED NOT NULL,
            `nama_kelas` VARCHAR(50) NOT NULL,
            `status` ENUM('Hadir', 'Izin', 'Sakit', 'Alpa') NOT NULL,
            `waktu_datang` TIME NULL,
            `keterangan` VARCHAR(255) NULL,
            `dicatat_oleh` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_kehadiran_tanggal_siswa` (`tanggal`, `siswa_id`),
            KEY `idx_kehadiran_kelas_tanggal` (`nama_kelas`, `tanggal`),
            CONSTRAINT `fk_kehadiran_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_kehadiran_guru` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "MIGRATION_KEHADIRAN_SUCCESS";
} catch (Throwable $exception) {
    error_log('Migration kehadiran gagal: ' . $exception->getMessage());
    http_response_code(500);
    echo "ERROR: " . $exception->getMessage();
}
