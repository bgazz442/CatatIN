@extends('layouts.app')

@section('title', 'Tugas')
@section('page-title', 'Task Manager')
@section('page-subtitle', 'Atur pekerjaan, deadline, dan prioritas Anda.')

@section('content')
<div class="space-y-5" data-ajax-list="tasks" data-ajax-empty-message="Belum ada tugas pada daftar ini.">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p data-ajax-count="{{ $tasks->total() }}" data-ajax-count-label="tugas ditemukan" class="text-sm text-slate-500">{{ $tasks->total() }} tugas ditemukan</p>
        <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Tugas</span>
        </a>
    </div>

    <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-4">
        <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari tugas..." class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
        <select name="status" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
            <option value="">Semua status</option>
            @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category_id" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
            <option value="">Semua kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="sort" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                <option value="deadline" @selected(($filters['sort'] ?? 'deadline') === 'deadline')>Deadline terdekat</option>
                <option value="created" @selected(($filters['sort'] ?? '') === 'created')>Terbaru dibuat</option>
            </select>
            <button class="rounded-lg bg-slate-800 px-4 text-sm font-semibold text-white">Filter</button>
        </div>
    </form>

    <div class="space-y-3">
        @forelse($tasks as $task)
            @php($overdue = $task->due_date && $task->due_date->lt(today()) && $task->status !== 'completed')
            @php($dueSoon = $task->due_date && $task->due_date->between(today(), today()->addDays(3)) && $task->status !== 'completed')
            <article data-ajax-item data-task-item class="rounded-xl border {{ $overdue ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-soft">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-slate-900">{{ $task->title }}</h2>
                            <span data-task-status class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['pending' => 'bg-slate-100 text-slate-700', 'in_progress' => 'bg-amber-100 text-amber-800', 'completed' => 'bg-emerald-100 text-emerald-800'][$task->status] }}">{{ ['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'][$task->status] }}</span>
                            @if($overdue)<span data-task-due-flag="overdue" class="text-xs font-bold text-rose-700">Terlambat</span>@elseif($dueSoon)<span data-task-due-flag="due-soon" class="text-xs font-semibold text-amber-700">Deadline dekat</span>@endif
                        </div>
                        @if($task->description)<p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $task->description }}</p>@endif
                        <p class="mt-2 text-xs text-slate-500">{{ $task->category?->name ?? 'Tanpa kategori' }} · Deadline: {{ $task->due_date?->format('d M Y') ?? 'Belum ditentukan' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</a>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini?')" data-ajax-form>
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">Hapus</button>
                        </form>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                    <span class="mr-1 text-xs font-semibold text-slate-500">Ubah status:</span>
                    @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $status => $label)
                        <form method="POST" action="{{ route('tasks.status.update', $task) }}" data-ajax-form data-ajax-task-status="true">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit"
                                @disabled($task->status === $status)
                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $task->status === $status
                                    ? ['pending' => 'border-slate-300 bg-slate-100 text-slate-700', 'in_progress' => 'border-amber-300 bg-amber-100 text-amber-800', 'completed' => 'border-emerald-300 bg-emerald-100 text-emerald-800'][$status]
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-[#2F6F64] hover:text-[#2F6F64]' }}">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <i data-lucide="list-checks" class="mx-auto h-8 w-8 text-slate-400"></i>
                <h2 class="mt-3 font-bold text-slate-800">Belum ada tugas</h2>
                <p class="mt-1 text-sm text-slate-500">Tambahkan tugas untuk mulai mengatur pekerjaan Anda.</p>
            </div>
        @endforelse
    </div>
    {{ $tasks->links() }}
</div>
@endsection
