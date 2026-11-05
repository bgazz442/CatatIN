@extends('layouts.app')

@section('title', 'Catatan Favorit')
@section('page-title', 'Catatan Favorit')
@section('page-subtitle', 'Kumpulan catatan dan materi penting yang telah Anda tandai bintang.')

@section('content')
<div class="space-y-6">

    <!-- Filter & Search Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-soft">
        <form method="GET" action="{{ route('catatan.favorit') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <!-- Search Input -->
                <div class="md:col-span-6 relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari dalam catatan favorit..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#2F6F64] focus:border-transparent transition placeholder:text-slate-400">
                </div>

                <!-- Category Filter -->
                <div class="md:col-span-3">
                    <select name="kategori" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#2F6F64] focus:border-transparent transition text-slate-700">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}" {{ request('kategori') == $kat ? 'selected' : '' }}>
                                {{ $kat }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="md:col-span-3">
                    <select name="sort" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#2F6F64] focus:border-transparent transition text-slate-700">
                        <option value="terbaru" {{ request('sort') != 'terlama' ? 'selected' : '' }}>Terbaru</option>
                        <option value="terlama" {{ request('sort') == 'terlama' ? 'selected' : '' }}>Terlama</option>
                    </select>
                </div>
            </div>

            <!-- Action buttons: Filter & Reset -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <div class="text-slate-500 font-medium flex items-center gap-1.5">
                    <i data-lucide="star" class="w-3.5 h-3.5 text-amber-500 fill-amber-400"></i>
                    <span>Menampilkan <strong class="text-slate-800">{{ $catatan->total() }}</strong> materi favorit</span>
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'kategori', 'sort']))
                        <a href="{{ route('catatan.favorit') }}" class="px-3 py-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition font-medium">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#2F6F64] text-white rounded-xl font-semibold hover:bg-[#24574F] transition shadow-xs">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Notes Grid -->
    @if($catatan->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($catatan as $item)
                <x-card-catatan :catatan="$item" />
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pt-4">
            {{ $catatan->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-soft">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="star-off" class="w-8 h-8"></i>
            </div>
            <h4 class="text-base font-bold text-slate-800 mb-1">Belum Ada Catatan Favorit</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                @if(request()->hasAny(['search', 'kategori']))
                    Tidak ada catatan favorit yang sesuai dengan filter pencarian Anda.
                @else
                    Tandai materi penting dengan menekan ikon bintang pada catatan agar mudah ditemukan saat menjelang ujian atau praktikum.
                @endif
            </p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#2F6F64] text-white text-xs font-semibold rounded-xl hover:bg-[#24574F] transition shadow-xs">
                    <i data-lucide="notebook-tabs" class="w-4 h-4"></i>
                    <span>Jelajahi Semua Catatan</span>
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
