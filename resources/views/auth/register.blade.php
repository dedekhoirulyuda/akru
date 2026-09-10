@extends('layouts.auth')

@section('title', 'Daftar Perusahaan Baru — AKRU')

@section('content')
<div class="mb-4 text-center">
    <h2 class="text-xl font-bold text-white">Mulai dengan AKRU</h2>
    <p class="text-xs text-slate-400 mt-1">Satu akun untuk kendali keuangan, akuntansi, dan pajak</p>
</div>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <label for="company_name" class="block text-sm font-medium text-slate-300">Nama Perusahaan / Bisnis</label>
        <input
            id="company_name"
            name="company_name"
            type="text"
            required
            autofocus
            value="{{ old('company_name') }}"
            class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
            placeholder="PT Maju Sukses Mandiri"
        >
        @error('company_name')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-slate-300">Nama Lengkap Penanggung Jawab</label>
        <input
            id="name"
            name="name"
            type="text"
            required
            value="{{ old('name') }}"
            class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
            placeholder="Budi Santoso"
        >
        @error('name')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-300">Email Bisnis</label>
        <input
            id="email"
            name="email"
            type="email"
            required
            value="{{ old('email') }}"
            class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
            placeholder="budi@perusahaan.com"
        >
        @error('email')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="password" class="block text-sm font-medium text-slate-300">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
                placeholder="Minimal 8 karakter"
            >
            @error('password')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Konfirmasi</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
                placeholder="Ulangi password"
            >
        </div>
    </div>

    <button
        type="submit"
        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 focus:ring-2 focus:ring-blue-400 transition-all mt-2"
    >
        Buat Perusahaan & Akun Baru
    </button>
</form>

<div class="mt-4 text-center">
    <p class="text-sm text-slate-500">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 font-medium transition-colors">Masuk di sini</a>
    </p>
</div>
@endsection
