@extends('layouts.app')

@section('title', 'Offline — AKRU')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="w-20 h-20 rounded-full bg-amber-50 flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636a9 9 0 010 12.728m-2.829-2.829a5 5 0 000-7.07m-4.243 2.121a1.5 1.5 0 112.122 2.122 1.5 1.5 0 01-2.122-2.122zM2.05 12a9.96 9.96 0 011.586-5.364"/>
        </svg>
    </div>
    <h2 class="text-xl font-semibold text-gray-900 mb-2">Anda Sedang Offline</h2>
    <p class="text-gray-500 max-w-md mb-6">
        Koneksi internet tidak tersedia. Anda masih dapat membuat draft transaksi yang akan disinkronisasi ketika kembali online.
    </p>
    <div class="flex gap-3">
        <button onclick="location.reload()" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
            Coba Lagi
        </button>
        <a href="/dashboard" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
