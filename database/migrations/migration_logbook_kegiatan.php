<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();

    // 1. Buat Tabel logbook
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `logbook` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tanggal` DATE NOT NULL,
            `nama_siswa` VARCHAR(100) NOT NULL,
            `nama_kelas` VARCHAR(50) NOT NULL,
            `nama_guru` VARCHAR(100) NOT NULL,
            `ringkasan_kegiatan` VARCHAR(255) NOT NULL,
            `detail_kegiatan` TEXT NULL,
            `status` ENUM('Dipublikasikan', 'Menunggu Pemeriksaan', 'Draft', 'Perlu Revisi') NOT NULL DEFAULT 'Draft',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Buat Tabel kegiatan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kegiatan` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `judul_kegiatan` VARCHAR(150) NOT NULL,
            `deskripsi` TEXT NULL,
            `tanggal` DATE NOT NULL,
            `waktu` VARCHAR(50) NOT NULL,
            `lokasi` VARCHAR(150) NOT NULL,
            `status` ENUM('Mendatang', 'Selesai') NOT NULL DEFAULT 'Mendatang',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Masukkan Data Sampel Logbook Sesuai Figma
    $pdo->exec("TRUNCATE TABLE `logbook`;");
    $insertLogbook = $pdo->prepare("
        INSERT INTO `logbook` (tanggal, nama_siswa, nama_kelas, nama_guru, ringkasan_kegiatan, detail_kegiatan, status)
        VALUES (:tgl, :siswa, :kelas, :guru, :ringkasan, :detail, :st)
    ");
    $sampleLogbooks = [
        [
            'tgl'       => '2025-05-19',
            'siswa'     => 'Raka Pratama',
            'kelas'     => 'Kelas 1',
            'guru'      => 'Alya Putri',
            'ringkasan' => 'Eksperimen perubahan wujud benda',
            'detail'    => 'Siswa melakukan observasi pencairan es batu dan pengembunan air dalam toples kaca. Seluruh siswa aktif mencatat hasil perubahan wujud benda padat menjadi cair.',
            'st'        => 'Dipublikasikan'
        ],
        [
            'tgl'       => '2025-05-19',
            'siswa'     => 'Nara Zahra',
            'kelas'     => 'Kelas 1',
            'guru'      => 'Alya Putri',
            'ringkasan' => 'Mengenal pecahan sederhana',
            'detail'    => 'Praktik pembagian pecahan 1/2, 1/4 menggunakan alat peraga pizza kertas warna-warni. Nara mampu menyusun dan menghitung dengan teliti.',
            'st'        => 'Menunggu Pemeriksaan'
        ],
        [
            'tgl'       => '2025-05-16',
            'siswa'     => 'Bima Alfarizi',
            'kelas'     => 'Kelas 2',
            'guru'      => 'Dimas Ardi',
            'ringkasan' => 'Membaca cerita rakyat Nusantara',
            'detail'    => 'Membaca dan menceritakan kembali kisah Malin Kundang di depan kelas. Pesan moral tentang menghormati orang tua dipahami dengan sangat baik.',
            'st'        => 'Draft'
        ],
        [
            'tgl'       => '2025-05-15',
            'siswa'     => 'Keira Anjani',
            'kelas'     => 'Kelas 3',
            'guru'      => 'Rani Wulandari',
            'ringkasan' => 'Praktik menanam biji kacang hijau',
            'detail'    => 'Menanam biji kacang hijau di media kapas basah untuk mengamati pertumbuhan tunas hari ke-1 sampai ke-3.',
            'st'        => 'Perlu Revisi'
        ]
    ];
    foreach ($sampleLogbooks as $lb) {
        $insertLogbook->execute($lb);
    }

    // 4. Masukkan Data Sampel Kegiatan Sesuai Figma
    $pdo->exec("TRUNCATE TABLE `kegiatan`;");
    $insertKegiatan = $pdo->prepare("
        INSERT INTO `kegiatan` (judul_kegiatan, deskripsi, tanggal, waktu, lokasi, status)
        VALUES (:j, :d, :t, :w, :l, :s)
    ");
    $sampleKegiatan = [
        [
            'j' => 'Kunjungan Museum Sains',
            'd' => 'Kegiatan bersama untuk memperkaya pengalaman belajar dan kebersamaan siswa.',
            't' => '2025-05-22',
            'w' => '07.30 - 14.00',
            'l' => 'Museum IPTEK TMII',
            's' => 'Mendatang'
        ],
        [
            'j' => 'Pentas Seni Kelas 1–3',
            'd' => 'Kegiatan bersama untuk memperkaya pengalaman belajar dan kebersamaan siswa.',
            't' => '2025-05-24',
            'w' => '08.00 - 11.30',
            'l' => 'Aula Utama Sekolah',
            's' => 'Mendatang'
        ],
        [
            'j' => 'Pertemuan Orang Tua',
            'd' => 'Kegiatan bersama untuk memperkaya pengalaman belajar dan kebersamaan siswa.',
            't' => '2025-05-31',
            'w' => '09.00 - 11.00',
            'l' => 'Ruang Kelas Masing-masing',
            's' => 'Mendatang'
        ]
    ];
    foreach ($sampleKegiatan as $kg) {
        $insertKegiatan->execute($kg);
    }

    echo "MIGRATION_LOGBOOK_KEGIATAN_SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
