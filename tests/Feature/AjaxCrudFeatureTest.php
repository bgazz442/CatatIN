<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Category;
use App\Models\DailyActivity;
use App\Models\Goal;
use App\Models\GoalProgressEntry;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AjaxCrudFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function ajaxHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    public function test_task_ajax_create_update_status_and_delete_return_confirmed_database_state(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'AJAX Task']);
        $this->actingAs($user);

        $created = $this->withHeaders($this->ajaxHeaders())->post(route('tasks.store'), [
            'title' => 'Create ajax task',
            'description' => 'Initial detail',
            'category_id' => $category->id,
            'status' => 'pending',
        ])->assertCreated()
            ->assertJsonPath('message', 'Tugas berhasil ditambahkan.')
            ->assertJsonPath('data.title', 'Create ajax task')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.category_id', $category->id);
        $task = Task::query()->findOrFail($created->json('data.id'));
        $this->assertSame($user->id, $task->user_id);

        $this->withHeaders($this->ajaxHeaders())->put(route('tasks.update', $task), [
            'title' => 'Updated ajax task',
            'description' => 'Updated detail',
            'category_id' => $category->id,
            'status' => 'pending',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated ajax task');
        $this->assertSame('Updated detail', $task->fresh()->description);

        $this->withHeaders($this->ajaxHeaders())->patch(route('tasks.status.update', $task), [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');
        $this->assertSame('completed', $task->fresh()->status);

        $this->withHeaders($this->ajaxHeaders())->delete(route('tasks.destroy', $task))
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_goal_ajax_create_update_and_progress_return_current_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $created = $this->withHeaders($this->ajaxHeaders())->post(route('goals.store'), [
            'name' => 'AJAX goal',
            'target_value' => '10',
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.current_value', 0)
            ->assertJsonPath('data.status', 'active');
        $goal = Goal::query()->findOrFail($created->json('data.id'));

        $this->withHeaders($this->ajaxHeaders())->put(route('goals.update', $goal), [
            'name' => 'Updated AJAX goal',
            'description' => 'Read the new edition',
            'target_value' => '20',
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated AJAX goal');
        $this->assertSame('Read the new edition', $goal->fresh()->description);

        $this->withHeaders($this->ajaxHeaders())->post(route('goals.progress.store', $goal), [
            'amount' => '5.50',
            'note' => 'First half',
        ])->assertOk()
            ->assertJsonPath('data.current_value', 5.5)
            ->assertJsonPath('data.percentage', 27.5)
            ->assertJsonPath('data.progress_entry.note', 'First half');
        $this->assertSame('5.50', $goal->fresh()->current_value);
        $this->assertSame(1, GoalProgressEntry::query()->where('goal_id', $goal->id)->count());
    }

    public function test_activity_ajax_create_update_and_delete_preserve_owner_scope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'AJAX Activity']);
        $this->actingAs($user);

        $created = $this->withHeaders($this->ajaxHeaders())->post(route('activities.store'), [
            'title' => 'AJAX study activity',
            'description' => 'Old detail',
            'activity_date' => today()->toDateString(),
            'category_id' => $category->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'AJAX study activity')
            ->assertJsonPath('data.start_time', '09:00')
            ->assertJsonPath('data.category_id', $category->id);
        $activity = DailyActivity::query()->findOrFail($created->json('data.id'));

        $this->withHeaders($this->ajaxHeaders())->put(route('activities.update', $activity), [
            'title' => 'Updated AJAX activity',
            'description' => 'New detail',
            'activity_date' => today()->toDateString(),
            'category_id' => $category->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated AJAX activity');
        $this->assertSame('New detail', $activity->fresh()->description);

        $foreign = DailyActivity::create([
            'user_id' => $otherUser->id,
            'title' => 'Foreign activity',
            'activity_date' => today(),
        ]);
        $this->withHeaders($this->ajaxHeaders())->put(route('activities.update', $foreign), [
            'title' => 'Changed by foreign owner',
            'activity_date' => today()->toDateString(),
            'category_id' => $category->id,
        ])->assertNotFound();
        $this->assertSame('Foreign activity', $foreign->fresh()->title);

        $this->withHeaders($this->ajaxHeaders())->delete(route('activities.destroy', $activity))
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id);
        $this->assertDatabaseMissing('daily_activities', ['id' => $activity->id]);
    }

    public function test_note_ajax_create_update_and_delete_return_saved_note_data(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'AJAX Notes']);
        $this->actingAs($user);

        $created = $this->withHeaders($this->ajaxHeaders())->post(route('catatan.store'), [
            'judul' => 'AJAX note title',
            'kategori' => $category->name,
            'isi' => 'Saved note body',
            'favorit' => 1,
        ])->assertCreated()
            ->assertJsonPath('data.judul', 'AJAX note title')
            ->assertJsonPath('data.favorit', true);
        $note = Catatan::query()->findOrFail($created->json('data.id'));

        $this->withHeaders($this->ajaxHeaders())->patch(route('catatan.toggle-favorit', $note))
            ->assertOk()
            ->assertJsonPath('data.favorit', false);
        $this->assertFalse($note->fresh()->favorit);

        $this->withHeaders($this->ajaxHeaders())->put(route('catatan.update', $note), [
            'judul' => 'Updated AJAX note',
            'kategori' => $category->name,
            'isi' => 'Updated note body',
        ])->assertOk()
            ->assertJsonPath('data.judul', 'Updated AJAX note');
        $this->assertSame('Updated note body', $note->fresh()->isi);

        $this->withHeaders($this->ajaxHeaders())->delete(route('catatan.destroy', $note))
            ->assertOk()
            ->assertJsonPath('data.id', $note->id);
        $this->assertDatabaseMissing('catatan', ['id' => $note->id]);
    }

    public function test_ajax_formdata_uploads_keep_existing_private_storage_and_validation_rules(): void
    {
        Storage::fake('learning_private');
        $user = User::factory()->create();
        $category = Category::query()->firstOrCreate(['name' => 'AJAX Uploads']);
        $this->actingAs($user)->withHeaders($this->ajaxHeaders());

        $goalResponse = $this->post(route('goals.store'), [
            'name' => 'AJAX image goal',
            'target_value' => '5',
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
            'image' => UploadedFile::fake()->image('goal.png', 100, 100),
        ])->assertCreated();
        $goal = Goal::query()->findOrFail($goalResponse->json('data.id'));
        Storage::disk('learning_private')->assertExists($goal->image_path);
        $this->assertStringContainsString(route('goals.image', $goal), $goalResponse->json('data.image_url'));

        $noteResponse = $this->post(route('catatan.store'), [
            'judul' => 'AJAX attachment note',
            'kategori' => $category->name,
            'isi' => '',
            'documents' => [UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf')],
        ])->assertCreated()
            ->assertJsonPath('data.attachment_count', 1);
        $note = Catatan::query()->findOrFail($noteResponse->json('data.id'));
        $this->assertDatabaseHas('catatan_attachments', [
            'catatan_id' => $note->id,
            'original_name' => 'lesson.pdf',
        ]);

        $this->post(route('catatan.store'), [
            'judul' => 'AJAX rejected attachment',
            'kategori' => $category->name,
            'isi' => '',
            'documents' => [UploadedFile::fake()->create('unsafe.exe', 10, 'application/octet-stream')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['documents.0']);
        $this->assertDatabaseMissing('catatan', ['judul' => 'AJAX rejected attachment']);
    }

    public function test_category_ajax_create_update_and_delete_work_and_in_use_categories_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $created = $this->withHeaders($this->ajaxHeaders())->post(route('categories.store'), [
            'name' => 'AJAX Category',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'AJAX Category');
        $category = Category::query()->findOrFail($created->json('data.id'));

        $this->withHeaders($this->ajaxHeaders())->put(route('categories.update', $category), [
            'name' => 'Renamed AJAX Category',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Renamed AJAX Category');

        $task = Task::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Category still in use',
            'status' => 'pending',
        ]);
        $this->withHeaders($this->ajaxHeaders())->delete(route('categories.destroy', $category))
            ->assertUnprocessable()
            ->assertJsonPath('errors.category.0', 'Kategori masih digunakan. Pindahkan data terkait ke kategori lain sebelum menghapusnya.');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $task->delete();
        $this->withHeaders($this->ajaxHeaders())->delete(route('categories.destroy', $category))
            ->assertOk()
            ->assertJsonPath('data.id', $category->id);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_ajax_validation_and_database_errors_never_return_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->withHeaders($this->ajaxHeaders())->post(route('tasks.store'), [
            'title' => '',
            'status' => 'not-a-status',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);
        $this->assertDatabaseCount('tasks', 0);

        if (DB::getDriverName() === 'sqlite') {
            config(['app.debug' => false]);
            DB::unprepared("CREATE TRIGGER fail_ajax_task_insert BEFORE INSERT ON tasks BEGIN SELECT RAISE(FAIL, 'test database failure'); END");
            $this->withHeaders($this->ajaxHeaders())->post(route('tasks.store'), [
                'title' => 'Trigger database failure',
                'status' => 'pending',
            ])->assertServerError()
                ->assertDontSee('test database failure')
                ->assertJsonMissingPath('data');
            $this->assertDatabaseCount('tasks', 0);
        }
    }

    public function test_ajax_routes_keep_auth_owner_validation_and_non_ajax_redirect_behavior(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignTask = Task::create([
            'user_id' => $otherUser->id,
            'title' => 'Foreign task',
            'status' => 'pending',
        ]);
        $foreignGoal = Goal::create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign goal',
            'target_value' => 10,
            'current_value' => 0,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);
        $foreignNote = Catatan::factory()->create([
            'user_id' => $otherUser->id,
            'judul' => 'Foreign note',
        ]);

        $this->actingAs($owner)->withHeaders($this->ajaxHeaders())
            ->put(route('tasks.update', $foreignTask), [
                'title' => 'Attempted takeover',
                'status' => 'pending',
            ])->assertNotFound();
        $this->assertSame('Foreign task', $foreignTask->fresh()->title);

        $this->withHeaders($this->ajaxHeaders())->put(route('goals.update', $foreignGoal), [
            'name' => 'Attempted goal takeover',
            'target_value' => 20,
            'unit' => 'pages',
            'start_date' => today()->toDateString(),
        ])->assertNotFound();
        $this->assertSame('Foreign goal', $foreignGoal->fresh()->name);

        $this->withHeaders($this->ajaxHeaders())->put(route('catatan.update', $foreignNote), [
            'judul' => 'Attempted note takeover',
            'kategori' => $foreignNote->kategori,
            'isi' => 'Unauthorized update',
        ])->assertNotFound();
        $this->assertSame('Foreign note', $foreignNote->fresh()->judul);

        $this->actingAs($owner)->withHeaders(['Accept' => 'text/html'])->post(route('tasks.store'), [
            'title' => 'Traditional task',
            'status' => 'pending',
        ])->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', ['user_id' => $owner->id, 'title' => 'Traditional task']);

        auth()->logout();
        $this->withHeaders($this->ajaxHeaders())
            ->post(route('tasks.store'), ['title' => 'Guest task', 'status' => 'pending'])
            ->assertUnauthorized();

        $response = $this->actingAs($owner)->get(route('tasks.create'))->assertOk();
        $response->assertSee('data-ajax-form', false)->assertSee('name="_token"', false);
        $routeMiddleware = app('router')->getRoutes()->getByName('tasks.store')->gatherMiddleware();
        $this->assertContains('web', $routeMiddleware);
        $this->assertContains('auth', $routeMiddleware);
        foreach (['goals.store', 'activities.store', 'catatan.store', 'categories.store'] as $routeName) {
            $middleware = app('router')->getRoutes()->getByName($routeName)->gatherMiddleware();
            $this->assertContains('web', $middleware);
            $this->assertContains('auth', $middleware);
        }
    }

    public function test_ajax_forms_guard_double_submission_and_update_the_page_without_navigation(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('tasks.create'))->assertOk();
        $response->assertSee('data-ajax-form', false);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertIsString($script);
        $this->assertStringContainsString("form.dataset.ajaxSubmitting === 'true'", $script);
        $this->assertStringContainsString("form.dataset.ajaxSubmitting = 'true'", $script);
        $this->assertStringContainsString('new FormData(form)', $script);
        $this->assertStringContainsString("'X-CSRF-TOKEN': token", $script);
        $this->assertStringContainsString("showAjaxToast('success', payload.message)", $script);
        $this->assertStringContainsString('.find((toast) => toast.dataset.toastType === type && toast.dataset.toastMessage === message)', $script);
        $this->assertStringNotContainsString('window.location.reload', $script);
        $this->assertStringNotContainsString('window.location.href', $script);
        $this->assertStringContainsString('data-ajax-toast', $script);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $script);
        $styles = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($styles);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $styles);
    }
}
