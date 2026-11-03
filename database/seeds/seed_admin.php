<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $insert = $pdo->prepare("
            INSERT INTO users (username, password, nama_lengkap, email, role, status)
            VALUES ('admin', :p, 'Administrator iTales', 'admin@sdsiber.sch.id', 'admin', 'aktif')
        ");
        $insert->execute([':p' => $hash]);
        echo "ADMIN_CREATED_SUCCESSFULLY";
    } else {
        echo "ADMIN_ALREADY_EXISTS";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
