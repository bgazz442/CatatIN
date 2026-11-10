# Flowchart CatatIn

> Diagram berikut menggambarkan alur yang ditemukan pada source code dan dokumentasi aplikasi, bukan rancangan fitur masa depan.

## Daftar Isi

- [Main Application Flow](#main-application-flow)
- [Authentication Flow](#authentication-flow)
- [Learning Notes Flow](#learning-notes-flow)
- [Task Manager Flow](#task-manager-flow)
- [Goal Tracker Flow](#goal-tracker-flow)
- [Admin Flow](#admin-flow)
- [Settings Flow](#settings-flow)

## Main Application Flow

Alur umum dimulai dari dashboard yang dapat dibuka guest. Fitur produktivitas memerlukan user aktif, sedangkan area admin memerlukan admin aktif. Jika guest mencoba halaman privat dari navigasi, aplikasi mengarahkannya ke login.

```mermaid
flowchart TD
    A["Mulai"] --> B["Buka dashboard atau halaman CatatIn"]
    B --> C{"Halaman memerlukan autentikasi?"}
    C -->|"Tidak"| D["Tampilkan halaman publik"]
    C -->|"Ya"| E{"Sudah login?"}
    E -->|"Belum"| F["Tampilkan login dengan tujuan kembali bila tersedia"]
    F --> G["Authentication Flow"]
    G --> H{"Akun aktif dan role sesuai?"}
    E -->|"Sudah"| H
    H -->|"Tidak"| I["Tolak akses"]
    H -->|"User aktif"| J["Dashboard dan fitur pribadi"]
    H -->|"Admin aktif"| K["Dashboard dan fitur admin"]
    J --> L{"Pilih fitur"}
    L --> M["Learning Notes Flow"]
    L --> N["Task Manager Flow"]
    L --> O["Goal Tracker Flow"]
    L --> P["Settings Flow"]
    K --> R["Admin Flow"]
```

**Keterangan proses:** dashboard `/` bersifat publik. Middleware `auth` dan role aktif membatasi halaman privat. Navigasi sidebar berbeda untuk pengguna dan admin.

## Authentication Flow

Pengguna dapat masuk, mendaftar, atau meminta reset password. Akun baru langsung login setelah berhasil dibuat. Login berhasil memerlukan akun aktif; reset password bergantung pada email yang dikonfigurasi.

```mermaid
flowchart TD
    A["Buka halaman login"] --> B{"Pilih tindakan"}
    B -->|"Masuk"| C["Isi email dan password"]
    C --> D{"Input valid dan akun aktif?"}
    D -->|"Tidak"| E["Tampilkan kesalahan dan kembali ke login"]
    E --> C
    D -->|"Ya"| F["Autentikasi dan regenerasi sesi"]
    F --> G{"Role akun"}
    G -->|"User"| H["Dashboard pengguna atau tujuan awal"]
    G -->|"Admin"| I["Dashboard admin"]
    B -->|"Daftar"| J["Isi nama, email, password, konfirmasi"]
    J --> K{"Email unik dan password memenuhi aturan?"}
    K -->|"Tidak"| L["Tampilkan kesalahan registrasi"]
    L --> J
    K -->|"Ya"| M["Buat akun user dan login"]
    M --> H
    B -->|"Lupa password"| N["Isi email"]
    N --> O["Kirim permintaan tautan reset"]
    O --> P["Tampilkan respons generik"]
    P --> Q{"Tautan valid dan belum kedaluwarsa?"}
    Q -->|"Tidak"| R["Tampilkan kesalahan token"]
    Q -->|"Ya"| S["Isi password baru dan konfirmasi"]
    S --> T{"Password memenuhi aturan?"}
    T -->|"Tidak"| U["Tampilkan kesalahan validasi"]
    U --> S
    T -->|"Ya"| V["Simpan hash password baru"]
    V --> W["Arahkan ke login"]
```

**Keterangan proses:** password registrasi dan reset minimum 8 karakter, tanpa syarat kombinasi karakter tertentu; konfirmasi password tetap harus sama. Respons permintaan reset tidak mengungkap apakah email terdaftar. Login dan reset diberi pembatasan request.

## Learning Notes Flow

User aktif dapat mengelola catatan miliknya, mencari dan memfilter daftar, serta mengunggah lampiran yang memenuhi konfigurasi aplikasi.

```mermaid
flowchart TD
    A["Buka daftar catatan"] --> B["Tampilkan catatan milik user"]
    B --> C{"Pilih tindakan"}
    C -->|"Cari atau filter"| D["Cari judul/isi, kategori, favorit, atau urutan"]
    D --> B
    C -->|"Buat catatan"| E["Isi judul, kategori, isi, lampiran opsional"]
    C -->|"Buka catatan"| F{"Catatan milik user?"}
    F -->|"Tidak"| G["Tolak akses"]
    F -->|"Ya"| H["Tampilkan detail catatan"]
    H --> I{"Pilih tindakan detail"}
    I -->|"Favorit"| J["Ubah status favorit"]
    J --> H
    I -->|"Edit"| K["Ubah data atau kelola lampiran"]
    K --> E
    I -->|"Hapus"| L["Hapus catatan dan lampiran terkait"]
    L --> B
    E --> M{"Valid dan isi atau lampiran tersedia?"}
    M -->|"Tidak"| N["Tampilkan kesalahan validasi"]
    N --> E
    M -->|"Ya"| O["Simpan catatan dan file pada storage privat"]
    O --> B
```

**Keterangan proses:** kategori wajib dipilih dari data yang tersedia. Catatan harus memiliki isi atau minimal satu lampiran. Akses preview/download lampiran melalui route yang memeriksa pemilik catatan.

## Task Manager Flow

Task Manager menyediakan daftar, filter, CRUD, dan perubahan status tugas milik user aktif.

```mermaid
flowchart TD
    A["Buka daftar tugas"] --> B["Tampilkan tugas milik user"]
    B --> C{"Pilih tindakan"}
    C -->|"Cari/filter/urutkan"| D["Atur pencarian, status, kategori, atau urutan"]
    D --> B
    C -->|"Tambah"| E["Isi judul, deskripsi, kategori opsional, tenggat opsional, status"]
    C -->|"Edit"| F{"Tugas milik user?"}
    F -->|"Tidak"| G["Tolak akses"]
    F -->|"Ya"| E
    C -->|"Ubah status"| H["Pilih status tugas"]
    H --> I{"Status valid?"}
    I -->|"Tidak"| J["Tampilkan kesalahan"]
    I -->|"Ya"| K["Simpan status"]
    K --> B
    C -->|"Hapus"| L{"Tugas milik user?"}
    L -->|"Tidak"| G
    L -->|"Ya"| M["Hapus tugas"]
    M --> B
    E --> N{"Input valid?"}
    N -->|"Tidak"| O["Tampilkan kesalahan validasi"]
    O --> E
    N -->|"Ya"| P["Simpan tugas"]
    P --> B
```

**Keterangan proses:** status yang diterima adalah pending, in progress, dan completed. Tenggat opsional; tugas belum tuntas bertenggat sebelum hari ini terhitung terlewat pada dashboard.

## Goal Tracker Flow

Goal Tracker mengelola target pribadi serta riwayat perubahan progres.

```mermaid
flowchart TD
    A["Buka daftar target"] --> B["Tampilkan target milik user"]
    B --> C{"Pilih tindakan"}
    C -->|"Buat target"| D["Isi nama, jenis, nilai target, tanggal, satuan bila custom"]
    D --> E{"Data valid?"}
    E -->|"Tidak"| F["Tampilkan kesalahan"]
    F --> D
    E -->|"Ya"| G["Simpan target dengan progres nol dan status aktif"]
    G --> B
    C -->|"Buka target"| H{"Target milik user?"}
    H -->|"Tidak"| I["Tolak akses"]
    H -->|"Ya"| J["Tampilkan target dan riwayat progres"]
    J --> K{"Pilih tindakan"}
    K -->|"Tambah atau kurangi progres"| L["Isi jumlah, arah, dan catatan opsional"]
    L --> M{"Perubahan tidak membuat nilai negatif atau persentase melewati target?"}
    M -->|"Tidak"| N["Tampilkan kesalahan"]
    N --> L
    M -->|"Ya"| O["Simpan entri dan hitung nilai berjalan"]
    O --> P{"Nilai berjalan mencapai target?"}
    P -->|"Ya"| Q["Tandai selesai"]
    P -->|"Tidak"| R["Tandai aktif"]
    Q --> J
    R --> J
    K -->|"Edit target"| S["Validasi dan simpan perubahan target"]
    S --> T["Hitung ulang status dari nilai berjalan"]
    T --> J
    K -->|"Hapus target"| U["Hapus target dan riwayat progres"]
    U --> B
```

**Keterangan proses:** gambar target bersifat opsional dan privat. Perubahan progres dilakukan dalam transaksi agar nilai target dan entri riwayat tetap konsisten.

## Admin Flow

Admin aktif diarahkan ke area administrasi. Hak untuk mengelola akun admin dibatasi, dan admin terakhir dilindungi dari penghapusan atau penonaktifan.

```mermaid
flowchart TD
    A["Admin masuk"] --> B{"Role admin dan akun aktif?"}
    B -->|"Tidak"| C["Tolak akses admin"]
    B -->|"Ya"| D["Dashboard admin"]
    D --> E{"Pilih menu"}
    E -->|"Dashboard"| F["Lihat statistik akun dan data agregat"]
    F --> D
    E -->|"Manajemen pengguna"| G["Cari atau filter daftar pengguna"]
    G --> H["Buka detail pengguna"]
    H --> I{"Akun target adalah diri sendiri?"}
    I -->|"Ya"| J["Tampilkan tanpa aksi perubahan akun"]
    I -->|"Tidak"| K{"Target akun admin?"}
    K -->|"Ya"| L{"Admin saat ini admin utama?"}
    L -->|"Tidak"| M["Tolak perubahan akun admin"]
    L -->|"Ya"| N["Perbarui atau hapus akun sesuai validasi"]
    K -->|"Tidak"| O["Perbarui atau hapus akun user"]
    N --> P{"Operasi menonaktifkan atau menghapus admin terakhir?"}
    O --> Q["Simpan perubahan"]
    P -->|"Ya"| R["Tolak operasi"]
    P -->|"Tidak"| Q
    Q --> G
    R --> G
    M --> G
    J --> G
    E -->|"Pengaturan admin"| S["Ubah nama/email atau password akun admin"]
    S --> T{"Password saat ini valid bila password diganti?"}
    T -->|"Tidak"| U["Tampilkan kesalahan"]
    T -->|"Ya"| V["Simpan pengaturan admin"]
    U --> S
    V --> D
```

**Keterangan proses:** admin tidak dapat mengubah atau menghapus dirinya melalui manajemen pengguna. Penggantian password admin memerlukan password saat ini. Perlindungan admin terakhir juga ditegakkan pada model.

## Settings Flow

Halaman pengaturan tampilan dapat dibuka guest, tetapi penyimpanan preferensi memerlukan user aktif. Admin diarahkan ke halaman pengaturan admin.

```mermaid
flowchart TD
    A["Buka /pengaturan"] --> B{"Role akun"}
    B -->|"Admin"| C["Alihkan ke pengaturan admin"]
    C --> D["Ubah nama/email atau password opsional"]
    D --> E{"Password saat ini valid jika password diganti?"}
    E -->|"Tidak"| F["Tampilkan kesalahan"]
    F --> D
    E -->|"Ya"| G["Simpan pengaturan akun admin"]
    G --> C
    B -->|"User atau guest"| H["Tampilkan pratinjau dan pilihan warna"]
    H --> I{"Pilih simpan tema"}
    I -->|"Guest"| J["Minta login untuk menyimpan"]
    J --> K["Authentication Flow"]
    K --> H
    I -->|"User aktif"| L["Validasi format warna dan kontras"]
    L --> M{"Warna valid?"}
    M -->|"Tidak"| N["Tampilkan kesalahan validasi"]
    N --> H
    M -->|"Ya"| O["Simpan preferensi warna milik user"]
    O --> H
    H --> P{"User membuka menu akun?"}
    P -->|"Ya, user login"| Q["Ubah nama profil"]
    Q --> R{"Nama valid?"}
    R -->|"Tidak"| S["Tampilkan kesalahan"]
    S --> Q
    R -->|"Ya"| T["Simpan nama dan kembali ke halaman asal"]
    P -->|"Tidak"| U["Selesai"]
    T --> U
```

**Keterangan proses:** warna harus berupa hex `#RRGGBB` dan memenuhi rasio kontras minimum 4,5 terhadap putih. Pengguna biasa dapat mengubah nama, tetapi source tidak menyediakan penggantian email/password biasa dari halaman pengaturan ini.
