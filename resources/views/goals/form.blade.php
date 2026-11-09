@extends('layouts.app')

@section('title', $goal->exists ? 'Edit Target' : 'Buat Target')
@section('page-title', $goal->exists ? 'Edit Target' : 'Buat Target')
@section('page-subtitle', 'Tentukan hasil yang ingin dicapai dan satuannya.')

@section('content')
<div class="workspace-form-card workspace-form-card--goal">
    <div class="workspace-form-intro workspace-form-intro--goal">
        <span class="workspace-form-intro-icon"><i data-lucide="{{ $goal->exists ? 'target' : 'sparkles' }}" class="h-5 w-5"></i></span>
        <div>
            <p class="workspace-form-kicker">{{ $goal->exists ? 'PANTAU PERKEMBANGAN' : 'MULAI DARI SATU TUJUAN' }}</p>
            <h2>{{ $goal->exists ? 'Perbarui target pribadi' : 'Bangun target yang ingin dicapai' }}</h2>
            <p>{{ $goal->exists ? 'Atur kembali detail target tanpa kehilangan riwayat progres.' : 'Pilih cara mengukur progres, lalu susun target yang terasa realistis.' }}</p>
        </div>
    </div>
    <form id="goal-form" method="POST" enctype="multipart/form-data" action="{{ $goal->exists ? route('goals.update', $goal) : route('goals.store') }}" class="workspace-form-fields" data-ajax-form data-ajax-goal-form="true" data-ajax-redirect="true">
        @csrf @if($goal->exists) @method('PUT') @endif
        @unless($goal->exists)
            <fieldset class="space-y-3">
                <legend class="mb-2 block text-sm font-bold text-slate-800">Jenis target</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="goal-type-option">
                        <input type="radio" name="goal_type" value="money" required @checked(old('goal_type') === 'money')>
                        <span class="goal-type-illustration goal-type-illustration-money" aria-hidden="true">
                            <svg viewBox="0 0 96 64" fill="none">
                                <rect x="36" y="12" width="48" height="38" rx="9" fill="currentColor" opacity=".14"/>
                                <rect x="40" y="8" width="46" height="38" rx="8" fill="white" stroke="currentColor" stroke-width="2"/>
                                <path d="M47 19h31M47 26h12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" opacity=".45"/>
                                <circle cx="65" cy="34" r="8" fill="currentColor"/>
                                <path d="M65 29v10m3-7c-.8-1-4-1-4 0 0 2 4 1 4 3s-3 2-5 1" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="25" cy="39" r="15" fill="#FDE68A"/>
                                <circle cx="25" cy="39" r="10" stroke="#D97706" stroke-width="2"/>
                                <path d="M25 33v12m3-9c-.8-1-4-1-4 0 0 2 4 1 4 3s-3 2-5 1" stroke="#B45309" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="15" cy="15" r="5" fill="#FDE68A"/>
                            </svg>
                        </span>
                        <span class="goal-type-option-copy">
                            <span class="goal-type-option-title">Target uang</span>
                            <span class="goal-type-option-description">Menabung atau mencapai jumlah Rupiah.</span>
                        </span>
                        <span class="goal-type-option-check"><i data-lucide="circle-check" class="h-5 w-5"></i></span>
                    </label>
                    <label class="goal-type-option">
                        <input type="radio" name="goal_type" value="percentage" required @checked(old('goal_type') === 'percentage')>
                        <span class="goal-type-illustration goal-type-illustration-percentage" aria-hidden="true">
                            <svg viewBox="0 0 96 64" fill="none">
                                <rect x="14" y="9" width="66" height="47" rx="10" fill="white" stroke="currentColor" stroke-width="2"/>
                                <circle cx="35" cy="26" r="6" fill="currentColor" opacity=".2"/>
                                <circle cx="35" cy="26" r="3" fill="currentColor"/>
                                <circle cx="59" cy="40" r="6" fill="currentColor" opacity=".2"/>
                                <circle cx="59" cy="40" r="3" fill="currentColor"/>
                                <path d="m36 43 23-22" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                <rect x="25" y="46" width="43" height="4" rx="2" fill="currentColor" opacity=".12"/>
                                <rect x="25" y="46" width="26" height="4" rx="2" fill="currentColor"/>
                                <circle cx="76" cy="13" r="6" fill="#A7F3D0"/>
                                <path d="m73 13 2 2 4-4" stroke="#047857" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="goal-type-option-copy">
                            <span class="goal-type-option-title">Target persentase</span>
                            <span class="goal-type-option-description">Pantau capaian dalam bentuk persen.</span>
                        </span>
                        <span class="goal-type-option-check"><i data-lucide="circle-check" class="h-5 w-5"></i></span>
                    </label>
                </div>
                @error('goal_type')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </fieldset>
        @else
            <div class="goal-type-current">
                <span class="goal-type-option-icon">
                    <i data-lucide="{{ $goal->goal_type === 'money' ? 'wallet' : ($goal->goal_type === 'percentage' ? 'percent' : 'ruler') }}" class="h-5 w-5"></i>
                </span>
                <span>
                    <span class="block text-sm font-bold text-slate-800">
                        {{ $goal->goal_type === 'money' ? 'Target uang (Rupiah)' : ($goal->goal_type === 'percentage' ? 'Target persentase' : 'Target satuan khusus') }}
                    </span>
                    <span class="mt-0.5 block text-xs text-slate-500">Jenis target tidak dapat diubah setelah target dibuat.</span>
                </span>
            </div>
        @endunless
        <div>
            <label for="name" class="mb-2 block text-sm font-bold text-slate-800">Nama target</label>
            <input id="name" name="name" value="{{ old('name', $goal->name) }}" required maxlength="100" pattern=" *[A-Za-z0-9]+(?: +[A-Za-z0-9]+)* *" oninput="const v=this.value.trim(); const valid=/^[A-Za-z0-9]+(?: [A-Za-z0-9]+)*$/.test(v) && /[A-Za-z]/.test(v); this.setCustomValidity(valid ? '' : 'Nama target harus memuat huruf dan hanya boleh berisi huruf, angka, serta spasi antar kata.')" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15" aria-describedby="goal-name-help" placeholder="Misal: Menyelesaikan membaca 10 buku non-fiksi">
            <p id="goal-name-help" class="mt-1.5 text-xs text-slate-500">Gunakan huruf atau angka, maksimal 100 karakter. Spasi hanya di antara kata.</p>
            @error('name')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="mb-2 block text-sm font-bold text-slate-800">Deskripsi</label>
            <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15" placeholder="Tuliskan motivasi atau penjelasan target ini...">{{ old('description', $goal->description) }}</textarea>
            @error('description')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="image" class="mb-2 block text-sm font-bold text-slate-800">Gambar motivasi (opsional)</label>
            @if($goal->image_path)
                <div class="mb-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                    <img data-goal-form-image src="{{ route('goals.image', $goal) }}" alt="Gambar motivasi untuk target {{ $goal->name }}" class="max-h-56 w-full object-cover">
                </div>
                <label class="mb-3 flex items-center gap-2 text-sm font-medium text-slate-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-[var(--theme-accent)] focus:ring-[var(--theme-accent)]">
                    Hapus gambar saat ini
                </label>
            @endif
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300">
            <p class="mt-1.5 text-xs text-slate-500">Format JPG, PNG, atau WebP. Maksimal 5 MB dan 4096 × 4096 piksel. Gambar hanya dapat dilihat oleh pemilik target.</p>
            @error('image')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="target_value" data-goal-target-label class="mb-2 block text-sm font-bold text-slate-800">{{ $goal->exists && $goal->goal_type === 'money' ? 'Target dana (Rupiah)' : ($goal->exists && $goal->goal_type === 'percentage' ? 'Target persentase' : 'Nilai target (harus lebih dari 0)') }}</label>
                <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-slate-50/70 focus-within:border-[var(--theme-accent)] focus-within:bg-white focus-within:ring-2 focus-within:ring-[var(--theme-accent)]/15">
                    <span data-goal-target-prefix class="items-center border-r border-slate-200 bg-slate-100 px-3.5 text-sm font-semibold text-slate-600" style="display: {{ $goal->goal_type === 'money' ? 'flex' : 'none' }}">{{ $goal->goal_type === 'money' ? 'Rp' : '' }}</span>
                    <input id="target_value" type="number" step="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" min="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" max="{{ $goal->goal_type === 'percentage' ? '100' : '' }}" name="target_value" value="{{ old('target_value', $goal->target_value) }}" required class="min-w-0 w-full border-0 bg-transparent px-4 py-3 text-sm text-slate-900 outline-none">
                    <span data-goal-target-suffix class="items-center border-l border-slate-200 bg-slate-100 px-3.5 text-sm font-semibold text-slate-600" style="display: {{ $goal->goal_type === 'percentage' ? 'flex' : 'none' }}">{{ $goal->goal_type === 'percentage' ? '%' : '' }}</span>
                </div>
                @error('target_value')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="progress_step" class="mb-2 block text-sm font-bold text-slate-800">Jumlah perubahan sekali klik</label>
                <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-slate-50/70 focus-within:border-[var(--theme-accent)] focus-within:bg-white focus-within:ring-2 focus-within:ring-[var(--theme-accent)]/15">
                    <span data-goal-step-prefix class="items-center border-r border-slate-200 bg-slate-100 px-3.5 text-sm font-semibold text-slate-600" style="display: {{ $goal->goal_type === 'money' ? 'flex' : 'none' }}">{{ $goal->goal_type === 'money' ? 'Rp' : '' }}</span>
                    <input id="progress_step" type="number" name="progress_step" inputmode="decimal" step="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" min="{{ $goal->goal_type === 'money' ? '1' : '0.01' }}" value="{{ old('progress_step', $goal->progress_step ?? ($goal->goal_type === 'money' ? 10000 : 1)) }}" required class="min-w-0 w-full border-0 bg-transparent px-4 py-3 text-sm text-slate-900 outline-none">
                    <span data-goal-step-suffix class="items-center border-l border-slate-200 bg-slate-100 px-3.5 text-sm font-semibold text-slate-600" style="display: {{ $goal->goal_type === 'percentage' || $goal->goal_type === 'custom' ? 'flex' : 'none' }}">{{ $goal->goal_type === 'percentage' ? '%' : ($goal->goal_type === 'custom' ? $goal->unit : '') }}</span>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Nilai ini dipakai tombol tambah/kurang di daftar target.</p>
                @error('progress_step')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div data-goal-custom-unit class="{{ $goal->goal_type === 'money' || $goal->goal_type === 'percentage' ? 'hidden' : '' }}">
                <label for="unit" class="mb-2 block text-sm font-bold text-slate-800">Satuan</label>
                <input id="unit" name="unit" value="{{ old('unit', $goal->unit) }}" placeholder="halaman, jam, Rupiah..." maxlength="40" required class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @error('unit')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        @if($goal->exists && in_array($goal->goal_type, ['money', 'percentage'], true))
            <input type="hidden" name="unit" value="{{ $goal->unit }}">
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="mb-2 block text-sm font-bold text-slate-800">Tanggal mulai</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $goal->start_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @error('start_date')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="deadline" class="mb-2 block text-sm font-bold text-slate-800">Deadline (opsional)</label>
                <input id="deadline" type="date" name="deadline" value="{{ old('deadline', $goal->deadline?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-900 focus:border-[var(--theme-accent)] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[var(--theme-accent)]/15">
                @error('deadline')<p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
            <p class="text-xs leading-relaxed text-slate-600">Progres dicatat sebagai riwayat terpisah; nilai progres saat ini tidak dapat diubah langsung dari formulir.</p>
        </div>
        @error('current_value')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    </form>
    <div class="workspace-form-actions">
        <a href="{{ $goal->exists ? route('goals.show', $goal) : route('goals.index') }}" class="workspace-secondary-button">Batal</a>
        <button type="submit" form="goal-form" class="workspace-primary-button">Simpan Target</button>
    </div>
    @if($goal->exists)
        <div class="workspace-delete-goal-actions">
            <form method="POST" action="{{ route('goals.destroy', $goal) }}" onsubmit="return confirm('Hapus target ini beserta riwayat progresnya?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="workspace-danger-button">Hapus Target</button>
            </form>
        </div>
    @endif
