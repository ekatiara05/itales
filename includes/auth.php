<?php
/**
 * Sistem Autentikasi dan Otorisasi Berbasis Role - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Daftar Role Resmi dalam Sistem
const ROLE_ADMIN     = 'admin';
const ROLE_GURU      = 'guru';
const ROLE_ORANG_TUA = 'orang_tua';

const ROLES = [
    ROLE_ADMIN     => 'Admin',
    ROLE_GURU      => 'Guru',
    ROLE_ORANG_TUA => 'Orang Tua'
];

/**
 * Mendapatkan label tampilan role yang ramah pengguna
 * 
 * @param string $role
 * @return string
 */
function get_role_label(string $role): string {
    return ROLES[$role] ?? ucfirst($role);
}

/**
 * Memeriksa apakah ada pengguna yang sedang login
 * 
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Mendapatkan data pengguna yang sedang login saat ini dari sesi
 * 
 * @return array|null
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'email'        => $_SESSION['email'] ?? '',
        'tanggung_jawab' => $_SESSION['tanggung_jawab'] ?? '',
        'role'         => $_SESSION['role'],
        'role_label'   => get_role_label($_SESSION['role'])
    ];
}

/**
 * Memeriksa apakah pengguna memiliki salah satu role yang diizinkan
 * 
 * @param string|array $allowedRoles
 * @return bool
 */
function has_role($allowedRoles): bool {
    if (!is_logged_in()) {
        return false;
    }

    $currentRole = $_SESSION['role'];
    
    if (is_array($allowedRoles)) {
        return in_array($currentRole, $allowedRoles, true);
    }

    return $currentRole === $allowedRoles;
}

/**
 * Penjaga halaman (Guard): Wajib login untuk mengakses
 * Jika belum login, redirect ke halaman login
 * 
 * @param string $redirectPath
 * @return void
 */
function require_login(string $redirectPath = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        $target = base_url($redirectPath);
        header("Location: $target");
        exit;
    }
}

/**
 * Penjaga halaman (Guard): Wajib memiliki role tertentu
 * Jika role tidak sesuai, tampilkan halaman 403 Forbidden atau redirect
 * 
 * @param string|array $allowedRoles
 * @return void
 */
function require_role($allowedRoles): void {
    require_login();

    if (!has_role($allowedRoles)) {
        http_response_code(403);
        
        // Render tampilan halaman akses ditolak yang rapi
        $requiredText = is_array($allowedRoles) 
            ? implode(', ', array_map('get_role_label', $allowedRoles)) 
            : get_role_label($allowedRoles);
        $userRoleText = get_role_label($_SESSION['role']);

        include __DIR__ . '/header.php';
        ?>
        <div class="auth-wrapper">
            <div class="auth-card" style="text-align: center;">
                <div class="badge-role" style="background-color: #fde8e8; color: #9b1c1c; margin-bottom: 1rem;">
                    Akses Ditolak (403)
                </div>
                <h2 style="color: var(--color-primary-dark); margin-bottom: 0.75rem;">Hak Akses Tidak Cukup</h2>
                <p style="color: #666; margin-bottom: 1.5rem;">
                    Halaman ini dikhususkan untuk pengguna dengan hak akses <strong><?= e($requiredText) ?></strong>.<br>
                    Peran akun Anda saat ini: <strong><?= e($userRoleText) ?></strong>.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <a href="<?= base_url('index.php') ?>" class="btn btn-primary">Kembali ke Beranda</a>
                    <a href="<?= base_url('logout.php') ?>" class="btn btn-secondary">Keluar</a>
                </div>
            </div>
        </div>
        <?php
        include __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Proses autentikasi login pengguna
 * 
 * @param string $identifier (Username atau Email)
 * @param string $password
 * @return array
 */
function login_user(string $identifier, string $password): array {
    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Username/Email dan Password wajib diisi.'
        ];
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT id, username, password, nama_lengkap, email, role, status, tanggung_jawab
            FROM users 
            WHERE username = :identifier_user OR email = :identifier_email 
            LIMIT 1
        ");
        $stmt->execute([
            ':identifier_user'  => $identifier,
            ':identifier_email' => $identifier
        ]);
        $user = $stmt->fetch();

        // Verifikasi keberadaan akun dan kecocokan password hash
        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Kombinasi akun atau kata sandi tidak sesuai.'
            ];
        }

        // Periksa status akun
        if ($user['status'] !== 'aktif') {
            return [
                'success' => false,
                'message' => 'Akun Anda berstatus non-aktif. Silakan hubungi Administrator.'
            ];
        }

        // Amankan session dari session fixation
        session_regenerate_id(true);

        // Simpan data login ke session
        $_SESSION['user_id']      = (int)$user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['tanggung_jawab'] = $user['tanggung_jawab'] ?? '';
        $_SESSION['role']         = $user['role'];
        $_SESSION['logged_at']    = time();

        return [
            'success' => true,
            'user'    => $user
        ];
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem saat mencoba login: ' . $e->getMessage()
        ];
    }
}

/**
 * Proses logout pengguna dan pembersihan sesi secara menyeluruh
 * 
 * @return void
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Kosongkan array session
        $_SESSION = [];

        // Hapus cookie session jika ada
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Hancurkan session
        session_destroy();
    }
}
