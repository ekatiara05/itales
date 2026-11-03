<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();

    // 1. Buat Tabel kelas jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kelas` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama_kelas` VARCHAR(50) NOT NULL,
            `tingkat` VARCHAR(20) NOT NULL,
            `guru_nama` VARCHAR(100) NULL,
            `jumlah_siswa` INT NOT NULL DEFAULT 0,
            `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Buat Tabel siswa jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `siswa` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nis` VARCHAR(30) NULL,
            `nama_siswa` VARCHAR(100) NOT NULL,
            `nama_kelas` VARCHAR(50) NOT NULL,
            `nama_orang_tua` VARCHAR(100) NULL,
            `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Masukkan Data Kelas 1, 2, 3 (Hanya Kelas 1-3 sesuai permintaan)
    $pdo->exec("TRUNCATE TABLE `kelas`;");
    $insertKelas = $pdo->prepare("
        INSERT INTO `kelas` (nama_kelas, tingkat, guru_nama, jumlah_siswa, status)
        VALUES (:nk, :t, :g, :j, :s)
    ");
    $kelasList = [
        ['nk' => 'Kelas 1', 't' => 'Kelas 1', 'g' => 'Alya Putri, S.Pd.', 'j' => 24, 's' => 'aktif'],
        ['nk' => 'Kelas 2', 't' => 'Kelas 2', 'g' => 'Dimas Ardi, S.Pd.', 'j' => 22, 's' => 'aktif'],
        ['nk' => 'Kelas 3', 't' => 'Kelas 3', 'g' => 'Rani Wulandari, M.Pd.', 'j' => 23, 's' => 'aktif'],
    ];
    foreach ($kelasList as $k) {
        $insertKelas->execute($k);
    }

    // 4. Masukkan Data Siswa (Hanya Kelas 1, 2, 3)
    $pdo->exec("TRUNCATE TABLE `siswa`;");
    $insertSiswa = $pdo->prepare("
        INSERT INTO `siswa` (nis, nama_siswa, nama_kelas, nama_orang_tua, status)
        VALUES (:nis, :ns, :nk, :no, :s)
    ");
    $siswaList = [
        ['nis' => '1001', 'ns' => 'Raka Pratama', 'nk' => 'Kelas 1', 'no' => 'Nadia Rahma', 's' => 'aktif'],
        ['nis' => '1002', 'ns' => 'Nara Zahra', 'nk' => 'Kelas 1', 'no' => 'Sinta Maharani', 's' => 'aktif'],
        ['nis' => '1003', 'ns' => 'Bima Alfarizi', 'nk' => 'Kelas 2', 'no' => 'Budi Santoso', 's' => 'aktif'],
        ['nis' => '1004', 'ns' => 'Keira Anjani', 'nk' => 'Kelas 3', 'no' => 'Ratih Anjani', 's' => 'aktif'],
    ];
    foreach ($siswaList as $s) {
        $insertSiswa->execute($s);
    }

    echo "MIGRATION_SISWA_KELAS_SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
