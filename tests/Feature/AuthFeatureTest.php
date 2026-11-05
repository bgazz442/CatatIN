<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_login_form_is_empty_and_uses_browser_password_autofill_tokens(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('autocomplete="username"', false);
        $response->assertSee('autocomplete="current-password"', false);
        $response->assertSee('autocomplete="on"', false);
        $response->assertSee('id="login-email" type="email" name="email" value=""', false);
        $response->assertDontSee($user->email);
    }

    public function test_registration_offers_new_password_autocomplete(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('autocomplete="new-password"', false);
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
            ->assertSee('Daily Activity')
            ->assertSee('Goal Tracker')
            ->assertSee('CatatIn Assistant')
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
        $response->assertSee('Profil Ayu Belajar', false);
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
}