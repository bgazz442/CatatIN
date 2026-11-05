@extends('layouts.app')

@section('title', 'Daily Activities')
@section('page-title', 'Daily Activities')
@section('page-subtitle', 'Catat kegiatan yang sudah kamu lakukan dan pantau aktivitas harianmu.')

@section('content')
<div class="space-y-5" data-ajax-list="activities" data-ajax-empty-message="Belum ada aktivitas pada daftar ini.">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $selectedDate->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('activities.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Aktivitas</span>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Aktivitas</p>
            <p data-ajax-count="{{ $activities->count() }}" data-ajax-count-label="aktivitas" class="mt-2 text-2xl font-bold text-slate-900">{{ $activities->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Durasi</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ intdiv($totalMinutes, 60) }} jam {{ $totalMinutes % 60 }} menit</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori Digunakan</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $categoryCount }}</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,.42fr)]">
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft lg:p-5" aria-label="Kalender aktivitas">
            <div class="mb-4 flex items-center justify-between">
                <a href="{{ route('activities.index', ['month' => $monthStart->copy()->subMonth()->format('Y-m'), 'category_id' => $filters['category_id'] ?? null]) }}" class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50" aria-label="Bulan sebelumnya">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </a>
                <h2 class="font-bold text-slate-900">{{ $monthStart->translatedFormat('F Y') }}</h2>
                <a href="{{ route('activities.index', ['month' => $monthStart->copy()->addMonth()->format('Y-m'), 'category_id' => $filters['category_id'] ?? null]) }}" class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50" aria-label="Bulan berikutnya">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </a>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500">
                @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $weekday)
                    <div class="py-2">{{ $weekday }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                @foreach($calendarDays as $calendarDay)
                    @php
                        $dateKey = $calendarDay->toDateString();
                        $markerCount = $markerRows->get($dateKey, 0);
                        $isSelected = $dateKey === $selectedDate->toDateString();
                        $isCurrentMonth = $calendarDay->month === $monthStart->month;
                    @endphp
                    <a href="{{ route('activities.index', ['date' => $dateKey, 'month' => $monthStart->format('Y-m'), 'category_id' => $filters['category_id'] ?? null]) }}"
                       class="relative aspect-square rounded-lg border text-sm transition {{ $isSelected ? 'border-[#2F6F64] bg-[#2F6F64] font-bold text-white' : 'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50' }} {{ $isCurrentMonth ? '' : 'opacity-40' }}"
                       aria-label="{{ $calendarDay->translatedFormat('l, d F Y') }}{{ $markerCount ? ', '.$markerCount.' aktivitas' : '' }}">
                        <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">{{ $calendarDay->day }}</span>
                        @if($markerCount > 0 && ! $isSelected)
                            <span class="absolute bottom-1.5 left-1/2 h-1.5 w-1.5 -translate-x-1/2 rounded-full bg-[#2F6F64]"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
            <h2 class="font-bold text-slate-900">Filter Kategori</h2>
            <form method="GET" action="{{ route('activities.index') }}" class="mt-3">
                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                <input type="hidden" name="month" value="{{ $monthStart->format('Y-m') }}">
                <select name="category_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="mt-3 w-full rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white">Terapkan Filter</button>
            </form>
        </aside>
    </div>

    <section class="space-y-3" aria-label="Daftar aktivitas tanggal dipilih">
        <div>
            <h2 class="font-bold text-slate-900">Aktivitas pada tanggal ini</h2>
            <p class="mt-1 text-sm text-slate-500"><span data-ajax-count="{{ $activities->count() }}" data-ajax-count-label="aktivitas tercatat.">{{ $activities->count() }} aktivitas tercatat.</span></p>
        </div>

        @forelse($activities as $activity)
            <article data-ajax-item class="rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-900">{{ $activity->title }}</h3>
                            @if($activity->category)
                                <span class="rounded-full bg-[#EDF4F1] px-2.5 py-1 text-xs font-semibold text-[#2F6F64]">{{ $activity->category->name }}</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Tanpa kategori</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $activity->start_time ? substr($activity->start_time, 0, 5) : 'Waktu tidak dicatat' }}
                            {{ $activity->end_time ? '–'.substr($activity->end_time, 0, 5) : '' }}
                        </p>
                        @if($activity->description)
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $activity->description }}</p>
                        @endif
                        @if($activity->durationMinutes() !== null)
                            @php($duration = $activity->durationMinutes())
                            <p class="mt-2 text-xs font-semibold text-[#2F6F64]">{{ intdiv($duration, 60) }} jam {{ $duration % 60 }} menit</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('activities.show', $activity) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Lihat</a>
                        <a href="{{ route('activities.edit', $activity) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</a>
                        <form method="POST" action="{{ route('activities.destroy', $activity) }}" onsubmit="return confirm('Hapus aktivitas ini?')" data-ajax-form>
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <i data-lucide="clock-3" class="mx-auto h-8 w-8 text-slate-400"></i>
                <h2 class="mt-3 font-bold text-slate-800">Belum ada aktivitas pada tanggal ini</h2>
                <p class="mt-1 text-sm text-slate-500">Catat kegiatan yang sudah benar-benar kamu lakukan.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
