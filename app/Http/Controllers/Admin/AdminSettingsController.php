<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.settings', ['admin' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => [
                'nullable',
                'string',
                'min:10',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null
                        && (! preg_match('/[A-Z]/', $value)
                            || ! preg_match('/[a-z]/', $value)
                            || ! preg_match('/[0-9]/', $value)
                            || ! preg_match('/[^A-Za-z0-9\s]/', $value))) {
                        $fail('Password harus memuat huruf besar, huruf kecil, angka, dan simbol khusus.');
                    }
                },
            ],
        ]);

        $admin = $request->user();
        $admin->name = $validated['name'];
        $admin->email = $validated['email'];
        if (! empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }
        $admin->save();

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan admin berhasil disimpan.');
    }
}
