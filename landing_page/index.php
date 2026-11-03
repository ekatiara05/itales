<?php
/**
 * Halaman Landing Page Utama - iTales
 * Sistem Informasi Logbook Harian SD Siber Labschool
 * Lokasi: landing_page/index.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'iTales - SD Siber Labschool | Every Day Has a Story';
$loginUrl  = base_url('login.php');
$homeUrl   = base_url('index.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Sistem Informasi Logbook Harian dan Catatan Belajar Siswa SD Siber Labschool.">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
</head>
<body class="landing-body">

<div class="landing-shell">
    <header class="landing-header">
        <a href="<?= $homeUrl ?>" class="landing-header-brand" aria-label="Beranda iTales">
            <div class="landing-header-logo-badge">
                <span aria-hidden="true">iT</span>
            </div>
            <div class="landing-brand-text">
                <span class="landing-brand-title">iTales</span>
                <span class="landing-brand-sub">INTEGRATED TALES OF LEARNING</span>
            </div>
        </a>

        <nav class="landing-nav" aria-label="Navigasi utama">
            <a href="<?= $homeUrl ?>">Beranda</a>
            <a href="#kegiatan">Kegiatan</a>
            <a href="#menu-makanan">Menu Makanan</a>
            <a href="#galeri">Galeri</a>
        </nav>

        <a href="<?= $loginUrl ?>" class="landing-header-signin-btn" id="btn-header-signin">Masuk</a>
    </header>

    <section class="landing-hero" id="hero-section">
        <div class="landing-hero-content">
            <div class="hero-pill-badge">
                <span aria-hidden="true">✦</span>
                <span>Every Day Has a Story.</span>
            </div>
            <div class="hero-title-wrap">
                <h1 class="hero-heading">
                    Selamat Datang di<br>
                    <span class="hero-heading-brand">SD Siber Labschool</span>
                </h1>
            </div>
            <div class="hero-subtitle">Setiap hari adalah cerita. Setiap kegiatan adalah pengalaman belajar yang berharga.</div>
            <p class="hero-desc">
                Melalui iTales, orang tua dapat mengenal kegiatan belajar, aktivitas harian, dan pengalaman anak selama berada di sekolah.
            </p>
            <div class="hero-actions-row">
                <a href="<?= $loginUrl ?>" class="hero-btn-primary" id="btn-login-hero">
                    <span>Masuk ke iTales</span>
                    <span aria-hidden="true">→</span>
                </a>
                <a href="#kegiatan" class="hero-btn-secondary" id="btn-explore-hero">
                    <span>Jelajahi Kegiatan</span>
                </a>
            </div>
        </div>
        <aside class="hero-story-card">
            <span class="hero-story-icon" aria-hidden="true">▣</span>
            <span><small>CERITA HARI INI</small><strong>Belajar, bermain, dan bertumbuh bersama.</strong></span>
        </aside>
    </section>

    <section class="landing-feature-section" id="jelajahi-itales">
        <div class="landing-intro">
            <p class="section-eyebrow">MENGENAL iTALES</p>
            <h2>Selamat Datang di iTales</h2>
            <p>iTales membantu sekolah dan orang tua terhubung melalui cerita kegiatan belajar anak setiap hari.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card">
                <span class="feature-icon feature-icon-peach" aria-hidden="true">▣</span>
                <span class="feature-number">01</span>
                <h3>Belajar Setiap Hari</h3>
                <p>Dokumentasi kegiatan belajar dan pengalaman siswa di sekolah.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon feature-icon-green" aria-hidden="true">⌂</span>
                <span class="feature-number">02</span>
                <h3>Cerita Anak</h3>
                <p>Kenali perkembangan, aktivitas, dan pengalaman anak melalui logbook harian.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon feature-icon-sand" aria-hidden="true">♡</span>
                <span class="feature-number">03</span>
                <h3>Terhubung dengan Sekolah</h3>
                <p>Informasi kegiatan sekolah dan menu makanan tersedia dalam satu tempat.</p>
            </article>
        </div>
    </section>

    <section class="landing-explore-section" id="galeri">
        <div class="landing-section-header">
            <div class="landing-section-title-wrap">
                <h2 class="landing-section-title">
                    <span>Jelajahi Cerita Sekolah</span>
                </h2>
                <p class="landing-section-desc">Cerita belajar, kegiatan, menu, dan momen berharga dari sekolah.</p>
            </div>
        </div>

        <!-- 4 Card Eksplorasi (Horizontal dengan Foto Nyata) -->
        <div class="landing-cards-grid">
            
            <!-- Card 1: KEGIATAN MENDATANG (Tema Hijau Lembut) -->
            <div class="explore-card card-theme-green" onclick="openPreviewModal('modal-kegiatan-mendatang')">
                <div class="explore-card-media">
                    <img src="<?= base_url('assets/images/card-activity.jpg') ?>" alt="Siswa sedang belajar di kelas" class="explore-card-img" loading="lazy">
                </div>
                <div class="explore-card-body">
                    <div>
                        <div class="explore-card-top-icon icon-bg-green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <h3 class="explore-card-title">Kegiatan Mendatang</h3>
                        <p class="explore-card-desc">Agenda sekolah dan kegiatan berikutnya.</p>
                    </div>
                    <div class="explore-card-action">
                        <div class="explore-circle-btn circle-btn-green">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: MENU MAKAN (Tema Cokelat / Peach Hangat) -->
            <div class="explore-card card-theme-orange" onclick="openPreviewModal('modal-menu-makan')">
                <div class="explore-card-media">
                    <img src="<?= base_url('assets/images/card-food.jpg') ?>" alt="Siswa menikmati makan siang sehat" class="explore-card-img" loading="lazy">
                </div>
                <div class="explore-card-body">
                    <div>
                        <div class="explore-card-top-icon icon-bg-orange">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2v20"></path>
                                <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path>
                                <path d="M7 2v5a2 2 0 0 1-2 2H3V2h4Z"></path>
                                <path d="M5 9v13"></path>
                            </svg>
                        </div>
                        <h3 class="explore-card-title">Menu Makan</h3>
                        <p class="explore-card-desc">Informasi menu makan mingguan.</p>
                    </div>
                    <div class="explore-card-action">
                        <div class="explore-circle-btn circle-btn-orange">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: KEGIATAN TERLAKSANA (Tema Biru Lembut) -->
            <div class="explore-card card-theme-blue" onclick="openPreviewModal('modal-kegiatan-terlaksana')">
                <div class="explore-card-media">
                    <img src="<?= base_url('assets/images/card-school-activity.jpg') ?>" alt="Aktivitas siswa di halaman sekolah" class="explore-card-img" loading="lazy">
                </div>
                <div class="explore-card-body">
                    <div>
                        <div class="explore-card-top-icon icon-bg-blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <h3 class="explore-card-title">Kegiatan Terlaksana</h3>
                        <p class="explore-card-desc">Cerita kegiatan yang sudah dilakukan.</p>
                    </div>
                    <div class="explore-card-action">
                        <div class="explore-circle-btn circle-btn-blue">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: GALERI SEKOLAH (Tema Ungu Lembut) -->
            <div class="explore-card card-theme-purple" onclick="openPreviewModal('modal-galeri-sekolah')">
                <div class="explore-card-media">
                    <img src="<?= base_url('assets/images/card-gallery.jpg') ?>" alt="Pameran karya seni dan lukisan siswa" class="explore-card-img" loading="lazy">
                </div>
                <div class="explore-card-body">
                    <div>
                        <div class="explore-card-top-icon icon-bg-purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                        <h3 class="explore-card-title">Galeri Sekolah</h3>
                        <p class="explore-card-desc">Dokumentasi foto kegiatan siswa.</p>
                    </div>
                    <div class="explore-card-action">
                        <div class="explore-circle-btn circle-btn-purple">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="story-section" id="kegiatan">
        <div class="section-heading-row">
            <div>
                <p class="section-eyebrow">CERITA HARI INI</p>
                <h2>Kegiatan Hari Ini</h2>
                <p>Yuk, lihat aktivitas seru yang dilakukan siswa hari ini.</p>
            </div>
            <a class="outline-link" href="<?= $loginUrl ?>">Lihat Semua Kegiatan <span aria-hidden="true">→</span></a>
        </div>
        <div class="activity-grid">
            <article class="activity-card">
                <div class="activity-image"><img src="<?= base_url('assets/images/card-activity.jpg') ?>" alt="Anak belajar dan membaca bersama di kelas" loading="lazy"><span>AKADEMIK</span></div>
                <div class="activity-content"><p class="activity-time">◷ &nbsp;08.00 – 09.00</p><h3>Pembelajaran di Kelas</h3><p>Siswa belajar bersama guru melalui aktivitas interaktif.</p></div>
            </article>
            <article class="activity-card">
                <div class="activity-image"><img src="<?= base_url('assets/images/card-school-activity.jpg') ?>" alt="Siswa mengikuti kegiatan bersama teman" loading="lazy"><span>PEMBIASAAN</span></div>
                <div class="activity-content"><p class="activity-time">◷ &nbsp;09.30 – 10.00</p><h3>Praktik Ibadah</h3><p>Siswa mengikuti kegiatan pembiasaan dan belajar bersama.</p></div>
            </article>
            <article class="activity-card">
                <div class="activity-image"><img src="<?= base_url('assets/images/card-gallery.jpg') ?>" alt="Siswa berkarya dan mengembangkan minat" loading="lazy"><span>PENGEMBANGAN</span></div>
                <div class="activity-content"><p class="activity-time">◷ &nbsp;13.00 – 14.00</p><h3>Ekstrakurikuler</h3><p>Kegiatan untuk mengembangkan minat dan bakat siswa.</p></div>
            </article>
        </div>
        <p class="sample-note">Jadwal dan cerita ditampilkan sebagai data contoh untuk keperluan demonstrasi.</p>
    </section>

    <section class="agenda-section" id="agenda">
        <div class="landing-intro">
            <p class="section-eyebrow">CATAT TANGGALNYA</p>
            <h2>Kegiatan Mendatang</h2>
            <p>Persiapkan diri untuk pengalaman belajar dan kegiatan seru berikutnya.</p>
        </div>
        <div class="agenda-grid">
            <article class="agenda-card"><div class="agenda-date"><strong>05</strong><span>NOV</span></div><div class="agenda-content"><span class="agenda-status">PENDAFTARAN DIBUKA</span><h3>Field Trip Sekolah</h3><p>◷ &nbsp;08.00 – 14.00 &nbsp;　⌖ &nbsp; Museum Sains</p><span>Pengalaman belajar seru di luar kelas bersama teman dan guru.</span></div></article>
            <article class="agenda-card"><div class="agenda-date"><strong>12</strong><span>NOV</span></div><div class="agenda-content"><span class="agenda-status">AKAN DATANG</span><h3>Hari Kreativitas Siswa</h3><p>◷ &nbsp;09.00 – 12.00 &nbsp;　⌖ &nbsp; Aula Sekolah</p><span>Pameran karya dan pertunjukan kreatif dari setiap kelas.</span></div></article>
            <article class="agenda-card"><div class="agenda-date"><strong>18</strong><span>NOV</span></div><div class="agenda-content"><span class="agenda-status">AKAN DATANG</span><h3>Kegiatan Olahraga</h3><p>◷ &nbsp;07.30 – 10.30 &nbsp;　⌖ &nbsp; Lapangan Sekolah</p><span>Hari aktif dan menyenangkan untuk membangun sportivitas.</span></div></article>
            <article class="agenda-card"><div class="agenda-date"><strong>25</strong><span>NOV</span></div><div class="agenda-content"><span class="agenda-status">INFORMASI</span><h3>Pertemuan Orang Tua</h3><p>◷ &nbsp;09.00 – 11.00 &nbsp;　⌖ &nbsp; Ruang Pertemuan</p><span>Berbagi kabar perkembangan belajar dan program sekolah.</span></div></article>
        </div>
        <p class="sample-note">Agenda berikut merupakan data contoh demonstrasi.</p>
    </section>

    <section class="food-section" id="menu-makanan">
        <div class="section-heading-row">
            <div><p class="section-eyebrow">SEHAT &amp; BERGIZI</p><h2>Menu Makanan Minggu Ini</h2><p>Kenali menu makanan yang menemani kegiatan belajar anak setiap hari.</p></div>
            <a class="outline-link" href="<?= $loginUrl ?>">Lihat Menu Mingguan <span aria-hidden="true">→</span></a>
        </div>
        <div class="food-grid">
            <article class="food-card"><img src="<?= base_url('assets/images/card-food.jpg') ?>" alt="Menu makan siang sehat untuk siswa" loading="lazy"><div><p><strong>SENIN</strong><span>02 Nov</span></p><h3>Nasi, ayam kecap &amp; sayur</h3><small>Protein dan sayuran segar</small></div></article>
            <article class="food-card"><img src="<?= base_url('assets/images/card-activity.jpg') ?>" alt="Makanan sehat menemani kegiatan sekolah" loading="lazy"><div><p><strong>SELASA</strong><span>03 Nov</span></p><h3>Nasi, ikan &amp; tumis sayur</h3><small>Kaya protein dan serat</small></div></article>
            <article class="food-card"><img src="<?= base_url('assets/images/card-food.jpg') ?>" alt="Pilihan makan siang seimbang" loading="lazy"><div><p><strong>RABU</strong><span>04 Nov</span></p><h3>Nasi, telur &amp; sayur bening</h3><small>Menu hangat dan seimbang</small></div></article>
            <article class="food-card"><img src="<?= base_url('assets/images/card-school-activity.jpg') ?>" alt="Menu makan siang di sekolah" loading="lazy"><div><p><strong>KAMIS</strong><span>05 Nov</span></p><h3>Nasi, ayam &amp; sup sayur</h3><small>Hangat, lezat, dan bergizi</small></div></article>
            <article class="food-card"><img src="<?= base_url('assets/images/card-food.jpg') ?>" alt="Menu makan siang sehat dan bergizi" loading="lazy"><div><p><strong>JUMAT</strong><span>06 Nov</span></p><h3>Nasi, lauk &amp; buah</h3><small>Dilengkapi buah segar</small></div></article>
        </div>
        <p class="sample-note">Menu dan jadwal ditampilkan sebagai data contoh untuk keperluan demonstrasi.</p>
    </section>

    <section class="landing-cta" id="mulai">
        <div><p class="section-eyebrow">MULAI CERITAMU</p><h2>Setiap Hari Punya Cerita</h2><p>Jelajahi pengalaman belajar anak dan tetap terhubung dengan kegiatan sekolah melalui iTales.</p></div>
        <a href="<?= $loginUrl ?>" class="hero-btn-primary">Masuk ke iTales <span aria-hidden="true">→</span></a>
    </section>
</div>

<footer class="landing-footer">
    <div class="landing-footer-inner">
        <div class="footer-columns">
            <div><a href="<?= $homeUrl ?>" class="footer-brand">iTales</a><p>Sistem Informasi Logbook Harian<br>SD Siber Labschool</p></div>
            <div><strong>NAVIGASI</strong><a href="<?= $homeUrl ?>">Beranda</a><a href="#kegiatan">Kegiatan</a><a href="#menu-makanan">Menu Makanan</a></div>
            <div><strong>ITALES</strong><p>Menghubungkan sekolah, guru, dan orang tua melalui cerita belajar setiap hari.</p></div>
            <div class="footer-motto">✦ &nbsp; Every Day<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Has a Story.</div>
        </div>
        <div class="footer-bottom"><span>© <?= date('Y') ?> iTales. SD Siber Labschool.</span><span>Data pada halaman ini merupakan contoh demonstrasi.</span></div>
    </div>
</footer>

<!-- MODAL PREVIEWS (INTERAKTIF SAAT CARD DIKLIK) -->

<!-- 1. Modal Kegiatan Mendatang -->
<div class="modal-overlay landing-preview-modal" id="modal-kegiatan-mendatang" onclick="handleModalOverlayClick(event, 'modal-kegiatan-mendatang')">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #3B7E54;">📅</span> Kegiatan Mendatang
            </h3>
            <button type="button" class="modal-close-btn" onclick="closePreviewModal('modal-kegiatan-mendatang')">&times;</button>
        </div>
        <div class="modal-body">
            <img src="<?= base_url('assets/images/card-activity.jpg') ?>" alt="Kegiatan Siswa" class="landing-preview-banner">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #FAF7F2; border: 1.5px solid #EDE4DB; border-radius: 12px; padding: 14px;">
                    <div style="font-weight: 700; color: #3D281A; margin-bottom: 4px;">Pentas Seni & Budaya Cilik</div>
                    <div style="font-size: 0.85rem; color: #7A6F66;">Jumat, 30 Oktober 2026 • 08:00 - 11:30 WIB</div>
                    <p style="font-size: 0.88rem; color: #4A3E36; margin: 6px 0 0 0;">Penampilan tari daerah dan kreasi musik angklung oleh siswa kelas 1-3.</p>
                </div>
                <div style="background: #FAF7F2; border: 1.5px solid #EDE4DB; border-radius: 12px; padding: 14px;">
                    <div style="font-weight: 700; color: #3D281A; margin-bottom: 4px;">Field Trip Edukasi Lingkungan</div>
                    <div style="font-size: 0.85rem; color: #7A6F66;">Selasa, 10 November 2026 • Kebun Raya & Sentra Botani</div>
                    <p style="font-size: 0.88rem; color: #4A3E36; margin: 6px 0 0 0;">Mengenal jenis tanaman obat dan belajar menanam pohon bersama pemandu.</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal('modal-kegiatan-mendatang')">Tutup</button>
            <a href="<?= $loginUrl ?>" class="btn btn-primary">Masuk ke Akun untuk Akses Lengkap →</a>
        </div>
    </div>
</div>

<!-- 2. Modal Menu Makan -->
<div class="modal-overlay landing-preview-modal" id="modal-menu-makan" onclick="handleModalOverlayClick(event, 'modal-menu-makan')">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #8C532B;">🍲</span> Menu Makan Siang Sehat
            </h3>
            <button type="button" class="modal-close-btn" onclick="closePreviewModal('modal-menu-makan')">&times;</button>
        </div>
        <div class="modal-body">
            <img src="<?= base_url('assets/images/card-food.jpg') ?>" alt="Menu Makan Siswa" class="landing-preview-banner">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #E5DCD3; padding-bottom: 8px;">
                    <div>
                        <div style="font-weight: 700; color: #3D281A;">Senin • Nasi Ayam Panggang Madu</div>
                        <div style="font-size: 0.82rem; color: #7A6F66;">Sayur bayam bening, jagung manis, tempe bacem, & buah semangka</div>
                    </div>
                    <span class="badge-role" style="background: #E6EEE5; color: #3C6040; font-size: 0.76rem;">Gizi Seimbang</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #E5DCD3; padding-bottom: 8px;">
                    <div>
                        <div style="font-weight: 700; color: #3D281A;">Selasa • Nasi Dori Asam Manis</div>
                        <div style="font-size: 0.82rem; color: #7A6F66;">Sup brokoli wortel, tahu krispi gurih, & susu UHT</div>
                    </div>
                    <span class="badge-role" style="background: #E6EEE5; color: #3C6040; font-size: 0.76rem;">Omega-3 Kaya</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 4px;">
                    <div>
                        <div style="font-weight: 700; color: #3D281A;">Rabu • Nasi Soto Ayam Lamongan</div>
                        <div style="font-size: 0.82rem; color: #7A6F66;">Kecambah segar, perkedel kentang kentara, & irisan buah melon</div>
                    </div>
                    <span class="badge-role" style="background: #E6EEE5; color: #3C6040; font-size: 0.76rem;">Tradisional Sehat</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal('modal-menu-makan')">Tutup</button>
            <a href="<?= $loginUrl ?>" class="btn btn-primary">Masuk ke Akun untuk Akses Lengkap →</a>
        </div>
    </div>
</div>

<!-- 3. Modal Kegiatan Terlaksana -->
<div class="modal-overlay landing-preview-modal" id="modal-kegiatan-terlaksana" onclick="handleModalOverlayClick(event, 'modal-kegiatan-terlaksana')">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #2F6E9E;">📖</span> Cerita Kegiatan Terlaksana
            </h3>
            <button type="button" class="modal-close-btn" onclick="closePreviewModal('modal-kegiatan-terlaksana')">&times;</button>
        </div>
        <div class="modal-body">
            <img src="<?= base_url('assets/images/card-school-activity.jpg') ?>" alt="Kegiatan Luar Kelas Siswa" class="landing-preview-banner">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #FAF7F2; border: 1.5px solid #EDE4DB; border-radius: 12px; padding: 14px;">
                    <div style="font-weight: 700; color: #3D281A; margin-bottom: 4px;">Eksplorasi Sains: Siklus Air Sederhana</div>
                    <div style="font-size: 0.85rem; color: #7A6F66;">Dilaksanakan: 22 Oktober 2026 • Kelas 2 & 3</div>
                    <p style="font-size: 0.88rem; color: #4A3E36; margin: 6px 0 0 0;">Siswa mempraktikkan proses penguapan dan pengembunan menggunakan toples kaca mini dan es batu.</p>
                </div>
                <div style="background: #FAF7F2; border: 1.5px solid #EDE4DB; border-radius: 12px; padding: 14px;">
                    <div style="font-weight: 700; color: #3D281A; margin-bottom: 4px;">Pekan Gemar Membaca & Menulis Cerita</div>
                    <div style="font-size: 0.85rem; color: #7A6F66;">Dilaksanakan: 15 Oktober 2026 • Seluruh Siswa</div>
                    <p style="font-size: 0.88rem; color: #4A3E36; margin: 6px 0 0 0;">Setiap anak membaca 1 buku cerita fabel dan menuliskan hikmah moralnya ke dalam buku logbook.</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal('modal-kegiatan-terlaksana')">Tutup</button>
            <a href="<?= $loginUrl ?>" class="btn btn-primary">Masuk ke Akun untuk Akses Lengkap →</a>
        </div>
    </div>
</div>

<!-- 4. Modal Galeri Sekolah -->
<div class="modal-overlay landing-preview-modal" id="modal-galeri-sekolah" onclick="handleModalOverlayClick(event, 'modal-galeri-sekolah')">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #7247A3;">🖼️</span> Galeri & Karya Siswa
            </h3>
            <button type="button" class="modal-close-btn" onclick="closePreviewModal('modal-galeri-sekolah')">&times;</button>
        </div>
        <div class="modal-body">
            <img src="<?= base_url('assets/images/card-gallery.jpg') ?>" alt="Galeri Seni Siswa" class="landing-preview-banner">
            <p style="font-size: 0.9rem; color: #4A3E36; line-height: 1.6; margin-bottom: 1rem;">
                Dokumentasi foto karya lukis, origami, kolase daun kering, serta momen ceria saat kegiatan belajar mengajar di SD Siber Labschool.
            </p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                <img src="<?= base_url('assets/images/card-activity.jpg') ?>" alt="Aktivitas Kelas" style="width: 100%; height: 110px; object-fit: cover; border-radius: 8px;">
                <img src="<?= base_url('assets/images/card-school-activity.jpg') ?>" alt="Bermain Bersama" style="width: 100%; height: 110px; object-fit: cover; border-radius: 8px;">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal('modal-galeri-sekolah')">Tutup</button>
            <a href="<?= $loginUrl ?>" class="btn btn-primary">Masuk ke Akun untuk Akses Lengkap →</a>
        </div>
    </div>
</div>

<!-- Javascript Modal Interactivity -->
<script>
function openPreviewModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closePreviewModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

function handleModalOverlayClick(e, id) {
    if (e.target && e.target.classList.contains('modal-overlay')) {
        closePreviewModal(id);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const exploreBtn = document.getElementById('btn-explore-hero');
    if (exploreBtn) {
        exploreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.getElementById('kegiatan');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
});
</script>

</body>
</html>
