# Product Requirements Document (PRD) - CatatIn

| Informasi | Detail |
|---|---|
| Produk | CatatIn |
| Status | Draft berdasarkan implementasi repository |
| Versi | 1.0 |
| Tanggal | 10 November 2026 |
| Bahasa | Indonesia |
| Sumber utama | [README](../README.md), [routes/web.php](../routes/web.php), dan implementasi fitur pada `app/`, `resources/views/`, serta `resources/ai/knowledge/` |

> Dokumen ini merangkum kebutuhan dan perilaku produk yang terlihat pada source code. Dokumen ini bukan hasil riset pengguna atau spesifikasi fitur baru yang telah disetujui. Tujuan, metrik, dan asumsi perlu divalidasi dengan pemilik produk dan pengguna.

## 1. Ringkasan Produk

CatatIn adalah aplikasi web untuk membantu individu mengatur kegiatan produktivitas dan materi belajar dalam satu ruang pribadi. Pengguna dapat mencatat bahan belajar, mengelola tugas dan target, menyimpan tautan media belajar, serta meminta bantuan CatatIn Assistant untuk menemukan informasi dari data yang dapat diakses akun tersebut.

Informasi pengguna bersifat privat dan dipisahkan berdasarkan akun. Aplikasi juga menyediakan dashboard ringkasan serta akses administrasi khusus untuk admin.

## 2. Masalah dan Peluang

Aktivitas belajar dan pekerjaan pribadi kerap tersebar di berbagai catatan, daftar tugas, dan tautan. CatatIn menyatukan informasi tersebut agar pengguna dapat menyimpan, mengelola, dan menemukannya kembali dari satu aplikasi.

**Peluang produk:** mengurangi hambatan untuk mencatat informasi, mengetahui pekerjaan yang perlu ditindaklanjuti, menjaga progres target tetap terlihat, dan menemukan kembali materi yang relevan.

Pernyataan masalah ini merupakan hipotesis produk dan belum didukung data riset pengguna di repository.

## 3. Visi dan Tujuan

### Visi

Menjadi ruang pribadi yang mudah digunakan untuk belajar, merencanakan pekerjaan, dan menjaga progres.

### Tujuan produk

1. Memudahkan pengguna mengelola catatan belajar dan materi pendukung.
2. Membantu pengguna melihat dan memperbarui status tugas serta target.
3. Memberikan ringkasan produktivitas yang relevan pada dashboard.
4. Membantu pengguna menemukan konteks dari catatan dan data ruang kerja melalui asisten AI, dengan batas akses yang jelas.
5. Menjaga data pribadi tetap hanya dapat diakses pemilik akun dan admin sesuai kewenangan.

### Metrik keberhasilan yang disarankan

Belum ada baseline atau instrumentasi analitik yang terkonfirmasi. Metrik berikut perlu disepakati sebelum digunakan untuk evaluasi:

- Aktivasi: persentase akun baru yang membuat catatan, tugas, atau target pertama.
- Retensi: pengguna aktif mingguan dan bulanan.
- Keterlibatan: jumlah catatan, tugas, dan pembaruan progres yang dibuat per pengguna aktif.
- Penyelesaian tugas: persentase tugas yang berpindah ke status tuntas.
- Keberhasilan pencarian bantuan: persentase sesi AI yang menghasilkan jawaban berguna menurut penilaian pengguna.
- Kualitas layanan: tingkat kegagalan request, penyimpanan lampiran, dan respons AI.
- Kepercayaan: jumlah insiden akses lintas akun yang terkonfirmasi; targetnya nol.

## 4. Pengguna Sasaran dan Persona

### Persona utama: Pelajar atau pembelajar mandiri

- Menyimpan catatan dan dokumen belajar.
- Mengelompokkan materi berdasarkan kategori.
- Menandai materi penting dan mencari kembali informasi yang tersimpan.
- Menggunakan AI untuk bertanya tentang materi atau catatan yang dimiliki.

### Persona pendukung: Individu yang mengelola pekerjaan dan tujuan pribadi

- Mencatat pekerjaan, tenggat, dan status tugas.
- Menyusun target dengan nilai, satuan, dan tenggat.
- Memperbarui progres dan memantau tugas yang akan datang atau terlewat.

### Persona operasional: Admin

