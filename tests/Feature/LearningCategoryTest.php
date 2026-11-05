<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\User;
use App\Services\LegacyCategoryBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LearningCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_existing_login_for_category_management(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login', [
            'login_required' => 1,
            'redirect_to' => '/kategori',
        ]));
    }

    public function test_authenticated_user_can_create_and_rename_shared_category(): void
    {
        $this->actingAs(User::factory()->create());

        $create = $this->post(route('categories.store'), ['name' => 'Rekayasa Perangkat Lunak']);
        $create->assertRedirect(route('categories.index'));
        $category = Category::where('name', 'Rekayasa Perangkat Lunak')->firstOrFail();
        $note = Catatan::factory()->create([
            'category_id' => $category->id,
            'kategori' => $category->name,
        ]);

        $update = $this->put(route('categories.update', $category), ['name' => 'Rekayasa Software']);

        $update->assertRedirect(route('categories.index'));
        $this->assertSame('Rekayasa Software', $note->fresh()->kategori);
        $this->assertSame($category->id, $note->fresh()->category_id);
        $this->get(route('catatan.create'))->assertSee('Rekayasa Software');
        $this->get(route('media-favorites.create'))->assertSee('Rekayasa Software');
    }

    public function test_category_names_are_case_insensitive_unique(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('categories.store'), ['name' => 'MATEMATIKA']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('categories', 7);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::where('name', 'Pemrograman')->firstOrFail();
        Catatan::factory()->create([
            'category_id' => $category->id,
            'kategori' => $category->name,
        ]);

        $response = $this->delete(route('categories.destroy', $category));

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'Kategori Uji Hapus']);

        $response = $this->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_backfill_preserves_legacy_values_in_temporary_mysql_tables(): void
    {
        $connection = DB::connection();
        $connection->statement('CREATE TEMPORARY TABLE qa_legacy_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) UNIQUE, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
        $connection->statement('CREATE TEMPORARY TABLE qa_legacy_catatan (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kategori VARCHAR(100), category_id BIGINT UNSIGNED NULL)');
        try {
            $connection->table('qa_legacy_catatan')->insert(['kategori' => 'Kategori Lama Unik']);
            app(LegacyCategoryBackfill::class)->run('qa_legacy_catatan', 'qa_legacy_categories');

            $category = $connection->table('qa_legacy_categories')->where('name', 'Kategori Lama Unik')->first();
            $note = $connection->table('qa_legacy_catatan')->first();
            $this->assertNotNull($category);
            $this->assertSame($category->id, $note->category_id);
            $this->assertSame('Kategori Lama Unik', $note->kategori);
        } finally {
            $connection->statement('DROP TEMPORARY TABLE IF EXISTS qa_legacy_catatan');
            $connection->statement('DROP TEMPORARY TABLE IF EXISTS qa_legacy_categories');
        }
    }
}