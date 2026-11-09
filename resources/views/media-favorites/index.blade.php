@extends('layouts.app')

@section('title', 'Media Favorit')
@section('page-title', 'Media Favorit')
@section('page-subtitle', 'Simpan dan temukan kembali video pembelajaran pilihan.')

@section('content')
<div class="space-y-6">
    <section class="media-library-banner">
        <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-white/65">Watchlist pribadi · {{ number_format($mediaItems->total()) }} video</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Belajar lewat tontonan pilihan.</h2>
            <p class="mt-2 max-w-xl text-sm leading-6 text-white/75">Simpan sumber video yang ingin Anda pelajari dan temukan kembali kapan saja.</p>
        </div>
        <a href="{{ route('media-favorites.create') }}" class="relative z-10 inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-lime-50">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah media</span>
        </a>
    </section>

    <!-- Filter Card -->
    <div class="library-toolbar p-4 sm:p-5">
        <form method="GET" action="{{ route('media-favorites.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                <div class="relative sm:col-span-2 lg:col-span-4">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </span>
                    <input name="search" value="{{ request('search') }}" placeholder="Cari judul media..." class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
                </div>
                <div class="lg:col-span-3">
                    <select name="platform" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-700 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
                        <option value="">Semua platform</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform }}" @selected(request('platform') === $platform)>{{ ucfirst($platform) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <select name="category_id" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-700 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="sort" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-700 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15 transition">
                        <option value="terbaru" @selected(request('sort') !== 'terlama')>Terbaru</option>
                        <option value="terlama" @selected(request('sort') === 'terlama')>Terlama</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                <div class="font-medium text-slate-500">
                    Menampilkan <span class="font-extrabold text-slate-800">{{ $mediaItems->total() }}</span> media tersimpan
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'platform', 'category_id', 'sort']))
                        <a href="{{ route('media-favorites.index') }}" class="rounded-xl px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#2F6F64] px-4 py-2 text-xs font-bold text-white shadow-xs shadow-[#2F6F64]/20 transition hover:bg-[#24574F]">
                        <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
        <h2 class="text-sm font-bold text-slate-900">Koleksi video</h2>
        <p class="text-xs font-medium text-slate-500">Terbaru ditambahkan</p>
    </div>

    <!-- Media Grid -->
    @if($mediaItems->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($mediaItems as $item)
                @php($thumbnail = $parser->thumbnailUrl($item->platform, $item->video_id))
                <article class="media-card group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white transition hover:border-[#2F6F64]/40">
                    <div>
                        <a href="{{ route('media-favorites.show', $item) }}" class="relative block aspect-video overflow-hidden bg-slate-900/5">
                            <div class="absolute inset-0 flex items-center justify-center text-slate-300" aria-hidden="true">
                                <i data-lucide="clapperboard" class="h-10 w-10"></i>
                            </div>
                            @if($thumbnail)
                                <img src="{{ $thumbnail }}" alt="Thumbnail {{ $item->title }}" loading="lazy" class="absolute inset-0 z-10 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" onerror="this.remove()">
                            @endif
                            <div class="absolute inset-0 z-20 flex items-center justify-center bg-slate-950/20 opacity-0 transition-opacity group-hover:opacity-100">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-[#2F6F64] shadow-lg">
                                    <i data-lucide="play-circle" class="h-6 w-6"></i>
                                </div>
                            </div>
                            <span class="absolute bottom-2.5 left-2.5 z-30 rounded-lg bg-slate-950/80 backdrop-blur-xs px-2.5 py-1 text-[11px] font-bold text-white uppercase tracking-wider">
                                {{ ucfirst($item->platform) }}
                            </span>
                        </a>
                        <div class="p-5">
                            <span class="inline-block rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 mb-2">
                                {{ $item->category->name }}
                            </span>
                            <h2 class="line-clamp-2 text-base font-bold text-slate-900 group-hover:text-[#2F6F64] transition-colors leading-snug">
                                <a href="{{ route('media-favorites.show', $item) }}">{{ $item->title }}</a>
                            </h2>
                            <p class="mt-2 text-xs text-slate-400 flex items-center gap-1.5">
                                <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                <span>{{ $item->created_at->format('d M Y') }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3.5 bg-slate-50/50">
                        <form method="POST" action="{{ route('media-favorites.toggle-favorite', $item) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold transition {{ $item->is_favorite ? 'text-amber-600' : 'text-slate-400 hover:text-amber-500' }}">
                                <i data-lucide="star" class="h-4 w-4 {{ $item->is_favorite ? 'fill-amber-400 text-amber-500' : '' }}"></i>
                                <span>{{ $item->is_favorite ? 'Favorit' : 'Jadikan favorit' }}</span>
                            </button>
                        </form>
                        <a href="{{ route('media-favorites.show', $item) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#2F6F64] hover:underline">
                            <span>Lihat media</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pt-2">{{ $mediaItems->links() }}</div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i data-lucide="clapperboard" class="h-7 w-7"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800">Belum ada media pembelajaran</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan tautan video YouTube atau TikTok yang ingin Anda simpan.</p>
            <div class="mt-5">
                <a href="{{ route('media-favorites.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2F6F64] px-4.5 py-2.5 text-sm font-bold text-white shadow-xs transition hover:bg-[#24574F]">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>Tambah Media Sekarang</span>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection