@extends('layouts.app')

@section('title', 'Kategori Pembelajaran')
@section('page-title', 'Kategori Pembelajaran')
@section('page-subtitle', 'Kelola kategori bersama untuk catatan dan media belajar.')

@section('content')
<div class="max-w-4xl space-y-5" data-ajax-list="categories" data-ajax-empty-message="Belum ada kategori.">
    @if($errors->has('category'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first('category') }}</div>
    @endif
    <div class="flex items-center justify-between gap-3">
        <p data-ajax-count="{{ $categories->count() }}" data-ajax-count-label="kategori" class="text-sm text-slate-500">{{ $categories->count() }} kategori</p>
        <a href="{{ route('categories.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#2F6F64] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24574F]">
            <i data-lucide="plus" class="h-4 w-4"></i><span>Tambah Kategori</span>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
        @forelse($categories as $category)
            <div data-ajax-item class="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="truncate text-sm font-semibold text-slate-900">{{ $category->name }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $category->catatan_count }} catatan · {{ $category->tasks_count }} tugas · {{ $category->daily_activities_count }} aktivitas · {{ $category->media_favorites_count }} media</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('categories.edit', $category) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                    @if($category->catatan_count === 0 && $category->media_favorites_count === 0 && $category->tasks_count === 0 && $category->daily_activities_count === 0)
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')" data-ajax-form>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                        </form>
                    @else
                        <button type="button" disabled title="Kategori masih digunakan" class="cursor-not-allowed rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-400">Hapus</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <h2 class="text-sm font-semibold text-slate-800">Belum ada kategori</h2>
                <p class="mt-1 text-xs text-slate-500">Tambahkan kategori untuk mengelompokkan materi.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection