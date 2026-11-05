@extends('layouts.app')

@section('title', $goal->exists ? 'Edit Target' : 'Buat Target')
@section('page-title', $goal->exists ? 'Edit Target' : 'Buat Target')
@section('page-subtitle', 'Tentukan hasil yang ingin dicapai dan satuannya.')

@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft md:p-7">
    <form method="POST" enctype="multipart/form-data" action="{{ $goal->exists ? route('goals.update', $goal) : route('goals.store') }}" class="space-y-5" data-ajax-form data-ajax-goal-form="true">
        @csrf @if($goal->exists) @method('PUT') @endif
        <div>
            <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama target</label>
            <input id="name" name="name" value="{{ old('name', $goal->name) }}" required maxlength="100" pattern=" *[A-Za-z0-9]+(?: +[A-Za-z0-9]+)* *" oninput="const v=this.value.trim(); const valid=/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/.test(v) && /[A-Za-z]/.test(v); this.setCustomValidity(valid ? '' : 'Nama target harus memuat huruf dan hanya boleh berisi huruf, angka, serta spasi antar kata.')" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm" aria-describedby="goal-name-help">
            <p id="goal-name-help" class="mt-1 text-xs text-slate-500">Gunakan huruf atau angka, maksimal 100 karakter. Spasi hanya di antara kata.</p>
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
            <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('description', $goal->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="image" class="mb-1.5 block text-sm font-semibold text-slate-700">Gambar motivasi (opsional)</label>
            @if($goal->image_path)
                <img data-goal-form-image src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" class="mb-3 max-h-56 w-full rounded-xl object-cover">
                <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                    Hapus gambar saat ini
                </label>
            @endif
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
            <p class="mt-1 text-xs text-slate-500">Format JPG, PNG, atau WebP. Maksimal 5 MB dan 4096 × 4096 piksel. Gambar hanya dapat dilihat oleh pemilik target.</p>
            @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="target_value" class="mb-1.5 block text-sm font-semibold text-slate-700">Nilai target (harus lebih dari 0)</label>
                <input id="target_value" type="number" step="0.01" min="0.01" name="target_value" value="{{ old('target_value', $goal->target_value) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('target_value')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="unit" class="mb-1.5 block text-sm font-semibold text-slate-700">Satuan</label>
                <input id="unit" name="unit" value="{{ old('unit', $goal->unit) }}" placeholder="halaman, jam, Rupiah..." maxlength="40" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('unit')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal mulai</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $goal->start_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('start_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="deadline" class="mb-1.5 block text-sm font-semibold text-slate-700">Deadline (opsional)</label>
                <input id="deadline" type="date" name="deadline" value="{{ old('deadline', $goal->deadline?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('deadline')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Progres dicatat sebagai riwayat terpisah; nilai progres saat ini tidak dapat diubah langsung dari formulir.</p>
        @error('current_value')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ $goal->exists ? route('goals.show', $goal) : route('goals.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
            <button class="rounded-xl bg-[#2F6F64] px-5 py-2.5 text-sm font-semibold text-white">Simpan Target</button>
        </div>
    </form>
</div>
@endsection
