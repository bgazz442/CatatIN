---
feature: Pengaturan
route: /pengaturan
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Pengaturan

## Tujuan
Memungkinkan pengguna mengubah warna tampilan CatatIn pada akun mereka sendiri.

## Fungsi dan cara penggunaan
- Memilih preset warna utama.
- Memilih warna utama custom.
- Memilih preset navbar/footer gelap.
- Memilih warna navbar/footer custom.
- Menyimpan preferensi tema untuk akun login.

## Alur pengguna
1. Buka `/pengaturan`.
2. Pilih warna utama atau preset.
3. Pilih warna navbar/footer.
4. Pengguna login menekan Simpan tampilan.
5. Sistem menyimpan UserPreference milik akun.

## Input dan validasi
- accent_color wajib, format hex #RRGGBB, dan memiliki rasio kontras minimal 4.5 terhadap putih.
- navigation_footer_color wajib, format hex #RRGGBB, dan memiliki kontras teks putih yang cukup.
- Guest hanya dapat melihat tombol login untuk menyimpan.

## Aturan bisnis
- Preferensi tema disimpan menggunakan user_id.
- Setiap akun dapat memiliki kombinasi tema tersendiri.
- Perubahan hanya memengaruhi tampilan akun tersebut.

## Batasan
Pengaturan saat ini berfokus pada tema visual. Tidak tersedia perubahan nama/email/password atau penghapusan akun pada halaman ini.

## Troubleshooting
- Jika warna ditolak, pilih warna yang cukup gelap atau kontras dengan teks putih.
- Jika preferensi tidak tersimpan, pastikan pengguna login.
- Refresh atau relogin bila tema tidak diterapkan pada seluruh tampilan.

## Route terkait
- `GET /pengaturan`
- `POST /pengaturan/tampilan`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/SettingsController.php`
- `app/Models/UserPreference.php`
- `resources/views/settings.blade.php`
