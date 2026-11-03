<?php
/**
 * Komponen Sidebar Navigasi Dinamis Berbasis Role - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Sesuai desain Figma iTales (Mendukung Admin, Guru, dan Orang Tua)
 */

$user = current_user() ?? [
    'nama_lengkap' => 'Admin Sekolah',
    'role_label'   => 'Admin',
    'role'         => 'admin'
];

$role = $user['role'] ?? 'admin';
$teacherClassLabel = 'Kelas 1';
if ($role === 'guru' && preg_match('/Kelas\s+[0-9A-Za-z]+/i', $user['tanggung_jawab'] ?? '', $classMatch)) {
    $assignedClass = preg_replace('/\s+/', ' ', trim($classMatch[0]));
    if (in_array($assignedClass, ['Kelas 1', 'Kelas 2', 'Kelas 3'], true)) {
        $teacherClassLabel = $assignedClass;
    }
}

// Ambil inisial nama untuk avatar
$words = explode(' ', trim($user['nama_lengkap']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'AS';
}

$currentMenu = $activeMenu ?? 'dashboard';

// Tentukan dashboard link sesuai role
$dashboardLink = ($role === 'guru') ? base_url('guru/dashboard.php') : (($role === 'orang_tua') ? base_url('orang_tua/dashboard.php') : base_url('admin/dashboard.php'));
?>

<aside class="sidebar-wrapper">
    <!-- Brand / Logo Header -->
    <div class="sidebar-brand">
        <a href="<?= $dashboardLink ?>" class="brand-link">
            <div class="brand-icon-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    <path d="M9 7h6"></path>
                    <path d="M9 11h4"></path>
                </svg>
            </div>
            <span class="brand-title">iTales</span>
        </a>
    </div>

    <!-- Menu Navigasi -->
    <nav class="sidebar-nav">
        <div class="sidebar-menu-label">MENU UTAMA</div>
        <ul class="nav-list">
            <!-- 1. Dashboard -->
            <li class="nav-item">
                <a href="<?= $dashboardLink ?>" class="nav-link <?= $currentMenu === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <?php if ($role === 'admin'): ?>
                <!-- Menu Khusus Admin -->
                <li class="nav-item">
                    <a href="<?= base_url('admin/guru.php') ?>" class="nav-link <?= $currentMenu === 'guru' ? 'active' : '' ?>" title="Modul Data Guru">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>
                        <span class="nav-text">Data Guru</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/orang_tua.php') ?>" class="nav-link <?= $currentMenu === 'orang_tua' ? 'active' : '' ?>" title="Modul Data Orang Tua">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                        </span>
                        <span class="nav-text">Data Orang Tua</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/siswa.php') ?>" class="nav-link <?= $currentMenu === 'siswa' ? 'active' : '' ?>" title="Modul Data Siswa">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                            </svg>
                        </span>
                        <span class="nav-text">Data Siswa</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/kelas.php') ?>" class="nav-link <?= $currentMenu === 'kelas' ? 'active' : '' ?>" title="Modul Data Kelas">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                <path d="M7 7h10"/>
                                <path d="M7 12h10"/>
                                <path d="M7 17h10"/>
                            </svg>
                        </span>
                        <span class="nav-text">Data Kelas</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/logbook.php') ?>" class="nav-link <?= $currentMenu === 'logbook' ? 'active' : '' ?>" title="Modul Semua Logbook">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </span>
                        <span class="nav-text">Semua Logbook</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/kegiatan.php') ?>" class="nav-link <?= $currentMenu === 'kegiatan' ? 'active' : '' ?>" title="Modul Kegiatan Sekolah">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span class="nav-text">Kegiatan Sekolah</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/menu_makanan.php') ?>" class="nav-link <?= $currentMenu === 'menu_makanan' ? 'active' : '' ?>" title="Modul Menu Makanan">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2v20"/>
                                <path d="m21 15-3-3-3 3"/>
                                <path d="M6 2v7a3 3 0 0 0 3 3v10"/>
                                <path d="M3 2v4a3 3 0 0 0 3 3"/>
                            </svg>
                        </span>
                        <span class="nav-text">Menu Makanan</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('admin/pengaturan.php') ?>" class="nav-link <?= $currentMenu === 'pengaturan' ? 'active' : '' ?>" title="Pengaturan Sistem">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                        <span class="nav-text">Pengaturan</span>
                    </a>
                </li>

            <?php elseif ($role === 'guru'): ?>
                <!-- Menu Utama Khusus Guru Sesuai Figma -->
                <li class="nav-item">
                    <a href="<?= base_url('guru/siswa.php') ?>" class="nav-link <?= $currentMenu === 'siswa' ? 'active' : '' ?>" title="Daftar Siswa Kelas">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                            </svg>
                        </span>
                        <span class="nav-text nav-text-wrap">Data Siswa</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('guru/kehadiran.php') ?>" class="nav-link <?= $currentMenu === 'kehadiran' ? 'active' : '' ?>" title="Kehadiran Siswa">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="17" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                                <path d="m8 15 2 2 5-5"/>
                            </svg>
                        </span>
                        <span class="nav-text">Kehadiran</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('guru/tambah_logbook.php') ?>" class="nav-link <?= $currentMenu === 'tambah_logbook' ? 'active' : '' ?>" title="Catat Logbook Baru">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </span>
                        <span class="nav-text">Tambah Logbook</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('guru/logbook.php') ?>" class="nav-link <?= $currentMenu === 'logbook' ? 'active' : '' ?>" title="Riwayat Catatan Logbook">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </span>
                        <span class="nav-text">Riwayat Logbook</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('guru/pengaturan.php') ?>" class="nav-link <?= $currentMenu === 'pengaturan' ? 'active' : '' ?>" title="Pengaturan Akun Guru">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V2a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9z"/>
                            </svg>
                        </span>
                        <span class="nav-text">Pengaturan</span>
                    </a>
                </li>

            <?php else: ?>
                <!-- Menu Khusus Orang Tua -->
                <li class="nav-item">
                    <a href="<?= base_url('orang_tua/logbook.php') ?>" class="nav-link <?= $currentMenu === 'logbook' ? 'active' : '' ?>" title="Logbook Anak">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </span>
                        <span class="nav-text">Logbook Anak</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= base_url('orang_tua/kegiatan.php') ?>" class="nav-link <?= $currentMenu === 'kegiatan' ? 'active' : '' ?>" title="Kegiatan Sekolah">
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span class="nav-text">Kegiatan Sekolah</span>
                    </a>
                </li>

            <?php endif; ?>
        </ul>
    </nav>

    <!-- Profile Card Bagian Bawah -->
    <div class="sidebar-profile">
        <div class="profile-avatar"><?= e($initials) ?></div>
        <div class="profile-info">
            <div class="profile-name" title="<?= e($user['nama_lengkap']) ?>"><?= e($user['nama_lengkap']) ?></div>
            <div class="profile-role"><?= e($user['role_label']) ?></div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="profile-logout-btn" title="Keluar dari Akun">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</aside>
