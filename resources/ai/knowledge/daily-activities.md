---
feature: Daily Activities
route: /aktivitas
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Daily Activities

## Tujuan
Mencatat kegiatan harian beserta kategori, waktu, deskripsi, dan durasi.

## Fungsi dan cara penggunaan
- Memilih tanggal pada kalender.
- Menelusuri bulan sebelumnya dan berikutnya.
- Memfilter aktivitas berdasarkan kategori.
- Melihat jumlah aktivitas, total durasi, dan jumlah kategori pada tanggal dipilih.
- Menambah, melihat, mengubah, atau menghapus aktivitas.

## Alur pengguna
1. Buka `/aktivitas`.
2. Pilih tanggal atau bulan.
3. Terapkan filter kategori jika diperlukan.
4. Tambah aktivitas dari formulir.
5. Gunakan menu lihat atau edit pada item.

## Input dan validasi
- Judul wajib, maksimum 100 karakter, dan memenuhi aturan judul alfanumerik.
- Deskripsi opsional maksimum 5000 karakter.
- Tanggal aktivitas wajib dan harus berupa tanggal.
- Waktu mulai dan selesai opsional, format H:i.
- Waktu selesai tidak boleh lebih awal dari waktu mulai.
- Kategori wajib dan harus ada pada tabel kategori.

## Aturan bisnis
- Aktivitas hanya milik pengguna login dan dibatasi melalui user_id.
- Kalender menandai tanggal yang memiliki aktivitas.
- Durasi dihitung dari selisih waktu mulai dan selesai.
- Pengguna tidak dapat mengakses atau mengubah aktivitas milik akun lain.

## Batasan
Tidak ada timer real-time atau pencatatan aktif otomatis. Aktivitas dihitung berdasarkan data tanggal dan waktu yang disimpan.

## Troubleshooting
- Jika end time ditolak, ubah agar tidak lebih awal dari start time.
- Jika kategori kosong, tambahkan atau pilih kategori terlebih dahulu.
- Jika aktivitas tidak terlihat pada bulan lain, pilih tanggal pada bulan yang sesuai.

## Route terkait
- `GET /aktivitas`
- `GET /aktivitas/tambah`
- `POST /aktivitas`
- `GET /aktivitas/{activity}`
- `PUT /aktivitas/{activity}`
- `DELETE /aktivitas/{activity}`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/DailyActivityController.php`
- `app/Models/DailyActivity.php`
- `resources/views/activities/index.blade.php`
