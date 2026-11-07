<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);
        if ($validator->fails()) {
            return redirect()->route('login')
                ->withErrors($validator)
                ->withInput(array_merge($request->only(['remember']), ['_form' => 'login']));
        }

        $credentials = $validator->validated();

        if (! Auth::attempt(
            [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'is_active' => true,
            ],
            $request->boolean('remember')
        )) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email atau password yang dimasukkan tidak sesuai.'])
                ->withInput(array_merge($request->only(['remember']), ['_form' => 'login']));
        }

        $request->session()->regenerate();
        $request->session()->forget('auth_provider');

        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $intendedPath = parse_url((string) $request->session()->get('url.intended'), PHP_URL_PATH);
        if (is_string($intendedPath) && ($intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/'))) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
            'remember' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 10 karakter.',
            'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
        ]);
        if ($validator->fails()) {
            return redirect()->route('login')
                ->withErrors($validator)
                ->withInput(array_merge($request->only(['name', 'email', 'remember']), ['_form' => 'register']));
        }

        $validated = $validator->validated();

        if (strtolower($validated['email']) === config('admin.email')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email ini tidak tersedia untuk registrasi.'])
                ->withInput(array_merge($request->only(['name', 'remember']), ['_form' => 'register']));
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('auth_provider');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari CatatIn.');
    }
}
