@extends('layouts.app')

@section('title', 'Media Favorit')
@section('page-title', 'Media Favorit')
@section('page-subtitle', 'Simpan dan temukan kembali video pembelajaran pilihan.')

@section('content')
<div class="space-y-5">
    <form method="GET" action="{{ route('media-favorites.index') }}" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-5">
        <input name="search" value="{{ request('search') }}" placeholder="Cari judul media..." class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-[#2F6F64] focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/20">
        <select name="platform" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
            <option value="">Semua platform</option>
            @foreach($platforms as $platform)
                <option value="{{ $platform }}" @selected(request('platform') === $platform)>{{ ucfirst($platform) }}</option>
            @endforeach
        </select>
        <select name="category_id" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
            <option value="">Semua kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
            <option value="terbaru" @selected(request('sort') !== 'terlama')>Terbaru</option>
            <option value="terlama" @selected(request('sort') === 'terlama')>Terlama</option>
        </select>
        <button type="submit" class="rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">Terapkan Filter</button>
    </form>

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $mediaItems->total() }} media</p>
        <a href="{{ route('media-favorites.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Media</span>
        </a>
    </div>

    @if($mediaItems->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($mediaItems as $item)
                @php($thumbnail = $parser->thumbnailUrl($item->platform, $item->video_id))
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
                    <a href="{{ route('media-favorites.show', $item) }}" class="relative block aspect-video bg-slate-100">
                        <div class="absolute inset-0 flex items-center justify-center text-slate-400" aria-hidden="true">
                            <i data-lucide="clapperboard" class="h-10 w-10"></i>
                        </div>
                        @if($thumbnail)
                            <img src="{{ $thumbnail }}" alt="Thumbnail {{ $item->title }}" loading="lazy" class="absolute inset-0 z-10 h-full w-full object-cover" onerror="this.remove()">
                        @endif
                        <span class="absolute bottom-2 left-2 rounded-md bg-slate-950/75 px-2 py-1 text-[11px] font-semibold text-white">{{ ucfirst($item->platform) }}</span>
                    </a>
                    <div class="space-y-3 p-4">
                        <div>
                            <h2 class="line-clamp-2 text-sm font-bold text-slate-900"><a href="{{ route('media-favorites.show', $item) }}">{{ $item->title }}</a></h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $item->category->name }} · {{ $item->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <form method="POST" action="{{ route('media-favorites.toggle-favorite', $item) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $item->is_favorite ? 'text-amber-700' : 'text-slate-500' }}">
                                    <i data-lucide="star" class="h-4 w-4"></i><span>{{ $item->is_favorite ? 'Favorit' : 'Jadikan favorit' }}</span>
                                </button>
                            </form>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('media-favorites.show', $item) }}" class="text-xs font-semibold text-[#2F6F64]">Lihat media</a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div>{{ $mediaItems->links() }}</div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center shadow-soft">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><i data-lucide="clapperboard" class="h-7 w-7"></i></div>
            <h2 class="text-sm font-bold text-slate-800">Belum ada media pembelajaran</h2>
            <p class="mt-1 text-xs text-slate-500">Tambahkan tautan video yang ingin Anda simpan.</p>
        </div>
    @endif
</div>
@endsection