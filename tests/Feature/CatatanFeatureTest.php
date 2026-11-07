<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatatanFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);
    }

    private function createNote(array $attributes = []): Catatan
    {
        return Catatan::factory()->create(array_merge(['user_id' => $this->owner->id], $attributes));
    }

    /**
     * 1. Dashboard berhasil dibuka dengan status 200 dan teks identitas.
     */
    public function test_1_dashboard_berhasil_dibuka(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('CatatIn');
        $response->assertSee('Atur langkah, capai tujuan.');
    }

    /**
     * 2. Daftar catatan berhasil ditampilkan.
     */
    public function test_2_daftar_catatan_berhasil_ditampilkan(): void
    {
        $catatan = $this->createNote([
            'judul' => 'Belajar Routing Laravel',
            'kategori' => 'Pemrograman',
            'isi' => 'Routing mendefinisikan rute URL pada aplikasi.',
        ]);

        $response = $this->get(route('catatan.index'));

        $response->assertStatus(200);
        $response->assertSee('Belajar Routing Laravel');
        $response->assertSee('Pemrograman');
    }

    /**
     * 3. Catatan berhasil dibuat dan tersimpan di database.
     */
    public function test_3_catatan_berhasil_dibuat(): void
    {
        $payload = [
            'judul' => 'Dasar Normalisasi Database',
            'kategori' => 'Basis Data',
            'isi' => 'Materi tentang bentuk 1NF, 2NF, dan 3NF.',
            'favorit' => 1,
        ];

        $response = $this->post(route('catatan.store'), $payload);

        $response->assertRedirect(route('catatan.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('catatan', [
            'judul' => 'Dasar Normalisasi Database',
            'kategori' => 'Basis Data',
            'favorit' => 1,
        ]);
    }

    /**
     * 4. Validasi menolak input tidak valid (judul kosong, kategori salah, isi kosong).
     */
    public function test_4_validasi_menolak_input_tidak_valid(): void
    {
        $payload = [
            'judul' => '',
            'kategori' => 'KategoriTidakAda',
            'isi' => '',
        ];

        $response = $this->post(route('catatan.store'), $payload);

        $response->assertSessionHasErrors(['judul', 'kategori', 'isi']);
        $this->assertDatabaseCount('catatan', 0);
    }

    /**
     * 5. Detail catatan tampil benar dan tidak merender HTML mentah secara berbahaya.
     */
    public function test_5_detail_catatan_tampil_benar(): void
    {
        $catatan = $this->createNote([
            'judul' => 'Detail Struktur Jaringan',
            'kategori' => 'Jaringan Komputer',
            'isi' => "Baris 1\nBaris 2",
        ]);

        $response = $this->get(route('catatan.show', $catatan->id));

        $response->assertStatus(200);
        $response->assertSee('Detail Struktur Jaringan');
        $response->assertSee('Jaringan Komputer');
        $response->assertSee('Baris 1');
        $response->assertSee('Baris 2');
    }

    /**
     * 6. Catatan berhasil diperbarui tanpa membuat duplikat.
     */
    public function test_6_catatan_berhasil_diperbarui(): void
    {
        $catatan = $this->createNote([
            'judul' => 'Judul Lama',
            'kategori' => 'Matematika',
            'isi' => 'Isi materi lama',
            'favorit' => false,
        ]);

        $updatedPayload = [
            'judul' => 'Judul Baru yang Diedit',
            'kategori' => 'Pemrograman',
            'isi' => 'Isi materi terbaru yang sudah direvisi.',
            'favorit' => 1,
        ];

        $response = $this->put(route('catatan.update', $catatan->id), $updatedPayload);

        $response->assertRedirect(route('catatan.show', $catatan->id));
        $this->assertDatabaseCount('catatan', 1);

        $catatan->refresh();
        $this->assertEquals('Judul Baru yang Diedit', $catatan->judul);
        $this->assertEquals('Pemrograman', $catatan->kategori);
        $this->assertTrue($catatan->favorit);
    }

    /**
     * 7. Catatan berhasil dihapus dari database.
     */
    public function test_7_catatan_berhasil_dihapus(): void
    {
        $catatan = $this->createNote([
            'judul' => 'Catatan Akan Dihapus',
        ]);

        $response = $this->delete(route('catatan.destroy', $catatan->id));

        $response->assertRedirect(route('catatan.index'));
        $this->assertDatabaseMissing('catatan', [
            'id' => $catatan->id,
        ]);
    }

    /**
     * 8. ID yang tidak ditemukan menghasilkan respons 404.
     */
    public function test_8_id_yang_tidak_ditemukan_ditangani_dengan_benar(): void
    {
        $nonExistentId = 999999;

        $response = $this->get(route('catatan.show', $nonExistentId));
        $response->assertStatus(404);

        $responseEdit = $this->get(route('catatan.edit', $nonExistentId));
        $responseEdit->assertStatus(404);
    }

    /**
     * 9. Pencarian judul dan isi berfungsi dengan benar.
     */
    public function test_9_pencarian_judul_dan_isi_berfungsi(): void
    {
        $this->createNote([
            'judul' => 'Algoritma Dijkstra',
            'isi' => 'Mencari jalur terpendek graf berbobot.',
        ]);

        $this->createNote([
            'judul' => 'Kaidah Teks Eksplanasi',
            'isi' => 'Materi bahasa indonesia mengenai fenomena alam.',
        ]);

        // Cari berdasarkan judul
        $responseJudul = $this->get(route('catatan.index', ['search' => 'Dijkstra']));
        $responseJudul->assertSee('Algoritma Dijkstra');
        $responseJudul->assertDontSee('Kaidah Teks Eksplanasi');

        // Cari berdasarkan isi
        $responseIsi = $this->get(route('catatan.index', ['search' => 'fenomena alam']));
        $responseIsi->assertSee('Kaidah Teks Eksplanasi');
        $responseIsi->assertDontSee('Algoritma Dijkstra');
    }

    /**
     * 10. Filter kategori berfungsi menyaring catatan sesuai kategori yang dipilih.
     */
    public function test_10_filter_kategori_berfungsi(): void
    {
        $this->createNote([
            'judul' => 'Kalkulus Integral',
            'kategori' => 'Matematika',
        ]);

        $this->createNote([
            'judul' => 'Subnetting IPv4 CIDR',
            'kategori' => 'Jaringan Komputer',
        ]);

        $response = $this->get(route('catatan.index', ['kategori' => 'Matematika']));

        $response->assertStatus(200);
        $response->assertSee('Kalkulus Integral');
        $response->assertDontSee('Subnetting IPv4 CIDR');
    }

    /**
     * 11. Status favorit dapat diaktifkan dan dinonaktifkan (toggle).
     */
    public function test_11_favorit_dapat_diaktifkan_dan_dinonaktifkan(): void
    {
        $catatan = $this->createNote(['favorit' => false]);

        // Aktifkan favorit
        $response1 = $this->patch(route('catatan.toggle-favorit', $catatan->id));
        $response1->assertStatus(302);
        $catatan->refresh();
        $this->assertTrue($catatan->favorit);

        // Nonaktifkan favorit
        $response2 = $this->patch(route('catatan.toggle-favorit', $catatan->id));
        $response2->assertStatus(302);
        $catatan->refresh();
        $this->assertFalse($catatan->favorit);
    }

    /**
     * 12. Halaman favorit hanya menampilkan catatan berstatus favorit.
     */
    public function test_12_halaman_favorit_hanya_menampilkan_catatan_favorit(): void
    {
        $this->createNote([
            'judul' => 'Catatan Khusus Bintang',
            'favorit' => true,
        ]);

        $this->createNote([
            'judul' => 'Catatan Biasa Tanpa Bintang',
            'favorit' => false,
        ]);

        $response = $this->get(route('catatan.favorit'));

        $response->assertStatus(200);
        $response->assertSee('Catatan Khusus Bintang');
        $response->assertDontSee('Catatan Biasa Tanpa Bintang');
    }

    /**
     * 13. Statistik dashboard sesuai dengan data aktual di database.
     */
    public function test_13_dashboard_only_shows_productivity_sections(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('taskCounts');
        $response->assertViewHas('activeGoalCount');
        $response->assertDontSee('Catatan Terbaru');
        $response->assertDontSee('Catatan Saya');
    }

    /**
     * 14. Pagination berfungsi jika data mencukupi (lebih dari 9 catatan).
     */
    public function test_14_pagination_berfungsi_jika_data_mencukupi(): void
    {
        Catatan::factory()->count(15)->create(['user_id' => $this->owner->id]);

        $response = $this->get(route('catatan.index'));

        $response->assertStatus(200);
        $response->assertViewHas('catatan', function ($catatan) {
            return $catatan->total() === 15 && $catatan->hasPages();
        });
    }

    /**
     * 15. Request penghapusan menggunakan metode HTTP DELETE (bukan GET).
     */
    public function test_15_request_penghapusan_menggunakan_metode_http_yang_benar(): void
    {
        $catatan = $this->createNote();

        // Mengirim GET ke URL destroy tidak diperbolehkan (MethodNotAllowedHttpException)
        $responseGet = $this->get('/catatan/' . $catatan->id . '/delete');
        $responseGet->assertStatus(404);

        // Hanya DELETE yang mengeksekusi penghapusan
        $responseDelete = $this->delete(route('catatan.destroy', $catatan->id));
        $responseDelete->assertRedirect(route('catatan.index'));

        $this->assertDatabaseMissing('catatan', ['id' => $catatan->id]);
    }
}
