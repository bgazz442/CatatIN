@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Atur tampilan CatatIn sesuai kenyamanan Anda.')

@section('content')
<div class="space-y-6">
    <!-- Live Theme Preview Banner -->
    <div class="settings-preview rounded-2xl border border-slate-200/80 bg-white p-6">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
            <div class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-theme-accent text-white shadow-xs">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Pratinjau Tema Aktif</h2>
                    <p class="text-xs text-slate-500">Warna tema akan langsung diterapkan ke seluruh elemen CatatIn secara real-time.</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-800">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Sinkron
            </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Aksen Utama</p>
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl bg-theme-accent shadow-sm flex items-center justify-center text-white">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold bg-theme-accent text-white">Tombol Aksen</span>
                        <p class="text-xs text-slate-500 mt-1">Mengatur tombol, indikator, dan progres</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Permukaan Navigasi</p>
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-xl theme-nav-surface shadow-sm flex items-center justify-center text-white">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold theme-nav-surface">Bilah Menu</span>
                        <p class="text-xs text-slate-500 mt-1">Mengatur sidebar, header, dan footer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Customization Card -->
    <section class="settings-section bg-white border border-slate-200/80 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-[var(--theme-accent)]/10 text-[var(--theme-accent)] flex items-center justify-center shrink-0 border border-[var(--theme-accent)]/20 shadow-xs">
                    <i data-lucide="palette" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Warna tampilan</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Pilih palet atau tentukan warna utama sendiri.</p>
                </div>
            </div>
        </div>

        <form id="theme-settings-form" method="POST" action="{{ route('settings.theme.update') }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
            @csrf
            @php
                $accentColor = $themePreference?->accent_color ?? '#2F6F64';
                $navigationFooterColor = $themePreference?->navigation_footer_color ?? '#1F2937';
            @endphp
            <div class="space-y-7 p-6 sm:p-7">
                <div>
                    <h3 class="mb-3 text-sm font-bold text-slate-800">Palet warna utama</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Preset warna tema">
                        <button type="button" data-theme-color="#2F6F64" class="theme-option flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl text-left hover:border-slate-400 transition" aria-pressed="false">
                            <span class="w-8 h-8 rounded-lg shrink-0 shadow-xs" style="background-color:#2F6F64"></span>
                            <span class="text-sm font-bold text-slate-700">Pinus</span>
                        </button>
                        <button type="button" data-theme-color="#3E6B8C" class="theme-option flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl text-left hover:border-slate-400 transition" aria-pressed="false">
                            <span class="w-8 h-8 rounded-lg shrink-0 shadow-xs" style="background-color:#3E6B8C"></span>
                            <span class="text-sm font-bold text-slate-700">Samudra</span>
                        </button>
                        <button type="button" data-theme-color="#9A593E" class="theme-option flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl text-left hover:border-slate-400 transition" aria-pressed="false">
                            <span class="w-8 h-8 rounded-lg shrink-0 shadow-xs" style="background-color:#9A593E"></span>
                            <span class="text-sm font-bold text-slate-700">Bata</span>
                        </button>
                        <button type="button" data-theme-color="#8A5E76" class="theme-option flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl text-left hover:border-slate-400 transition" aria-pressed="false">
                            <span class="w-8 h-8 rounded-lg shrink-0 shadow-xs" style="background-color:#8A5E76"></span>
                            <span class="text-sm font-bold text-slate-700">Anggur</span>
                        </button>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label for="custom-theme-color" class="block text-sm font-bold text-slate-800">Warna pilihan</label>
                            <p class="mt-0.5 text-xs text-slate-500">Tentukan warna utama sendiri. Pilih warna yang tetap jelas dengan teks putih.</p>
                        </div>
                        <input id="custom-theme-color" type="color" name="accent_color" value="{{ $accentColor }}" class="w-14 h-11 p-1 bg-white border border-slate-200 rounded-xl cursor-pointer shadow-xs" aria-label="Pilih warna utama">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <h3 class="mb-1 text-sm font-bold text-slate-800">Palet navbar dan footer</h3>
                    <p class="mb-3.5 text-xs text-slate-500">Satu pilihan warna untuk bilah navigasi desktop/mobile dan footer.</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Preset warna navbar dan footer">
                        <button type="button" data-navigation-footer-color="#1F2937" class="navigation-footer-option flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-lg shadow-xs" style="background-color:#1F2937"></span><span class="text-sm font-bold text-slate-700">Arang</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#173B35" class="navigation-footer-option flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-lg shadow-xs" style="background-color:#173B35"></span><span class="text-sm font-bold text-slate-700">Pinus Gelap</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#1E3A5F" class="navigation-footer-option flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-lg shadow-xs" style="background-color:#1E3A5F"></span><span class="text-sm font-bold text-slate-700">Samudra Malam</span>
                        </button>
                        <button type="button" data-navigation-footer-color="#3B2A4A" class="navigation-footer-option flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 text-left transition hover:border-slate-400" aria-pressed="false">
                            <span class="h-8 w-8 shrink-0 rounded-lg shadow-xs" style="background-color:#3B2A4A"></span><span class="text-sm font-bold text-slate-700">Anggur Gelap</span>
                        </button>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label for="custom-navigation-footer-color" class="block text-sm font-bold text-slate-800">Warna navbar/footer pilihan</label>
                            <p class="mt-0.5 text-xs text-slate-500">Gunakan warna gelap agar teks putih tetap jelas.</p>
                        </div>
                        <input id="custom-navigation-footer-color" type="color" name="navigation_footer_color" value="{{ $navigationFooterColor }}" class="h-11 w-14 cursor-pointer rounded-xl border border-slate-200 bg-white p-1 shadow-xs" aria-label="Pilih warna navbar dan footer">
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Palet disimpan pada akun Anda dan terpisah dari akun lain.</p>
                    @auth
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[var(--theme-accent)] px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-[var(--theme-accent)]/20 transition hover:bg-[var(--theme-accent-hover)] hover:shadow-md">
                            <i data-lucide="save" class="h-4 w-4"></i><span>Simpan tampilan</span>
                        </button>
                    @else
                        <button type="button" data-login-required data-return-to="{{ route('settings', [], false) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[var(--theme-accent)] px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-[var(--theme-accent)]/20 transition hover:bg-[var(--theme-accent-hover)] hover:shadow-md">
                            <i data-lucide="log-in" class="h-4 w-4"></i><span>Masuk untuk menyimpan</span>
                        </button>
                    @endauth
                </div>
            </div>
        </form>
    </section>

    <!-- Accessibility Card -->
    <section class="settings-section bg-white border border-slate-200/80 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200 shadow-xs">
                    <i data-lucide="accessibility" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Aksesibilitas</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Kurangi animasi untuk pengalaman yang lebih tenang.</p>
                </div>
            </div>
        </div>
        <label for="reduce-motion" class="flex items-center justify-between gap-5 p-6 cursor-pointer hover:bg-slate-50/60 transition">
            <span>
                <span class="block text-sm font-bold text-slate-800">Kurangi animasi</span>
                <span class="block mt-1 text-xs text-slate-500">Saat aktif, animasi masuk, hover, dan transisi dinonaktifkan.</span>
            </span>
            <input id="reduce-motion" type="checkbox" class="w-5 h-5 accent-[var(--theme-accent)] shrink-0 rounded-md">
        </label>
    </section>

    <!-- Reset Theme & Status -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
        <p id="theme-status" class="text-xs text-slate-500" role="status" aria-live="polite">
            @auth
                Palet warna tersimpan pada akun Anda; preferensi animasi hanya berlaku di browser ini.
            @else
                Pilihan warna aktif di browser ini; masuk untuk menyimpannya pada akun.
            @endauth
        </p>
        <button id="reset-theme" type="button" class="inline-flex items-center justify-center gap-2 px-4.5 py-2.5 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl bg-white hover:bg-slate-50 hover:border-slate-400 transition shadow-xs">
            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
            <span>Atur ulang tampilan</span>
        </button>
    </div>
</div>
@endsection