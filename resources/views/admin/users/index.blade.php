@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('content')
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Administrasi</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Manajemen User</h1>
            <p class="mt-2 text-sm text-slate-500 sm:text-base">Cari akun, lihat status, dan kelola akses pengguna.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm">
            <span class="font-bold text-slate-900">{{ number_format($users->total()) }}</span>
            <span class="text-slate-500">akun ditemukan</span>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-5 grid gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_auto] lg:items-end lg:p-5">
        <div class="sm:col-span-2 lg:col-span-1">
            <p class="mb-3 text-sm font-bold text-slate-900">Filter pengguna</p>
        <label class="text-sm font-medium text-slate-700">Nama atau email
            <input name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500" placeholder="Cari pengguna">
        </label>
        </div>
        <label class="text-sm font-medium text-slate-700">Role
            <select name="role" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                <option value="">Semua role</option>
                @foreach(['user' => 'User', 'admin' => 'Admin'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-slate-700">Status akun
            <select name="status" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                <option value="">Semua status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
            </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
            <button class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 21-4.34-4.34M19 10.5a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z"/></svg>
                Terapkan
            </button>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-900">Daftar akun</h2>
            <p class="text-xs text-slate-500">Data pengguna CatatIn</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/80 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr><th class="px-5 py-3.5">Pengguna</th><th class="px-5 py-3.5">Role</th><th class="px-5 py-3.5">Status</th><th class="px-5 py-3.5">Task / Goal</th><th class="px-5 py-3.5">Terdaftar</th><th class="px-5 py-3.5"><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <div class="flex min-w-52 items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-sm font-bold uppercase text-slate-600">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span class="min-w-0"><span class="block truncate font-semibold text-slate-900">{{ $user->name }}</span><span class="mt-0.5 block truncate text-xs text-slate-500">{{ $user->email }}</span></span>
                                </div>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold capitalize {{ $user->role === 'admin' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $user->role }}</span></td>
                            <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}"><span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="px-5 py-4 text-slate-600"><span class="font-semibold text-slate-800">{{ $user->tasks_count }}</span><span class="px-1 text-slate-300">/</span>{{ $user->goals_count }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">Detail <span aria-hidden="true">&rarr;</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center">
                            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m16-13a4 4 0 0 1 0 8m4 5v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg></span>
                            <p class="mt-3 font-semibold text-slate-800">Tidak ada pengguna ditemukan</p>
                            <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci atau filter pencarian.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
