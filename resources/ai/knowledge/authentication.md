---
feature: Registrasi dan Login
route: /login
visibility: public
version: 1.0
last_updated: 2026-11-04
---

# Registrasi dan Login

## Tujuan
Memastikan pengguna memiliki akun pribadi dan dapat masuk atau keluar dengan aman.

## Fungsi dan cara penggunaan
- Pengguna membuka halaman login.
- Form login meminta email dan password.
- Pengguna dapat memilih remember.
- Pengguna dapat membuka tab registrasi dan membuat akun baru.
- Logout mengakhiri sesi autentikasi tanpa menghapus akun atau data.

## Alur pengguna
1. Buka `/login`.
2. Pilih login atau registrasi.
3. Login menggunakan email dan password atau daftar akun baru.
4. Sistem membuat atau memvalidasi session Laravel.
5. Pengguna diarahkan ke halaman yang dimaksud atau dashboard.
6. Logout mengakhiri sesi.

## Input dan validasi
Login:
- Email wajib dan harus format email.
- Password wajib berupa string.
- remember opsional berupa boolean.

Registrasi:
- Nama wajib maksimum 255 karakter.
- Email wajib, format email, maksimum 255 karakter, dan unique pada users.email.
- Password wajib minimal 10 karakter, confirmed, memuat huruf besar, huruf kecil, angka, dan simbol khusus.

## Aturan bisnis
- Password baru disimpan menggunakan hash Laravel.
- Session diregenerasi setelah login atau register.
- Logout memanggil Auth::logout, invalidate session, dan regenerate CSRF token.
- Logout tidak menghapus password hash, akun, catatan, atau data pribadi.
- Data browser/password manager tidak dikontrol oleh CatatIn sebagai penyimpanan global website.
- Input login email browser menggunakan autocomplete username dan password autocomplete current-password.

## Batasan
Tidak ada role admin dalam implementasi saat ini. Guest dapat membuka halaman publik, sedangkan fitur produktivitas memerlukan login.

## Troubleshooting
- Jika login gagal, periksa email dan password.
- Jika email sudah digunakan saat registrasi, gunakan email lain atau login.
- Jika password ditolak, pastikan memenuhi seluruh aturan karakter.
- Jika kredensial tersimpan tidak ditawarkan, periksa pengaturan password manager Chrome pada perangkat.

## Route terkait
- `GET /login`
- `POST /login`
- `POST /register`
- `POST /logout`

## Referensi file
- `routes/web.php`
- `app/Http/Controllers/Auth/AuthController.php`
- `resources/views/auth/login.blade.php`