</div>
@unless($goal->exists)
    <script>
        (() => {
            const form = document.getElementById('goal-form');
            const target = form?.querySelector('#target_value');
            const label = form?.querySelector('[data-goal-target-label]');
            const prefix = form?.querySelector('[data-goal-target-prefix]');
            const suffix = form?.querySelector('[data-goal-target-suffix]');
            const stepInput = form?.querySelector('#progress_step');
            const stepPrefix = form?.querySelector('[data-goal-step-prefix]');
            const stepSuffix = form?.querySelector('[data-goal-step-suffix]');
            const customUnit = form?.querySelector('[data-goal-custom-unit]');
            const unitInput = form?.querySelector('#unit');
            const updateGoalType = (type, resetStep = false) => {
                const isMoney = type === 'money';
                const isPercentage = type === 'percentage';
                if (label) label.textContent = isMoney ? 'Target dana (Rupiah)' : isPercentage ? 'Target persentase' : 'Nilai target (harus lebih dari 0)';
                if (prefix) {
                    prefix.textContent = isMoney ? 'Rp' : '';
                    prefix.style.display = isMoney ? 'flex' : 'none';
                }
                if (suffix) {
                    suffix.textContent = isPercentage ? '%' : '';
                    suffix.style.display = isPercentage ? 'flex' : 'none';
                }
                if (target) {
                    target.step = isMoney ? '1' : '0.01';
                    target.min = isMoney ? '1' : '0.01';
                    target.max = isPercentage ? '100' : '';
                }
                if (stepInput) {
                    stepInput.step = isMoney ? '1' : '0.01';
                    stepInput.min = isMoney ? '1' : '0.01';
                    stepInput.max = target?.value || '';
                    if (resetStep) stepInput.value = isMoney ? '10000' : '1';
                    else if (isMoney && Number(stepInput.value) < 10000) stepInput.value = '10000';
                    if (Number(target?.value) > 0 && Number(stepInput.value) > Number(target.value)) {
                        stepInput.value = target.value;
                    }
                }
                if (stepPrefix) {
                    stepPrefix.textContent = isMoney ? 'Rp' : '';
                    stepPrefix.style.display = isMoney ? 'flex' : 'none';
                }
                if (stepSuffix) {
                    stepSuffix.textContent = isPercentage ? '%' : isMoney ? '' : (unitInput?.value || '');
                    stepSuffix.style.display = isMoney ? 'none' : 'flex';
                }
                if (customUnit) customUnit.classList.toggle('hidden', !type);
                if (unitInput) unitInput.required = !type;
            };
            form?.querySelectorAll('input[name="goal_type"]').forEach((input) => {
                input.addEventListener('change', () => updateGoalType(input.value, true));
            });
            target?.addEventListener('input', () => {
                const selectedType = form?.querySelector('input[name="goal_type"]:checked')?.value;
                if (selectedType) updateGoalType(selectedType);
            });
            const selectedType = form?.querySelector('input[name="goal_type"]:checked')?.value;
            if (selectedType) updateGoalType(selectedType);
        })();
    </script>
@endunless
@endsection
