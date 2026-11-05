<?php

namespace Tests\Feature;

use App\Models\DailyActivity;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_titles_accept_valid_values_and_reject_invalid_create_and_update_values(): void
    {
        $user = User::factory()->create();

        foreach (['123456', '!!!@@@', 'Belajar Laravel!', 'Target #1', '@Bagas'] as $invalid) {
            $this->actingAs($user)->from(route('tasks.create'))->post(route('tasks.store'), [
                'title' => $invalid,
                'status' => 'pending',
            ])->assertSessionHasErrors('title');
        }

        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => '  Target UTBK 2027  ',
            'status' => 'pending',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Target UTBK 2027',
            'status' => 'pending',
        ])->assertRedirect();

        $task = Task::where('title', 'Target UTBK 2027')->firstOrFail();
        $this->actingAs($user)->from(route('tasks.edit', $task))->put(route('tasks.update', $task), [
            'title' => 'Belajar Laravel!',
            'status' => 'pending',
        ])->assertSessionHasErrors('title');
    }

    public function test_activity_and_goal_titles_reject_symbols_and_accept_spaced_alphanumeric_names(): void
    {
        $user = User::factory()->create();
        $category = \App\Models\Category::query()->firstOrCreate(['name' => 'Akademik']);

        foreach ([
            ['route' => route('activities.store'), 'field' => 'title', 'data' => [
                'title' => '123456', 'activity_date' => '2026-11-02', 'category_id' => $category->id,
            ]],
            ['route' => route('activities.store'), 'field' => 'title', 'data' => [
                'title' => 'Belajar Database!', 'activity_date' => '2026-11-02', 'category_id' => $category->id,
            ]],
        ] as $case) {
            $this->actingAs($user)->from(route('activities.create'))->post($case['route'], $case['data'])
                ->assertSessionHasErrors($case['field']);
        }

        $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'Latihan Database 01',
            'activity_date' => '2026-11-02',
            'category_id' => $category->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('daily_activities', ['title' => 'Latihan Database 01', 'user_id' => $user->id]);

        foreach (['123456', '!!!@@@', 'Target #1'] as $invalid) {
            $this->actingAs($user)->from(route('goals.create'))->post(route('goals.store'), [
                'name' => $invalid,
                'target_value' => 100,
                'unit' => 'halaman',
                'start_date' => '2026-11-02',
            ])->assertSessionHasErrors('name');
        }

        $this->actingAs($user)->post(route('goals.store'), [
            'name' => 'Belajar Laravel',
            'target_value' => 100,
            'unit' => 'halaman',
            'start_date' => '2026-11-02',
        ])->assertRedirect();
        $this->assertDatabaseHas('goals', ['name' => 'Belajar Laravel', 'user_id' => $user->id]);
    }

    public function test_activity_update_rejects_invalid_title(): void
    {
        $user = User::factory()->create();
        $category = \App\Models\Category::query()->firstOrCreate(['name' => 'Akademik']);
        $activity = DailyActivity::create([
            'user_id' => $user->id,
            'title' => 'Belajar Database',
            'activity_date' => '2026-11-02',
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)->from(route('activities.edit', $activity))->put(route('activities.update', $activity), [
            'title' => 'Belajar Database!',
            'activity_date' => '2026-11-02',
            'category_id' => $category->id,
        ])->assertSessionHasErrors('title');
    }

    public function test_activity_accepts_full_24_hour_time_values_and_displays_selected_day(): void
    {
        $user = User::factory()->create();
        $category = \App\Models\Category::query()->firstOrCreate(['name' => 'Akademik']);

        $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'Belajar Database 01',
            'activity_date' => '2026-11-02',
            'category_id' => $category->id,
            'start_time' => '00:05',
            'end_time' => '23:59',
        ])->assertRedirect();

        $this->assertDatabaseHas('daily_activities', [
            'user_id' => $user->id,
            'title' => 'Belajar Database 01',
            'start_time' => '00:05:00',
            'end_time' => '23:59:00',
        ]);

        $this->actingAs($user)->get(route('activities.index', [
            'date' => '2026-11-02',
            'month' => '2026-11',
        ]))->assertOk();
    }

    public function test_goal_update_rejects_invalid_name(): void
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => 'Belajar Laravel',
            'target_value' => 100,
            'current_value' => 0,
            'unit' => 'halaman',
            'start_date' => '2026-11-02',
            'status' => 'active',
        ]);

        $this->actingAs($user)->from(route('goals.edit', $goal))->put(route('goals.update', $goal), [
            'name' => 'Target #1',
            'target_value' => 100,
            'unit' => 'halaman',
            'start_date' => '2026-11-02',
        ])->assertSessionHasErrors('name');
    }

    public function test_registration_password_policy_rejects_each_missing_requirement(): void
    {
        $invalidPasswords = [
            'Password!' => 'konfirmasi_password', // fewer than 10 and no number
            'password123!' => 'huruf_besar',
            'PASSWORD123!' => 'huruf_kecil',
            'PasswordABC!' => 'angka',
            'Password123' => 'simbol',
        ];

        foreach ($invalidPasswords as $password => $unused) {
            $email = str_replace('.', '', uniqid('secure')).'@example.com';
            $this->from(route('login'))->post(route('register.store'), [
                'name' => 'Pengguna Validasi',
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                '_form' => 'register',
            ])->assertSessionHasErrors('password');
        }
    }

    public function test_registration_accepts_strong_password_and_hashes_it(): void
    {
        $password = 'Password123!';

        $this->post(route('register.store'), [
            'name' => 'Pengguna Aman',
            'email' => 'aman@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            '_form' => 'register',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'aman@example.com')->firstOrFail();
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_registration_rejects_password_confirmation_mismatch(): void
    {
        $this->from(route('login'))->post(route('register.store'), [
            'name' => 'Pengguna Validasi',
            'email' => 'confirmation@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123?',
            '_form' => 'register',
        ])->assertSessionHasErrors('password');
    }
}
