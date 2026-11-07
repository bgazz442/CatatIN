<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - CatatIn</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @php
        $adminNavigation = [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M3 10.5 12 3l9 7.5M5.25 9v11.25h13.5V9M9 20.25v-6h6v6'],
            ['route' => 'admin.users.index', 'label' => 'Manajemen User', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m16-13a4 4 0 0 1 0 8m4 5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ['route' => 'admin.settings.edit', 'label' => 'Pengaturan Admin', 'icon' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0-12v2m0 13v2m8.49-15.49-1.42 1.42m-14.14 14.14-1.42 1.42M21 12h-2m-14 0H3m17.49 8.49-1.42-1.42M5.93 5.93 4.51 4.51'],
        ];
    @endphp
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-72 shrink-0 flex-col bg-slate-950 text-white lg:flex">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-white/10 px-7 py-7">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-500 text-xl font-black text-white shadow-lg shadow-emerald-950/30">C</span>
                <span>
                    <span class="block text-lg font-bold tracking-tight">CatatIn</span>
                    <span class="mt-0.5 block text-[11px] font-semibold uppercase tracking-[.2em] text-emerald-300">Admin Console</span>
                </span>
            </a>

            <div class="px-5 pt-8">
                <p class="px-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500">Menu utama</p>
                <nav aria-label="Navigasi admin" class="mt-3 space-y-1.5">
                    @foreach($adminNavigation as $item)
                        @php($active = request()->routeIs($item['route']) || ($item['route'] === 'admin.users.index' && request()->routeIs('admin.users.show')))
                        <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                           class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition {{ $active ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="{{ $item['icon'] }}"/>
                            </svg>
                            {{ $item['label'] }}
                            @if($active)<span class="ml-auto h-1.5 w-1.5 rounded-full bg-white"></span>@endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="mt-auto p-5">
                <div class="rounded-2xl border border-white/10 bg-white/[.06] p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-400/15 text-sm font-bold text-emerald-200">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</span>
                            <span class="mt-0.5 block text-xs text-slate-400">Administrator</span>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-rose-400/30 hover:bg-rose-400/10 hover:text-rose-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                            Keluar dari akun
                        </button>
                    </form>
                </div>
                <p class="mt-5 text-center text-[11px] text-slate-600">CatatIn Admin · Panel pengelolaan</p>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
                <div class="flex min-h-[72px] items-center justify-between gap-4 px-4 sm:px-7 lg:px-10">
                    <div class="flex min-w-0 items-center gap-3">
                        <a href="{{ route('admin.dashboard') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-950 font-bold text-white lg:hidden">C</a>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">@yield('title', 'Dashboard Admin')</p>
                            <p class="hidden text-xs text-slate-500 sm:block">Kelola CatatIn dengan mudah dan aman</p>
                        </div>
                    </div>
                    <div class="hidden items-center gap-3 sm:flex">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-800">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <div class="max-w-52">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
                <nav aria-label="Navigasi admin mobile" class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2.5 lg:hidden">
                    @foreach($adminNavigation as $item)
                        @php($active = request()->routeIs($item['route']) || ($item['route'] === 'admin.users.index' && request()->routeIs('admin.users.show')))
                        <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold {{ $active ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100' }}">{{ $item['label'] }}</a>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Keluar</button>
                    </form>
                </nav>
            </header>

            <main class="mx-auto w-full max-w-[1500px] px-4 py-6 sm:px-7 sm:py-8 lg:px-10 lg:py-10">
                @if(session('success'))
                    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-900 shadow-sm" role="status">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-900 shadow-sm" role="alert">
                        <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                        <ul class="mt-1 list-inside list-disc text-rose-800">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
