<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlphanumericTitle implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Judul hanya boleh menggunakan huruf, angka, dan spasi.');
            return;
        }

        if (trim($value) !== $value) {
            $fail('Spasi di awal atau akhir judul tidak diperbolehkan.');
            return;
        }

        if (preg_match('/\A[A-Za-z0-9]+(?: [A-Za-z0-9]+)*\z/', $value) !== 1) {
            $fail('Judul hanya boleh berisi huruf A-Z, angka 0-9, dan spasi antar kata.');
            return;
        }

        if (! preg_match('/[A-Za-z]/', $value)) {
            $fail('Judul tidak boleh hanya berisi angka atau simbol.');
        }
    }
}
