---
feature: Goal Tracker
route: /target
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Goal Tracker

## Tujuan
Membantu pengguna menetapkan target, mencatat progres, dan memantau pencapaian target pribadi.

## Fungsi dan cara penggunaan
- Membuat target dengan nama, deskripsi, nilai target, satuan, tanggal mulai, deadline, dan gambar motivasi opsional.
- Melihat daftar target aktif dan selesai.
- Menambah catatan progres dengan jumlah penambahan dan catatan.
- Mengubah atau menghapus target beserta riwayat progresnya.

## Alur pengguna
1. Buka `/target`.
2. Buat target baru.
3. Isi nilai target, satuan, dan tanggal.
4. Tambahkan progres melalui detail target.
5. Sistem memperbarui nilai berjalan dan status aktif/tuntas.

## Input dan validasi
- Nama wajib dan maksimum 100 karakter.
- Deskripsi opsional maksimum 5000 karakter.
- target_value wajib numerik lebih besar dari nol dengan maksimum dua desimal.
- unit wajib maksimum 40 karakter.
- start_date wajib dan harus tanggal.
- deadline opsional dan harus setelah atau sama dengan tanggal mulai.
- Gambar opsional harus JPG/JPEG/PNG/WebP, maksimum 5 MB, dan maksimum 4096 x 4096.
- Progres amount wajib numerik lebih besar dari nol, maksimum dua desimal.
- Catatan progres opsional maksimum 500 karakter.

## Aturan bisnis
- Progress awal selalu 0 ketika target dibuat.
- Progres awal tidak boleh dikirim sebagai field terpisah saat pembuatan.
- Status berubah menjadi completed jika nilai berjalan mencapai nilai target.
- Status kembali active jika update target membuat nilai berjalan tetap di bawah target.
- Target dan riwayat progres dibatasi pada pemiliknya.
- Setiap pengguna dapat memiliki data target sendiri.

## Batasan
Tidak ada target bersama antar pengguna, penjadwalan progres otomatis, atau pembagian target ke akun lain.

## Troubleshooting
- Jika deadline ditolak, pastikan deadline tidak sebelum start date.
- Jika gambar ditolak, periksa format, ukuran, dan dimensi.
- Jika progres tidak bertambah, pastikan amount lebih besar dari 0 dan transaksi berhasil.

## Route terkait
- `GET /target`
- `POST /target`
- `GET /target/{goal}`
- `POST /target/{goal}/progres`
- `PUT /target/{goal}`
- `DELETE /target/{goal}`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/GoalController.php`
- `app/Models/Goal.php`
- `resources/views/goals/index.blade.php`
