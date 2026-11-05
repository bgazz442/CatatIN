@extends('layouts.app')

@section('title', 'Target Pribadi')
@section('page-title', 'Goal Tracker')
@section('page-subtitle', 'Tetapkan target dan pantau progres yang Anda bangun.')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $goals->total() }} target</p>
        <a href="{{ route('goals.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Buat Target</span>
        </a>
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($goals as $goal)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                @if($goal->image_path)
                    <a href="{{ route('goals.show', $goal) }}" class="mb-4 block overflow-hidden rounded-xl">
                        <img src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" loading="lazy" class="h-40 w-full object-cover">
                    </a>
                @endif
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('goals.show', $goal) }}" class="font-bold text-slate-900 hover:text-[#2F6F64]">{{ $goal->name }}</a>
                        <p class="mt-1 text-xs text-slate-500">{{ $goal->status === 'completed' ? 'Tuntas' : 'Aktif' }} · {{ $goal->deadline?->format('d M Y') ?? 'Tanpa deadline' }}</p>
                    </div>
                    <span class="rounded-full bg-[#2F6F64]/10 px-2.5 py-1 text-xs font-bold text-[#2F6F64]">{{ number_format($goal->percentage(), 1) }}%</span>
                </div>
                @if($goal->description)<p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $goal->description }}</p>@endif
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-[#2F6F64]" style="width: {{ $goal->percentage() }}%"></div></div>
                <p class="mt-2 text-sm text-slate-600">{{ number_format((float) $goal->current_value, 2) }} / {{ number_format((float) $goal->target_value, 2) }} {{ $goal->unit }} · Sisa {{ number_format($goal->remaining(), 2) }} {{ $goal->unit }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $goal->progress_entries_count }} catatan progres</p>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center lg:col-span-2">
                <i data-lucide="target" class="mx-auto h-8 w-8 text-slate-400"></i>
                <h2 class="mt-3 font-bold text-slate-800">Belum ada target</h2>
                <p class="mt-1 text-sm text-slate-500">Buat target pribadi dan tambahkan progres secara bertahap.</p>
            </div>
        @endforelse
    </div>
    {{ $goals->links() }}
</div>
@endsection
