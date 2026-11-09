@extends('layouts.app')

@section('title', $task->exists ? 'Edit Tugas' : 'Tambah Tugas')
@section('page-title', $task->exists ? 'Edit Tugas' : 'Tambah Tugas')
@section('page-subtitle', 'Lengkapi detail dan deadline tugas Anda.')

@section('content')
<div class="workspace-form-card workspace-form-card--task">
    <div class="workspace-form-intro workspace-form-intro--task">
        <span class="workspace-form-intro-icon"><i data-lucide="{{ $task->exists ? 'edit-3' : 'list-checks' }}" class="h-5 w-5"></i></span>
        <div>
            <p class="workspace-form-kicker">{{ $task->exists ? 'PERBARUI PEKERJAAN' : 'RUANG KERJA' }}</p>
            <h2>{{ $task->exists ? 'Perbarui detail tugas' : 'Rencanakan tugas baru' }}</h2>
            <p>{{ $task->exists ? 'Sesuaikan detail agar langkah berikutnya tetap jelas.' : 'Tambahkan informasi penting agar tugas mudah dikelola dan diselesaikan.' }}</p>
        </div>
    </div>
    <form method="POST" action="{{ $task->exists ? route('tasks.update', $task) : route('tasks.store') }}" class="workspace-form-fields" data-ajax-form data-ajax-redirect="true">
        @csrf
        @if($task->exists) @method('PUT') @endif
        <div>
            <label for="title" class="mb-2 block text-sm font-bold text-slate-800">Judul tugas</label>
            <input id="title" name="title" value="{{ old('title', $task->title) }}" required maxlength="100" pattern=" *[A-Za-z0-9]+(?: +[A-Za-z0-9]+)* *" oninput="const v=this.value.trim(); const valid=/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/.test(v) && /[A-Za-z]/.test(v); this.setCustomValidity(valid ? '' : 'Judul harus memuat huruf dan hanya boleh berisi huruf, angka, serta spasi antar kata.')" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15" aria-describedby="title-help" placeholder="Misal: Menyusun laporan keuangan mingguan">
            <p id="title-help" class="mt-1.5 text-xs text-slate-500">Gunakan huruf atau angka, maksimal 100 karakter. Spasi hanya di antara kata.</p>
            @error('title')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-2 block text-sm font-bold text-slate-800">Deskripsi</label>
            <textarea id="description" name="description" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15" placeholder="Tambahkan catatan atau detail instruksi tugas ini...">{{ old('description', $task->description) }}</textarea>
            @error('description')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="mb-2 block text-sm font-bold text-slate-800">Kategori</label>
                <select id="category_id" name="category_id" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                    <option value="">Tanpa kategori</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $task->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="due_date" class="mb-2 block text-sm font-bold text-slate-800">Deadline</label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @error('due_date')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-bold text-slate-800">Status</label>
            <select id="status" name="status" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @foreach(['pending' => 'Pending', 'in_progress' => 'Dikerjakan', 'completed' => 'Tuntas'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $task->status ?? 'pending') === $value)>{{ $label }}</option>@endforeach
            </select>
            @error('status')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="workspace-form-actions">
            <a href="{{ route('tasks.index') }}" class="workspace-secondary-button">Batal</a>
            <button class="workspace-primary-button">Simpan Tugas</button>
        </div>
    </form>
</div>
@endsection
