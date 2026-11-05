@extends('layouts.app')

@php($editing = $category->exists)
@section('title', $editing ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-title', $editing ? 'Edit Kategori' : 'Tambah Kategori')
@section('page-subtitle', 'Kategori tersedia untuk catatan dan media pembelajaran.')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-soft sm:p-6" data-ajax-form>
        @csrf
        @if($editing) @method('PUT') @endif
        <div>
            <label for="category-name" class="mb-1.5 block text-sm font-semibold text-slate-800">Nama kategori</label>
            <input id="category-name" name="name" value="{{ old('name', $category->name) }}" maxlength="100" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">
            @error('name')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ route('categories.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-lg bg-[#2F6F64] px-4 py-2 text-sm font-semibold text-white hover:bg-[#24574F]">{{ $editing ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
        </div>
    </form>
</div>
@endsection