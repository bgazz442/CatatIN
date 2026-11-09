@extends('layouts.app')

@section('title', 'Tambah Catatan Baru')
@section('page-title', 'Tambah Catatan Belajar')
@section('page-subtitle', 'Simpan materi, rumus, atau catatan praktikum baru.')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
        <a href="{{ route('dashboard') }}" class="hover:text-slate-800">Dashboard</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <a href="{{ route('catatan.index') }}" class="hover:text-slate-800">Semua Catatan</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[var(--theme-accent)] font-semibold">Tambah Baru</span>
    </nav>

    <!-- Card Form -->
    <div class="note-form-panel bg-white rounded-2xl border border-slate-200/80 p-6 md:p-8">
        <div class="pb-5 mb-6 border-b border-slate-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[var(--theme-accent)]/10 text-[var(--theme-accent)] flex items-center justify-center">
                <i data-lucide="file-plus-2" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900">Form Catatan Materi Pelajaran</h2>
                <p class="text-xs text-slate-500">Lengkapi data di bawah ini untuk menyimpan catatan belajar ke database.</p>
            </div>
        </div>

        <form action="{{ route('catatan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-ajax-form>
            @csrf

            <!-- Input Judul -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="judul" class="block text-sm font-semibold text-slate-800">
                        Judul Catatan <span class="text-rose-500">*</span>
                    </label>
                    <span id="judul-counter" class="text-xs text-slate-400">0 / 150</span>
                </div>
                <input type="text" 
                       name="judul" 
                       id="judul" 
                       maxlength="150"
                       value="{{ old('judul') }}"
                       placeholder="Contoh: Konfigurasi Virtual Host Apache & Nginx di Debian" 
                       class="w-full px-4 py-2.5 bg-slate-50 border @error('judul') border-rose-300 ring-2 ring-rose-100 @else border-slate-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)] focus:border-transparent transition"
                       required>
                @error('judul')
                    <p class="text-xs text-rose-600 mt-1.5 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Input Kategori -->
            <div>
                <label for="kategori" class="block text-sm font-semibold text-slate-800 mb-1.5">
                    Kategori Pelajaran <span class="text-rose-500">*</span>
                </label>
                <select name="kategori" 
                        id="kategori" 
                        class="w-full px-4 py-2.5 bg-slate-50 border @error('kategori') border-rose-300 ring-2 ring-rose-100 @else border-slate-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)] focus:border-transparent transition text-slate-700"
                        required>
                    <option value="" disabled {{ old('kategori') ? '' : 'selected' }}>-- Pilih Kategori Mata Pelajaran --</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ old('kategori') == $kat ? 'selected' : '' }}>
                            {{ $kat }}
                        </option>
                    @endforeach
                </select>
                @error('kategori')
                    <p class="text-xs text-rose-600 mt-1.5 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            @include('components.document-upload', ['catatan' => $catatan, 'returnUrl' => route('catatan.create', [], false)])

            <!-- Input Isi Materi -->
            <div>
                <label for="isi" class="block text-sm font-semibold text-slate-800 mb-1.5">
                    Isi Catatan Materi <span class="text-slate-400">(opsional jika ada lampiran)</span>
                </label>
                <textarea name="isi" 
                          id="isi" 
                          rows="10" 
                          placeholder="Tuliskan ringkasan materi, baris kode, rumus, atau poin-poin penjelasan penting di sini..."
                          class="w-full px-4 py-3 bg-slate-50 border @error('isi') border-rose-300 ring-2 ring-rose-100 @else border-slate-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)] focus:border-transparent transition leading-relaxed"
                          >{{ old('isi') }}</textarea>
                @error('isi')
                    <p class="text-xs text-rose-600 mt-1.5 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Checkbox Favorit -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" 
                           name="favorit" 
                           value="1" 
                           {{ old('favorit') ? 'checked' : '' }}
                           class="w-4 h-4 text-[var(--theme-accent)] bg-white border-slate-300 rounded focus:ring-[var(--theme-accent)] focus:ring-2">
                    <div>
                        <span class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <i data-lucide="star" class="w-4 h-4 text-amber-500 fill-amber-400"></i>
                            <span>Tandai sebagai Catatan Favorit</span>
                        </span>
                        <p class="text-xs text-slate-500 mt-0.5">Catatan favorit akan muncul di menu cepat dan mudah diakses kembali.</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('catatan.index') }}" class="px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[var(--theme-accent)] text-white text-sm font-semibold rounded-xl hover:bg-[var(--theme-accent-hover)] transition-all shadow-sm shadow-[var(--theme-accent)]/20">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Catatan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Live Character Counter for Judul
    const judulInput = document.getElementById('judul');
    const counter = document.getElementById('judul-counter');
    if (judulInput && counter) {
        const updateCounter = () => {
            counter.textContent = `${judulInput.value.length} / 150`;
        };
        judulInput.addEventListener('input', updateCounter);
        updateCounter();
    }
</script>
@endsection
