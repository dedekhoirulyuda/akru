@extends('layouts.auth')

@section('title', 'Masuk — AKRU')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-slate-300">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            required
            autofocus
            value="{{ old('email') }}"
            class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
            placeholder="nama@perusahaan.com"
        >
        @error('email')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="block text-sm font-medium text-slate-300">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Lupa password?</a>
        </div>
        <input
            id="password"
            name="password"
            type="password"
            required
            class="mt-1 w-full rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-500 px-4 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-colors"
            placeholder="••••••••"
        >
        @error('password')
            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center">
        <input
            id="remember"
            name="remember"
            type="checkbox"
            class="rounded border-white/20 bg-white/5 text-blue-500 focus:ring-blue-400 focus:ring-offset-0"
        >
        <label for="remember" class="ml-2 text-sm text-slate-400">Ingat saya</label>
    </div>

    <button
        type="submit"
        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all"
    >
        Masuk
    </button>
</form>

<div class="mt-4 text-center">
    <p class="text-sm text-slate-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 font-medium transition-colors">Daftar sekarang</a>
    </p>
</div>
@endsection
