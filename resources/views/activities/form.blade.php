@extends('layouts.app')

@section('title', $activity->exists ? 'Edit Aktivitas' : 'Catat Aktivitas')
@section('page-title', $activity->exists ? 'Edit Aktivitas' : 'Catat Aktivitas')
@section('page-subtitle', 'Simpan aktivitas dan waktu yang digunakan.')

@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft md:p-7">
    <form method="POST" action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}" class="space-y-5" data-ajax-form>
        @csrf @if($activity->exists) @method('PUT') @endif
        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-700">Judul aktivitas</label>
            <input id="title" name="title" value="{{ old('title', $activity->title) }}" required maxlength="100" pattern=" *[A-Za-z0-9]+(?: +[A-Za-z0-9]+)* *" oninput="const v=this.value.trim(); const valid=/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/.test(v) && /[A-Za-z]/.test(v); this.setCustomValidity(valid ? '' : 'Judul harus memuat huruf dan hanya boleh berisi huruf, angka, serta spasi antar kata.')" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm" aria-describedby="title-help">
            <p id="title-help" class="mt-1 text-xs text-slate-500">Gunakan huruf atau angka, maksimal 100 karakter. Spasi hanya di antara kata.</p>
            @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
            <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('description', $activity->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <div>
                <label for="activity_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal</label>
                <input id="activity_date" type="date" name="activity_date" value="{{ old('activity_date', $activity->activity_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('activity_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        @php
            $startValue = old('start_time', $activity->start_time ? substr($activity->start_time, 0, 5) : '');
            $endValue = old('end_time', $activity->end_time ? substr($activity->end_time, 0, 5) : '');
        @endphp
        <div class="grid gap-4 sm:grid-cols-2">
            <fieldset>
                <legend class="mb-1.5 text-sm font-semibold text-slate-700">Mulai</legend>
                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                    <select id="start_hour" aria-label="Jam mulai" class="rounded-xl border border-slate-200 px-3 py-3 text-sm">
                        <option value="">Tidak dicatat</option>
                        @for($hour = 0; $hour <= 23; $hour++)
                            <option value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                    <span class="font-bold text-slate-500">:</span>
                    <select id="start_minute" aria-label="Menit mulai" class="rounded-xl border border-slate-200 px-3 py-3 text-sm">
                        @for($minute = 0; $minute <= 59; $minute++)
                            <option value="{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <input id="start_time" type="hidden" name="start_time" value="{{ $startValue }}">
                @error('start_time')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </fieldset>
            <fieldset>
                <legend class="mb-1.5 text-sm font-semibold text-slate-700">Selesai</legend>
                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                    <select id="end_hour" aria-label="Jam selesai" class="rounded-xl border border-slate-200 px-3 py-3 text-sm">
                        <option value="">Tidak dicatat</option>
                        @for($hour = 0; $hour <= 23; $hour++)
                            <option value="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                    <span class="font-bold text-slate-500">:</span>
                    <select id="end_minute" aria-label="Menit selesai" class="rounded-xl border border-slate-200 px-3 py-3 text-sm">
                        @for($minute = 0; $minute <= 59; $minute++)
                            <option value="{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <input id="end_time" type="hidden" name="end_time" value="{{ $endValue }}">
                @error('end_time')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </fieldset>
        </div>
        <div>
            <label for="category_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori</label>
            <select id="category_id" name="category_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                <option value="">Pilih kategori</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $activity->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
            @error('category_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('activities.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
            <button class="rounded-xl bg-[#2F6F64] px-5 py-2.5 text-sm font-semibold text-white">Simpan Aktivitas</button>
        </div>
    </form>
</div>
<script>
    (() => {
        const bindTimeSelects = (prefix) => {
            const hour = document.getElementById(`${prefix}_hour`);
            const minute = document.getElementById(`${prefix}_minute`);
            const hidden = document.getElementById(prefix);
            if (!hour || !minute || !hidden) return;

            const current = /^([0-2]\d):([0-5]\d)/.exec(hidden.value);
            if (current) {
                hour.value = current[1];
                minute.value = current[2];
            }

            const sync = () => {
                hidden.value = hour.value === '' ? '' : `${hour.value}:${minute.value}`;
            };

            hour.addEventListener('change', sync);
            minute.addEventListener('change', sync);
        };

        bindTimeSelects('start_time');
        bindTimeSelects('end_time');
    })();
</script>
@endsection
