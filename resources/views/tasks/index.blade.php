@extends('layouts.app')

@section('title', 'Tugas')
@section('page-title', 'Task Manager')
@section('page-subtitle', 'Atur pekerjaan, deadline, dan prioritas Anda.')

@section('content')
<div class="space-y-6" data-ajax-list="tasks" data-ajax-empty-message="Belum ada tugas pada daftar ini.">
    <!-- Header Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p data-ajax-count="{{ $tasks->total() }}" data-ajax-count-label="tugas ditemukan" class="text-sm font-medium text-slate-500 tabular-nums">
                <span class="font-extrabold text-slate-900">{{ $tasks->total() }}</span> tugas ditemukan
            </p>
        </div>
        <a href="{{ route('tasks.create') }}" class="workspace-action-button">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Tugas</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('tasks.index') }}" class="task-filter-panel">
        <div class="task-filter-heading">
            <div>
                <h2>Filter tugas</h2>
                <p>Temukan pekerjaan berdasarkan kata kunci, status, atau kategori.</p>
            </div>
            @if(collect($filters)->filter()->isNotEmpty())
                <a href="{{ route('tasks.index') }}" class="task-filter-reset">Atur ulang</a>
            @endif
        </div>
        <div class="task-filter-fields">
            <div class="task-filter-field task-search-field">
                <label for="task-search">Cari tugas</label>
                <div class="task-filter-control task-search-control">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    <input id="task-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau deskripsi tugas">
                </div>
            </div>
            <div class="task-filter-field">
                <label for="task-status">Status</label>
                <select id="task-status" name="status" class="task-filter-control">
                    <option value="">Semua status</option>
                    @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="task-filter-field">
                <label for="task-category">Kategori</label>
                <select id="task-category" name="category_id" class="task-filter-control">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="task-filter-field">
                <label for="task-sort">Urutkan</label>
                <select id="task-sort" name="sort" class="task-filter-control">
                    <option value="deadline" @selected(($filters['sort'] ?? 'deadline') === 'deadline')>Deadline terdekat</option>
                    <option value="created" @selected(($filters['sort'] ?? '') === 'created')>Terbaru dibuat</option>
                </select>
            </div>
            <button type="submit" class="task-filter-submit">
                <i data-lucide="filter" class="h-4 w-4"></i><span>Terapkan filter</span>
            </button>
        </div>
    </form>

    <!-- Task List -->
    <div class="space-y-3.5">
        @forelse($tasks as $task)
            @php($overdue = $task->due_date && $task->due_date->lt(today()) && $task->status !== 'completed')
            @php($dueSoon = $task->due_date && $task->due_date->between(today(), today()->addDays(3)) && $task->status !== 'completed')
            <article data-ajax-item data-task-item class="rounded-2xl border {{ $overdue ? 'border-rose-200/90 bg-rose-50/30' : 'border-slate-200/80 bg-white' }} p-5 shadow-xs transition hover:shadow-md">
                <div class="flex flex-col gap-3.5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-base font-bold text-slate-900">{{ $task->title }}</h2>
                            <span data-task-status class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['pending' => 'bg-slate-100 text-slate-700 border border-slate-200', 'in_progress' => 'bg-amber-50 text-amber-800 border border-amber-200', 'completed' => 'bg-emerald-50 text-emerald-800 border border-emerald-200'][$task->status] }}">{{ ['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'][$task->status] }}</span>
                            @if($overdue)
                                <span data-task-due-flag="overdue" class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-xs font-bold text-rose-700">Terlambat</span>
                            @elseif($dueSoon)
                                <span data-task-due-flag="due-soon" class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Deadline dekat</span>
                            @endif
                        </div>
                        @if($task->description)
                            <p class="mt-1.5 whitespace-pre-line text-sm text-slate-600 leading-relaxed">{{ $task->description }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                                <i data-lucide="tag" class="h-3 w-3 text-slate-400"></i>
                                {{ $task->category?->name ?? 'Tanpa kategori' }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700 tabular-nums">
                                <i data-lucide="calendar" class="h-3 w-3 text-slate-400"></i>
                                Deadline: {{ $task->due_date?->format('d M Y') ?? 'Belum ditentukan' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('tasks.edit', $task) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Edit</a>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini?')" data-ajax-form>
                            @csrf @method('DELETE')
                            <button class="rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 hover:border-rose-300">Hapus</button>
                        </form>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3.5">
                    <span class="mr-1 text-xs font-semibold text-slate-500">Ubah status:</span>
                    @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $status => $label)
                        <form method="POST" action="{{ route('tasks.status.update', $task) }}" data-ajax-form data-ajax-task-status="true">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit"
                                @disabled($task->status === $status)
                                class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition {{ $task->status === $status
                                    ? ['pending' => 'border-slate-300 bg-slate-100 text-slate-700 font-bold', 'in_progress' => 'border-amber-300 bg-amber-100 text-amber-800 font-bold', 'completed' => 'border-emerald-300 bg-emerald-100 text-emerald-800 font-bold'][$status]
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-[#2F6F64] hover:text-[#2F6F64]' }}">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <i data-lucide="list-checks" class="mx-auto h-10 w-10 text-slate-400"></i>
                <h2 class="mt-3 text-base font-bold text-slate-800">Belum ada tugas</h2>
                <p class="mt-1 text-sm text-slate-500">Tambahkan tugas untuk mulai mengatur pekerjaan Anda.</p>
            </div>
        @endforelse
    </div>
    {{ $tasks->links() }}
</div>
@endsection
