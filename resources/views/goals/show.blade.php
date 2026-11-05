@extends('layouts.app')

@section('title', $goal->name)
@section('page-title', 'Detail Target')
@section('page-subtitle', 'Pantau progres dan riwayat pencapaiannya.')

@section('content')
<div data-goal-progress data-goal-remaining-value="{{ number_format($goal->remaining(), 2, '.', '') }}" class="mx-auto max-w-4xl space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft md:p-7">
        @if($goal->image_path)
            <img src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" class="mb-5 max-h-96 w-full rounded-xl object-cover">
        @endif
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h2 class="text-xl font-bold text-slate-900">{{ $goal->name }}</h2>            <p class="mt-1 text-sm text-slate-500">{{ $goal->start_date->format('d M Y') }} – {{ $goal->deadline?->format('d M Y') ?? 'tanpa deadline' }} · <span data-goal-status>{{ $goal->status === 'completed' ? 'Tuntas' : 'Aktif' }}</span></p></div>
            <div class="flex gap-2"><a href="{{ route('goals.edit', $goal) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</a><a href="{{ route('goals.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Daftar Target</a></div>
        </div>
        @if($goal->description)<p class="mt-4 whitespace-pre-line text-sm text-slate-600">{{ $goal->description }}</p>@endif
        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100"><div data-goal-progress-bar class="h-full rounded-full bg-[#2F6F64]" style="width: {{ $goal->percentage() }}%"></div></div>
        <div class="mt-2 flex flex-wrap justify-between gap-2 text-sm"><span data-goal-progress-label class="font-bold text-[#2F6F64]">{{ number_format($goal->percentage(), 1) }}% tercapai</span><span data-goal-progress-value>{{ number_format((float) $goal->current_value, 2) }} / {{ number_format((float) $goal->target_value, 2) }} {{ $goal->unit }}</span></div>
        <p data-goal-remaining class="mt-1 text-sm text-slate-500">Sisa {{ number_format($goal->remaining(), 2) }} {{ $goal->unit }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
        <h3 class="font-bold text-slate-900">Tambah Progres</h3>
        <form method="POST" action="{{ route('goals.progress.store', $goal) }}" class="mt-4 grid gap-3 sm:grid-cols-[minmax(180px,1fr)_minmax(220px,2fr)_auto]" data-ajax-form>
            @csrf
            <div>
                <label for="amount" class="mb-1.5 block text-xs font-semibold text-slate-600">Jumlah progres</label>
                <div class="flex overflow-hidden rounded-lg border border-slate-200 focus-within:border-[#2F6F64] focus-within:ring-2 focus-within:ring-[#2F6F64]/10">
                    <input id="amount" type="number" name="amount" inputmode="decimal" step="0.01" min="0.01" value="{{ old('amount') }}" required placeholder="0,00" class="min-w-0 flex-1 border-0 px-3 py-2.5 text-sm outline-none">
                    <span class="flex items-center border-l border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-500">{{ $goal->unit }}</span>
                </div>
                <p data-goal-quick-remaining class="mt-1.5 text-xs text-slate-400">Sisa target: {{ number_format($goal->remaining(), 2) }} {{ $goal->unit }}</p>
                @error('amount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div><label for="note" class="mb-1.5 block text-xs font-semibold text-slate-600">Catatan</label><input id="note" name="note" maxlength="500" value="{{ old('note') }}" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">@error('note')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <button class="rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white">Tambahkan</button>
        </form>
        @if($goal->remaining() > 0)
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-500">Isi cepat:</span>
                @foreach([25 => '25%', 50 => '50%', 75 => '75%', 100 => '100%'] as $percent => $label)
                    <button type="button" data-progress-percent="{{ $percent }}" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-[#2F6F64] hover:text-[#2F6F64]">{{ $label }}</button>
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
                        amountInput.value = (remaining * percent / 100).toFixed(2);
                        amountInput.focus();
                    });
                });
            })();
        </script>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
        <h3 class="font-bold text-slate-900">Riwayat Progres</h3>
        <div data-goal-progress-entries class="mt-4 divide-y divide-slate-100">
            @forelse($progressEntries as $entry)
                <div class="flex flex-wrap items-center justify-between gap-2 py-3"><div><p class="font-semibold text-slate-800">+{{ number_format((float) $entry->amount, 2) }} {{ $goal->unit }}</p>@if($entry->note)<p class="text-sm text-slate-500">{{ $entry->note }}</p>@endif</div><time class="text-xs text-slate-400">{{ $entry->recorded_at->format('d M Y H:i') }}</time></div>
            @empty
                <p data-goal-progress-empty class="py-6 text-sm text-slate-500">Belum ada progres yang dicatat.</p>
            @endforelse
        </div>
        {{ $progressEntries->links() }}
    </div>
</div>
@endsection
