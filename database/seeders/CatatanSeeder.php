<?php

namespace Database\Seeders;

use App\Models\Catatan;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CatatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contohCatatan = [
            [
                'judul' => 'Pengenalan Logika Pemrograman & Algoritma',
                'kategori' => 'Pemrograman',
                'isi' => "Algoritma adalah urutan langkah logis yang disusun secara sistematis untuk menyelesaikan suatu masalah.\nKarakteristik penting algoritma:\n1. Finiteness (keterbatasan)\n2. Definiteness (kepastian)\n3. Input & Output yang jelas\n4. Effectiveness (efektivitas)\nContoh penerapan: flowchart dan pseudocode dalam memecahkan masalah percabangan if-else.",
                'favorit' => true,
            ],
            [
                'judul' => 'Normalisasi Basis Data (1NF, 2NF, 3NF)',
                'kategori' => 'Basis Data',
                'isi' => "Normalisasi adalah proses mengorganisasi data dalam database guna menghindari anomali (insert, update, delete) dan redundansi data.\n- 1NF: Tidak ada kolom dengan nilai berulang (atomic values).\n- 2NF: Memenuhi 1NF dan tidak ada ketergantungan parsial pada primary key.\n- 3NF: Memenuhi 2NF dan tidak ada ketergantungan transitif.",
                'favorit' => true,
            ],
            [
                'judul' => 'Model OSI Layer & TCP/IP pada Jaringan',
                'kategori' => 'Jaringan Komputer',
                'isi' => "Model OSI terdiri dari 7 lapisan:\n1. Physical\n2. Data Link\n3. Network (IP addressing, routing)\n4. Transport (TCP/UDP)\n5. Session\n6. Presentation\n7. Application (HTTP, DNS, FTP)\n\nPahami perbedaan TCP (connection-oriented, reliable) dan UDP (connectionless, fast).",
                'favorit' => false,
            ],
            [
                'judul' => 'Rumus Barisan dan Deret Aritmatika & Geometri',
                'kategori' => 'Matematika',
                'isi' => "Barisan Aritmatika:\nUn = a + (n - 1)b\nSn = n/2 * (2a + (n - 1)b)\n\nBarisan Geometri:\nUn = a * r^(n - 1)\nSn = a * (r^n - 1) / (r - 1) untuk r > 1.",
                'favorit' => false,
            ],
            [
                'judul' => 'Struktur Teks Laporan Hasil Observasi',
                'kategori' => 'Bahasa Indonesia',
                'isi' => "Teks LHO disusun dengan struktur:\n1. Pernyataan umum/klasifikasi\n2. Deskripsi bagian\n3. Deskripsi manfaat/kesimpulan\n\nKaidah kebahasaan: kata benda (nomina), kata kerja aksi (verba), kata teknis atau istilah ilmiah.",
                'favorit' => true,
            ],
            [
                'judul' => 'Technical Documentation & Report Writing',
                'kategori' => 'Bahasa Inggris',
                'isi' => "Tips for writing technical documentation:\n- Use active voice and concise vocabulary.\n- Organize content with clear headings and bullet points.\n- Include code snippets or diagrams when clarifying complex workflows.\n- Check grammar and technical accuracy before publishing.",
                'favorit' => false,
            ],
        ];

        foreach ($contohCatatan as $data) {
            $category = Category::firstOrCreate(['name' => $data['kategori']]);
            $data['category_id'] = $category->id;
            Catatan::create($data);
        }
    }
}
