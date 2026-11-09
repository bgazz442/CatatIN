@extends('layouts.app')

@section('title', $goal->name)
@section('page-title', 'Detail Target')
@section('page-subtitle', 'Pantau progres dan riwayat pencapaiannya.')

@section('content')
<div data-goal-progress data-goal-type="{{ $goal->goal_type }}" data-goal-remaining-value="{{ number_format($goal->remaining(), 2, '.', '') }}" class="mx-auto max-w-4xl space-y-6">
    <!-- Hero Goal Summary Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs md:p-8">
        @if($goal->image_path)
            <div class="mb-6 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50">
                <img src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" class="max-h-96 w-full object-cover">
            </div>
        @endif
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $goal->name }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">
                    <span class="tabular-nums">{{ $goal->start_date->format('d M Y') }} – {{ $goal->deadline?->format('d M Y') ?? 'tanpa deadline' }}</span>
                    <span class="px-1.5 text-slate-300">·</span>
                    <span data-goal-status class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $goal->status === 'completed' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">{{ $goal->status === 'completed' ? 'Tuntas' : 'Aktif' }}</span>
                </p>
            </div>
            <div class="flex gap-2.5 shrink-0">
                <a href="{{ route('goals.edit', $goal) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Edit</a>
                <a href="{{ route('goals.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Daftar Target</a>
            </div>
        </div>
        @if($goal->description)
            <p class="mt-4 whitespace-pre-line text-sm text-slate-600 leading-relaxed">{{ $goal->description }}</p>
        @endif

        <div class="mt-6 border-t border-slate-100 pt-5">
            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                <div data-goal-progress-bar class="h-full rounded-full bg-[var(--theme-accent)] transition-all" style="width: {{ $goal->percentage() }}%"></div>
            </div>
            <div class="mt-3 flex flex-wrap justify-between gap-2 text-sm tabular-nums">
                <span data-goal-progress-label class="font-extrabold text-[var(--theme-accent)]">{{ number_format($goal->percentage(), 1) }}% tercapai</span>
                <span data-goal-progress-value class="font-bold text-slate-900">{{ $goal->formattedValue($goal->current_value) }} / {{ $goal->formattedValue($goal->target_value) }}</span>
            </div>
            <p data-goal-remaining class="mt-1 text-xs text-slate-500 tabular-nums">{{ $goal->remainingLabel() }}</p>
        </div>
    </div>

    <!-- Add Progress Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7">
        <h3 class="text-base font-bold text-slate-900">Perbarui Progres</h3>
        <p class="text-xs text-slate-500 mt-0.5">Masukkan jumlah, lalu pilih apakah ingin menambah atau mengurangi capaian.</p>
        <form method="POST" action="{{ route('goals.progress.store', $goal) }}" class="mt-4 grid gap-3.5 sm:grid-cols-[minmax(180px,1fr)_minmax(220px,2fr)_auto]" data-ajax-form data-ajax-goal-progress="true">
            @csrf
            <input type="hidden" name="direction" value="add">
            <div>
                <label for="amount" class="mb-1.5 block text-xs font-bold text-slate-700">Jumlah progres</label>
                <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-slate-50/70 focus-within:border-[var(--theme-accent)] focus-within:bg-white focus-within:ring-2 focus-within:ring-[var(--theme-accent)]/15">
                    <input id="amount" type="number" name="amount" inputmode="decimal" step="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" min="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" value="{{ old('amount') }}" required placeholder="{{ $goal->goal_type === 'money' ? '0' : '0,00' }}" class="min-w-0 flex-1 border-0 bg-transparent px-3.5 py-2.5 text-sm text-slate-900 outline-none">
                    <span class="flex items-center border-l border-slate-200 bg-slate-100 px-3.5 text-xs font-semibold text-slate-600">{{ $goal->goal_type === 'money' ? 'Rp' : $goal->unit }}</span>
                </div>
                <p data-goal-quick-remaining class="mt-1.5 text-xs text-slate-500 tabular-nums">Sisa target: {{ $goal->remainingLabel() }}</p>
                @error('direction')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                @error('amount')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                @error('progress')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="note" class="mb-1.5 block text-xs font-bold text-slate-700">Catatan</label>
                <input id="note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Catatan (opsional)" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @error('note')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <div class="grid w-full gap-2 sm:w-auto">
                    <button type="submit" data-progress-direction="add" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[var(--theme-accent)] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[var(--theme-accent-hover)]">
                        <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah</span>
                    </button>
                    <button type="submit" data-progress-direction="subtract" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50">
                        <i data-lucide="minus" class="h-4 w-4"></i><span>Kurangi</span>
                    </button>
                </div>
            </div>
        </form>
        @if($goal->remaining() > 0)
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3.5">
                <span class="text-xs font-semibold text-slate-500">Isi cepat:</span>
                @foreach([25 => '25%', 50 => '50%', 75 => '75%', 100 => '100%'] as $percent => $label)
                    <button type="button" data-progress-percent="{{ $percent }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-[var(--theme-accent)] hover:text-[var(--theme-accent)]">{{ $label }}</button>
                @endforeach
            </div>
        @endif
        <script>
            (() => {
                const amountInput = document.getElementById('amount');
                const goalPanel = document.querySelector('[data-goal-progress]');
                document.querySelectorAll('[data-progress-percent]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const percent = Number(button.dataset.progressPercent);
                        const remaining = Number(goalPanel?.dataset.goalRemainingValue || 0);
                        const amount = remaining * percent / 100;
                        amountInput.value = goalPanel?.dataset.goalType === 'money'
                            ? String(Math.round(amount))
                            : amount.toFixed(2);
                        amountInput.focus();
                    });
                });
            })();
        </script>
    </div>

    <!-- Progress History Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7">
        <h3 class="text-base font-bold text-slate-900">Riwayat Progres</h3>
        <p class="text-xs text-slate-500 mt-0.5">Catatan perkembangan waktu demi waktu</p>
        <div data-goal-progress-entries class="mt-4 divide-y divide-slate-100">
            @forelse($progressEntries as $entry)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3.5 transition hover:bg-slate-50/70 px-2 -mx-2 rounded-xl">
                    <div>
                        <p class="font-bold text-slate-900 tabular-nums text-sm">{{ $goal->formattedProgressEntry($entry->amount) }}</p>
                        @if($entry->note)<p class="text-xs text-slate-500 mt-0.5">{{ $entry->note }}</p>@endif
                    </div>
                    <time class="text-xs font-medium text-slate-400 tabular-nums">{{ $entry->recorded_at->format('d M Y H:i') }}</time>
                </div>
            @empty
                <p data-goal-progress-empty class="py-8 text-center text-sm text-slate-500">Belum ada progres yang dicatat.</p>
            @endforelse
        </div>
        {{ $progressEntries->links() }}
    </div>
</div>
@endsection
