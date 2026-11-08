<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use LogicException;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $status = Password::sendResetLink($validated);

        $mailer = config('mail.default');
        if ($mailer === 'log') {
            $message = 'Mode log tidak mengirim email. Jika alamat terdaftar, tautan reset dicatat ke storage/logs/laravel.log untuk pengujian lokal.';
        } elseif ($mailer === 'array') {
            $message = 'Mode array tidak mengirim email. Jika alamat terdaftar, pesan reset hanya tersedia sementara selama aplikasi berjalan.';
        } else {
            $message = match ($status) {
                PasswordBroker::RESET_LINK_SENT,
                PasswordBroker::INVALID_USER => 'Jika alamat email terdaftar, permintaan tautan reset telah diproses.',
                PasswordBroker::RESET_THROTTLED => 'Permintaan reset password baru saja dikirim. Silakan tunggu sebelum meminta tautan baru.',
                default => throw new LogicException('Status permintaan reset password tidak dikenali.'),
            };
        }

        return redirect()->route('password.request')->with('status', $message);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:10',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)
                        || ! preg_match('/[A-Z]/', $value)
                        || ! preg_match('/[a-z]/', $value)
                        || ! preg_match('/[0-9]/', $value)
                        || ! preg_match('/[^A-Za-z0-9\s]/', $value)) {
                        $fail('Password harus memuat huruf besar, huruf kecil, angka, dan simbol khusus.');
                    }
                },
            ],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password harus sama dengan password baru.',
        ]);

        $status = Password::reset(
            [...$validated, 'token' => $token],
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    if (config('session.driver') === 'database') {
                        DB::table(config('session.table', 'sessions'))
                            ->where('user_id', $user->getAuthIdentifier())
                            ->delete();
                    }
                });
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
        }

        $message = match ($status) {
            PasswordBroker::INVALID_USER,
            PasswordBroker::INVALID_TOKEN => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            PasswordBroker::RESET_THROTTLED => 'Permintaan reset password terlalu sering. Silakan tunggu sebelum mencoba kembali.',
            default => throw new LogicException('Status reset password tidak dikenali.'),
        };

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $validated['email'],
        ])->withErrors(['email' => $message]);
    }
}
