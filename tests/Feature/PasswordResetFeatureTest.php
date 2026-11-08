<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_password_reset_request_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false)
            ->assertSee('Lupa password?');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(route('password.email'), false)
            ->assertSee('Masukkan email akun Anda.');
    }

    public function test_password_reset_link_is_sent_to_the_account_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Mode array tidak mengirim email. Jika alamat terdaftar, pesan reset hanya tersedia sementara selama aplikasi berjalan.');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, &$token): bool {
            $token = $notification->token;
            $resetUrl = route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]);

            return str_contains($notification->toMail($user)->actionUrl, $resetUrl);
        });

        $this->assertIsString($token);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertTrue(Hash::check($token, $storedToken));
    }

    public function test_unknown_email_receives_the_same_generic_confirmation(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.com']);

        $response->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Mode log tidak mengirim email. Jika alamat terdaftar, tautan reset dicatat ke storage/logs/laravel.log untuk pengujian lokal.');

        Notification::assertNothingSent();
    }

    public function test_log_mailer_does_not_claim_that_a_reset_email_was_sent(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $user = User::factory()->create();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Email tidak dikirim karena aplikasi sedang menggunakan mode')
            ->assertSee('storage/logs/laravel.log')
            ->assertSee('Jangan gunakan mode ini sebagai kanal reset password di production.');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Mode log tidak mengirim email. Jika alamat terdaftar, tautan reset dicatat ke storage/logs/laravel.log untuk pengujian lokal.');
    }

    public function test_invalid_email_format_is_rejected_without_creating_a_reset_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'not-an-email'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_email_token_and_confirmation(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $user = User::factory()->create(['password' => 'OldPassword123!']);
        $oldPasswordHash = $user->password;
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Mode log tidak mengirim email. Jika alamat terdaftar, tautan reset dicatat ke storage/logs/laravel.log untuk pengujian lokal.');
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertIsString($token);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Buat password baru');

        $this->post(route('password.update', ['token' => $token]), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame($oldPasswordHash, $user->password);
        $this->assertTrue(Hash::check('NewPassword456!', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $newPasswordHash = $user->password;
        $this->post(route('password.update', ['token' => $token]), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherPassword789!',
            'password_confirmation' => 'AnotherPassword789!',
        ])->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertSessionHasErrors('email');

        $this->assertSame($newPasswordHash, $user->fresh()->password);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'NewPassword456!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'OldPassword123!',
        ])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_revokes_existing_database_sessions_for_the_user(): void
    {
        Notification::fake();
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'OldPassword123!']);
        $otherUser = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => str_repeat('a', 40),
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'test',
                'last_activity' => time(),
            ],
            [
                'id' => str_repeat('b', 40),
                'user_id' => $otherUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'test',
                'last_activity' => time(),
            ],
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update', ['token' => $token]), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('sessions', ['id' => str_repeat('a', 40)]);
        $this->assertDatabaseHas('sessions', ['id' => str_repeat('b', 40)]);
    }

    public function test_password_reset_rejects_mismatched_confirmation_without_changing_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'OriginalPassword123!']);
        $originalHash = $user->password;
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update', ['token' => $token]), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewPassword456!',
                'password_confirmation' => 'DifferentPassword456!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_invalid_reset_token_cannot_change_the_password(): void
    {
        $user = User::factory()->create(['password' => 'OriginalPassword123!']);
        $originalHash = $user->password;

        $this->post(route('password.update', ['token' => 'invalid-token']), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_expired_reset_token_cannot_change_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'OriginalPassword123!']);
        $originalHash = $user->password;
        $token = null;

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertIsString($token);
        $this->travel((int) config('auth.passwords.users.expire') + 1)->minutes();

        $this->post(route('password.update', ['token' => $token]), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertSame($originalHash, $user->fresh()->password);
    }
}
