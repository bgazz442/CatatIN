---
feature: Catatan Belajar
route: /catatan
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Catatan Belajar

## Tujuan
Menyimpan materi belajar pribadi sebagai catatan dan lampiran yang dapat dicari, difavoritkan, diedit, dan dihapus.

## Fungsi dan cara penggunaan
- Melihat daftar catatan milik pengguna.
- Membuat, melihat, mengubah, dan menghapus catatan.
- Menandai catatan sebagai favorit.
- Melihat kategori dan filter favorit.
- Mengunggah serta mengelola lampiran sesuai konfigurasi learning.
- Fitur AI dapat meretrieval isi catatan dan lampiran teks yang mendukung.

## Alur pengguna
1. Buka `/catatan`.
2. Buat catatan baru dan isi judul, kategori, dan isi.
3. Unggah lampiran bila diperlukan.
4. Gunakan filter atau favorit untuk menemukan catatan.
5. Edit atau hapus catatan melalui halaman terkait.

## Input dan validasi
- Judul wajib, maksimum 150 karakter.
- Isi/isi catatan mengikuti aturan konfigurasi learning.
- Kategori dapat dipilih atau dibuat melalui fitur kategori.
- Lampiran dibatasi konfigurasi uploads pada config learning.
- Validasi attachment diperiksa sebelum penyimpanan.

## Aturan bisnis
- Catatan dan lampiran dibatasi pada pemilik akun.
- Pengguna tidak dapat membuka catatan milik pengguna lain melalui route biasa.
- Menghapus catatan dapat menghapus material index terkait.
- Data favorit dapat diubah melalui route toggle.

## Batasan
- PDF dan gambar belum diekstraksi secara lengkap oleh chatbot.
- Lampiran TXT dan CSV merupakan format teks yang dapat diproses oleh retriever learning saat ini.
- AI tidak mengubah atau menghapus catatan tanpa aksi pengguna dari website.

## Troubleshooting
- Jika lampiran ditolak, periksa ekstensi dan batas ukuran konfigurasi learning.
- Jika catatan tidak muncul di pencarian AI, pastikan catatan milik akun login dan embedding Ollama tersedia.
- Jika materi berubah, cache embedding diperbarui berdasarkan content hash saat dilakukan retrieval.

## Route terkait
- `GET /catatan`
- `POST /catatan`
- `GET /catatan/{id}`
- `PUT /catatan/{id}`
- `DELETE /catatan/{id}`
- `PATCH /catatan/{id}/favorit`
- `GET /lampiran/{attachment}/preview`
- `GET /lampiran/{attachment}/download`
- `DELETE /lampiran/{attachment}`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/CatatanController.php`
- `app/Http/Controllers/CatatanAttachmentController.php`
- `app/Models/Catatan.php`
- `app/Services/AI/LearningMaterialRetriever.php`
- `app/Services/AI/LearningMaterialIndexer.php`
