<?php

namespace Tests\Feature;

use App\Models\Catatan;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_live_counts_and_has_separate_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Tes task']);
        Goal::create([
            'user_id' => $user->id,
            'name' => 'Tes goal',
            'target_value' => 10,
            'current_value' => 2,
            'unit' => 'unit',
            'start_date' => today(),
        ]);
        Catatan::create([
            'user_id' => $user->id,
            'judul' => 'Tes catatan',
            'kategori' => 'Lainnya',
            'isi' => 'Isi privat tidak ditampilkan dalam dashboard admin.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $response->assertSee('Dashboard Admin')
            ->assertSee('Manajemen User')
            ->assertSee('Pengguna berdasarkan role')
            ->assertSee('Pendaftaran enam bulan terakhir')
            ->assertDontSee('Task Manager')
            ->assertDontSee('Daily Activities')
            ->assertSee('2'); // admin and regular user
    }

    public function test_admin_can_search_filter_paginate_and_view_user_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory(18)->create();
        $target = User::factory()->create([
            'name' => 'Target Pagination',
            'email' => 'target-pagination@example.com',
            'is_active' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manajemen User')
            ->assertSee('15')
            ->assertSee('Next');

        $this->get(route('admin.users.index', ['search' => 'target-pagination', 'status' => 'inactive']))
            ->assertOk()
            ->assertSee('target-pagination@example.com')
            ->assertDontSee('name="password"', false);

        $this->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('Detail Pengguna')
            ->assertSee('Password dan password hash tidak ditampilkan');
    }

    public function test_only_primary_admin_can_grant_or_revoke_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $primaryAdmin = User::factory()->create([
            'email' => config('admin.email'),
            'role' => 'admin',
        ]);
        $user = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertDontSee('<option value="admin"', false);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.com',
            'role' => 'admin',
            'is_active' => '0',
        ])->assertForbidden();

        foreach ([$admin, $otherAdmin, $primaryAdmin] as $protectedAdmin) {
            $this->put(route('admin.users.update', $protectedAdmin), [
                'name' => 'Tidak boleh',
                'email' => 'forbidden@example.com',
                'role' => 'user',
                'is_active' => '0',
            ])->assertForbidden();
            $this->delete(route('admin.users.destroy', $protectedAdmin))->assertForbidden();
        }

        $this->actingAs($primaryAdmin)->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('<option value="admin"', false);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.com',
            'role' => 'admin',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.com',
            'role' => 'user',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user', 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $primaryAdmin->id, 'role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_delete_regular_user_and_dependent_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Akan terhapus bersama pemilik']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_admin_settings_update_profile_and_require_current_password_for_password_change(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Admin')
            ->assertDontSee('password hash');

        $this->put(route('admin.settings.update'), [
            'name' => 'Admin Baru',
            'email' => $admin->email,
            'role' => 'user',
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewAdminPassword456!',
            'password_confirmation' => 'NewAdminPassword456!',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('Admin Baru', $admin->fresh()->name);
        $this->assertTrue(Hash::check('NewAdminPassword456!', $admin->fresh()->password));

        $this->put(route('admin.settings.update'), [
            'name' => 'Admin',
            'email' => $admin->email,
            'current_password' => 'salah',
            'password' => 'AnotherPassword456!',
            'password_confirmation' => 'AnotherPassword456!',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_admin_and_regular_routes_are_isolated_and_logout_removes_admin_access(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('tasks.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login', [
            'login_required' => 1,
            'redirect_to' => '/admin/dashboard',
        ]));
    }
}
