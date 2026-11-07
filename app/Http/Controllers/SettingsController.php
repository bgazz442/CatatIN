<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()?->role === 'admin') {
            return redirect()->route('admin.settings.edit');
        }

        return view('settings', [
            'themePreference' => $request->user()?->themePreference,
        ]);
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'accent_color' => [
                'required',
                'regex:/\A#[0-9A-Fa-f]{6}\z/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $this->contrastRatio($value, '#FFFFFF') < 4.5) {
                        $fail('Pilih warna utama yang cukup gelap agar teks tombol tetap terbaca.');
                    }
                },
            ],
            'navigation_footer_color' => [
                'required',
                'regex:/\A#[0-9A-Fa-f]{6}\z/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $this->contrastRatio($value, '#FFFFFF') < 4.5) {
                        $fail('Pilih warna gelap agar teks navbar dan footer tetap terbaca.');
                    }
                },
            ],
        ]);

        UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return redirect()->route('settings')->with('success', 'Warna tampilan berhasil disimpan untuk akun Anda.');
    }

    private function contrastRatio(string $firstColor, string $secondColor): float
    {
        if (! preg_match('/\A#[0-9A-Fa-f]{6}\z/', $firstColor)
            || ! preg_match('/\A#[0-9A-Fa-f]{6}\z/', $secondColor)) {
            return 0;
        }

        $luminance = static function (string $color): float {
            $channels = [];
            foreach ([1, 3, 5] as $offset) {
                $channel = hexdec(substr($color, $offset, 2)) / 255;
                $channels[] = $channel <= 0.04045
                    ? $channel / 12.92
                    : (($channel + 0.055) / 1.055) ** 2.4;
            }

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        $luminances = [$luminance($firstColor), $luminance($secondColor)];
        rsort($luminances);

        return ($luminances[0] + 0.05) / ($luminances[1] + 0.05);
    }
}
