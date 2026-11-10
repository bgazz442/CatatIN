# CatatIn

CatatIn adalah aplikasi web Laravel untuk membantu pengguna mengelola catatan belajar dan aktivitas produktivitas dalam satu ruang pribadi. Aplikasi menyediakan dashboard, catatan dan lampiran privat, tugas, target, media belajar tersimpan, serta panel administrasi.

> **Status integrasi:** aplikasi berjalan sebagai proyek Laravel lokal. Mailpit dapat digunakan untuk menguji email reset password secara lokal. Pengiriman email production melalui Brevo memerlukan kredensial SMTP dan sender/domain terverifikasi yang disediakan pemilik deployment; kredensial tersebut tidak disimpan di repository.

## Daftar isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Menjalankan secara lokal dengan Laragon](#menjalankan-secara-lokal-dengan-laragon)
- [Email reset password](#email-reset-password)
- [Lampiran dan penyimpanan file](#lampiran-dan-penyimpanan-file)
- [Akun administrator](#akun-administrator)
- [Pengujian](#pengujian)
- [Catatan deployment](#catatan-deployment)
- [Keamanan dan data](#keamanan-dan-data)

## Fitur

### Akun dan autentikasi

- Registrasi, login, logout, dan opsi “Ingat saya”.
- Form login dikosongkan saat pertama dibuka dan mencegah autofill sebelum pengguna memilih field; password manager browser tetap dapat menawarkan kredensial setelah field dipilih.
- Lupa password melalui tautan email dengan token Laravel yang kedaluwarsa setelah 60 menit dan dibatasi permintaannya.
- Password saat registrasi dan reset minimal 8 karakter; kombinasi huruf, angka, dan simbol bebas. Setelah reset berhasil, password baru langsung menjadi password akun.
- Pesan permintaan reset dibuat generik agar keberadaan akun tidak diumumkan.
- Proteksi route berbasis autentikasi dan peran pengguna/admin.

### Catatan belajar

- Buat, baca, ubah, dan hapus catatan.
- Cari catatan, urutkan, filter kategori, dan tandai favorit.
- Lampirkan dokumen secara privat; pratinjau dan unduhan dilakukan melalui route aplikasi yang memeriksa kepemilikan.

### Produktivitas

- Kelola tugas, tenggat, kategori, dan status pending, dikerjakan, atau selesai.
- Cari dan filter tugas, ubah status, dan pantau tugas mendatang serta tenggat terlewat dari dashboard.
- Buat target dengan nilai saat ini, nilai sasaran, tenggat, tipe target, riwayat progres, dan gambar opsional.
- Dashboard menampilkan ringkasan tugas dan target aktif.

### Media belajar tersimpan

- Simpan tautan media belajar, kategori, platform, deskripsi, dan status favorit.
- Cari, filter, urutkan, lihat detail, ubah, dan hapus media.
- Tautan video dapat ditampilkan melalui embed yang sesuai dengan platform yang didukung. Penyimpanan tautan tidak berarti CatatIn menganalisis isi video.

### Pengaturan dan administrasi

- Pengguna dapat mengubah nama profil dan preferensi warna tampilan miliknya.
- Admin yang berwenang dapat melihat dashboard admin, mengelola pengguna, serta mengubah pengaturan admin.
- Penghapusan atau penurunan status admin terakhir dibatasi oleh aplikasi.

## Teknologi

- PHP 8.3+ dan Laravel 11.
- Blade, Tailwind CSS 3, Vite 5, Bootstrap pada halaman autentikasi, dan ikon Lucide.
- MySQL/MariaDB untuk data aplikasi.
- SMTP Mailpit untuk pengembangan lokal; SMTP Brevo dapat dikonfigurasi untuk pengiriman sungguhan.

## Persyaratan

Untuk pengembangan Windows/Laragon:

- PHP 8.3+ dengan ekstensi Laravel yang diperlukan, termasuk `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, dan `zip`.
- Composer.
- Node.js/npm.
- MySQL atau MariaDB lokal.
- Git.
- Mailpit opsional untuk melihat email reset password lokal.

## Menjalankan secara lokal dengan Laragon

### 1. Ambil source dan pasang dependency

```powershell
git clone https://github.com/bgazz442/CatatIN.git
cd CatatIN
composer install
npm ci
npm run build
```

Jika repository sudah tersedia, pindah ke folder proyek dan sinkronkan branch yang ingin dijalankan sebelum memasang dependency.

### 2. Atur environment dan database

Salin `.env.example` menjadi `.env` bila belum ada. Buat database MySQL/MariaDB lokal, lalu atur `APP_KEY`, URL lokal, dan variabel database seperti `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` di file `.env` lokal.

Contoh URL saat menggunakan `php artisan serve`:

```dotenv
APP_URL=http://127.0.0.1:8000
```

Buat application key jika belum ada:

```powershell
php artisan key:generate
```

### 3. Tinjau migrasi sebelum dijalankan

Periksa status migrasi:

```powershell
php artisan migrate:status
```

**Penting:** migrasi `2026_11_07_000000_remove_daily_activities` menghapus tabel `daily_activities`. Sebelum menerapkan migrasi tertunda pada database yang berisi data penting, tinjau file migrasinya, buat backup, dan tentukan cara mempertahankan data yang dibutuhkan. Jangan menggunakan `migrate:fresh` atau `db:wipe` pada database yang ingin dipertahankan.

Setelah migrasi tertunda ditinjau dan database aman untuk diperbarui:

```powershell
php artisan migrate
```

### 4. Jalankan aplikasi

```powershell
php artisan serve
```

Buka `http://127.0.0.1:8000`. Untuk pengembangan frontend dengan hot reload, jalankan `npm run dev` di terminal terpisah.

## Email reset password

### Pengiriman lokal dengan Mailpit

Mailpit menangkap email di komputer lokal dan **tidak** mengirim email tersebut ke Gmail/internet. Jalankan Mailpit:

```powershell
mailpit
```

Default alamat Mailpit:

- SMTP: `127.0.0.1:1025`
- Web inbox: <http://127.0.0.1:8025>

Atur nilai berikut secara manual di `.env` lokal:

```dotenv
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="catatin@localhost"
MAIL_FROM_NAME="CatatIn"
```

Jika aplikasi dijalankan melalui virtual host Laragon, ganti `APP_URL` dengan origin virtual host yang benar. `config/mail.php` menggunakan variabel SMTP individual dan tidak memakai `MAIL_URL`, sehingga `MAIL_URL` tidak menimpa host/port tersebut. Setelah mengubah variabel, restart server Laravel atau bersihkan config cache bila sebelumnya pernah diaktifkan:

```powershell
php artisan config:clear
```

Untuk menguji, buka halaman **Lupa password?**, masukkan email akun yang ada di database lokal, lalu lihat pesan **Reset Password Notification** di inbox Mailpit. Link dalam email menggunakan `APP_URL`; buka link pada browser yang dapat mengakses alamat lokal tersebut. Token berlaku 60 menit, tidak dapat dipakai kembali setelah berhasil, dan endpoint permintaan reset diberi rate limit.

### Pengiriman sungguhan dengan Brevo SMTP

Brevo SMTP belum dapat dianggap aktif sampai pemilik aplikasi memasukkan kredensial SMTP yang valid dan melakukan uji kirim nyata. Dapatkan **SMTP login** dan **SMTP key** melalui pengaturan SMTP Brevo; jangan gunakan API key sebagai SMTP password. Verifikasi alamat pengirim/domain di Brevo terlebih dahulu.

Contoh variabel untuk `.env` production atau secret manager hosting:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=login-smtp-brevo-anda
MAIL_PASSWORD=smtp-key-brevo-anda
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=email-pengirim-terverifikasi
MAIL_FROM_NAME="CatatIn"
```

Ganti semua nilai contoh sebelum digunakan. Jangan commit `.env`, SMTP key, password, atau kredensial. Atur `APP_URL` ke domain HTTPS production—jangan memakai localhost. Setelah kredensial diatur, bersihkan config cache bila perlu, kirim permintaan reset ke akun uji yang dapat diakses, dan pastikan email benar-benar diterima. Penerimaan oleh Mailpit hanya menguji alur lokal, bukan pengiriman Brevo ke inbox sungguhan.

## Lampiran dan penyimpanan file

Lampiran catatan dan gambar target disimpan pada disk privat `learning_private`, bukan direktori publik. Route pratinjau/unduh memeriksa kepemilikan sebelum mengirim file. Untuk deployment, gunakan persistent storage privat atau object storage yang dikonfigurasi dan dilindungi dengan benar. Jangan mengekspos folder storage privat melalui web server. Disk `public` hanya untuk berkas yang memang sengaja dibuat publik.

## Akun administrator

Admin awal dibuat melalui seeder `AdminSeeder`, bukan akun yang otomatis dibuat saat registrasi biasa. Sebelum menjalankan seeder, atur `ADMIN_PASSWORD` hanya di environment lokal/secret store. Password admin awal minimal 16 karakter serta harus memiliki huruf besar, huruf kecil, angka, dan simbol. Aturan password admin ini terpisah dari aturan registrasi dan reset akun pengguna.

Tinjau kode seeder dan pastikan database yang dipilih benar sebelum menjalankan:

```powershell
php artisan db:seed --class=AdminSeeder
```

Seeder tidak mengubah akun admin yang sudah ada dan akan menolak mengambil alih email yang sudah dimiliki akun non-admin. Jangan menaruh password admin di Git atau README.

## Pengujian

Konfigurasi PHPUnit di `phpunit.xml` menunjuk database MySQL **terpisah** bernama `catatin_test_db`, memakai mailer `array`, dan session driver `array`. Pastikan database tersebut memang database tes dan bukan database berisi data lokal/production sebelum menjalankan:

```powershell
php artisan test
```

Jalankan pemeriksaan frontend dan route secara opsional:

```powershell
npm run build
php artisan route:list
```

Tes reset password mencakup validasi email, token valid/kedaluwarsa/tidak valid, penggunaan ulang token, hashing password, login dengan password baru, URL `APP_URL`, throttling, respons generik, dan kegagalan SMTP. Tes otomatis tidak membuktikan bahwa SMTP production dapat mengirim email ke inbox penerima.

## Catatan deployment

Source ini adalah aplikasi Laravel full-stack dengan MySQL, session/cache, private file storage, dan email. Repository saja tidak menyediakan database production, kredensial email, atau storage persisten. Siapkan dependency tersebut di hosting sebelum aplikasi digunakan.

Untuk deployment Laravel:

1. Gunakan host PHP yang mendukung Laravel 11 dan PHP 8.3+.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS publik, `APP_KEY`, dan seluruh secret di environment hosting.
3. Percayakan proxy HTTPS yang benar bila berada di belakang reverse proxy; proyek mengaktifkan trust proxy Laravel dan memaksa skema URL HTTPS pada production.
4. Konfigurasikan database, session/cache, email, serta penyimpanan privat yang persisten.
5. Siapkan Node/npm saat build dan jalankan `npm ci` lalu `npm run build` agar `public/build/manifest.json` dan aset Vite tersedia.
6. Tinjau migrasi tertunda serta backup sebelum migrasi database.
7. Uji email, lampiran privat, login, dan semua layanan eksternal dari environment hosting.

Jangan menganggap pengiriman Brevo atau layanan production lain sudah siap sebelum konfigurasi hosting dan pengujian nyata dilakukan. Vercel saja bukan pengganti backend Laravel persisten.

## Keamanan dan data

- Jangan commit `.env`, kredensial, SMTP key/API key, backup, dump SQL, log, atau file pengguna privat.
- Gunakan HTTPS, `APP_DEBUG=false`, secret yang unik, akun database berhak minimum, backup, dan pemantauan error untuk production.
- Jangan mengarahkan tes atau perintah migrasi ke database production.
- Tinjau dan backup data sebelum migrasi yang mengubah atau menghapus tabel.
- Jaga lampiran privat tetap di disk non-publik dan pertahankan pemeriksaan otorisasi pemilik.
