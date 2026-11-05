@extends('layouts.app')

@section('title', $mediaFavorite->title)
@section('page-title', 'Media Pembelajaran')
@section('page-subtitle', 'Video tersimpan dalam koleksi Media Favorit.')

@section('content')
<div class="max-w-4xl space-y-5">
    <a href="{{ route('media-favorites.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-[#2F6F64]"><i data-lucide="arrow-left" class="h-4 w-4"></i><span>Kembali ke Media Favorit</span></a>
    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
        <div class="aspect-video bg-slate-100">
            @if($embedUrl)
                <iframe src="{{ $embedUrl }}" title="{{ $mediaFavorite->title }}" class="h-full w-full border-0" loading="lazy" sandbox="allow-scripts allow-same-origin allow-presentation" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            @else
                <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                    <i data-lucide="external-link" class="h-9 w-9 text-slate-400"></i>
                    <p class="text-sm text-slate-600">Pemutar tidak tersedia untuk platform ini.</p>
                    <a href="{{ $mediaFavorite->url }}" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-[#2F6F64] px-4 py-2 text-sm font-semibold text-white hover:bg-[#24574F]">Tonton di Situs Asli</a>
                </div>
            @endif
        </div>
        <div class="space-y-4 p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-slate-900">{{ $mediaFavorite->title }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($mediaFavorite->platform) }} · {{ $mediaFavorite->category->name }} · {{ $mediaFavorite->created_at->format('d M Y') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('media-favorites.toggle-favorite', $mediaFavorite) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-amber-700">{{ $mediaFavorite->is_favorite ? 'Hapus favorit' : 'Jadikan favorit' }}</button>
                    </form>
                    <a href="{{ route('media-favorites.edit', $mediaFavorite) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</a>
                </div>
            </div>
            @if($mediaFavorite->description)
                <p class="whitespace-pre-line border-t border-slate-100 pt-4 text-sm leading-relaxed text-slate-700">{{ $mediaFavorite->description }}</p>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <a href="{{ $mediaFavorite->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-[#2F6F64]"><i data-lucide="external-link" class="h-4 w-4"></i><span>Tonton di Situs Asli</span></a>
                <form method="POST" action="{{ route('media-favorites.destroy', $mediaFavorite) }}" onsubmit="return confirm('Hapus media ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"><i data-lucide="trash-2" class="h-4 w-4"></i><span>Hapus media</span></button>
                </form>
            </div>
        </div>
    </article>
</div>
@endsection