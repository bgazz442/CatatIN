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
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('redirect_url', route('tasks.index'));
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
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('redirect_url', route('goals.show', Goal::query()->latest('id')->first()));
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

        $this->withHeaders($this->ajaxHeaders())->post(route('goals.progress.store', $goal), [
            'amount' => '2.25',
            'direction' => 'subtract',
            'note' => 'Penyesuaian',
        ])->assertOk()
            ->assertJsonPath('message', 'Progres berhasil dikurangi dari target.')
            ->assertJsonPath('data.current_value', 3.25)
            ->assertJsonPath('data.progress_entry.amount', '-2.25');
        $this->assertSame('3.25', $goal->fresh()->current_value);
        $this->assertDatabaseHas('goal_progress_entries', [
            'goal_id' => $goal->id,
            'amount' => '-2.25',
            'note' => 'Penyesuaian',
        ]);

        $this->withHeaders($this->ajaxHeaders())->post(route('goals.progress.store', $goal), [
            'amount' => '4',
            'direction' => 'subtract',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
        $this->assertSame('3.25', $goal->fresh()->current_value);
    }

    public function test_goal_list_quick_progress_actions_use_the_saved_step_in_both_directions(): void
    {
        $user = User::factory()->create();
        $goal = Goal::query()->create([
            'user_id' => $user->id,
            'name' => 'Stepped goal',
            'target_value' => 10,
            'current_value' => 0,
            'progress_step' => 2.5,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('goals.index'))
            ->assertOk()
            ->assertSee('Per klik:', false)
            ->assertSee('name="use_progress_step" value="1"', false)
            ->assertSee(route('goals.destroy', $goal), false);

        $this->withHeaders($this->ajaxHeaders())->post(route('goals.progress.store', $goal), [
            'use_progress_step' => '1',
            'direction' => 'add',
        ])->assertOk()
            ->assertJsonPath('data.current_value', 2.5);
        $this->assertSame('2.50', $goal->fresh()->current_value);

        $this->withHeaders($this->ajaxHeaders())->post(route('goals.progress.store', $goal), [
            'use_progress_step' => '1',
            'direction' => 'subtract',
        ])->assertOk()
            ->assertJsonPath('data.current_value', 0);
        $this->assertSame('0.00', $goal->fresh()->current_value);
    }

    public function test_goal_edit_form_exposes_delete_action_and_goal_progress_form_has_add_and_subtract_controls(): void
    {
        $user = User::factory()->create();
        $goal = Goal::query()->create([
            'user_id' => $user->id,
            'name' => 'Editable goal',
            'target_value' => 10,
            'current_value' => 2,
            'unit' => 'pages',
            'start_date' => today(),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('goals.edit', $goal))
            ->assertOk()
            ->assertSee('data-ajax-redirect="true"', false)
            ->assertSee('Hapus Target')
            ->assertSee(route('goals.destroy', $goal), false);

        $this->get(route('goals.show', $goal))
            ->assertOk()
            ->assertSee('data-ajax-goal-progress="true"', false)
            ->assertSee('data-progress-direction="add"', false)
            ->assertSee('data-progress-direction="subtract"', false);

        $this->withHeaders($this->ajaxHeaders())->delete(route('goals.destroy', $goal))
            ->assertOk()
            ->assertJsonPath('data.id', $goal->id);
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_task_and_goal_create_forms_enable_navigation_after_successful_ajax_submit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('data-ajax-redirect="true"', false);

        $this->get(route('goals.create'))
            ->assertOk()
            ->assertSee('data-ajax-redirect="true"', false)
            ->assertSee('name="goal_type" value="money"', false)
            ->assertSee('name="goal_type" value="percentage"', false)
            ->assertSee('goal-type-illustration-money', false)
            ->assertSee('goal-type-illustration-percentage', false)
            ->assertSee('name="progress_step"', false);
    }

    public function test_goal_tracker_shows_remaining_progress_as_percentage_and_amount(): void
    {
        $user = User::factory()->create();
        $goal = Goal::query()->create([
            'user_id' => $user->id,
            'name' => 'Five units',
            'target_value' => 5,
            'current_value' => 0,
            'unit' => '10',
            'start_date' => today(),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertSee('100,0% tersisa (5,00 10)')
            ->assertSee('Sisa target: 100,0% tersisa (5,00 10)');

        $this->get(route('goals.index'))
            ->assertOk()
            ->assertSee('100,0% tersisa (5,00 10)');
    }

    public function test_goal_tracker_supports_rupiah_and_percentage_goal_types(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withHeaders($this->ajaxHeaders());

        $moneyResponse = $this->post(route('goals.store'), [
            'name' => 'Emergency fund',
            'goal_type' => 'money',
            'target_value' => '1500000',
            'start_date' => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.goal_type', 'money')
            ->assertJsonPath('data.unit', 'Rupiah')
            ->assertJsonPath('data.formatted_target_value', 'Rp 1.500.000');
        $moneyGoal = Goal::query()->findOrFail($moneyResponse->json('data.id'));

        $this->post(route('goals.progress.store', $moneyGoal), [
            'amount' => '250000',
            'note' => 'Setoran pertama',
        ])->assertOk()
            ->assertJsonPath('data.formatted_current_value', 'Rp 250.000')
            ->assertJsonPath('data.formatted_remaining_value', 'Rp 1.250.000')
            ->assertJsonPath('data.remaining_label', 'Sisa Rp 1.250.000 (83,3% lagi)')
            ->assertJsonPath('data.progress_entry.label', '+Rp 250.000');

        $this->post(route('goals.progress.store', $moneyGoal), [
            'amount' => '250000.50',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $percentageResponse = $this->post(route('goals.store'), [
            'name' => 'Complete course',
            'goal_type' => 'percentage',
            'target_value' => '100',
            'start_date' => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.goal_type', 'percentage')
            ->assertJsonPath('data.unit', '%')
            ->assertJsonPath('data.formatted_target_value', '100%');
        $percentageGoal = Goal::query()->findOrFail($percentageResponse->json('data.id'));

        $this->post(route('goals.progress.store', $percentageGoal), [
            'amount' => '42.5',
        ])->assertOk()
            ->assertJsonPath('data.formatted_current_value', '42,5%')
            ->assertJsonPath('data.remaining_label', 'Sisa 57,5% lagi')
            ->assertJsonPath('data.progress_entry.label', '+42,5%');

        $this->post(route('goals.progress.store', $percentageGoal), [
            'amount' => '60',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->get(route('goals.show', $moneyGoal))
            ->assertOk()
            ->assertSee('Rp 1.500.000')
            ->assertSee('Sisa Rp 1.250.000 (83,3% lagi)');
        $this->get(route('goals.show', $percentageGoal))
            ->assertOk()
            ->assertSee('Sisa 57,5% lagi');
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
        foreach (['goals.store', 'catatan.store', 'categories.store'] as $routeName) {
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
        $this->assertStringContainsString("showAjaxToast('error', message || 'Periksa kembali data yang dimasukkan.')", $script);
        $this->assertStringNotContainsString('data-ajax-result', $script);
        $this->assertStringNotContainsString('Tersimpan:', $script);
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
