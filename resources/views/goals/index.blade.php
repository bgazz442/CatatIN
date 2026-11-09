@extends('layouts.app')

@section('title', 'Target Pribadi')
@section('page-title', 'Goal Tracker')
@section('page-subtitle', 'Tetapkan target dan pantau progres yang Anda bangun.')

@section('content')
<div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm font-medium text-slate-500 tabular-nums">
            <span class="font-extrabold text-slate-900">{{ $goals->total() }}</span> target terdaftar
        </p>
        <a href="{{ route('goals.create') }}" class="workspace-action-button">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Buat Target</span>
        </a>
    </div>

    <!-- Goals Grid -->
    <div class="grid gap-5 lg:grid-cols-2">
        @forelse($goals as $goal)
            <article data-goal-progress data-goal-type="{{ $goal->goal_type }}" data-goal-remaining-value="{{ number_format($goal->remaining(), 2, '.', '') }}" class="group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div>
                    @if($goal->image_path)
                        <a href="{{ route('goals.show', $goal) }}" class="mb-4 block overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                            <img src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" loading="lazy" class="h-44 w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                        </a>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('goals.show', $goal) }}" class="text-base font-bold text-slate-900 transition hover:text-[var(--theme-accent)]">{{ $goal->name }}</a>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1 rounded-full border border-[var(--theme-accent)]/15 bg-[var(--theme-accent)]/5 px-2 py-0.5 font-semibold text-[var(--theme-accent)]">
                                    <i data-lucide="{{ $goal->goal_type === 'money' ? 'wallet' : ($goal->goal_type === 'percentage' ? 'percent' : 'ruler') }}" class="h-3 w-3"></i>
                                    {{ $goal->goal_type === 'money' ? 'Uang' : ($goal->goal_type === 'percentage' ? 'Persentase' : 'Satuan khusus') }}
                                </span>
                                <span data-goal-status class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold {{ $goal->status === 'completed' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $goal->status === 'completed' ? 'Tuntas' : 'Aktif' }}
                                </span>
                                <span>·</span>
                                <span class="tabular-nums">{{ $goal->deadline?->format('d M Y') ?? 'Tanpa deadline' }}</span>
                            </div>
                        </div>
                        <span data-goal-progress-label class="rounded-xl border border-[var(--theme-accent)]/20 bg-[var(--theme-accent)]/10 px-3 py-1 text-xs font-extrabold tabular-nums text-[var(--theme-accent)]">{{ number_format($goal->percentage(), 1) }}% tercapai</span>
                    </div>
                    @if($goal->description)
                        <p class="mt-3 line-clamp-2 text-sm text-slate-600 leading-relaxed">{{ $goal->description }}</p>
                    @endif
                </div>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div data-goal-progress-bar class="h-full rounded-full bg-[var(--theme-accent)] transition-all" style="width: {{ $goal->percentage() }}%"></div>
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600 tabular-nums">
                        <span data-goal-progress-value class="font-bold text-slate-900">{{ $goal->formattedValue($goal->current_value) }} / {{ $goal->formattedValue($goal->target_value) }}</span>
                        <span data-goal-remaining class="text-right text-slate-500">{{ $goal->remainingLabel() }}</span>
                    </div>
                    <p data-goal-progress-count class="mt-2 text-[11px] font-medium text-slate-400">{{ $goal->progress_entries_count }} catatan progres</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <span class="text-xs font-medium text-slate-500">
                            Per klik:
                            <strong class="font-bold text-slate-700">{{ $goal->formattedValue($goal->progress_step) }}</strong>
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('goals.progress.store', $goal) }}" data-ajax-form data-ajax-goal-progress="true">
                                @csrf
                                <input type="hidden" name="use_progress_step" value="1">
                                <input type="hidden" name="direction" value="add">
                                <button type="submit" class="goal-quick-action goal-quick-action--add" aria-label="Tambah progres {{ $goal->formattedValue($goal->progress_step) }} untuk {{ $goal->name }}">
                                    <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('goals.progress.store', $goal) }}" data-ajax-form data-ajax-goal-progress="true">
                                @csrf
                                <input type="hidden" name="use_progress_step" value="1">
                                <input type="hidden" name="direction" value="subtract">
                                <button type="submit" class="goal-quick-action goal-quick-action--subtract" aria-label="Kurangi progres {{ $goal->formattedValue($goal->progress_step) }} untuk {{ $goal->name }}">
                                    <i data-lucide="minus" class="h-4 w-4"></i><span>Kurangi</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('goals.destroy', $goal) }}" onsubmit="return confirm('Hapus target ini beserta riwayat progresnya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="goal-quick-action goal-quick-action--delete" aria-label="Hapus target {{ $goal->name }}">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i><span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center lg:col-span-2">
                <i data-lucide="target" class="mx-auto h-10 w-10 text-slate-400"></i>
                <h2 class="mt-3 text-base font-bold text-slate-800">Belum ada target</h2>
                <p class="mt-1 text-sm text-slate-500">Buat target pribadi dan tambahkan progres secara bertahap.</p>
            </div>
        @endforelse
    </div>
    {{ $goals->links() }}
</div>
@endsection
