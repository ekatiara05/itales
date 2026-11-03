<?php
/**
 * Migration & Seeder Menu Makanan - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();

    // 1. Buat Tabel menu_makanan
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `menu_makanan` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `hari` VARCHAR(20) NOT NULL,
            `tanggal` DATE NOT NULL UNIQUE,
            `menu_utama` VARCHAR(150) NOT NULL,
            `pelengkap` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Data Sampel Sesuai Desain Figma (19 - 23 Mei 2025)
    $sampleMenus = [
        [
            'hari'       => 'Senin',
            'tanggal'    => '2025-05-19',
            'menu_utama' => 'Nasi ayam teriyaki',
            'pelengkap'  => "Sayur bening • Semangka • Susu"
        ],
        [
            'hari'       => 'Selasa',
            'tanggal'    => '2025-05-20',
            'menu_utama' => 'Nasi, ikan saus tomat',
            'pelengkap'  => "Tumis buncis • Pepaya • Air mineral"
        ],
        [
            'hari'       => 'Rabu',
            'tanggal'    => '2025-05-21',
            'menu_utama' => 'Nasi kuning komplet',
            'pelengkap'  => "Telur • Tempe • Melon"
        ],
        [
            'hari'       => 'Kamis',
            'tanggal'    => '2025-05-22',
            'menu_utama' => 'Sup makaroni ayam',
            'pelengkap'  => "Roti gandum • Pisang • Susu"
        ],
        [
            'hari'       => 'Jumat',
            'tanggal'    => '2025-05-23',
            'menu_utama' => 'Nasi, ayam bumbu kecap',
            'pelengkap'  => "Capcay • Jeruk • Air mineral"
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `menu_makanan` (hari, tanggal, menu_utama, pelengkap)
        VALUES (:hari, :tanggal, :menu_utama, :pelengkap)
        ON DUPLICATE KEY UPDATE 
            menu_utama = VALUES(menu_utama),
            pelengkap = VALUES(pelengkap)
    ");

    foreach ($sampleMenus as $menu) {
        $stmt->execute($menu);
    }

    echo "MIGRATION_MENU_MAKANAN_SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