- Memantau ringkasan administrasi.
- Mengelola akun pengguna dan pengaturan admin dengan akses yang dibatasi oleh peran.
- Menjaga agar perubahan akun tidak menghilangkan perlindungan admin terakhir.

Persona tersebut diturunkan dari fitur aplikasi, bukan hasil wawancara pengguna.

## 5. Ruang Lingkup Produk

### Termasuk dalam produk

- Registrasi, login, logout, dan pemulihan password.
- Dashboard produktivitas.
- Catatan belajar, kategori, favorit, dan lampiran privat.
- Tugas dengan kategori, tenggat, dan status.
- Target pribadi dengan progres dan riwayat progres.
- Media belajar berupa tautan, pencarian, kategori, dan favorit.
- CatatIn Assistant dengan riwayat percakapan dan konteks yang tersedia.
- Pengaturan profil dan preferensi tampilan.
- Panel admin untuk pengelolaan pengguna dan pengaturan admin.

### Tidak termasuk atau belum aktif

- Kolaborasi, berbagi catatan, penugasan pekerjaan ke pengguna lain, atau target bersama.
- Perubahan data pengguna secara otomatis oleh AI.
- Analisis otomatis isi video hanya karena tautannya disimpan.
- Transkripsi audio/video baru; alur transkripsi saat ini dinonaktifkan.
- Klaim bahwa AI memproses PDF, gambar, audio, atau video secara penuh.
- Pengukuran metrik produk sebelum instrumentasi dan definisi metrik disepakati.

## 6. Alur Pengguna Utama

### A. Mulai menggunakan CatatIn

1. Pengunjung membuka halaman dashboard.
2. Pengunjung memilih masuk atau mendaftar.
3. Setelah autentikasi, pengguna dapat membuka fitur produktivitas.
4. Pengguna membuat catatan, tugas, atau target pertama.

### B. Mengelola pekerjaan

1. Pengguna membuka daftar tugas.
2. Pengguna mencari atau memfilter tugas berdasarkan status, kategori, atau urutan.
3. Pengguna membuat atau mengubah tugas dan tenggat.
4. Pengguna memperbarui status tugas hingga tuntas.
5. Dashboard menampilkan ringkasan, tugas mendatang, dan tugas terlewat.

### C. Menyimpan materi belajar

1. Pengguna membuat catatan dan memilih atau membuat kategori.
2. Pengguna dapat menambahkan lampiran yang didukung.
3. Pengguna mencari, memfavoritkan, melihat, mengubah, atau menghapus catatan.
4. Pengguna dapat menyimpan tautan media belajar secara terpisah.

### D. Mengelola target

1. Pengguna membuat target dengan nilai, satuan, dan tanggal mulai.
2. Pengguna menambahkan progres beserta catatan opsional.
3. Sistem memperbarui nilai berjalan dan status target.
4. Pengguna meninjau detail target dan riwayat progres.

### E. Menggunakan CatatIn Assistant

1. Pengguna yang sudah login membuka halaman AI.
2. Pengguna mengirim pertanyaan.
3. Jika Ollama tersedia, sistem menghasilkan respons dan dapat menyertakan referensi sumber.
4. Pengguna dapat membuka riwayat atau menghapus percakapan miliknya.
5. AI tidak melakukan mutasi catatan, tugas, atau target.

## 7. Kebutuhan Fungsional

Prioritas: **P0** = inti dan diperlukan untuk penggunaan dasar; **P1** = penting untuk pengalaman produk; **P2** = pendukung atau terbatas pada operasional.

