<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_be_logged_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ayu Belajar',
            'email' => 'ayu@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ayu@example.com']);
        $this->assertNotNull(User::where('email', 'ayu@example.com')->first()->remember_token);
    }

    public function test_user_can_login_with_remember_me(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_wrong_login_password_shows_form_error_not_login_required_modal(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->from(route('login', ['login_required' => 1, 'redirect_to' => '/tasks']))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password-salah',
                '_form' => 'login',
            ]);

        $response->assertRedirect(route('login'));
        $this->get(route('login'))->assertDontSee('Login Diperlukan');
    }

    public function test_invalid_registration_shows_form_error_not_login_required_modal(): void
    {
        $response = $this->from(route('login', ['login_required' => 1]))
            ->post(route('register.store'), [
                'name' => '',
                'email' => 'bukan-email',
                'password' => 'pendek',
                'password_confirmation' => 'berbeda',
                '_form' => 'register',
            ]);

        $response->assertRedirect(route('login'));
        $this->followRedirects($response)
            ->assertSee('Periksa kembali data yang Anda masukkan.')
            ->assertDontSee('Login Diperlukan');
    }

    public function test_email_login_cannot_reuse_a_google_oauth_chat_session(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-linked-email-login',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession(['auth_provider' => 'google'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('auth_provider'));
    }

    public function test_google_oauth_routes_are_not_available(): void
    {
        $this->get('/auth/google')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $hashedPassword = $user->password;
        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
            'password' => $hashedPassword,
        ]);
    }

    public function test_login_form_allows_browser_credential_autofill(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<form method="POST" action="'.route('login.store').'" autocomplete="on">', false);
        $response->assertSee('id="login-email" type="email" name="email" value=""', false);
        $response->assertSee('name="email" value="" class="form-control', false);
        $response->assertSee('id="login-password" type="password" name="password" value="" class="form-control', false);
        $response->assertSee('autocomplete="username" required', false);
        $response->assertSee('autocomplete="current-password" required', false);
        $response->assertDontSee('readonly', false);
        $response->assertDontSee($user->email);
    }

    public function test_registration_offers_new_password_autocomplete(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('minlength="8"', false)
            ->assertSee('Password minimal 8 karakter. Kombinasi huruf, angka, atau simbol bebas.');
    }

    public function test_login_and_registration_password_fields_can_be_shown_or_hidden(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle="login-password"', false)
            ->assertSee('data-password-toggle="register-password"', false)
            ->assertSee('data-password-toggle="register-password-confirmation"', false)
            ->assertSee("input.type = visible ? 'text' : 'password'", false)
            ->assertSee('Sembunyikan password', false);
    }

    public function test_failed_login_does_not_return_the_submitted_email_in_the_form(): void
    {
        $email = 'tidaktersimpan@example.com';

        $response = $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $email,
                'password' => 'PasswordSalah123!',
                '_form' => 'login',
            ]);

        $response->assertRedirect(route('login'));
        $this->followRedirects($response)
            ->assertDontSee('value="'.$email.'"', false);
    }

    public function test_main_feature_menus_remain_visible_for_guests(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Task Manager')
            ->assertDontSee('Daily Activity')
            ->assertSee('Goal Tracker')
            ->assertDontSee('Catatan Belajar')
            ->assertDontSee('Catatan Favorit')
            ->assertDontSee('Media Favorit')
            ->assertDontSee('Kategori')
            ->assertDontSee('Personal Notes');
    }

    public function test_settings_page_shows_theme_controls_and_login_link_for_guests(): void
    {
        $response = $this->get(route('settings'));

        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('data-theme-color="#2F6F64"', false);
        $response->assertSee('id="custom-theme-color"', false);
        $response->assertSee('id="reduce-motion"', false);
        $response->assertSee(route('login'), false);
    }

    public function test_authenticated_user_avatar_replaces_login_button(): void
    {
        $user = User::factory()->create([
            'name' => 'Ayu Belajar',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('https://example.com/avatar.jpg', false);
        $response->assertSee('Info akun dan ubah nama', false);
        $response->assertDontSee('>Masuk</a>', false);
    }

    public function test_guest_returns_to_requested_feature_after_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->get(route('media-favorites.index'))
            ->assertRedirect(route('login', [
                'login_required' => 1,
                'redirect_to' => '/media-favorit',
            ]));

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('media-favorites.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_user_login_ignores_an_intended_admin_url(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login', [
                'login_required' => 1,
                'redirect_to' => '/admin/dashboard',
            ]));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('ValidAdminPassword123!'),
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'ValidAdminPassword123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Admin');
    }

    public function test_regular_user_login_redirects_to_user_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('ValidUserPassword123!')]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ValidUserPassword123!',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_regular_user_and_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login', [
                'login_required' => 1,
                'redirect_to' => '/admin/dashboard',
            ]));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_registration_cannot_assign_admin_role_or_claim_reserved_admin_email(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Pengguna',
            'email' => 'pengguna@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'pengguna@example.com',
            'role' => 'user',
        ]);

        $this->from(route('login'))->post(route('register.store'), [
            'name' => 'Admin Palsu',
            'email' => 'admin54321@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('login'));
    }

    public function test_admin_seeder_creates_active_hashed_admin_idempotently(): void
    {
        config(['admin.password' => 'StrongLocalAdminPass123!']);

        $this->seed(AdminSeeder::class);
        $admin = User::query()->where('email', 'admin54321@gmail.com')->sole();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('StrongLocalAdminPass123!', $admin->password));
        $this->assertNotSame('StrongLocalAdminPass123!', $admin->password);

        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin54321@gmail.com')->count());
    }

    public function test_admin_seeder_refuses_to_promote_existing_non_admin_account(): void
    {
        config(['admin.password' => null]);
        $existingUser = User::factory()->create([
            'email' => 'admin54321@gmail.com',
            'role' => 'user',
            'is_active' => false,
            'password' => '12345678',
        ]);

        try {
            $this->seed(AdminSeeder::class);
            $this->fail('The seeder must not promote an existing account based only on its email.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('already belongs to a non-admin account', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'role' => 'user',
            'is_active' => false,
        ]);
        $this->assertTrue(Hash::check('12345678', $existingUser->fresh()->password));
    }

    public function test_admin_seeder_does_not_mutate_an_existing_admin(): void
    {
        config(['admin.password' => null]);
        $admin = User::factory()->create([
            'email' => 'admin54321@gmail.com',
            'name' => 'Existing Admin',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'OriginalAdminPassword123!',
        ]);
        $passwordHash = $admin->password;
        $createdAt = $admin->created_at;

        $this->seed(AdminSeeder::class);

        $admin->refresh();
        $this->assertSame('Existing Admin', $admin->name);
        $this->assertTrue($admin->is_active);
        $this->assertSame($passwordHash, $admin->password);
        $this->assertEquals($createdAt, $admin->created_at);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'unknown@example.com',
                'password' => 'WrongPassword123!',
            ])->assertRedirect(route('login'));
        }

        $this->post(route('login.store'), [
            'email' => 'unknown@example.com',
            'password' => 'WrongPassword123!',
        ])->assertTooManyRequests();
    }

    public function test_last_admin_cannot_be_deactivated_or_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);

        try {
            $admin->is_active = false;
            $admin->save();
            $this->fail('The last active admin should not be deactivated.');
        } catch (\LogicException) {
            $this->assertTrue($admin->fresh()->is_active);
        }

        try {
            $admin->delete();
            $this->fail('The last admin should not be deleted.');
        } catch (\LogicException) {
            $this->assertDatabaseHas('users', ['id' => $admin->id]);
        }
    }
}
