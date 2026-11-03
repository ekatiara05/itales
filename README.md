# iTales - Sistem Informasi Logbook Harian SD Siber Labschool

Selamat datang di proyek **iTales**, sebuah aplikasi web berbasis PHP Native dan MySQL yang dirancang untuk pencatatan dan pemantauan logbook kegiatan harian siswa di SD Siber Labschool.

Proyek ini dibangun secara bertahap dan modular dengan fondasi keamanan tinggi: prepared statements PDO, enkripsi kata sandi `password_hash()` BCRYPT, proteksi CSRF, session fixation prevention, serta kontrol akses berbasis peran (*Role-Based Access Control* - RBAC) untuk role **Admin**, **Guru**, dan **Orang Tua**.

---

## 🎨 Palet Warna Desain iTales

Sesuai dengan panduan desain Figma iTales, sistem menggunakan palet warna resmi:
- **Cokelat Utama (`#805538`)**: Warna aksen utama tombol, tautan, dan penegasan visual.
- **Cokelat Tua (`#5F3F2C`)**: Warna judul, teks penting, dan gradasi status aktif.
- **Hijau (`#6F8F72`)**: Aksen status sekunder dan peran pendidik (Guru).
- **Hijau Muda (`#E6EEE5`)**: Latar belakang lencana (*badge*), alert ramah, dan kartu status.
- **Krem (`#F6F1EA`)**: Warna latar belakang (*background*) utama aplikasi.
- **Putih (`#FFFFFF`)**: Latar kartu form, kontainer data, dan elemen kartu interaktif.

---

## 📁 Struktur Direktori Proyek

```text
c:\xampp\htdocs\iTales\  (atau c:\xampp\htdocs\logbook-sd-siber\)
│
├── assets/
│   ├── css/
│   │   └── style.css            # Desain global, tipografi, warna, kartu, form, dan tombol
│   ├── js/
│   │   └── app.js              # Interaktivitas UI ringan (toggle show/hide kata sandi)
│   └── images/                 # Aset grafis/logo aplikasi
│
├── config/
│   ├── app.php                 # Konfigurasi dasar sistem, session, CSRF, flash message, base URL dinamis
│   └── database.php            # Koneksi database menggunakan PHP Data Objects (PDO)
│
├── database/
│   └── schema.sql              # Skrip SQL pembuatan database `itales_db` dan tabel awal `users`
│
├── includes/
│   ├── auth.php                # Fungsi login, logout, require_login(), require_role(), dan current_user()
│   ├── header.php              # Komponen header HTML, meta SEO, dan pemanggilan CSS
│   └── footer.php              # Komponen footer HTML dan pemanggilan skrip JavaScript
│
├── dashboard_placeholder.php   # Halaman verifikasi fondasi & pengujian role-based access control
├── index.php                   # Titik masuk utama yang memuat landing page
├── login.php                   # Antarmuka login tunggal (tanpa selector role, tanpa akun demo)
├── logout.php                  # Handler logout aman yang menghancurkan sesi dan cookie
├── setup_admin.php             # Wizard inisialisasi akun Admin pertama (otomatis terkunci setelah dibuat)
└── README.md                   # Dokumentasi instalasi dan panduan pengoperasian
```

---

## 🚀 Panduan Menjalankan Proyek di XAMPP

Ikuti langkah-langkah berikut untuk menjalankan dan menguji proyek di komputer lokal Anda:

### 1. Menyalakan Layanan XAMPP
1. Buka aplikasi **XAMPP Control Panel**.
2. Pada baris **Apache**, klik tombol **Start**.
3. Pada baris **MySQL**, klik tombol **Start**.
4. Pastikan kedua indikator telah berubah menjadi warna hijau dan tidak mengalami konflik port.

---

### 2. Mengimpor Skema Database melalui phpMyAdmin
1. Buka browser (Google Chrome, Firefox, atau Edge) lalu akses:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Pada panel navigasi di sebelah kiri, klik menu **Import** (atau buat database baru bernama `itales_db` terlebih dahulu).
3. Klik tombol **Choose File** / **Pilih File**.
4. Arahkan dan pilih file SQL yang berada di:
   ```text
   C:\xampp\htdocs\iTales\database\schema.sql
   ```
   *(atau `C:\xampp\htdocs\logbook-sd-siber\database\schema.sql`)*
5. Gulir ke bagian paling bawah halaman phpMyAdmin, lalu klik tombol **Import** / **Go**.
6. Sistem akan mengeksekusi skrip dan membuat:
   - Database `itales_db`
   - Tabel `users` beserta indeks perannya.

---

### 3. Mengatur Koneksi Database (Jika Diperlukan)
File konfigurasi database terletak di:
```text
C:\xampp\htdocs\iTales\config\database.php
```
Secara default, konfigurasi telah disesuaikan dengan standar instalasi XAMPP bawaan:
- **Host**: `localhost`
- **Database**: `itales_db`
- **Port**: `3306`
- **User**: `root`
- **Password**: *(kosong)*

Jika instalasi MySQL / MariaDB di XAMPP Anda menggunakan kata sandi khusus atau port berbeda, ubah baris berikut pada file `config/database.php`:
```php
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', 'kata_sandi_anda');
```

---

### 4. Membuat Akun Admin Pertama (Inisialisasi Aman)
Untuk mematuhi prinsip keamanan:
- Tidak ada akun bawaan atau kredensial demo di kode sumber.
- Pembuatan akun Admin pertama dilakukan melalui wizard inisialisasi mandiri:

1. Buka URL berikut di browser:
   ```text
   http://localhost/iTales/setup_admin.php
   ```
2. Isi formulir dengan data administrator utama:
   - **Nama Lengkap**: misalnya `Administrator SD Siber`
   - **Username**: misalnya `admin` (minimal 4 karakter)
   - **Email**: misalnya `admin@sdsiber.sch.id`
   - **Kata Sandi**: minimal 8 karakter
   - **Konfirmasi Kata Sandi**: ketik ulang kata sandi yang sama
3. Klik tombol **Simpan Akun Administrator**.
4. Akun akan dienkripsi dengan standar `password_hash()` BCRYPT dan disimpan ke database.
5. Anda akan otomatis dialihkan ke halaman Login dengan notifikasi sukses.

> 🔒 **Keamanan Terjamin**: Begitu akun Admin pertama berhasil dibuat, halaman `setup_admin.php` akan **otomatis terkunci**. Siapapun yang mencoba membukanya kembali akan ditolak dan melihat status terkunci.

---

### 5. Menguji Fondasi Autentikasi dan Role
1. Pastikan **Apache** dan **MySQL** sudah berjalan dari XAMPP, lalu buka landing page:
   ```text
   http://localhost:8080/logbook-sd-siber/
   ```
   Jika Apache Anda memakai port standar, gunakan `http://localhost/logbook-sd-siber/`.
   Halaman yang sama juga dapat dibuka langsung melalui:
   ```text
   http://localhost:8080/logbook-sd-siber/landing_page/index.php
   ```
2. Klik tombol **Masuk** atau **Masuk ke iTales** untuk membuka halaman login:
   ```text
   http://localhost:8080/logbook-sd-siber/login.php
   ```
3. Masukkan **Username/Email** dan **Kata Sandi** akun Admin yang baru saja Anda buat, lalu jawab verifikasi keamanan.
4. Setelah berhasil login, Anda akan diarahkan otomatis ke dashboard Admin.

### 6. Masuk dan Membuka Dashboard Guru
1. Login sebagai Admin, lalu buka menu **Data Guru**. Anda juga dapat membuka alamat berikut saat sudah login sebagai Admin:
   ```text
   http://localhost:8080/logbook-sd-siber/admin/guru.php
   ```
2. Tambahkan akun guru. Isi **Tanggung Jawab** dengan nama kelas yang sesuai, misalnya `Kelas 1`, agar ringkasan dashboard menampilkan jumlah siswa dan logbook kelas yang benar.
3. Keluar dari akun Admin, lalu kembali ke halaman login:
   ```text
   http://localhost:8080/logbook-sd-siber/login.php
   ```
4. Masukkan username dan kata sandi akun guru, kemudian jawab verifikasi keamanan. Setelah login berhasil, aplikasi otomatis membuka dashboard Guru.
5. Untuk membuka dashboard Guru secara langsung setelah login, gunakan:
   ```text
   http://localhost:8080/logbook-sd-siber/guru/dashboard.php
   ```
   Halaman ini dilindungi hak akses Guru; jika belum login, Anda akan diarahkan ke halaman login. Akun dengan role lain tidak dapat mengaksesnya.
6. Pilih **Tambah Logbook** untuk mencatat siswa dari Kelas 1, Kelas 2, atau Kelas 3. Langkah dokumentasi menerima hingga 10 foto/video sekaligus (maksimal 20 MB per file dan 35 MB total); lampiran tampil pada detail logbook.
7. Pilih **Kehadiran**, pilih kelas dan tanggal. Untuk siswa yang sudah terdaftar, pilih namanya lalu klik **Tambahkan siswa terdaftar**. Untuk mendaftarkan siswa baru langsung, klik **Tambah Kehadiran**, isi nama serta statusnya, lalu simpan. Siswa baru otomatis masuk ke Data Siswa kelas yang dipilih. Untuk instalasi database yang belum memakai tabel kehadiran, jalankan `php database/migrations/migration_kehadiran.php` dari folder proyek terlebih dahulu.
8. Untuk menambah siswa, login sebagai Admin, buka **Data Siswa** (`http://localhost:8080/logbook-sd-siber/admin/siswa.php`), lalu pilih **Tambah Siswa**. Isi nama, pilih Kelas 1/2/3, dan isi NIS serta nama orang tua jika tersedia. Data ini kemudian digunakan pada halaman siswa, logbook, dan kehadiran Guru.
9. Menu **Pengaturan** Guru (`http://localhost:8080/logbook-sd-siber/guru/pengaturan.php`) digunakan untuk memperbarui nama, email, nomor telepon, serta kata sandi akun. Menu ini tidak mengubah pengaturan sistem milik Admin.

> **Daftar kelas:** aplikasi hanya menampilkan dan menerima Kelas 1, Kelas 2, dan Kelas 3. Data kelas lama di luar daftar tersebut tidak dihapus dari database, tetapi disembunyikan dari halaman terkait.

---

## 👥 Pengujian Hak Akses (Role-Based Access Control)
Sistem mengenali 3 peran pengguna:
1. **Admin** (`role = 'admin'`)
2. **Guru** (`role = 'guru'`)
3. **Orang Tua** (`role = 'orang_tua'`)

Setiap halaman modul telah dilindungi dengan `require_role()` sesuai perannya. Sebagai contoh, dashboard Guru hanya dapat dibuka oleh akun dengan role `guru`; pengguna yang belum login akan diarahkan ke halaman login.