| ID | Prioritas | Kebutuhan | Kriteria penerimaan |
|---|---|---|---|
| FR-01 | P0 | Autentikasi dan akun | Pengguna dapat mendaftar, login, logout, dan meminta reset password. Password baru memenuhi kebijakan keamanan aplikasi; respons permintaan reset tidak mengungkap apakah email terdaftar. |
| FR-02 | P0 | Isolasi data per akun | Catatan, lampiran, tugas, target, progres, percakapan AI, dan preferensi hanya dapat diakses atau diubah oleh pemilik akun. Akses ke data pengguna lain ditolak. |
| FR-03 | P0 | Dashboard produktivitas | Dashboard menampilkan jumlah tugas per status, target aktif, tugas dengan tenggat tujuh hari ke depan, dan tugas belum tuntas yang tenggatnya telah lewat. Data yang ditampilkan sesuai akun aktif. |
| FR-04 | P0 | Catatan belajar | Pengguna dapat membuat, melihat, mencari, mengubah, menghapus, mengategorikan, dan memfavoritkan catatan miliknya. |
| FR-05 | P1 | Lampiran catatan | Pengguna dapat mengunggah lampiran yang memenuhi konfigurasi aplikasi serta mempratinjau, mengunduh, atau menghapusnya. File disimpan privat dan setiap akses memeriksa kepemilikan. |
| FR-06 | P0 | Manajemen tugas | Pengguna dapat membuat, melihat, mencari, memfilter, mengubah, menghapus, dan memperbarui status tugas miliknya. Status yang tersedia adalah pending, in progress, dan completed. |
| FR-07 | P0 | Manajemen target | Pengguna dapat membuat, mengubah, melihat, dan menghapus target pribadi; menambahkan progres positif; serta melihat riwayat progres. Target menjadi completed saat nilai berjalan mencapai nilai target dan kembali active jika perubahan target membuat nilai berjalan di bawah target. |
| FR-08 | P1 | Media belajar tersimpan | Pengguna dapat menyimpan dan mengelola tautan media, kategori, platform, deskripsi, serta status favorit. Tautan dapat ditampilkan melalui embed hanya untuk platform yang didukung; penyimpanan tautan bukan analisis konten media. |
| FR-09 | P1 | CatatIn Assistant | Pengguna login dapat membuat chat dan membuka atau menghapus percakapannya. Jika layanan AI tersedia, AI dapat menggunakan konteks yang relevan dari data akun dan pengetahuan situs. AI tidak boleh membaca data akun lain atau mengubah data melalui chat. |
| FR-10 | P1 | Pengaturan pengguna | Pengguna dapat mengubah nama profil dan preferensi warna tampilan untuk akunnya sendiri. Preferensi warna harus memenuhi validasi kontras aplikasi. |
| FR-11 | P2 | Administrasi | Admin yang berwenang dapat membuka dashboard admin, melihat dan mengelola akun, serta mengubah pengaturan admin. Operasi yang dapat menghapus atau menurunkan status admin terakhir harus ditolak. |
| FR-12 | P2 | Kategori belajar | Pengguna dapat membuat dan mengelola kategori yang tersedia untuk mengorganisasi materi dan fitur terkait sesuai implementasi aplikasi. |

## 8. Kebutuhan Nonfungsional

### Privasi dan keamanan

- Semua fitur produktivitas memerlukan autentikasi dan otorisasi per pemilik data.
- Lampiran dan gambar target disimpan pada penyimpanan privat, bukan direktori publik.
- Validasi input diterapkan pada server; akses data tidak bergantung hanya pada penyembunyian elemen UI.
- Password disimpan dalam bentuk hash; sesi diperbarui pada autentikasi dan diinvalidasi saat logout.
- Data workspace yang diberikan kepada AI dibatasi pada akun yang sedang login. Konten dokumen diperlakukan sebagai data, bukan instruksi sistem.
- Rahasia layanan dan kredensial deployment berada di environment/secret manager, bukan repository.

### Ketersediaan dan ketergantungan

- Fitur dasar aplikasi bergantung pada Laravel dan database yang dikonfigurasi.
- Reset password melalui email memerlukan SMTP yang benar dan pengujian pada lingkungan tujuan.
- CatatIn Assistant bergantung pada layanan Ollama dan model yang tersedia; kegagalan layanan harus ditampilkan sebagai kegagalan, bukan jawaban sukses palsu.
- Penyimpanan lampiran memerlukan disk privat yang persisten pada deployment.

### Kemudahan penggunaan

- Antarmuka menggunakan Bahasa Indonesia untuk alur utama.
- Form memberikan validasi yang jelas ketika input tidak memenuhi aturan.
- Halaman utama menyediakan akses langsung ke tindakan penting dan status yang relevan.
- Dashboard, daftar, formulir, dan navigasi harus tetap dapat digunakan pada viewport yang didukung aplikasi.

## 9. Aturan Bisnis

