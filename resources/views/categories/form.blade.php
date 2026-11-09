@extends('layouts.app')

@php($editing = $category->exists)
@section('title', $editing ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-title', $editing ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-subtitle', 'Kategori tersedia untuk catatan dan media pembelajaran.')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}" class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7" data-ajax-form>
        @csrf
        @if($editing) @method('PUT') @endif
        <div>
            <label for="category-name" class="mb-2 block text-sm font-bold text-slate-800">Nama kategori</label>
            <input id="category-name" name="name" value="{{ old('name', $category->name) }}" maxlength="100" required placeholder="Misal: Pemrograman Web, Desain UI, Bisnis..." class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
            @error('name')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('categories.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Batal</a>
            <button type="submit" class="rounded-xl bg-[var(--theme-accent)] px-6 py-2.5 text-sm font-bold text-white shadow-sm shadow-[var(--theme-accent)]/20 transition hover:bg-[var(--theme-accent-hover)] hover:shadow-md">{{ $editing ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
        </div>
    </form>
</div>
@endsection