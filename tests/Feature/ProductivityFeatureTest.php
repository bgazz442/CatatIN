<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalProgressEntry;
use App\Models\AiMaterialChunk;
use App\Models\MediaFavorite;
use App\Models\Task;
use App\Models\User;
use App\Services\AI\LearningMaterialRetriever;
use App\Services\AI\ProductivityContextRetriever;
use App\Services\AI\WorkspaceContextRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductivityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_crud_search_filter_and_owner_isolation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'Task Test']);
        $otherTask = Task::create([
            'user_id' => $otherUser->id,
            'title' => 'Other user private task',
            'status' => 'pending',
        ]);
        $this->actingAs($owner)->post(route('tasks.store'), [
            'title' => 'Owner report',
            'description' => 'Prepare the report',
            'category_id' => $category->id,
            'due_date' => today()->addDays(2)->toDateString(),
            'status' => 'pending',
            'user_id' => $otherUser->id,
        ])->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'Owner report')->firstOrFail();
        $this->assertSame($owner->id, $task->user_id);
        Task::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Priority task',
            'status' => 'in_progress',
            'due_date' => today()->addDay(),
        ]);
        Task::create([
            'user_id' => $owner->id,
            'title' => 'Task without deadline',
            'status' => 'pending',
        ]);

        $this->get(route('tasks.index', ['search' => 'report', 'status' => 'pending']))
            ->assertOk()
            ->assertSee('Owner report')
            ->assertDontSee('Other user private task');

        $this->get(route('tasks.index', ['sort' => 'deadline']))
            ->assertSeeInOrder(['Priority task', 'Owner report', 'Task without deadline']);
        $this->get(route('tasks.index', ['sort' => 'created']))
            ->assertSeeInOrder(['Task without deadline', 'Priority task', 'Owner report']);

        $this->get(route('tasks.index', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('Owner report')
            ->assertDontSee('Other user private task');

        $this->put(route('tasks.update', $task), [
            'title' => 'Updated report',
            'description' => 'Updated details',
            'category_id' => $category->id,
            'due_date' => today()->addDays(3)->toDateString(),
            'status' => 'in_progress',
        ])->assertRedirect(route('tasks.index'));
        $this->assertSame('in_progress', $task->fresh()->status);

        $this->get(route('tasks.edit', $otherTask))->assertNotFound();
        $this->delete(route('tasks.destroy', $otherTask))->assertNotFound();
        $this->assertDatabaseHas('tasks', ['id' => $otherTask->id, 'user_id' => $otherUser->id]);
        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_goal_progress_is_append_only_accumulates_and_marks_goal_complete(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('goals.store'), [
            'name' => 'Read books',
            'target_value' => '10',
            'unit' => 'books',
            'start_date' => today()->toDateString(),
            'current_value' => 999,
        ])->assertSessionHasErrors('current_value');

        $this->post(route('goals.store'), [
            'name' => 'Read books',
            'target_value' => '10',
            'unit' => 'books',
            'start_date' => today()->toDateString(),
        ])->assertRedirect();
        $goal = Goal::query()->where('user_id', $owner->id)->firstOrFail();

        $this->post(route('goals.progress.store', $goal), ['amount' => '0'])
            ->assertSessionHasErrors('amount');
        $this->post(route('goals.progress.store', $goal), ['amount' => '4.25'])
            ->assertRedirect(route('goals.show', $goal));
        $this->post(route('goals.progress.store', $goal), ['amount' => '7.75'])
            ->assertRedirect(route('goals.show', $goal));

        $goal->refresh();
        $this->assertSame('12.00', $goal->current_value);
        $this->assertSame('completed', $goal->status);
        $this->assertSame(100.0, $goal->percentage());
        $this->assertSame(0.0, $goal->remaining());
        $this->assertSame(2, GoalProgressEntry::query()->where('goal_id', $goal->id)->count());

        $otherGoal = Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Private goal',
            'target_value' => 5,
            'current_value' => 0,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);
        $this->get(route('goals.show', $otherGoal))->assertNotFound();
        $this->post(route('goals.progress.store', $otherGoal), ['amount' => 1])->assertNotFound();
        $this->put(route('goals.update', $goal), [
            'name' => 'Updated reading target',
            'target_value' => '20',
            'unit' => 'books',
            'start_date' => today()->toDateString(),
        ])->assertRedirect(route('goals.show', $goal));
        $this->assertSame('active', $goal->fresh()->status);
        $this->delete(route('goals.destroy', $goal))->assertRedirect(route('goals.index'));
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_goal_images_and_progress_are_private_to_the_goal_owner(): void
    {
        Storage::fake('learning_private');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('goals.store'), [
            'name' => 'Private image goal',
            'target_value' => '10',
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
            'image' => UploadedFile::fake()->image('motivation.png', 100, 100),
        ])->assertRedirect();

        $goal = Goal::query()->where('user_id', $owner->id)->firstOrFail();
        Storage::disk('learning_private')->assertExists($goal->image_path);
        $imageResponse = $this->get(route('goals.image', $goal))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', $imageResponse->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $imageResponse->headers->get('Cache-Control'));

        $this->post(route('goals.progress.store', $goal), ['amount' => '2', 'note' => 'Private progress'])
            ->assertRedirect(route('goals.show', $goal));
        $otherGoal = Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Another account goal',
            'target_value' => 5,
            'current_value' => 0,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);
        GoalProgressEntry::create([
            'goal_id' => $otherGoal->id,
            'user_id' => $otherUser->id,
            'amount' => 3,
            'note' => 'Other account progress',
            'recorded_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('goals.index'))
            ->assertOk()
            ->assertDontSee('Private image goal')
            ->assertDontSee('Private progress');
        $this->get(route('goals.show', $goal))->assertNotFound();
        $this->get(route('goals.image', $goal))->assertNotFound();
        $this->get(route('goals.edit', $goal))->assertNotFound();
        $this->put(route('goals.update', $goal), [
            'name' => 'Stolen goal',
            'target_value' => 10,
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
        ])->assertNotFound();
        $this->post(route('goals.progress.store', $goal), ['amount' => 1])->assertNotFound();
        $this->delete(route('goals.destroy', $goal))->assertNotFound();
        Storage::disk('learning_private')->assertExists($goal->image_path);

        $this->actingAs($owner)
            ->post(route('goals.store'), [
                'name' => 'Reject unsafe image',
                'target_value' => 1,
                'unit' => 'item',
                'start_date' => today()->toDateString(),
                'image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('image');

        $this->actingAs($owner)->delete(route('goals.destroy', $goal))
            ->assertRedirect(route('goals.index'));
        Storage::disk('learning_private')->assertMissing($goal->image_path);
    }

    public function test_theme_preferences_are_saved_per_account_and_validate_color_values(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('settings.theme.update'), [
            'accent_color' => '#2457A6',
            'navigation_footer_color' => '#1E3A5F',
        ])->assertRedirect(route('settings'));

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $owner->id,
            'accent_color' => '#2457A6',
            'navigation_footer_color' => '#1E3A5F',
        ]);
        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('data-theme-accent="#2457A6"', false)
            ->assertSee('data-theme-nav-surface="#1E3A5F"', false)
            ->assertSee('class="theme-nav-surface', false)
            ->assertSee('Palet navbar dan footer')
            ->assertDontSee('Palet warna latar');

        $this->actingAs($otherUser)->get(route('settings'))
            ->assertOk()
            ->assertSee('data-theme-accent="#2F6F64"', false)
            ->assertSee('data-theme-nav-surface="#1F2937"', false)
            ->assertDontSee('#2457A6');

        $this->post(route('settings.theme.update'), [
            'accent_color' => 'red; background-image:url(https://example.invalid)',
            'navigation_footer_color' => '#FFFFFF',
        ])->assertSessionHasErrors(['accent_color', 'navigation_footer_color']);
        $this->post(route('settings.theme.update'), [
            'accent_color' => '#FFFFFF',
            'navigation_footer_color' => '#FFFFFF',
        ])->assertSessionHasErrors(['accent_color', 'navigation_footer_color']);

        auth()->logout();
        $this->post(route('settings.theme.update'), [
            'accent_color' => '#2457A6',
            'navigation_footer_color' => '#1E3A5F',
        ])->assertRedirectContains('login_required=1');
    }

    public function test_dashboard_and_productivity_retrieval_only_include_the_authenticated_users_tasks_and_goals(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'Personal Test']);

        Task::create([
            'user_id' => $owner->id,
            'title' => 'Owner task',
            'status' => 'pending',
            'due_date' => today()->addDays(2),
        ]);
        Task::create(['user_id' => $otherUser->id, 'title' => 'Foreign task', 'status' => 'pending']);
        Task::create([
            'user_id' => $owner->id,
            'title' => 'Owner overdue task',
            'status' => 'in_progress',
            'due_date' => today()->subDay(),
        ]);
        $goal = Goal::create([
            'user_id' => $owner->id,
            'name' => 'Owner target',
            'target_value' => 10,
            'current_value' => 2,
            'unit' => 'hours',
            'start_date' => today(),
            'status' => 'active',
        ]);
        Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Owner private note',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Owner note body',
        ]);
        Catatan::create([
            'user_id' => $otherUser->id,
            'judul' => 'Foreign private note',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Foreign note body',
        ]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Owner task')
            ->assertSee('Owner overdue task')
            ->assertSee('Owner target')
            ->assertDontSee('Foreign task')
            ->assertDontSee('Owner private note')
            ->assertDontSee('Foreign private note');

        $context = app(ProductivityContextRetriever::class)
            ->retrieve($owner->id, 'Apa tugas saya dan progres target saya?');
        $this->assertStringContainsString('Owner task', $context['context']);
        $this->assertStringContainsString('Owner target', $context['context']);
        $this->assertStringNotContainsString('Foreign task', $context['context']);
        $this->assertNotEmpty($context['sources']);
    }

    public function test_workspace_context_does_not_include_removed_media_favorites(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'Assistant Media Test']);

        MediaFavorite::create([
            'user_id' => $owner->id,
            'title' => 'Owner private video',
            'url' => 'https://example.com/owner',
            'platform' => 'YouTube',
            'category_id' => $category->id,
            'is_favorite' => true,
        ]);
        MediaFavorite::create([
            'user_id' => $otherUser->id,
            'title' => 'Foreign private video',
            'url' => 'https://example.com/foreign',
            'platform' => 'YouTube',
            'category_id' => $category->id,
            'is_favorite' => true,
        ]);

        $context = app(WorkspaceContextRetriever::class)
            ->retrieve($owner->id, 'Media favorit apa yang sudah saya simpan?');

        $this->assertStringNotContainsString('Owner private video', $context['context']);
        $this->assertStringNotContainsString('Foreign private video', $context['context']);
        $this->assertNotContains('media-favorite', array_column($context['sources'], 'type'));
    }

    public function test_task_and_goal_list_and_form_pages_render(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            route('tasks.index'),
            route('tasks.create'),
            route('goals.index'),
            route('goals.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $task = Task::create(['user_id' => $user->id, 'title' => 'Form render task', 'status' => 'pending']);
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => 'Form render goal',
            'target_value' => 3,
            'current_value' => 0,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);

        $this->get(route('tasks.edit', $task))->assertOk();
        $this->get(route('goals.show', $goal))->assertOk();
        $this->get(route('goals.edit', $goal))->assertOk();
    }

    public function test_dashboard_navigation_is_first_and_retired_menu_items_are_hidden(): void
    {
        $user = User::factory()->create(['google_id' => 'google-dashboard-nav']);
        $response = $this->withSession(['auth_provider' => 'google'])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeInOrder([
                'Menu Utama',
                'Dashboard',
                'Task Manager',
                'Goal Tracker',
                'CatatIn Assistant',
                'Pengaturan',
            ])
            ->assertDontSee('Daily Activity')
            ->assertDontSee('Catatan Belajar')
            ->assertDontSee('Catatan / Media Favorit')
            ->assertDontSee('Media Belajar')
            ->assertDontSee('Catatan Terbaru')
            ->assertDontSee('Tambah Catatan');
    }

    public function test_desktop_sidebar_stays_fixed_while_long_pages_scroll(): void
    {
        $response = $this->get(route('settings'));

        $response->assertOk()
            ->assertSee('md:pl-64', false)
            ->assertSee('id="sidebar" class="theme-nav-surface fixed md:fixed top-0 bottom-0 left-0 md:h-screen', false)
            ->assertSee('p-4 flex-1 min-h-0 space-y-1.5 overflow-y-auto', false);
    }

    public function test_guests_cannot_open_private_productivity_or_note_routes(): void
    {
        Task::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Guest must not see this task',
            'status' => 'pending',
        ]);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Guest must not see this task');

        $this->get(route('tasks.index'))->assertRedirect();
        $this->get('/aktivitas')->assertNotFound();
        $this->get(route('goals.index'))->assertRedirect();
        $this->get(route('catatan.index'))->assertRedirect();
        $this->post(route('ai.chat'), ['message' => 'Can I access this?'])->assertRedirect();
    }

    public function test_note_records_cannot_be_accessed_by_changing_the_record_id(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'Private Note Test']);
        $note = Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Private note',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Private contents',
        ]);

        $this->actingAs($otherUser)
            ->get(route('catatan.show', $note))
            ->assertNotFound();
        $this->put(route('catatan.update', $note), [
            'judul' => 'Changed by another user',
            'kategori' => $category->name,
            'isi' => 'Should not persist',
        ])->assertNotFound();
        $this->delete(route('catatan.destroy', $note))->assertNotFound();

        $this->assertDatabaseHas('catatan', [
            'id' => $note->id,
            'user_id' => $owner->id,
            'judul' => 'Private note',
        ]);
    }

    public function test_phase_4c_dynamic_retrieval_reflects_crud_and_preserves_user_isolation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Membuat laporan PKL',
            'description' => 'Draft laporan praktik kerja lapangan',
            'status' => 'pending',
        ]);
        Task::create([
            'user_id' => $otherUser->id,
            'title' => 'Tugas rahasia pengguna lain',
            'status' => 'pending',
        ]);

        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Apa saja tugas saya?');
        $this->assertStringContainsString('Membuat laporan PKL', $retrieved['context']);
        $this->assertStringNotContainsString('Tugas rahasia pengguna lain', $retrieved['context']);

        $task->update([
            'title' => 'Menyelesaikan laporan PKL',
            'description' => 'Finalisasi laporan praktik kerja lapangan',
        ]);
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Apa nama task saya sekarang?');
        $this->assertStringContainsString('Menyelesaikan laporan PKL', $retrieved['context']);
        $this->assertStringNotContainsString('Membuat laporan PKL', $retrieved['context']);
        $this->assertStringContainsString('Finalisasi laporan praktik kerja lapangan', $retrieved['context']);

        $task->update(['status' => 'completed']);
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Apa status task saya sekarang?');
        $this->assertStringContainsString('status: completed', $retrieved['context']);

        $task->delete();
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Apa tugas saya sekarang?');
        $this->assertStringNotContainsString('Menyelesaikan laporan PKL', $retrieved['context']);

        Goal::create([
            'user_id' => $owner->id,
            'name' => 'Belajar Laravel',
            'target_value' => 20,
            'current_value' => 8,
            'unit' => 'jam',
            'start_date' => today()->subDays(10),
            'status' => 'active',
        ]);
        Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Goal rahasia pengguna lain',
            'target_value' => 100,
            'current_value' => 5,
            'unit' => 'persen',
            'start_date' => today()->subDays(10),
            'status' => 'active',
        ]);
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Goal gw gimana?');
        $this->assertStringContainsString('Belajar Laravel', $retrieved['context']);
        $this->assertStringNotContainsString('Goal rahasia pengguna lain', $retrieved['context']);
        $goal = Goal::query()->where('user_id', $owner->id)->firstOrFail();
        $goal->update([
            'name' => 'Menguasai Laravel',
            'description' => 'Menyelesaikan materi framework',
            'current_value' => 15,
        ]);
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Berapa progres goal saya sekarang?');
        $this->assertStringContainsString('Menguasai Laravel', $retrieved['context']);
        $this->assertStringNotContainsString('Belajar Laravel', $retrieved['context']);
        $this->assertStringContainsString('15.00 / 20.00 jam (75%)', $retrieved['context']);
        $this->assertStringContainsString('Menyelesaikan materi framework', $retrieved['context']);
        $goal->delete();
        $retrieved = app(ProductivityContextRetriever::class)->retrieve($owner->id, 'Berapa progres goal saya sekarang?');
        $this->assertStringNotContainsString('Menguasai Laravel', $retrieved['context']);

        $note = Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Ringkasan Laravel',
            'kategori' => 'Programming',
            'isi' => 'Laravel menggunakan routing, controller, dan Eloquent ORM.',
        ]);
        Catatan::create([
            'user_id' => $otherUser->id,
            'judul' => 'Catatan rahasia pengguna lain',
            'kategori' => 'Private',
            'isi' => 'Materi rahasia pengguna lain.',
        ]);
        $retrieved = app(LearningMaterialRetriever::class)->retrieve($owner->id, 'Rangkum catatan saya tentang Laravel');
        $this->assertStringContainsString('Ringkasan Laravel', $retrieved['context']);
        $this->assertStringNotContainsString('Catatan rahasia pengguna lain', $retrieved['context']);

        $note->update([
            'judul' => 'Panduan Framework PHP',
            'isi' => 'Laravel memakai middleware dan layanan dependency injection untuk menangani request.',
        ]);
        $retrieved = app(LearningMaterialRetriever::class)->retrieve($owner->id, 'Rangkum catatan saya tentang Laravel');
        $this->assertStringContainsString('Panduan Framework PHP', $retrieved['context']);
        $this->assertStringContainsString('middleware dan layanan dependency injection', $retrieved['context']);
        $this->assertStringNotContainsString('routing, controller, dan Eloquent ORM', $retrieved['context']);

        $note->delete();
        $retrieved = app(LearningMaterialRetriever::class)->retrieve($owner->id, 'Rangkum catatan saya tentang Laravel');
        $this->assertStringNotContainsString('Panduan Framework PHP', $retrieved['context']);
    }

    public function test_notification_feedback_is_fixed_and_supports_success_error_without_page_reload_flow(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['success' => 'Tugas berhasil ditambahkan.'])
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('notification-stack', false)
            ->assertSee('fixed bottom-4', false)
            ->assertSee('Tugas berhasil ditambahkan.', false)
            ->assertSee('data-auto-dismiss="4500"', false);

        $this->actingAs(User::factory()->create())
            ->withSession(['error' => 'Item gagal disimpan.'])
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Item gagal disimpan.', false)
            ->assertSee('role="alert"', false);

        $script = file_get_contents(base_path('resources/js/app.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString('data-notification', $script);
        $this->assertStringContainsString('notification-exit', $script);
        $this->assertStringNotContainsString('window.location.reload', $script);
    }

    public function test_learning_material_embedding_cache_is_replaced_after_note_content_changes(): void
    {
        $user = User::factory()->create();
        $note = Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Catatan Laravel cache',
            'kategori' => 'Programming',
            'isi' => 'Isi lama membahas routing legacy.',
        ]);

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(['response' => 'Tidak digunakan.']);
        });

        $retriever = app(LearningMaterialRetriever::class);
        $initial = $retriever->retrieve($user->id, 'Rangkum catatan Laravel cache');
        $this->assertStringContainsString('routing legacy', $initial['context']);
        $initialHash = AiMaterialChunk::query()
            ->where('user_id', $user->id)
            ->where('source_type', 'catatan')
            ->where('source_id', $note->id)
            ->value('content_hash');
        $this->assertNotNull($initialHash);

        $note->update(['isi' => 'Isi terbaru membahas dependency injection modern.']);
        $updated = $retriever->retrieve($user->id, 'Rangkum catatan Laravel cache');
        $this->assertStringContainsString('dependency injection modern', $updated['context']);
        $this->assertStringNotContainsString('routing legacy', $updated['context']);

        $updatedHash = AiMaterialChunk::query()
            ->where('user_id', $user->id)
            ->where('source_type', 'catatan')
            ->where('source_id', $note->id)
            ->value('content_hash');
        $this->assertNotSame($initialHash, $updatedHash);
        $this->assertDatabaseMissing('ai_material_chunks', [
            'user_id' => $user->id,
            'source_type' => 'catatan',
            'source_id' => $note->id,
            'content_hash' => $initialHash,
        ]);
    }

    public function test_workspace_and_category_retrieval_uses_live_global_categories_and_owned_data(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($owner);
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/tags')) {
                return Http::response(['models' => [
                    ['name' => 'qwen3:1.7b'],
                    ['name' => 'nomic-embed-text:latest'],
                ]]);
            }
            if (str_ends_with($request->url(), '/api/embed')) {
                return Http::response([
                    'embeddings' => array_fill(0, count($request['input']), [1.0, 0.0]),
                ]);
            }

            return Http::response(['response' => 'Tidak digunakan.']);
        });

        $category = Category::create(['name' => 'Workspace Alpha']);
        $otherCategory = Category::create(['name' => 'Workspace Empty']);
        $note = Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Alpha database notes',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Database normalization and indexing.',
        ]);
        Catatan::create([
            'user_id' => $owner->id,
            'judul' => 'Alpha second note',
            'kategori' => $category->name,
            'category_id' => $category->id,
            'isi' => 'Transactions and constraints.',
        ]);
        Task::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Alpha pending task',
            'status' => 'pending',
        ]);
        Task::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Alpha completed task',
            'status' => 'completed',
        ]);
        Task::create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'title' => 'Foreign alpha task',
            'status' => 'pending',
        ]);
        Goal::create([
            'user_id' => $owner->id,
            'name' => 'Alpha study goal',
            'description' => 'Learn database systems',
            'target_value' => 10,
            'current_value' => 7,
            'unit' => 'hours',
            'start_date' => today()->subDays(5),
            'status' => 'active',
        ]);
        Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign alpha goal',
            'target_value' => 100,
            'current_value' => 80,
            'unit' => 'points',
            'start_date' => today(),
            'status' => 'active',
        ]);
        $workspace = app(WorkspaceContextRetriever::class)->retrieve($owner->id, 'Bagaimana kondisi workspace saya?');
        $this->assertStringContainsString('2 catatan', $workspace['context']);
        $this->assertStringContainsString('2 total; status terpisah: pending 1, sedang dikerjakan 0, tuntas 1.', $workspace['context']);
        $this->assertStringContainsString('Jumlah goal: 1; total progres tersimpan 7.00 dari 10.00', $workspace['context']);
        $this->assertStringContainsString('Workspace Alpha: 2 catatan / 2 tugas.', $workspace['context']);
        $this->assertStringContainsString('Workspace Empty: 0 catatan / 0 tugas.', $workspace['context']);
        $this->assertStringContainsString('Alpha database notes', $workspace['context']);
        $this->assertStringContainsString('Alpha pending task', $workspace['context']);
        $this->assertStringContainsString('Alpha study goal', $workspace['context']);
        $this->assertStringNotContainsString('Foreign alpha', $workspace['context']);
        $this->assertSame(2, $workspace['facts']['notes_count']);
        $this->assertSame(2, $workspace['facts']['tasks_count']);
        $this->assertSame(1, $workspace['facts']['task_status_counts']['pending']);
        $this->assertSame(1, $workspace['facts']['goals_count']);

        $taskCategoryContext = app(ProductivityContextRetriever::class)
            ->retrieve($owner->id, 'Apa saja task dalam kategori Workspace Alpha?');
        $this->assertSame(2, $taskCategoryContext['facts']['tasks']['count']);
        $this->assertStringContainsString('mungkin tidak lengkap', $taskCategoryContext['context']);
        $this->assertStringContainsString('Alpha pending task', $taskCategoryContext['context']);
        $this->assertStringContainsString('Alpha completed task', $taskCategoryContext['context']);
        $this->assertStringNotContainsString('Foreign alpha task', $taskCategoryContext['context']);

        $noteCategoryContext = app(LearningMaterialRetriever::class)
            ->retrieve($owner->id, 'Apa saja catatan dalam kategori Workspace Alpha?');
        $this->assertStringContainsString('Alpha database notes', $noteCategoryContext['context']);
        $this->assertStringContainsString('Alpha second note', $noteCategoryContext['context']);

        $this->put(route('categories.update', $category), ['name' => 'Workspace Renamed'])
            ->assertRedirect(route('categories.index'));
        $renamedWorkspace = app(WorkspaceContextRetriever::class)
            ->retrieve($owner->id, 'Kategori apa saja yang tersedia?');
        $this->assertStringContainsString('Workspace Renamed: 2 catatan / 2 tugas.', $renamedWorkspace['context']);
        $this->assertStringNotContainsString('Workspace Alpha:', $renamedWorkspace['context']);
        $this->assertSame('Workspace Renamed', $note->fresh()->kategori);

        $note->delete();
        Catatan::query()->where('user_id', $owner->id)->where('category_id', $category->id)->delete();
        $this->delete(route('categories.destroy', $otherCategory))
            ->assertRedirect(route('categories.index'));
        $afterDelete = app(WorkspaceContextRetriever::class)
            ->retrieve($owner->id, 'Kategori apa saja yang tersedia?');
        $this->assertStringNotContainsString('Workspace Empty', $afterDelete['context']);

        $otherWorkspace = app(WorkspaceContextRetriever::class)
            ->retrieve($otherUser->id, 'Bagaimana kondisi workspace saya?');
        $this->assertStringContainsString('Foreign alpha task', $otherWorkspace['context']);
        $this->assertStringNotContainsString('Alpha database notes', $otherWorkspace['context']);
        $this->assertStringNotContainsString('Alpha study goal', $otherWorkspace['context']);
    }
}
