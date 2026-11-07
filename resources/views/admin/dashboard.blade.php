@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
    <section class="relative mb-8 overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
        <div class="pointer-events-none absolute -right-12 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 right-1/4 h-40 w-40 rounded-full bg-teal-400/10 blur-3xl"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold tracking-wide text-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                    AREA ADMINISTRASI
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Dashboard Admin</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">Pantau perkembangan CatatIn dan kelola pengguna dari satu tempat.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-300">
                Kelola pengguna
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
            </a>
        </div>
    </section>

    @php
        $statistics = [
            ['label' => 'Total pengguna', 'value' => $totalUsers, 'hint' => 'Akun terdaftar', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m16-13a4 4 0 0 1 0 8m4 5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'tone' => 'bg-indigo-50 text-indigo-700'],
            ['label' => 'Pengguna aktif', 'value' => $activeUsers, 'hint' => 'Akun dapat digunakan', 'icon' => 'M20 6 9 17l-5-5', 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Task tersimpan', 'value' => $totalTasks, 'hint' => 'Task seluruh pengguna', 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 12h6m-6 4h6', 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Goal tersimpan', 'value' => $totalGoals, 'hint' => 'Goal seluruh pengguna', 'icon' => 'M12 3v3m0 12v3m9-9h-3M6 12H3m15.36-6.36-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0-2.12-2.12M7.76 7.76 5.64 5.64M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z', 'tone' => 'bg-sky-50 text-sky-700'],
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4" aria-label="Statistik website">
        @foreach($statistics as $stat)
            <article class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($stat['value']) }}</p>
                    </div>
                    <span class="grid h-11 w-11 place-items-center rounded-2xl {{ $stat['tone'] }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $stat['icon'] }}"/></svg>
                    </span>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">{{ $stat['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Pengguna berdasarkan role</h2>
                    <p class="mt-1 text-sm text-slate-500">Komposisi akun terdaftar</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m16-13a4 4 0 0 1 0 8m4 5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                </span>
            </div>
            <dl class="mt-6 space-y-4">
                @forelse($roleCounts as $role => $count)
                    @php($roleTotal = max(1, array_sum($roleCounts->all())))
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                            <dt class="font-medium capitalize text-slate-700">{{ $role }}</dt>
                            <dd class="font-bold text-slate-900">{{ number_format($count) }} <span class="font-normal text-slate-400">· {{ number_format($count / $roleTotal * 100, 1) }}%</span></dd>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $role === 'admin' ? 'bg-amber-400' : 'bg-emerald-500' }}" style="width: {{ min(100, $count / $roleTotal * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Belum ada data pengguna.</p>
                @endforelse
            </dl>
        </article>

        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Pendaftaran pengguna</h2>
                    <p class="mt-1 text-sm text-slate-500">Pendaftaran enam bulan terakhir</p>
                </div>
                <span class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800">6 bulan</span>
            </div>
            <dl class="mt-6 space-y-4">
                @foreach($registrationMonths as $month)
                    <div class="grid grid-cols-[minmax(96px,.7fr)_minmax(0,2fr)_32px] items-center gap-3">
                        <dt class="truncate text-sm text-slate-600">{{ $month['label'] }}</dt>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-teal-400 transition-all" style="width: {{ min(100, $month['count'] / max(1, $registrationMonths->max('count')) * 100) }}%"></div>
                        </div>
                        <dd class="text-right text-sm font-bold tabular-nums text-slate-800">{{ number_format($month['count']) }}</dd>
                    </div>
                @endforeach
            </dl>
        </article>
    </section>

    <section class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:px-6">
        <div class="flex items-center gap-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-violet-50 text-violet-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3M16 3h5v5m-10 6L21 4"/></svg>
            </span>
            <div>
                <h2 class="font-bold text-slate-900">Ringkasan data website</h2>
                <p class="mt-1 text-sm text-slate-500">Terdapat <span class="font-semibold text-slate-700">{{ number_format($totalNotes) }} catatan belajar</span> yang tersimpan di CatatIn.</p>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">
            Lihat semua pengguna
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </a>
    </section>
@endsection
