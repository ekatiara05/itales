<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$hash = password_hash('ortu123', PASSWORD_BCRYPT);

$parents = [
    [
        'username'        => 'nadia.r',
        'nama_lengkap'    => 'Nadia Rahma',
        'email'           => 'nadia.r@gmail.com',
        'no_telepon'      => '0812••••7821',
        'siswa_terhubung' => 'Raka Pratama',
        'status'          => 'aktif'
    ],
    [
        'username'        => 'sinta.m',
        'nama_lengkap'    => 'Sinta Maharani',
        'email'           => 'sinta.m@gmail.com',
        'no_telepon'      => '0813••••2690',
        'siswa_terhubung' => 'Nara Zahra',
        'status'          => 'aktif'
    ],
    [
        'username'        => 'budi.s',
        'nama_lengkap'    => 'Budi Santoso',
        'email'           => 'budi.s@gmail.com',
        'no_telepon'      => '0857••••1492',
        'siswa_terhubung' => 'Bima Alfarizi',
        'status'          => 'aktif'
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO users (username, password, nama_lengkap, email, no_telepon, role, siswa_terhubung, status)
    VALUES (:username, :password, :nama_lengkap, :email, :no_telepon, 'orang_tua', :siswa_terhubung, :status)
    ON DUPLICATE KEY UPDATE 
        nama_lengkap = VALUES(nama_lengkap),
        no_telepon = VALUES(no_telepon),
        siswa_terhubung = VALUES(siswa_terhubung),
        status = VALUES(status)
");

foreach ($parents as $p) {
    $stmt->execute([
        ':username'        => $p['username'],
        ':password'        => $hash,
        ':nama_lengkap'    => $p['nama_lengkap'],
        ':email'           => $p['email'],
        ':no_telepon'      => $p['no_telepon'],
        ':siswa_terhubung' => $p['siswa_terhubung'],
        ':status'          => $p['status']
    ]);
}

echo "PARENTS_SEEDED_SUCCESSFULLY";
