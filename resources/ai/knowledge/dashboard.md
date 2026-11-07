---
feature: Dashboard
route: /
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Dashboard

## Tujuan
Memberikan ringkasan produktivitas pribadi pengguna dari tugas, target, dan informasi yang perlu ditindaklanjuti.

## Fungsi dan cara penggunaan
- Menampilkan jumlah tugas berdasarkan status pending, dikerjakan, dan tuntas.
- Menampilkan tugas yang deadline-nya dalam tujuh hari.
- Menampilkan tugas yang terlewat.
- Menampilkan target aktif beserta persentase dan sisa nilai.
- Tombol Tambah Tugas membuka formulir tugas.
- Guest melihat ajakan masuk, sedangkan pengguna login dapat membuka menu ringkasan.

## Alur pengguna
1. Masuk atau buka dashboard.
2. Baca ringkasan tugas dan target.
3. Gunakan tautan halaman untuk membuka detail fitur.
4. Tambah atau catat data melalui tombol yang tersedia.

## Input dan validasi
Dashboard tidak memiliki formulir utama. Filter, pencarian, dan validasi dilakukan pada halaman fitur terkait.

## Aturan bisnis
- Semua ringkasan harus dibatasi pada akun pengguna yang login.
- Tugas terlewat adalah tugas belum tuntas yang deadline-nya sebelum hari ini.
- Target aktif adalah target dengan status active.

## Batasan
Dashboard tidak mengubah atau membuat data secara otomatis. Ringkasan bergantung pada data yang tersimpan pada akun pengguna.

## Troubleshooting
- Jika ringkasan kosong, pastikan data sudah dibuat pada akun yang sama.
- Jika data tidak tampil, periksa login dan refresh halaman.
- Dashboard tidak menampilkan data pengguna lain.

## Route terkait
- `/`
- `/dashboard`
- `/tugas`
- `/target`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/CatatanController.php`
- `resources/views/dashboard.blade.php`
