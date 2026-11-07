<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', config('admin.email'))->first();
        if ($admin) {
            if ($admin->role !== 'admin') {
                throw new InvalidArgumentException(
                    'The configured admin email already belongs to a non-admin account. Verify ownership and resolve the account conflict manually.'
                );
            }

            return;
        }

        $password = config('admin.password');

        if (! is_string($password)
            || strlen($password) < 16
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/[0-9]/', $password)
            || ! preg_match('/[^A-Za-z0-9\s]/', $password)) {
            throw new InvalidArgumentException(
                'Set ADMIN_PASSWORD locally to a unique password of at least 16 characters, including uppercase, lowercase, a number, and a symbol.'
            );
        }

        $admin = new User;
        $admin->forceFill([
            'name' => 'Admin',
            'role' => 'admin',
            'is_active' => true,
            'email' => config('admin.email'),
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ])->save();
    }
}
