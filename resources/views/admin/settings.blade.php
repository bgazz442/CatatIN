@extends('layouts.admin')

@section('title', 'Pengaturan Admin')

@section('content')
    <div class="mb-7">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Administrasi</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Pengaturan Admin</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base">Kelola informasi profil dan keamanan akun admin. Role admin tidak dapat diubah dari pengaturan profil.</p>
    </div>

    <div class="grid max-w-5xl gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(250px,.8fr)]">
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6 flex items-center gap-4 border-b border-slate-100 pb-5">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-800">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m12-14a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
            </span>
            <div>
                <h2 class="font-bold text-slate-900">Profil akun</h2>
                <p class="mt-1 text-sm text-slate-500">Perbarui nama, email, atau password Anda.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <label class="block text-sm font-semibold text-slate-700">Nama
                <input name="name" value="{{ old('name', $admin->name) }}" required maxlength="255" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Email
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required maxlength="255" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
            </label>
            <div class="border-t border-slate-100 pt-5">
                <h2 class="font-bold text-slate-900">Keamanan password</h2>
                <p class="mt-1 text-sm text-slate-500">Kosongkan password baru jika tidak ingin menggantinya.</p>
            </div>
            <label class="block text-sm font-semibold text-slate-700">Password saat ini
                <input type="password" name="current_password" autocomplete="current-password" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Password baru
                <input type="password" name="password" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Konfirmasi password baru
                <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
            </label>
            <button class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                Simpan pengaturan
            </button>
        </form>
    </section>
    <aside class="h-fit rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-50 text-amber-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <h2 class="mt-4 font-bold text-slate-900">Jaga akun tetap aman</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan password yang kuat dan jangan bagikan informasi masuk akun kepada siapa pun.</p>
        <div class="mt-5 rounded-xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Masuk sebagai</p>
            <p class="mt-1 break-all text-sm font-semibold text-slate-800">{{ $admin->email }}</p>
            <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Administrator</span>
        </div>
    </aside>
    </div>
@endsection
