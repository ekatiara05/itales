-- ==========================================================
-- iTales - Sistem Informasi Logbook Harian SD Siber Labschool
-- Skema Database Awal (Fondasi Autentikasi & Pengguna)
-- Versi: 1.0.0
-- ==========================================================

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `itales_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `itales_db`;

-- ----------------------------------------------------------
-- Tabel: users
-- Menyimpan kredensial akun dan data profil dasar untuk
-- role: admin, guru, orang_tua
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL UNIQUE,
    `no_telepon` VARCHAR(30) NULL,
    `role` ENUM('admin', 'guru', 'orang_tua') NOT NULL,
    `tanggung_jawab` VARCHAR(100) NULL,
    `siswa_terhubung` VARCHAR(100) NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `foto` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabel: kelas
-- Menyimpan pembagian rombel / kelas (Kelas 1 - Kelas 3)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kelas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_kelas` VARCHAR(50) NOT NULL,
    `tingkat` VARCHAR(20) NOT NULL,
    `guru_nama` VARCHAR(100) NULL,
    `jumlah_siswa` INT NOT NULL DEFAULT 0,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabel: siswa
-- Menyimpan data profil siswa & orang tua terhubung
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `siswa` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nis` VARCHAR(30) NULL,
    `nama_siswa` VARCHAR(100) NOT NULL,
    `nama_kelas` VARCHAR(50) NOT NULL,
    `nama_orang_tua` VARCHAR(100) NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_siswa_kelas` (`nama_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabel: kehadiran
-- Menyimpan status kehadiran siswa per tanggal
-- ----------------------------------------------------------
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
