# CatatIn — Aplikasi Catatan Belajar Digital Siswa SMK

> **"Catat, Pahami, Raih Prestasi."**

**CatatIn** adalah aplikasi catatan belajar digital yang dirancang khusus untuk siswa SMK jurusan **Rekayasa Perangkat Lunak (RPL)**. Aplikasi ini mendemonstrasikan penerapan arsitektur **MVC (Model-View-Controller)**, operasi **CRUD**, validasi server-side, migrasi basis data, dan antarmuka modern menggunakan Tailwind CSS.

Aplikasi mendukung autentikasi pengguna dan pengelolaan materi serta produktivitas secara lokal, cocok untuk media pembelajaran, portofolio praktik, dan bahan ujian kompetensi kejuruan.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: Laravel 11.x (PHP 8.3+)
- **Frontend Engine**: Blade Templating
- **Styling**: Tailwind CSS v3 dengan palet khusus CatatIn (Primary: `#6D5CE8`, Background: `#F8FAFC`, Surface: `#FFFFFF`)
- **Icons**: Lucide Icons
- **Database**: MySQL (`catatin_db`)
- **ORM**: Eloquent ORM
- **Asset Bundler**: Vite 5.x

---

## 📁 Struktur Utama Proyek

- **Model**: [`app/Models/Catatan.php`](file:///c:/laragon/www/Catatin/app/Models/Catatan.php) — Definisi tabel, mass assignment (`$fillable`), type casting (`favorit` boolean), dan Eloquent query scopes (`search`, `filterKategori`).
- **Controller**: [`app/Http/Controllers/CatatanController.php`](file:///c:/laragon/www/Catatin/app/Http/Controllers/CatatanController.php) — Menangani logika dashboard statistik, CRUD, pagination, filtering, serta toggle status favorit.
- **Routes**: [`routes/web.php`](file:///c:/laragon/www/Catatin/routes/web.php) — Daftar rute bernama (`dashboard`, `catatan.index`, `catatan.create`, `catatan.store`, `catatan.show`, `catatan.edit`, `catatan.update`, `catatan.destroy`, `catatan.toggle-favorit`, `catatan.favorit`).
- **Migration**: [`database/migrations/2026_10_28_173530_create_catatan_table.php`](file:///c:/laragon/www/Catatin/database/migrations/2026_10_28_173530_create_catatan_table.php) — Skema tabel `catatan` di MySQL.
- **Seeder Opsional**: [`database/seeders/CatatanSeeder.php`](file:///c:/laragon/www/Catatin/database/seeders/CatatanSeeder.php) — Contoh materi pembelajaran SMK untuk demonstrasi.
- **Views**:
  - [`resources/views/layouts/app.blade.php`](file:///c:/laragon/www/Catatin/resources/views/layouts/app.blade.php) — Layout utama, sidebar navigasi, notifikasi flash, modal konfirmasi hapus.
  - [`resources/views/components/card-catatan.blade.php`](file:///c:/laragon/www/Catatin/resources/views/components/card-catatan.blade.php) — Kartu catatan reusable dengan badge warna tiap mapel.
  - [`resources/views/dashboard.blade.php`](file:///c:/laragon/www/Catatin/resources/views/dashboard.blade.php) — Statistik database dan 5 catatan terbaru.
  - [`resources/views/catatan/index.blade.php`](file:///c:/laragon/www/Catatin/resources/views/catatan/index.blade.php) — Pencarian judul/isi, filter kategori, status favorit, sortir, dan pagination.
  - [`resources/views/catatan/favorit.blade.php`](file:///c:/laragon/www/Catatin/resources/views/catatan/favorit.blade.php) — Halaman khusus materi berbintang (favorit).
  - [`resources/views/catatan/create.blade.php`](file:///c:/laragon/www/Catatin/resources/views/catatan/create.blade.php) — Form tambah catatan dengan validasi dan penghitung karakter.
  - [`resources/views/catatan/edit.blade.php`](file:///c:/laragon/www/Catatin/resources/views/catatan/edit.blade.php) — Form edit catatan dengan method spoofing `@method('PUT')`.
  - [`resources/views/catatan/show.blade.php`](file:///c:/laragon/www/Catatin/resources/views/catatan/show.blade.php) — Detail materi lengkap dengan rendering teks aman (`nl2br(e($catatan->isi))`).
- **Automated Tests**: [`tests/Feature/CatatanFeatureTest.php`](file:///c:/laragon/www/Catatin/tests/Feature/CatatanFeatureTest.php) — 15 skenario pengujian fitur otomatis.

---

## 🚀 Panduan Menjalankan Aplikasi

### 1. Prasyarat Sistem
Pastikan Laragon atau server lokal aktif (PHP 8.2+, MySQL, Composer, Node.js & npm).

### 2. Konfigurasi Database (.env)
Buka file `.env` dan pastikan pengaturan MySQL sudah terhubung:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=catatin_db
DB_USERNAME=root
DB_PASSWORD=
```

Buat database MySQL bernama `catatin_db` jika belum ada:
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS catatin_db;"
```

### 3. Menjalankan Migrasi Tabel
Jalankan migrasi untuk membuat tabel aplikasi dan menambahkan kolom role/status akun:
```bash
php artisan migrate
```

### 4. Setup akun Admin (opsional)
Atur `ADMIN_PASSWORD` hanya di file `.env` lokal dengan password unik minimal 16 karakter yang memiliki huruf besar, huruf kecil, angka, dan simbol. Jangan bagikan atau commit nilainya. Buat/perbarui akun Admin secara aman dengan seeder berikut (aman dijalankan ulang dan tidak mengganti password Admin yang sudah ada):
```bash
php artisan db:seed --class=AdminSeeder
```
Email Admin ditetapkan sebagai `admin54321@gmail.com`. Seeder hanya membuat akun baru dengan password kuat dari `ADMIN_PASSWORD`; akun yang sudah ber-role Admin dibiarkan tanpa perubahan. Jika email tersebut sudah dimiliki akun non-admin, seeder berhenti dan meminta verifikasi serta penyelesaian konflik secara manual, bukan menaikkan role berdasarkan email saja. Login Admin menuju `/admin/dashboard`; halaman itu hanya dapat diakses akun Admin aktif. Registrasi publik tidak dapat membuat email Admin atau menetapkan role.

Dashboard Admin menyediakan statistik database, daftar pengguna dengan pencarian/filter/paginasi, detail akun, pengelolaan akun, serta pengaturan profil Admin. Hanya alamat Admin utama yang dikonfigurasi yang dapat memberi atau mencabut role Admin dan mengelola akun Admin lain; admin tidak dapat mengubah atau menghapus akunnya sendiri. Admin memakai layout terpisah dan tidak dapat membuka route fitur user; seluruh route administrasi dilindungi middleware `auth` dan `admin`.

*(Opsional)* Jika ingin mengisi data contoh materi pelajaran untuk demonstrasi langsung:
```bash
php artisan db:seed --class=CatatanSeeder
```

### 5. Kompilasi Aset Frontend (Tailwind CSS)
```bash
npm run build
```
*(Atau gunakan `npm run dev` untuk live reload saat pengembangan)*

### 6. Menjalankan Server Lokal
```bash
php artisan serve
```
Akses aplikasi melalui browser di: **`http://127.0.0.1:8000`**

---

## 🧪 Menjalankan Automated Tests

Aplikasi dilengkapi dengan 15 skenario Feature Test komprehensif pada database terpisah (`catatin_test_db`).

Jalankan seluruh pengujian:
```bash
php artisan test
```

Hasil pengujian mencakup:
- ✅ Dashboard terbuka dengan status HTTP 200
- ✅ Daftar catatan dan badge kategori tampil
- ✅ Penyimpanan catatan baru ke database
- ✅ Validasi penolakan data tidak valid
- ✅ Detail catatan dan keamanan escaping HTML
- ✅ Pembaruan data tanpa duplikasi catatan
- ✅ Penghapusan data via method DELETE
- ✅ Penanganan error 404 jika ID tidak ditemukan
- ✅ Pencarian berbasis kata kunci judul dan isi materi
- ✅ Filter mata pelajaran / kategori
- ✅ Fitur toggle favorit (bintang)
- ✅ Halaman filter khusus catatan favorit
- ✅ Akurasi hitungan statistik dashboard
- ✅ Pagination berjalan normal saat data banyak
- ✅ Validasi metode HTTP request penghapusan

---

## 🔒 Catatan Keamanan & Batasan Penggunaan

Aplikasi CatatIn dirancang untuk kebutuhan **praktik lokal dan demonstrasi akademik**. Halaman Admin memerlukan akun aktif ber-role `admin`; akses ditolak untuk guest dan user biasa. Gunakan konfigurasi rahasia lingkungan yang aman dan tinjau seluruh konfigurasi deployment sebelum penggunaan production.
