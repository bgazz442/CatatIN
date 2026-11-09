@extends('layouts.app')

@section('title', 'Kategori Pembelajaran')
@section('page-title', 'Kategori Pembelajaran')
@section('page-subtitle', 'Kelola kategori bersama untuk catatan dan media belajar.')

@section('content')
<div class="space-y-6" data-ajax-list="categories" data-ajax-empty-message="Belum ada kategori.">
    @if($errors->has('category'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 shadow-xs">{{ $errors->first('category') }}</div>
    @endif
    <section class="note-library-banner flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-[var(--theme-accent)]">Struktur koleksi</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Kategori untuk menjaga fokus.</h2>
            <p data-ajax-count="{{ $categories->count() }}" data-ajax-count-label="kategori" class="mt-2 text-sm text-slate-600 tabular-nums">
                {{ $categories->count() }} kategori mengatur catatan, tugas, dan media Anda.
            </p>
        </div>
        <a href="{{ route('categories.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[var(--theme-accent)] px-4.5 py-2.5 text-sm font-bold text-white shadow-sm shadow-[var(--theme-accent)]/20 transition hover:bg-[var(--theme-accent-hover)] hover:shadow-md">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Kategori</span>
        </a>
    </section>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse($categories as $category)
            <article data-ajax-item class="category-tile flex min-w-0 flex-col justify-between gap-5 p-5">
                <div class="min-w-0">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]">
                            <i data-lucide="folder-cog" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-bold text-slate-900">{{ $category->name }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $category->catatan_count }} catatan · {{ $category->tasks_count }} tugas · {{ $category->media_favorites_count }} media</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('categories.edit', $category) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">Edit</a>
                    @if($category->catatan_count === 0 && $category->media_favorites_count === 0 && $category->tasks_count === 0)
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')" data-ajax-form>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 hover:border-rose-300">Hapus</button>
                        </form>
                    @else
                        <button type="button" disabled title="Kategori masih digunakan" class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-400">Hapus</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white/70 p-12 text-center">
                <i data-lucide="folder-open" class="mx-auto h-10 w-10 text-slate-400"></i>
                <h2 class="mt-3 text-base font-bold text-slate-800">Belum ada kategori</h2>
                <p class="mt-1 text-sm text-slate-500">Tambahkan kategori untuk mengelompokkan materi.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection