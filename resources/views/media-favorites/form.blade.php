@extends('layouts.app')

@php($editing = $mediaFavorite->exists)
@section('title', $editing ? 'Edit Media' : 'Tambah Media')
@section('page-title', $editing ? 'Edit Media' : 'Tambah Media')
@section('page-subtitle', 'Simpan tautan video HTTPS untuk ditonton kembali.')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $editing ? route('media-favorites.update', $mediaFavorite) : route('media-favorites.store') }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-soft sm:p-6">
        @csrf
        @if($editing) @method('PUT') @endif
        <div>
            <label for="media-title" class="mb-1.5 block text-sm font-semibold text-slate-800">Judul video</label>
            <input id="media-title" name="title" value="{{ old('title', $mediaFavorite->title) }}" maxlength="180" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">
            @error('title')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="media-url" class="mb-1.5 block text-sm font-semibold text-slate-800">Link video</label>
            <input id="media-url" type="url" name="url" value="{{ old('url', $mediaFavorite->url) }}" placeholder="https://..." required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">
            <p class="mt-1.5 text-xs text-slate-500">Tautan HTTPS YouTube, TikTok, atau platform lain.</p>
            @error('url')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="media-category" class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori</label>
            <select id="media-category" name="category_id" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">
                <option value="">Pilih kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $mediaFavorite->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="media-description" class="mb-1.5 block text-sm font-semibold text-slate-800">Catatan singkat <span class="font-normal text-slate-500">(opsional)</span></label>
            <textarea id="media-description" name="description" rows="4" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">{{ old('description', $mediaFavorite->description) }}</textarea>
            @error('description')<p class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_favorite" value="1" @checked(old('is_favorite', $mediaFavorite->is_favorite)) class="h-4 w-4 rounded border-slate-300 text-[#2F6F64] focus:ring-[#2F6F64]">
            <span>Tandai sebagai favorit</span>
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <a href="{{ $editing ? route('media-favorites.show', $mediaFavorite) : route('media-favorites.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-lg bg-[#2F6F64] px-4 py-2 text-sm font-semibold text-white hover:bg-[#24574F]">{{ $editing ? 'Simpan Perubahan' : 'Simpan Media' }}</button>
        </div>
    </form>
</div>
@endsection