<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalProgressEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $this->postJson(route('settings.theme.update'), [
            'accent_color' => '#2457A6',
            'navigation_footer_color' => '#1E3A5F',
        ])->assertOk()
            ->assertJsonPath('preferences.accent_color', '#2457A6')
            ->assertJsonPath('preferences.navigation_footer_color', '#1E3A5F');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $owner->id,
            'accent_color' => '#2457A6',
            'navigation_footer_color' => '#1E3A5F',
        ]);
        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('data-theme-accent="#2457A6"', false)
            ->assertSee('data-theme-nav-surface="#1E3A5F"', false)
            ->assertSee('--theme-accent: #2457A6', false)
            ->assertSee('--theme-accent-rgb: 36, 87, 166', false)
            ->assertSee('data-theme-save-url=', false)
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

    public function test_authenticated_user_can_update_their_profile_name_from_the_account_menu(): void
    {
        $user = User::factory()->create(['name' => 'Nama Lama']);

        $this->actingAs($user)->get(route('settings'))
            ->assertOk()
            ->assertSee('Info akun dan ubah nama')
            ->assertSee('account-menu--desktop', false)
            ->assertSee('data-account-menu-trigger', false)
            ->assertSee('data-account-menu-panel hidden', false)
            ->assertSee('account-menu-identity-avatar', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertDontSee('Perbarui nama yang ditampilkan di CatatIn.');
        $this->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Menu lainnya')
            ->assertSee('Info akun dan ubah nama')
            ->assertDontSee('id="stop-generation"', false);

        $this->put(route('settings.profile.update'), ['name' => 'Nama Baru', 'return_to' => '/dashboard'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success', 'Nama profil berhasil diperbarui.');

        $this->assertSame('Nama Baru', $user->fresh()->name);

        $this->put(route('settings.profile.update'), [
            'name' => 'Nama Baru',
            'return_to' => 'https://example.invalid/',
        ])->assertRedirect(route('settings', [], false));

        $this->put(route('settings.profile.update'), ['name' => '   '])
            ->assertSessionHasErrors('name');

        auth()->logout();
        $this->put(route('settings.profile.update'), ['name' => 'Tidak Sah'])
            ->assertRedirectContains('login_required=1');
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

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dashboard-panel-heading', false)
            ->assertSee('dashboard-empty-state', false)
            ->assertSee('Semua tugas')
            ->assertSee('Semua target');

        $this->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('workspace-form-intro--task', false)
            ->assertSee('Rencanakan tugas baru');

        $this->get(route('goals.create'))
            ->assertOk()
            ->assertSee('workspace-form-intro--goal', false)
            ->assertSee('Bangun target yang ingin dicapai');

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
            ->assertDontSee('Catatan Favorit')
            ->assertDontSee('Media Favorit')
            ->assertDontSee('Kategori');
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
        $this->get('/ai')->assertRedirect();
        $this->postJson('/ai/chat', ['message' => 'Can I access this?'])->assertUnauthorized();
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

}
