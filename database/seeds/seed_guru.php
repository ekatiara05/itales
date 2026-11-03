<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();
$hash = password_hash('guru123', PASSWORD_BCRYPT);

$teachers = [
    [
        'username'       => 'alya.putri',
        'nama_lengkap'   => 'Alya Putri, S.Pd.',
        'email'          => 'alya.putri@sdsiber.sch.id',
        'tanggung_jawab' => 'Wali Kelas 1',
        'status'         => 'aktif'
    ],
    [
        'username'       => 'dimas.ardi',
        'nama_lengkap'   => 'Dimas Ardi, S.Pd.',
        'email'          => 'dimas.ardi@sdsiber.sch.id',
        'tanggung_jawab' => 'Wali Kelas 2',
        'status'         => 'aktif'
    ],
    [
        'username'       => 'rani.w',
        'nama_lengkap'   => 'Rani Wulandari, M.Pd.',
        'email'          => 'rani.w@sdsiber.sch.id',
        'tanggung_jawab' => 'Wali Kelas 3',
        'status'         => 'aktif'
    ],
    [
        'username'       => 'fahmi.akbar',
        'nama_lengkap'   => 'Fahmi Akbar, S.Pd.',
        'email'          => 'fahmi.akbar@sdsiber.sch.id',
        'tanggung_jawab' => 'Guru PAI',
        'status'         => 'nonaktif'
    ]
];

$stmt = $pdo->prepare("
    INSERT INTO users (username, password, nama_lengkap, email, role, tanggung_jawab, status)
    VALUES (:username, :password, :nama_lengkap, :email, 'guru', :tanggung_jawab, :status)
    ON DUPLICATE KEY UPDATE 
        nama_lengkap = VALUES(nama_lengkap),
        tanggung_jawab = VALUES(tanggung_jawab),
        status = VALUES(status)
");

foreach ($teachers as $t) {
    $stmt->execute([
        ':username'       => $t['username'],
        ':password'       => $hash,
        ':nama_lengkap'   => $t['nama_lengkap'],
        ':email'          => $t['email'],
        ':tanggung_jawab' => $t['tanggung_jawab'],
        ':status'         => $t['status']
    ]);
}

echo "TEACHERS_SEEDED_SUCCESSFULLY";
