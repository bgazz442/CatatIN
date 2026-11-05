---
feature: Task Manager
route: /tugas
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Task Manager

## Tujuan
Mengelola pekerjaan pengguna beserta deskripsi, kategori, deadline, dan status.

## Fungsi dan cara penggunaan
- Melihat daftar tugas milik akun dengan pencarian dan filter.
- Menambah tugas baru.
- Mengubah atau menghapus tugas.
- Mengubah status menjadi Pending, Dikerjakan, atau Tuntas.
- Filter status mendukung pending, in_progress, dan completed.
- Urutan default menempatkan deadline terdekat lebih dahulu; sort created menampilkan tugas terbaru.

## Alur pengguna
1. Buka `/tugas`.
2. Gunakan pencarian, filter status, kategori, atau urutan.
3. Pilih Tambah Tugas untuk membuat data.
4. Edit tugas atau ubah status pada daftar.
5. Hapus tugas melalui tombol yang tersedia.

## Input dan validasi
- Judul wajib, berupa string maksimum 100 karakter, dan harus memenuhi aturan judul alfanumerik CatatIn.
- Deskripsi opsional maksimum 5000 karakter.
- kategori_id opsional dan harus merupakan kategori yang tersedia.
- deadline opsional dan harus berbentuk tanggal.
- status wajib, hanya pending, in_progress, atau completed.

## Aturan bisnis
- Tugas hanya dapat dilihat, diedit, diubah status, atau dihapus oleh pemiliknya.
- Akses tugas milik pengguna lain mengembalikan 404.
- Menghapus tugas tidak menghapus akun atau data pengguna lain.

## Batasan
Task Manager tidak menyediakan assignment antar pengguna, parent/subtask, reminder otomatis, atau proyek lintas akun.

## Troubleshooting
- Jika status atau kategori tidak dapat dipilih, periksa nilai pilihan yang tersedia.
- Jika edit tidak terbuka, kemungkinan tugas milik akun lain.
- Deadline kosong diperbolehkan.

## Route terkait
- `GET /tugas`
- `GET /tugas/tambah`
- `POST /tugas`
- `GET /tugas/{task}/edit`
- `PUT /tugas/{task}`
- `PATCH /tugas/{task}/status`
- `DELETE /tugas/{task}`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/TaskController.php`
- `app/Models/Task.php`
- `resources/views/tasks/index.blade.php`
- `app/Rules/AlphanumericTitle.php`
