<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50" data-account-id="{{ auth()->id() }}" data-theme-accent="{{ auth()->user()?->themePreference?->accent_color ?? '#2F6F64' }}" data-theme-nav-surface="{{ auth()->user()?->themePreference?->navigation_footer_color ?? '#1F2937' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="CatatIn - Aplikasi produktivitas personal untuk mengelola tugas dan target pribadi.">
    <title>@yield('title', 'Dashboard') - CatatIn</title>

    <!-- Google Fonts: Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-[#F8FAFC] text-[#1F2937] flex flex-col md:flex-row md:pl-64">

    <!-- Mobile Header -->
    <div class="theme-nav-surface md:hidden flex items-center justify-between bg-white border-b border-slate-200 px-4 py-3 sticky top-0 z-40">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl theme-gradient flex items-center justify-center text-white shadow-md shadow-emerald-100">
                <i data-lucide="book-open" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="font-bold text-lg text-slate-800 tracking-tight">Catat<span class="text-[#2F6F64]">In</span></span>
            </div>
        </a>
        <div class="flex items-center gap-2">
            @guest
                <a href="{{ route('login') }}" class="px-2 py-1.5 text-[#2F6F64] text-xs font-semibold rounded-lg hover:bg-slate-100 transition">Masuk</a>
            @endguest
            @auth
            <span class="inline-flex items-center justify-center w-9 h-9 overflow-hidden rounded-full bg-[#2F6F64] text-white text-xs font-bold ring-2 ring-white shadow-sm" title="{{ auth()->user()->name }}" aria-label="Profil {{ auth()->user()->name }}">
                @if(auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </span>
            @endauth
            <button id="mobile-menu-btn" type="button" class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition" aria-label="Buka Menu">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Backdrop -->
    <div id="mobile-backdrop" class="fixed inset-0 bg-slate-900/40 z-40 hidden md:hidden backdrop-blur-xs transition-opacity"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="theme-nav-surface fixed md:fixed top-0 bottom-0 left-0 md:h-screen w-64 bg-white border-r border-[#E5E7EB] z-50 flex flex-col transition-transform duration-300 -translate-x-full md:translate-x-0 shadow-sm shrink-0">
        <!-- Logo / App Identity -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl theme-gradient flex items-center justify-center text-white shadow-md shadow-[#2F6F64]/20">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="font-bold text-xl text-slate-900 tracking-tight">Catat<span class="text-[#2F6F64]">In</span></span>
                    <p class="text-[10px] text-[#64748B] font-medium leading-none mt-0.5">Produktivitas Personal</p>
                </div>
            </a>
            <button id="close-mobile-menu" type="button" class="md:hidden text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Sidebar Navigation Links -->
        <div class="p-4 flex-1 min-h-0 space-y-1.5 overflow-y-auto">
            <p class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</p>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('dashboard') ? 'bg-[#2F6F64]/10 text-[#2F6F64] font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-[#2F6F64]' : 'text-slate-400' }}"></i>
                <span>Dashboard</span>
            </a>

            <a href="{{ auth()->check() ? route('tasks.index') : route('login', ['login_required' => 1, 'redirect_to' => '/tugas']) }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('tasks.*') ? 'bg-[#2F6F64]/10 text-[#2F6F64] font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i data-lucide="list-checks" class="w-4 h-4 {{ request()->routeIs('tasks.*') ? 'text-[#2F6F64]' : 'text-slate-400' }}"></i>
                <span>Task Manager</span>
            </a>
            <a href="{{ auth()->check() ? route('goals.index') : route('login', ['login_required' => 1, 'redirect_to' => '/target']) }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('goals.*') ? 'bg-[#2F6F64]/10 text-[#2F6F64] font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i data-lucide="target" class="w-4 h-4 {{ request()->routeIs('goals.*') ? 'text-[#2F6F64]' : 'text-slate-400' }}"></i>
                <span>Goal Tracker</span>
            </a>
            <a href="{{ auth()->check() ? route('ai.index') : route('login', ['login_required' => 1, 'redirect_to' => '/ai']) }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('ai.*') ? 'bg-[#6D5CE8]/10 text-[#6D5CE8] font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i data-lucide="sparkles" class="w-4 h-4 {{ request()->routeIs('ai.*') ? 'text-[#6D5CE8]' : 'text-slate-400' }}"></i>
                    <span>CatatIn Assistant</span>
                </a>

            <a href="{{ route('settings') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('settings') ? 'bg-[#2F6F64]/10 text-[#2F6F64] font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i data-lucide="settings-2" class="w-4 h-4 {{ request()->routeIs('settings') ? 'text-[#2F6F64]' : 'text-slate-400' }}"></i>
                <span>Pengaturan</span>
            </a>

        </div>

        <!-- Sidebar Footer / Tagline -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/60">
            <div class="p-3 bg-white border border-slate-200/80 rounded-xl">
                <div class="flex items-center gap-2 mb-1 text-[#2F6F64]">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span class="text-xs font-semibold">CatatIn Personal</span>
                </div>
                <p class="text-xs text-slate-600 italic">"Atur langkah, capai tujuan."</p>
                <p class="text-[10px] text-slate-400 mt-1">Tugas, target, dan assistant</p>
            </div>
            @guest
                <a href="{{ route('login') }}" class="mt-3 w-full flex items-center justify-center gap-2 px-3 py-2.5 bg-[#2F6F64] text-white text-sm font-semibold rounded-lg hover:bg-[#24574F] transition">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Masuk</span>
                </a>
            @endguest
            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-rose-50 hover:text-rose-700 transition">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        <!-- Topbar Desktop -->
        <header class="theme-nav-surface hidden md:flex items-center justify-between bg-white border-b border-[#E5E7EB] px-8 py-4 sticky top-0 z-30 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs text-[#64748B] mt-0.5">@yield('page-subtitle', 'Kelola seluruh ringkasan pelajaran Anda dengan rapi dan terorganisir.')</p>
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
                @guest
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50 transition">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Masuk</span>
                    </a>
                @endguest
                @auth
                    <span class="inline-flex items-center justify-center w-10 h-10 overflow-hidden rounded-full bg-[#2F6F64] text-white text-sm font-bold ring-2 ring-white shadow-sm" title="{{ auth()->user()->name }}" aria-label="Profil {{ auth()->user()->name }}">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </span>
                @endauth
                </div>
        </header>

        <!-- Notification Alerts -->
        <div class="notification-stack pointer-events-none fixed bottom-4 left-4 right-4 z-[70] flex flex-col items-stretch gap-2 md:bottom-6 md:left-auto md:right-6 md:w-[360px] md:items-end" aria-live="polite">
            @if(session('success'))
                <div id="alert-success" data-notification="success" data-auto-dismiss="4500" class="notification-enter pointer-events-auto flex w-full items-center justify-between rounded-xl border border-emerald-200 bg-white/95 p-3.5 text-sm text-emerald-800 shadow-lg shadow-slate-900/10 backdrop-blur" role="status">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-emerald-900">Berhasil!</p>
                            <p class="text-xs text-emerald-700">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button type="button" data-dismiss-notification="alert-success" class="ml-3 shrink-0 rounded-md p-1 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700" aria-label="Tutup notifikasi">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div id="alert-error" data-notification="error" data-auto-dismiss="6500" class="notification-enter pointer-events-auto flex w-full items-center justify-between rounded-xl border border-rose-200 bg-white/95 p-3.5 text-sm text-rose-800 shadow-lg shadow-slate-900/10 backdrop-blur" role="alert">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-rose-900">Terjadi Kesalahan!</p>
                            <p class="text-xs text-rose-700">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button type="button" data-dismiss-notification="alert-error" class="ml-3 shrink-0 rounded-md p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-700" aria-label="Tutup notifikasi">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif
        </div>

        <!-- Page Main Slot -->
        <main class="flex-1 px-4 pt-4 md:px-8 md:pt-5 pb-10">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="theme-page-footer theme-nav-surface mt-auto border-t px-5 py-8 md:px-8 md:py-9" role="contentinfo">
            <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                <div class="flex items-center gap-3">
                    <div class="theme-footer-mark flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white shadow-sm">
                        <i data-lucide="book-open-check" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Catat<span class="theme-footer-accent">In</span></p>
                        <p class="mt-1 text-xs text-slate-500">Produktivitas personal untuk tugas dan target</p>
                    </div>
                </div>
                <p class="theme-footer-copy rounded-full px-4 py-2 text-xs font-semibold">&copy; {{ now()->year }} CatatIn</p>
            </div>
        </footer>
    </div>

    @guest
        <div id="login-required-modal" data-login-url="{{ route('login') }}" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4 bg-slate-950/55 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="login-required-title">
            <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#2F6F64]/10 text-[#2F6F64]"><i data-lucide="lock-keyhole" class="h-5 w-5"></i></span>
                    <h2 id="login-required-title" class="text-lg font-bold text-slate-900">Login Diperlukan</h2>
                </div>
                <p class="text-sm leading-relaxed text-slate-600">Silakan login terlebih dahulu untuk menggunakan fitur ini dan menyimpan materi pembelajaran Anda.</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-login-required-later class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Nanti</button>
                    <a id="login-required-link" href="{{ route('login') }}" class="rounded-lg bg-[#2F6F64] px-4 py-2 text-sm font-semibold text-white hover:bg-[#24574F]">Login Sekarang</a>
                </div>
            </div>
            <button type="button" data-login-required-backdrop class="absolute inset-0 -z-10 cursor-default" aria-label="Tutup dialog"></button>
        </div>
    @endguest

    <!-- Global Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-all">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 transform transition-all animate-scaleUp">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <i data-lucide="trash-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Hapus Data</h3>
                    <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            
            <p class="text-sm text-slate-600 mb-6">
                Apakah Anda yakin ingin menghapus <strong id="delete-item-title" class="text-slate-900"></strong>? Data yang dihapus akan hilang dari database.
            </p>

            <form id="delete-modal-form" method="POST" action="" data-ajax-form data-ajax-delete-page="true">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-end gap-3">
                    <button type="button" id="delete-modal-cancel" class="px-4 py-2 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 text-white text-sm font-semibold rounded-xl hover:bg-rose-700 transition shadow-sm shadow-rose-600/30">
                        Ya, Hapus Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts for UI Interactions -->
    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeMobileBtn = document.getElementById('close-mobile-menu');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('mobile-backdrop');

        function toggleSidebar(open) {
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => toggleSidebar(true));
        }
        if (closeMobileBtn) {
            closeMobileBtn.addEventListener('click', () => toggleSidebar(false));
        }
        if (backdrop) {
            backdrop.addEventListener('click', () => toggleSidebar(false));
        }

        // Global Delete Modal Trigger
        window.openDeleteModal = function(actionUrl, itemTitle) {
            const modal = document.getElementById('delete-modal');
            const form = document.getElementById('delete-modal-form');
            const titleElem = document.getElementById('delete-item-title');
            
            form.action = actionUrl;
            titleElem.textContent = '"' + itemTitle + '"';
            modal.classList.remove('hidden');
        };

        const deleteCancelBtn = document.getElementById('delete-modal-cancel');
        if (deleteCancelBtn) {
            deleteCancelBtn.addEventListener('click', () => {
                document.getElementById('delete-modal').classList.add('hidden');
            });
        }
    </script>
</body>
</html>
