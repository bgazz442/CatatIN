@extends('layouts.app')

@section('title', 'Detail Aktivitas')
@section('page-title', 'Detail Aktivitas')
@section('page-subtitle', 'Rincian kegiatan yang sudah dicatat.')

@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft md:p-7">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $activity->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $activity->activity_date->translatedFormat('l, d F Y') }}</p>
        </div>
        <span class="rounded-full bg-[#EDF4F1] px-3 py-1.5 text-xs font-semibold text-[#2F6F64]">
            {{ $activity->category?->name ?? 'Tanpa kategori' }}
        </span>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Waktu mulai</dt>
            <dd class="mt-2 text-sm font-semibold text-slate-800">{{ $activity->start_time ? substr($activity->start_time, 0, 5) : 'Tidak dicatat' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Waktu selesai</dt>
            <dd class="mt-2 text-sm font-semibold text-slate-800">{{ $activity->end_time ? substr($activity->end_time, 0, 5) : 'Tidak dicatat' }}</dd>
        </div>
    </dl>

    @if($activity->durationMinutes() !== null)
        @php($duration = $activity->durationMinutes())
        <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm font-semibold text-[#2F6F64]">
            Durasi: {{ intdiv($duration, 60) }} jam {{ $duration % 60 }} menit
        </div>
    @endif

    <div class="mt-5">
        <h2 class="text-sm font-semibold text-slate-700">Deskripsi atau catatan</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $activity->description ?: 'Tidak ada catatan.' }}</p>
    </div>

    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
        <a href="{{ route('activities.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali</a>
        <a href="{{ route('activities.edit', $activity) }}" class="rounded-xl bg-[#2F6F64] px-5 py-2.5 text-sm font-semibold text-white">Edit Aktivitas</a>
    </div>
</div>
@endsection
