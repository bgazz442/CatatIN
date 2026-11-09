@props(['catatan'])

@php
    $kategori = $catatan->kategori;
    $badgeClasses = match($kategori) {
        'Pemrograman' => 'bg-purple-50 text-purple-700 border-purple-200/70',
        'Basis Data' => 'bg-sky-50 text-sky-700 border-sky-200/70',
        'Jaringan Komputer' => 'bg-rose-50 text-rose-700 border-rose-200/70',
        'Matematika' => 'bg-blue-50 text-blue-700 border-blue-200/70',
        'Bahasa Indonesia' => 'bg-amber-50 text-amber-700 border-amber-200/70',
        'Bahasa Inggris' => 'bg-emerald-50 text-emerald-700 border-emerald-200/70',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

<article class="note-card group relative flex flex-col justify-between p-5 transition-all duration-200">
    <div>
        <!-- Top Row: Category Badge & Favorit Toggle -->
        <div class="flex items-center justify-between gap-2 mb-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $badgeClasses }}">
                {{ $catatan->kategori }}
            </span>

            <form action="{{ route('catatan.toggle-favorit', $catatan->id) }}" method="POST" class="inline" data-ajax-form data-ajax-note-favorite="card">
                @csrf
                @method('PATCH')
                <button type="submit" data-note-favorite-button
                        class="p-1.5 rounded-lg transition-colors {{ $catatan->favorit ? 'text-amber-500 bg-amber-50 hover:bg-amber-100' : 'text-slate-300 hover:text-amber-400 hover:bg-slate-50' }}"
                        title="{{ $catatan->favorit ? 'Hapus dari favorit' : 'Tandai sebagai favorit' }}"
                        aria-label="Toggle Favorit">
                    <i data-lucide="star" class="w-4 h-4 {{ $catatan->favorit ? 'fill-amber-400 text-amber-500' : '' }}"></i>
                </button>
            </form>
        </article>

        <!-- Note Title -->
        <h3 class="text-base font-bold text-slate-900 group-hover:text-[var(--theme-accent)] transition-colors line-clamp-2 mb-2 leading-snug">
            <a href="{{ route('catatan.show', $catatan->id) }}">
                {{ $catatan->judul }}
            </a>
        </h3>

        <!-- Note Excerpt -->
        <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed mb-4">
            {{ Str::limit($catatan->isi, 130) }}
        </p>
    </div>

    <!-- Bottom Meta & Actions -->
    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
        <div class="flex items-center gap-1.5 text-slate-500">
            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
            <span>{{ $catatan->updated_at ? $catatan->updated_at->format('d M Y') : $catatan->created_at->format('d M Y') }}</span>
        </div>

        <div class="flex items-center gap-1">
            <!-- View Detail -->
            <a href="{{ route('catatan.show', $catatan->id) }}"
               class="p-1.5 text-slate-500 hover:text-[var(--theme-accent)] hover:bg-slate-100 rounded-lg transition"
               title="Lihat Detail">
                <i data-lucide="eye" class="w-4 h-4"></i>
            </a>

            <!-- Edit -->
            <a href="{{ route('catatan.edit', $catatan->id) }}" 
               class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition" 
               title="Edit Catatan">
                <i data-lucide="edit-3" class="w-4 h-4"></i>
            </a>

            <!-- Delete Trigger -->
            <button type="button" 
                    onclick="openDeleteModal('{{ route('catatan.destroy', $catatan->id) }}', '{{ addslashes($catatan->judul) }}')" 
                    class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                    title="Hapus Catatan">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>
