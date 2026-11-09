@extends('layouts.app')

@php($editing = $mediaFavorite->exists)
@section('title', $editing ? 'Edit Media' : 'Tambah Media')
@section('page-title', $editing ? 'Edit Media' : 'Tambah Media')
@section('page-subtitle', 'Simpan tautan video HTTPS untuk ditonton kembali.')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $editing ? route('media-favorites.update', $mediaFavorite) : route('media-favorites.store') }}" class="space-y-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-8">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="border-b border-slate-100 pb-4 mb-2 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-[#2F6F64]">
                <i data-lucide="clapperboard" class="h-5 w-5"></i>
            </div>
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ $editing ? 'Perbarui Data Media' : 'Informasi Video Pembelajaran' }}</h2>
                <p class="text-xs text-slate-500">Tautan video akan otomatis dibuatkan pemutar jika platform didukung.</p>
            </div>
        </div>

        <div>
            <label for="media-title" class="mb-2 block text-sm font-bold text-slate-800">Judul video <span class="text-rose-500">*</span></label>
            <input id="media-title" name="title" value="{{ old('title', $mediaFavorite->title) }}" maxlength="180" required placeholder="Contoh: Tutorial Algoritma dan Struktur Data" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
            @error('title')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="media-url" class="mb-2 block text-sm font-bold text-slate-800">Link video HTTPS <span class="text-rose-500">*</span></label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                </span>
                <input id="media-url" type="url" name="url" value="{{ old('url', $mediaFavorite->url) }}" placeholder="https://www.youtube.com/watch?v=..." required class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
            </div>
            <p class="mt-1.5 text-xs text-slate-500">Mendukung tautan HTTPS dari YouTube, TikTok, atau platform web lainnya.</p>
            @error('url')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="media-category" class="mb-2 block text-sm font-bold text-slate-800">Kategori <span class="text-rose-500">*</span></label>
            <select id="media-category" name="category_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-800 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
                <option value="">Pilih kategori pembelajaran</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $mediaFavorite->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="media-description" class="mb-2 block text-sm font-bold text-slate-800">Catatan singkat <span class="font-normal text-slate-400 text-xs">(opsional)</span></label>
            <textarea id="media-description" name="description" rows="3" maxlength="5000" placeholder="Tuliskan poin penting dari video ini..." class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">{{ old('description', $mediaFavorite->description) }}</textarea>
            @error('description')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-3.5 text-sm text-slate-700 cursor-pointer hover:bg-slate-50 transition">
            <input type="checkbox" name="is_favorite" value="1" @checked(old('is_favorite', $mediaFavorite->is_favorite)) class="h-4.5 w-4.5 rounded border-slate-300 text-[#2F6F64] focus:ring-[#2F6F64]">
            <span class="font-semibold text-slate-800">Tandai sebagai video favorit</span>
        </label>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ $editing ? route('media-favorites.show', $mediaFavorite) : route('media-favorites.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Batal</a>
            <button type="submit" class="rounded-xl bg-[#2F6F64] px-6 py-2.5 text-sm font-bold text-white shadow-sm shadow-[#2F6F64]/20 transition hover:bg-[#24574F] hover:shadow-md">{{ $editing ? 'Simpan Perubahan' : 'Simpan Media' }}</button>
        </div>
    </form>
</div>
@endsection