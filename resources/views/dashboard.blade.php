@extends('layouts.app')

@section('title', 'Dashboard Produktivitas')
@section('page-title', 'Dashboard Produktivitas')
@section('page-subtitle', 'Ringkasan tugas dan target pribadi Anda.')

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl dashboard-hero p-6 text-white shadow-lg shadow-[#2F6F64]/15 md:p-8">
        <div class="relative z-10 max-w-2xl">
            <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-md">
                <i data-lucide="sparkles" class="h-3.5 w-3.5 text-amber-300"></i><span>Ruang Produktivitas Pribadi</span>
            </div>
            <h2 class="mb-2 text-2xl font-extrabold tracking-tight md:text-3xl">Rencanakan, lakukan, capai.</h2>
            <p class="mb-6 text-sm leading-relaxed text-white/85 md:text-base">Kelola tugas, pantau target, dan gunakan asisten pribadi Anda.</p>
            <div class="flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-[#2F6F64] shadow-md hover:bg-slate-50"><i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Tugas</span></a>
                @else
                    <a href="{{ route('login', ['login_required' => 1, 'redirect_to' => '/tugas']) }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-[#2F6F64] shadow-md hover:bg-slate-50"><i data-lucide="log-in" class="h-4 w-4"></i><span>Masuk untuk mulai</span></a>
                @endauth
            </div>
        </div>
        <div class="pointer-events-none absolute right-12 top-6 hidden opacity-15 lg:block"><i data-lucide="layout-dashboard" class="h-48 w-48"></i></div>
    </section>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach([
            ['Total Tugas', $taskCounts->sum(), 'list-checks', 'text-slate-800'],
            ['Pending', $taskCounts->get('pending', 0), 'circle-dashed', 'text-slate-700'],
            ['Dikerjakan', $taskCounts->get('in_progress', 0), 'loader-circle', 'text-amber-700'],
            ['Tuntas', $taskCounts->get('completed', 0), 'circle-check', 'text-emerald-700'],
            ['Target Aktif', $activeGoalCount, 'target', 'text-violet-700'],
        ] as [$label, $value, $icon, $color])
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-soft">
                <div class="flex items-center justify-between gap-2"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $color }}"></i></div>
                <p class="mt-2 text-2xl font-extrabold {{ $color }}">{{ number_format((int) $value) }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3"><div><h2 class="font-bold text-slate-900">Tugas Mendatang</h2><p class="text-xs text-slate-500">Deadline dalam 7 hari</p></div>@auth<a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-[#2F6F64]">Semua tugas</a>@endauth</div>
            <div class="divide-y divide-slate-100">
                @forelse($upcomingTasks as $task)
                    <div class="flex items-center justify-between gap-3 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $task->title }}</p><p class="text-xs text-slate-500">{{ $task->category?->name ?? 'Tanpa kategori' }} · {{ $task->status === 'in_progress' ? 'Dikerjakan' : 'Pending' }}</p></div><time class="shrink-0 text-xs font-semibold text-slate-600">{{ $task->due_date->format('d M') }}</time></div>
                @empty<p class="py-6 text-sm text-slate-500">Tidak ada tugas mendatang.</p>@endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-soft">
            <div class="flex items-center justify-between border-b border-rose-100 pb-3"><div><h2 class="font-bold text-slate-900">Deadline Terlewat</h2><p class="text-xs text-slate-500">Tugas belum tuntas sebelum hari ini</p></div>@auth<a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-rose-700">Periksa</a>@endauth</div>
            <div class="divide-y divide-slate-100">
                @forelse($overdueTasks as $task)
                    <div class="flex items-center justify-between gap-3 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $task->title }}</p><p class="text-xs text-rose-700">{{ $task->category?->name ?? 'Tanpa kategori' }} · {{ $task->due_date->format('d M Y') }}</p></div><span class="rounded-full bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-700">Terlambat</span></div>
                @empty<p class="py-6 text-sm text-slate-500">Tidak ada tugas yang terlewat.</p>@endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3"><div><h2 class="font-bold text-slate-900">Target Aktif</h2><p class="text-xs text-slate-500">Ringkasan progres target pribadi</p></div>@auth<a href="{{ route('goals.index') }}" class="text-xs font-semibold text-[#2F6F64]">Semua target</a>@endauth</div>
            <div class="space-y-4 pt-3">
                @forelse($activeGoals as $goal)
                    <div><div class="flex items-center justify-between gap-3"><p class="truncate text-sm font-semibold text-slate-800">{{ $goal->name }}</p><span class="shrink-0 text-xs font-bold text-[#2F6F64]">{{ number_format($goal->percentage(), 1) }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-[#2F6F64]" style="width: {{ $goal->percentage() }}%"></div></div><p class="mt-1 text-xs text-slate-500">{{ number_format((float) $goal->current_value, 2) }} / {{ number_format((float) $goal->target_value, 2) }} {{ $goal->unit }} · Sisa {{ number_format($goal->remaining(), 2) }}</p></div>
                @empty<p class="py-3 text-sm text-slate-500">Belum ada target aktif.</p>@endforelse
            </div>
        </section>
    </div>

    @auth
        <section class="flex flex-col gap-4 rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 to-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700"><i data-lucide="sparkles" class="h-5 w-5"></i></span>
                <div><h2 class="font-bold text-slate-900">CatatIn Assistant</h2><p class="mt-1 text-sm text-slate-600">Tanyakan prioritas tugas, target, atau materi yang pernah Anda catat.</p></div>
            </div>
            <a href="{{ route('ai.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700"><span>Buka Assistant</span><i data-lucide="arrow-up-right" class="h-4 w-4"></i></a>
        </section>
    @endauth
</div>
@endsection
