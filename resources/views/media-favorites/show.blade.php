@extends('layouts.app')

@section('title', $mediaFavorite->title)
@section('page-title', 'Media Pembelajaran')
@section('page-subtitle', 'Video tersimpan dalam koleksi Media Favorit.')

@section('header-actions')
<a href="{{ route('media-favorites.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
    <i data-lucide="arrow-left" class="h-4 w-4"></i>
    <span>Kembali ke Media Favorit</span>
</a>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-800">Dashboard</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <a href="{{ route('media-favorites.index') }}" class="hover:text-slate-800">Media Favorit</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-slate-800 font-semibold truncate max-w-xs">{{ $mediaFavorite->title }}</span>
    </nav>

    <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="aspect-video bg-slate-950 overflow-hidden">
            @if($embedUrl)
                <iframe src="{{ $embedUrl }}" title="{{ $mediaFavorite->title }}" class="h-full w-full border-0" loading="lazy" sandbox="allow-scripts allow-same-origin allow-presentation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            @else
                <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center text-white">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-slate-300">
                        <i data-lucide="external-link" class="h-7 w-7"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-300">Pemutar video tidak tersedia langsung untuk platform ini.</p>
                    <a href="{{ $mediaFavorite->url }}" target="_blank" rel="noopener noreferrer" class="mt-2 rounded-xl bg-[#2F6F64] px-5 py-2.5 text-sm font-bold text-white shadow-xs hover:bg-[#24574F] transition">Tonton di Situs Asli</a>
                </div>
            @endif
        </div>

        <div class="space-y-5 p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 uppercase tracking-wider">
                            {{ ucfirst($mediaFavorite->platform) }}
                        </span>
                        <span class="rounded-lg bg-[#2F6F64]/10 px-2.5 py-1 text-xs font-bold text-[#2F6F64]">
                            {{ $mediaFavorite->category->name }}
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-tight">{{ $mediaFavorite->title }}</h2>
                    <p class="mt-2 text-xs text-slate-400 flex items-center gap-2">
                        <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                        <span>Ditambahkan pada {{ $mediaFavorite->created_at->format('d M Y, H:i') }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('media-favorites.toggle-favorite', $mediaFavorite) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition {{ $mediaFavorite->is_favorite ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="star" class="h-4 w-4 {{ $mediaFavorite->is_favorite ? 'fill-amber-400 text-amber-500' : 'text-slate-400' }}"></i>
                            <span>{{ $mediaFavorite->is_favorite ? 'Favorit' : 'Jadikan Favorit' }}</span>
                        </button>
                    </form>
                    <a href="{{ route('media-favorites.edit', $mediaFavorite) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">
                        <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                        <span>Edit</span>
                    </a>
                </div>
            </div>

            @if($mediaFavorite->description)
                <div class="space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan Ringkasan</h3>
                    <p class="whitespace-pre-line rounded-xl bg-slate-50/70 p-4 text-sm leading-relaxed text-slate-700 border border-slate-100">{{ $mediaFavorite->description }}</p>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <a href="{{ $mediaFavorite->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-bold text-[#2F6F64] hover:underline">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                    <span>Buka Tautan Asli</span>
                </a>
                <form method="POST" action="{{ route('media-favorites.destroy', $mediaFavorite) }}" onsubmit="return confirm('Hapus media ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-50 hover:border-rose-300">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        <span>Hapus Media</span>
                    </button>
                </form>
            </div>
        </div>
    </article>
</div>
@endsection