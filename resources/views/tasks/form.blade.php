@extends('layouts.app')

@section('title', $task->exists ? 'Edit Tugas' : 'Tambah Tugas')
@section('page-title', $task->exists ? 'Edit Tugas' : 'Tambah Tugas')
@section('page-subtitle', 'Lengkapi detail dan deadline tugas Anda.')

@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft md:p-7">
    <form method="POST" action="{{ $task->exists ? route('tasks.update', $task) : route('tasks.store') }}" class="space-y-5" data-ajax-form>
        @csrf
        @if($task->exists) @method('PUT') @endif
        <div>
            <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-700">Judul tugas</label>
            <input id="title" name="title" value="{{ old('title', $task->title) }}" required maxlength="100" pattern=" *[A-Za-z0-9]+(?: +[A-Za-z0-9]+)* *" oninput="const v=this.value.trim(); const valid=/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/.test(v) && /[A-Za-z]/.test(v); this.setCustomValidity(valid ? '' : 'Judul harus memuat huruf dan hanya boleh berisi huruf, angka, serta spasi antar kata.')" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm" aria-describedby="title-help">
            <p id="title-help" class="mt-1 text-xs text-slate-500">Gunakan huruf atau angka, maksimal 100 karakter. Spasi hanya di antara kata.</p>
            @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
            <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('description', $task->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori</label>
                <select id="category_id" name="category_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    <option value="">Tanpa kategori</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $task->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="due_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Deadline</label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @error('due_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Status</label>
            <select id="status" name="status" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $task->status ?? 'pending') === $value)>{{ $label }}</option>@endforeach
            </select>
            @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <a href="{{ route('tasks.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
            <button class="rounded-xl bg-[#2F6F64] px-5 py-2.5 text-sm font-semibold text-white">Simpan Tugas</button>
        </div>
    </form>
</div>
@endsection
