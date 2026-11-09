@extends('layouts.app')

@section('title', 'Dashboard Produktivitas')
@section('page-title', 'Dashboard Produktivitas')
@section('page-subtitle', 'Ringkasan tugas dan target pribadi Anda.')

@section('content')
<div class="dashboard-layout space-y-8">
    <section class="dashboard-hero-grid">
        <div class="dashboard-welcome">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.18em] text-white/75">
                <span class="h-2 w-2 rounded-full bg-lime-300"></span>
                <span>Ruang produktivitas pribadi</span>
            </div>
            <h2 class="mt-5 max-w-xl text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl">Satu langkah kecil, progres yang berarti.</h2>
            <p class="mt-3 max-w-lg text-sm leading-6 text-white/75 sm:text-base">Atur pekerjaan, simpan materi penting, dan jaga target tetap terlihat.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('tasks.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[var(--theme-accent)] transition hover:bg-lime-50">
                        <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah tugas</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[var(--theme-accent)] transition hover:bg-lime-50">
                        <i data-lucide="log-in" class="h-4 w-4"></i><span>Masuk untuk mulai</span>
                    </a>
                @endauth
            </div>
            <div class="dashboard-orbit dashboard-orbit-one"></div>
            <div class="dashboard-orbit dashboard-orbit-two"></div>
        </div>

        <aside class="dashboard-date-panel">
            <div class="dashboard-date-heading">
                <span class="dashboard-date-icon"><i data-lucide="calendar" class="h-4 w-4"></i></span>
                <p>Hari ini</p>
                <span class="dashboard-date-live"><span></span> Agenda harian</span>
            </div>
            <div class="dashboard-date-display">
                <p class="dashboard-date-day">{{ now()->translatedFormat('d') }}</p>
                <div class="dashboard-date-month">
                    <p>{{ now()->translatedFormat('l') }}</p>
                    <p>{{ now()->translatedFormat('F Y') }}</p>
                </div>
            </div>
            <div class="dashboard-date-message">
                <span><i data-lucide="sparkles" class="h-4 w-4"></i></span>
                <p>Mulai dari satu hal yang paling penting untuk hari ini.</p>
            </div>
            <a href="{{ auth()->check() ? route('tasks.index') : route('login') }}" class="dashboard-date-link">
                <span>Lihat pekerjaan</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            </a>
        </aside>
    </section>

    <!-- Metrics Cards Grid -->
    <section class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 xl:grid-cols-5" aria-label="Statistik Produktivitas">
        @foreach([
            ['Total Tugas', $taskCounts->sum(), 'list-checks', 'text-slate-800', 'bg-slate-100 text-slate-700 border-slate-200'],
            ['Pending', $taskCounts->get('pending', 0), 'circle-dashed', 'text-slate-700', 'bg-slate-100 text-slate-700 border-slate-200'],
            ['Dikerjakan', $taskCounts->get('in_progress', 0), 'loader-circle', 'text-amber-700', 'bg-amber-50 text-amber-700 border-amber-200'],
            ['Tuntas', $taskCounts->get('completed', 0), 'circle-check', 'text-emerald-700', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['Target Aktif', $activeGoalCount, 'target', 'text-violet-700', 'bg-violet-50 text-violet-700 border-violet-200'],
        ] as [$label, $value, $icon, $color, $badgeTone])
            <div class="dashboard-metric flex min-h-[112px] flex-col rounded-2xl border border-slate-200/80 bg-white p-4 transition sm:p-5">
                <div class="flex min-h-8 items-center justify-between gap-3">
                    <p class="min-w-0 text-xs font-bold uppercase leading-5 tracking-wide text-slate-500">{{ $label }}</p>
                    <span class="grid h-8 w-8 place-items-center rounded-xl border {{ $badgeTone }}">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold leading-none tracking-tight tabular-nums {{ $color }}">{{ number_format((int) $value) }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[1.15fr_.85fr]">
        <!-- Upcoming Tasks -->
        <section class="dashboard-panel dashboard-panel--upcoming rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
            <div class="dashboard-panel-heading">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon dashboard-panel-icon--accent"><i data-lucide="calendar" class="h-5 w-5"></i></span>
                    <div>
                    <h2 class="font-bold text-slate-900">Tugas Mendatang</h2>
                    <p class="text-xs text-slate-500">Deadline dalam 7 hari</p>
                    </div>
                </div>
                @auth
                    <a href="{{ route('tasks.index') }}" class="dashboard-panel-link"><span>Semua tugas</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
                @endauth
            </div>
            <div class="dashboard-task-list">
                @forelse($upcomingTasks as $task)
                    <div class="dashboard-task-row">
                        <span class="dashboard-task-marker"><i data-lucide="{{ $task->status === 'in_progress' ? 'loader-circle' : 'circle' }}" class="h-4 w-4"></i></span>
                        <div class="dashboard-task-copy">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $task->title }}</p>
                            <p class="dashboard-task-meta">{{ $task->category?->name ?? 'Tanpa kategori' }} <span>·</span> {{ $task->status === 'in_progress' ? 'Dikerjakan' : 'Pending' }}</p>
                        </div>
                        <time class="dashboard-task-date" datetime="{{ $task->due_date->toDateString() }}"><span>{{ $task->due_date->format('d') }}</span>{{ $task->due_date->translatedFormat('M') }}</time>
                    </div>
                @empty
                    <div class="dashboard-empty-state">
                        <span class="dashboard-empty-icon"><i data-lucide="circle-check" class="h-5 w-5"></i></span>
                        <p>Tidak ada tugas mendatang.</p>
                        <span>Jadwalmu dalam 7 hari ke depan masih lega.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Overdue Tasks -->
        <section class="dashboard-panel dashboard-panel--overdue rounded-2xl border border-rose-200/80 bg-rose-50/35 p-5 sm:p-6">
            <div class="dashboard-panel-heading dashboard-panel-heading--overdue">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon dashboard-panel-icon--danger"><i data-lucide="alert-circle" class="h-5 w-5"></i></span>
                    <div>
                    <h2 class="font-bold text-slate-900">Deadline Terlewat</h2>
                    <p class="text-xs text-slate-500">Tugas belum tuntas sebelum hari ini</p>
                    </div>
                </div>
                @auth
                    <a href="{{ route('tasks.index') }}" class="dashboard-panel-link dashboard-panel-link--danger"><span>Periksa</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
                @endauth
            </div>
            <div class="dashboard-task-list dashboard-task-list--overdue">
                @forelse($overdueTasks as $task)
                    <div class="dashboard-task-row">
                        <span class="dashboard-task-marker dashboard-task-marker--danger"><i data-lucide="alert-circle" class="h-4 w-4"></i></span>
                        <div class="dashboard-task-copy">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $task->title }}</p>
                            <p class="dashboard-task-meta dashboard-task-meta--danger">{{ $task->category?->name ?? 'Tanpa kategori' }} <span>·</span> {{ $task->due_date->format('d M Y') }}</p>
                        </div>
                        <span class="dashboard-overdue-badge">Terlambat</span>
                    </div>
                @empty
                    <div class="dashboard-empty-state dashboard-empty-state--clear">
                        <span class="dashboard-empty-icon dashboard-empty-icon--clear"><i data-lucide="circle-check" class="h-5 w-5"></i></span>
                        <p>Tidak ada tugas yang terlewat.</p>
                        <span>Semua deadline masih terkendali.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Active Goals -->
        <section class="dashboard-panel dashboard-panel--goals rounded-2xl border border-slate-200/80 bg-white p-5 sm:col-span-2 sm:p-6 xl:col-span-2">
            <div class="dashboard-panel-heading">
                <div class="dashboard-panel-title">
                    <span class="dashboard-panel-icon dashboard-panel-icon--goal"><i data-lucide="target" class="h-5 w-5"></i></span>
                    <div>
                    <h2 class="font-bold text-slate-900">Target Aktif</h2>
                    <p class="text-xs text-slate-500">Ringkasan progres target pribadi</p>
                    </div>
                </div>
                @auth
                    <a href="{{ route('goals.index') }}" class="dashboard-panel-link"><span>Semua target</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
                @endauth
            </div>
            <div class="dashboard-goals-grid">
                @forelse($activeGoals as $goal)
                    <div class="dashboard-goal-card">
                        <div class="dashboard-goal-heading">
                            <span class="dashboard-goal-mark"><i data-lucide="target" class="h-4 w-4"></i></span>
                            <p class="truncate text-sm font-bold text-slate-800">{{ $goal->name }}</p>
                            <span class="dashboard-goal-percent">{{ number_format($goal->percentage(), 1) }}%</span>
                        </div>
                        <div class="dashboard-goal-progress">
                            <div class="dashboard-goal-progress-fill" style="width: {{ $goal->percentage() }}%"></div>
                        </div>
                        <div class="dashboard-goal-values">
                            <span>{{ $goal->formattedValue($goal->current_value) }} <span class="dashboard-goal-separator">dari</span> {{ $goal->formattedValue($goal->target_value) }}</span>
                            <span>{{ $goal->remainingLabel() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-empty-state dashboard-empty-state--goals">
                        <span class="dashboard-empty-icon"><i data-lucide="target" class="h-5 w-5"></i></span>
                        <p>Belum ada target aktif.</p>
                        <span>Buat target kecil dan pantau progresnya di sini.</span>
                        @auth
                            <a href="{{ route('goals.create') }}" class="dashboard-panel-link"><i data-lucide="plus" class="h-4 w-4"></i><span>Buat target</span></a>
                        @endauth
                    </div>
                @endforelse
            </div>
        </section>
    </div>

</div>
@endsection
