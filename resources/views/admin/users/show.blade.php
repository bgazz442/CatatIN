@extends('layouts.admin')

@section('title', 'Detail Pengguna')

@section('content')
    <div class="mb-7">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#2F6F64] transition hover:text-[#24574F]">
            <span aria-hidden="true">&larr;</span> Kembali ke manajemen user
        </a>
        <div class="mt-4 flex flex-wrap items-center gap-4">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-xl font-bold uppercase text-emerald-800 border border-emerald-100 shadow-xs">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-950">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }} <span class="px-1 text-slate-300">·</span> Terdaftar {{ $user->created_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Informasi akun</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola profil, role, dan status pengguna.</p>
            </div>
            @if(! auth()->user()->is($user) && ($user->role === 'user' || auth()->user()->email === config('admin.email')))
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 space-y-4" onsubmit="if ((this.elements.role.value === 'admin' && @js($user->role !== 'admin')) || (this.elements.is_active.value === '0' && @js($user->is_active))) return confirm('Tindakan ini akan mengubah akses akun pengguna. Lanjutkan?'); return true;">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-semibold text-slate-700">Nama
                        <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15">
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Email
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15">
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-slate-700">Role
                            <select name="role" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15">
                                <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                                @if(auth()->user()->email === config('admin.email'))
                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                                @endif
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Status
                            <select name="is_active" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#2F6F64] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2F6F64]/15">
                                <option value="1" @selected((string) old('is_active', (int) $user->is_active) === '1')>Aktif</option>
                                <option value="0" @selected((string) old('is_active', (int) $user->is_active) === '0')>Nonaktif</option>
                            </select>
                        </label>
                    </div>
                    <button class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-slate-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        Simpan perubahan
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-8 border-t border-rose-100 pt-5" onsubmit="return confirm('Hapus akun ini beserta data yang dimiliki pengguna? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <p class="mb-3 text-sm text-slate-500">Tindakan ini menghapus akun dan data terkait secara permanen.</p>
                    <button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-50">Hapus akun pengguna</button>
                </form>
            @else
                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-100"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</dt><dd class="mt-1.5 font-bold text-slate-900">{{ $user->name }}</dd></div>
                    <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-100"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Email</dt><dd class="mt-1.5 break-all font-bold text-slate-900">{{ $user->email }}</dd></div>
                    <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-100"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Role</dt><dd class="mt-1.5 font-bold capitalize text-slate-900">{{ $user->role }}</dd></div>
                    <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-100"><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt><dd class="mt-1.5 font-bold text-slate-900">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                </dl>
                @if(auth()->user()->is($user))
                    <a href="{{ route('admin.settings.edit') }}" class="mt-6 inline-flex rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-slate-800">Kelola pengaturan akun admin</a>
                @else
                    <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Akun admin hanya dapat dikelola oleh admin utama yang dikonfigurasi.</p>
                @endif
            @endif
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <h2 class="font-bold text-slate-900">Ringkasan data</h2>
            <p class="mt-1 text-sm text-slate-500">Jumlah data milik pengguna</p>
            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex items-center justify-between rounded-xl bg-slate-50/80 border border-slate-100 px-4 py-3"><dt class="text-slate-600 font-medium">Catatan belajar</dt><dd class="font-bold tabular-nums text-slate-900">{{ number_format($user->catatan_count) }}</dd></div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50/80 border border-slate-100 px-4 py-3"><dt class="text-slate-600 font-medium">Task</dt><dd class="font-bold tabular-nums text-slate-900">{{ number_format($user->tasks_count) }}</dd></div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50/80 border border-slate-100 px-4 py-3"><dt class="text-slate-600 font-medium">Goal</dt><dd class="font-bold tabular-nums text-slate-900">{{ number_format($user->goals_count) }}</dd></div>
            </dl>
            <div class="mt-5 rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                <p class="text-xs font-bold text-slate-700">Privasi pengguna</p>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Password dan password hash tidak ditampilkan. Isi catatan serta data pribadi lainnya juga tidak ditampilkan di panel ini.</p>
            </div>
        </aside>
    </div>
@endsection
