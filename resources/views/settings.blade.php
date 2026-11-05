@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Atur tampilan CatatIn sesuai kenyamanan Anda.')

@section('content')
<div class="max-w-4xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl shadow-soft overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#2F6F64]/10 text-[#2F6F64] flex items-center justify-center shrink-0">
                    <i data-lucide="palette" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Warna tampilan</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih palet atau tentukan warna utama sendiri.</p>
                </div>
            </div>
        </div>

        <form id="theme-settings-form" method="POST" action="{{ route('settings.theme.update') }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
            @csrf
            @php
                $accentColor = $themePreference?->accent_color ?? '#2F6F64';
                $navigationFooterColor = $themePreference?->navigation_footer_color ?? '#1F2937';
            @endphp
            <div class="space-y-7 p-6">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-slate-800">Palet warna utama</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Preset warna tema">
                <button type="button" data-theme-color="#2F6F64" class="theme-option flex items-center gap-3 p-3 border border-slate-200 rounded-lg text-left hover:border-slate-400 transition" aria-pressed="false">
                    <span class="w-8 h-8 rounded-md shrink-0" style="background-color:#2F6F64"></span>
                    <span class="text-sm font-semibold text-slate-700">Pinus</span>
                </button>
                <button type="button" data-theme-color="#3E6B8C" class="theme-option flex items-center gap-3 p-3 border border-slate-200 rounded-lg text-left hover:border-slate-400 transition" aria-pressed="false">
                    <span class="w-8 h-8 rounded-md shrink-0" style="background-color:#3E6B8C"></span>
                    <span class="text-sm font-semibold text-slate-700">Samudra</span>
                </button>
                <button type="button" data-theme-color="#9A593E" class="theme-option flex items-center gap-3 p-3 border border-slate-200 rounded-lg text-left hover:border-slate-400 transition" aria-pressed="false">
                    <span class="w-8 h-8 rounded-md shrink-0" style="background-color:#9A593E"></span>
                    <span class="text-sm font-semibold text-slate-700">Bata</span>
                </button>
                <button type="button" data-theme-color="#8A5E76" class="theme-option flex items-center gap-3 p-3 border border-slate-200 rounded-lg text-left hover:border-slate-400 transition" aria-pressed="false">
                    <span class="w-8 h-8 rounded-md shrink-0" style="background-color:#8A5E76"></span>
                    <span class="text-sm font-semibold text-slate-700">Anggur</span>
                </button>
            </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label for="custom-theme-color" class="block text-sm font-semibold text-slate-800">Warna pilihan</label>
                            <p class="mt-1 text-xs text-slate-500">Tentukan warna utama sendiri. Pilih warna yang tetap jelas dengan teks putih.</p>
                        </div>
                        <input id="custom-theme-color" type="color" name="accent_color" value="{{ $accentColor }}" class="w-12 h-10 p-1 bg-white border border-slate-200 rounded-lg cursor-pointer" aria-label="Pilih warna utama">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <h3 class="mb-1 text-sm font-semibold text-slate-800">Palet navbar dan footer</h3>
                    <p class="mb-3 text-xs text-slate-500">Satu pilihan warna untuk bilah navigasi desktop/mobile dan footer.</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Preset warna navbar dan footer">
                        <button type="button" data-navigation-footer-color="#1F2937" class="navigation-footer-option flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-md" style="background-color:#1F2937"></span><span class="text-sm font-semibold text-slate-700">Arang</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#173B35" class="navigation-footer-option flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-md" style="background-color:#173B35"></span><span class="text-sm font-semibold text-slate-700">Pinus Gelap</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#1E3A5F" class="navigation-footer-option flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-md" style="background-color:#1E3A5F"></span><span class="text-sm font-semibold text-slate-700">Samudra Malam</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#3B2A4A" class="navigation-footer-option flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-md" style="background-color:#3B2A4A"></span><span class="text-sm font-semibold text-slate-700">Anggur Gelap</span>
                        </button>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label for="custom-navigation-footer-color" class="block text-sm font-semibold text-slate-800">Warna navbar/footer pilihan</label>
                            <p class="mt-1 text-xs text-slate-500">Gunakan warna gelap agar teks putih tetap jelas.</p>
                        </div>
                        <input id="custom-navigation-footer-color" type="color" name="navigation_footer_color" value="{{ $navigationFooterColor }}" class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-1" aria-label="Pilih warna navbar dan footer">
                    </div>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Palet disimpan pada akun Anda dan terpisah dari akun lain.</p>
                    @auth
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
                            <i data-lucide="save" class="h-4 w-4"></i><span>Simpan tampilan</span>
                        </button>
                    @else
                        <button type="button" data-login-required data-return-to="{{ route('settings', [], false) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
                            <i data-lucide="log-in" class="h-4 w-4"></i><span>Masuk untuk menyimpan</span>
                        </button>
                    @endauth
                </div>
            </div>
        </form>
    </section>

    <section class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Kategori pembelajaran</h2>
            <p class="mt-1 text-xs text-slate-500">Tambah, ubah, dan kelola kategori catatan serta media.</p>
        </div>
        @auth
            <a href="{{ route('categories.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
                <i data-lucide="folder-cog" class="h-4 w-4"></i><span>Kelola Kategori</span>
            </a>
        @else
            <button type="button" data-login-required data-return-to="{{ route('categories.index', [], false) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
                <i data-lucide="folder-cog" class="h-4 w-4"></i><span>Kelola Kategori</span>
            </button>
        @endauth
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-soft overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                    <i data-lucide="accessibility" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Aksesibilitas</h2>
                    <p class="mt-1 text-sm text-slate-500">Kurangi animasi untuk pengalaman yang lebih tenang.</p>
                </div>
            </div>
        </div>
        <label for="reduce-motion" class="flex items-center justify-between gap-5 p-6 cursor-pointer">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Kurangi animasi</span>
                <span class="block mt-1 text-xs text-slate-500">Saat aktif, animasi masuk, hover, dan transisi dinonaktifkan.</span>
            </span>
            <input id="reduce-motion" type="checkbox" class="w-5 h-5 accent-[#2F6F64] shrink-0">
        </label>
    </section>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p id="theme-status" class="text-sm text-slate-500" role="status" aria-live="polite">Palet warna tersimpan pada akun Anda; preferensi animasi hanya berlaku di browser ini.</p>
        <button id="reset-theme" type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50 transition">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            <span>Atur ulang tampilan</span>
        </button>
    </div>
</div>
@endsection