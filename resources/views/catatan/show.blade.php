@extends('layouts.app')

@section('title', $catatan->judul)
@section('page-title', 'Detail Catatan Belajar')
@section('page-subtitle', 'Materi lengkap, tanggal pembuatan, dan aksi pengelolaan.')

@section('header-actions')
<a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>
    <span>Kembali</span>
</a>
@endsection

@section('content')
@php
    $kategori = $catatan->kategori;
    $badgeClasses = 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<div class="max-w-4xl mx-auto space-y-5">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-800">Dashboard</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <a href="{{ route('catatan.index') }}" class="hover:text-slate-800">Semua Catatan</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-slate-800 font-semibold truncate max-w-xs">{{ $catatan->judul }}</span>
    </nav>

    <!-- Main Content Card -->
    <article class="bg-white rounded-2xl border border-slate-200/80 p-6 md:p-8 shadow-soft">
        
        <!-- Header Info -->
        <div class="pb-6 border-b border-slate-100">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold border {{ $badgeClasses }}">
                    {{ $catatan->kategori }}
                </span>

                <!-- Favorit Toggle Button -->
                <form action="{{ route('catatan.toggle-favorit', $catatan->id) }}" method="POST" class="inline" data-ajax-form data-ajax-note-favorite="detail">
                    @csrf
                    @method('PATCH')
                    <button type="submit" data-note-favorite-button
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all {{ $catatan->favorit ? 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                        <i data-lucide="star" class="w-4 h-4 {{ $catatan->favorit ? 'fill-amber-400 text-amber-500' : 'text-slate-400' }}"></i>
                        <span>{{ $catatan->favorit ? 'Catatan Favorit' : 'Jadikan Favorit' }}</span>
                    </button>
                </form>
            </div>

            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 leading-tight">
                {{ $catatan->judul }}
            </h1>

            <!-- Timestamp Metadata -->
            <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-slate-500">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                    <span>Dibuat: <strong>{{ $catatan->created_at->format('d M Y, H:i') }}</strong></span>
                </div>
                @if($catatan->updated_at && $catatan->updated_at != $catatan->created_at)
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400"></i>
                        <span>Terakhir diperbarui: <strong>{{ $catatan->updated_at->format('d M Y, H:i') }}</strong></span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Note Body Content (Safely Escaped Text) -->
        @if(filled($catatan->isi))
        <div class="py-8 prose prose-slate max-w-none text-slate-800 text-sm md:text-base leading-relaxed whitespace-pre-line font-normal selection:bg-[#2F6F64]/20">
{!! nl2br(e($catatan->isi)) !!}
        </div>
        @endif

        @if($catatan->attachments()->exists())
            @include('components.document-upload', ['catatan' => $catatan, 'returnUrl' => route('catatan.show', $catatan)])
        @endif

        <!-- Bottom Action Bar -->
        <div class="pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Daftar</span>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('catatan.edit', $catatan->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    <span>Edit Catatan</span>
                </a>

                <button type="button" 
                        onclick="openDeleteModal('{{ route('catatan.destroy', $catatan->id) }}', '{{ addslashes($catatan->judul) }}')" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 text-sm font-semibold rounded-xl border border-rose-200/80 transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
    </article>

</div>
@endsection