1. Data produktivitas bersifat per akun dan tidak dibagikan antar pengguna.
2. Tugas belum tuntas dengan tenggat sebelum hari ini termasuk tugas terlewat.
3. Tugas tanpa tenggat diperbolehkan.
4. Status tugas hanya pending, in progress, atau completed.
5. Progres target harus lebih besar dari nol; status target dihitung dari nilai berjalan terhadap nilai target.
6. Kategori dan sumber daya pengguna tidak boleh digunakan untuk mengakses data milik akun lain.
7. CatatIn Assistant hanya memberikan konteks berdasarkan data yang berhak diakses akun aktif dan tidak melakukan perubahan data.
8. Fitur transkripsi lama tidak boleh dipresentasikan sebagai fitur yang tersedia.

## 10. Ketergantungan, Risiko, dan Mitigasi

| Risiko/ketergantungan | Dampak | Mitigasi |
|---|---|---|
| Layanan Ollama atau model tidak tersedia | Jawaban AI tidak dapat dihasilkan | Tampilkan status layanan secara jelas dan pastikan fitur lain tetap dapat digunakan. |
| Email production belum dikonfigurasi | Reset password tidak sampai ke pengguna | Uji pengiriman SMTP sungguhan pada deployment; jangan menyamakan Mailpit dengan pengiriman production. |
| Penyimpanan privat tidak persisten atau terekspos | Kehilangan atau kebocoran lampiran | Gunakan storage privat persisten dan verifikasi otorisasi route preview/download. |
| Scope dan manfaat belum divalidasi lewat riset | Prioritas fitur dan metrik bisa tidak tepat | Wawancarai pengguna sasaran dan validasi alur paling penting sebelum menetapkan roadmap. |
| Metrik belum memiliki baseline | Perubahan produk sulit dievaluasi | Tentukan definisi, sumber data, persetujuan privasi, dan baseline sebelum peluncuran analitik. |

## 11. Kriteria Kesiapan Rilis

- Autentikasi, reset password, dan role access bekerja pada environment target.
- Pemeriksaan akses lintas akun lulus untuk seluruh resource privat.
- Catatan, tugas, target, progres, dan lampiran dapat dibuat serta dikelola melalui alur pengguna.
- Dashboard hanya menampilkan data akun yang sedang login dan menghitung status/tenggat dengan benar.
- Kegagalan layanan email atau AI ditampilkan secara eksplisit.
- File privat tidak dapat diakses melalui URL publik tanpa pemeriksaan otorisasi.
- Migrasi database dan backup ditinjau sebelum deployment.
- Pengujian fungsional yang relevan lulus pada database tes terpisah, bukan database production.

## 12. Asumsi dan Pertanyaan Terbuka

### Asumsi

- CatatIn ditujukan untuk penggunaan personal, bukan kolaborasi tim.
- Implementasi yang ada merupakan representasi utama dari produk yang diminta untuk didokumentasikan.
- Fitur AI bersifat opsional karena memerlukan Ollama yang berjalan.
- Penggunaan utama saat ini adalah aplikasi web berbahasa Indonesia.

### Pertanyaan untuk validasi lanjutan

1. Siapa pengguna utama yang diprioritaskan: pelajar, mahasiswa, pekerja, atau pembelajar umum?
2. Masalah mana yang paling penting untuk diselesaikan: catatan belajar, pengelolaan tugas, target, atau pencarian AI?
3. Apakah ada target penggunaan, retensi, atau penyelesaian tugas yang diharapkan?
4. Apakah aplikasi akan tetap hanya untuk satu pengguna per ruang kerja, atau kolaborasi masuk roadmap?
5. Format dan batas lampiran mana yang perlu didukung secara resmi?
6. Bagaimana kepuasan atau kualitas jawaban AI akan diukur tanpa menyimpan data sensitif yang tidak diperlukan?
7. Apakah ada halaman website publik atau desain terbaru yang perlu dicocokkan dengan PRD ini?

## 13. Referensi Implementasi

- [README.md](../README.md) - gambaran produk, integrasi, dan batasan deployment.
- [routes/web.php](../routes/web.php) - halaman dan route fitur.
- [resources/views/dashboard.blade.php](../resources/views/dashboard.blade.php) - dashboard pengguna.
- `app/Http/Controllers/` - alur aplikasi dan validasi request.
- `app/Models/` dan `database/migrations/` - data dan relasi produk.
- `resources/ai/knowledge/` - pengetahuan fitur yang digunakan CatatIn Assistant.
