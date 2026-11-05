@php
    $currentAttachments = $catatan?->attachments ?? collect();
    $returnUrl = $returnUrl ?? request()->getRequestUri();
@endphp

<section class="p-4 sm:p-5 bg-slate-50/80 border border-slate-200 rounded-xl space-y-4" aria-labelledby="attachment-heading">
    <div>
        <h3 id="attachment-heading" class="text-sm font-bold text-slate-800">Lampiran Materi</h3>
        <p class="mt-1 text-xs text-slate-500">Maksimal 10 file dan total ukuran 100 MB untuk setiap materi.</p>
    </div>

    @auth
        <div data-document-dropzone
             data-current-count="{{ $currentAttachments->count() }}"
             data-current-bytes="{{ $currentAttachments->sum('size') }}"
             data-max-files="{{ config('learning.uploads.max_files') }}"
             data-max-file-bytes="{{ config('learning.uploads.max_file_bytes') }}"
             data-max-bytes="{{ config('learning.uploads.max_total_bytes') }}"
             class="rounded-lg border border-dashed border-slate-300 bg-white p-4 sm:p-5">
            <input type="file" name="documents[]" multiple class="sr-only" data-document-input accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.jpg,.jpeg,.png,.webp">
            <button type="button" data-document-browse class="w-full text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2F6F64] rounded-md p-2">
                <i data-lucide="upload" class="w-6 h-6 mx-auto mb-2 text-[#2F6F64]"></i>
                <span class="block text-sm font-semibold text-slate-700">Pilih file atau seret ke area ini</span>
                <span class="block mt-1 text-xs text-slate-500">PDF, Office, TXT, CSV, JPG, PNG, WEBP</span>
            </button>
            <ul data-document-list class="mt-3 space-y-2"></ul>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500" aria-live="polite">
                <span data-document-count>{{ $currentAttachments->count() }} / {{ config('learning.uploads.max_files') }} file</span>
                <span data-document-size>{{ number_format($currentAttachments->sum('size') / 1048576, 1) }} / 100 MB digunakan</span>
            </div>
            <p data-document-error class="hidden mt-2 text-xs font-medium text-rose-700" role="alert"></p>
        </div>
        <p data-upload-progress class="hidden text-xs font-medium text-[#2F6F64]" role="status" aria-live="polite"></p>

        @if($currentAttachments->isNotEmpty())
            <div class="space-y-2">
                <p class="text-xs font-semibold text-slate-700">File tersimpan</p>
                @foreach($currentAttachments as $attachment)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white border border-slate-200 rounded-lg">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="w-10 h-10 shrink-0 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold uppercase">{{ $attachment->extension }}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $attachment->original_name }}</p>
                                <p class="text-xs text-slate-500">{{ number_format($attachment->size / 1048576, 2) }} MB</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if(in_array($attachment->mime_type, config('learning.uploads.preview_mime_types'), true))
                                <a href="{{ route('attachments.preview', $attachment) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-md hover:bg-slate-50">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i><span>Preview</span>
                                </a>
                            @endif
                            <a href="{{ route('attachments.download', $attachment) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-md hover:bg-slate-50">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i><span>Download</span>
                            </a>
                            <button type="button" data-delete-attachment data-action="{{ route('attachments.destroy', $attachment) }}" data-token="{{ csrf_token() }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-rose-700 border border-rose-200 rounded-md hover:bg-rose-50">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i><span>Hapus</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <button type="button" data-login-required data-return-to="{{ $returnUrl }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 border border-slate-300 bg-white text-sm font-semibold text-slate-700 rounded-lg hover:bg-slate-50">
            <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
            <span>Login untuk mengelola lampiran</span>
        </button>
        @if($currentAttachments->isNotEmpty())
            <p class="text-xs text-slate-500">Masuk untuk melihat dan mengelola lampiran pada materi ini.</p>
        @endif
    @endauth
</section>