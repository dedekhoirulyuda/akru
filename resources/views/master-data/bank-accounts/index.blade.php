@extends('layouts.app')

@section('title', 'Rekening Kas & Bank — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kas & Rekening Bank</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pengelolaan rekening operasional dan pemetaan ke akun Buku Besar (GL)</p>
    </div>
    <div x-data="{ openModal: false }">
        <button @click="openModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Rekening Bank
        </button>

        {{-- Modal Tambah Rekening --}}
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="openModal = false" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Tambah Rekening Baru</h3>
                <form method="POST" action="{{ route('bank-accounts.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank / Kas</label>
                        <input type="text" name="bank_name" required placeholder="Contoh: Bank Central Asia (BCA)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening</label>
                        <input type="text" name="account_number" placeholder="Contoh: 123-456-7890" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama Rekening</label>
                        <input type="text" name="account_holder_name" placeholder="PT Akru Maju Bersama" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tautkan ke Akun GL</label>
                        <select name="account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">-- Pilih Akun Aset / Kas --</option>
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Simpan Rekening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($bankAccounts as $bank)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg">
                {{ substr($bank->bank_name, 0, 1) }}
            </div>
            <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
            </span>
        </div>
        <h3 class="font-bold text-slate-900 text-base">{{ $bank->bank_name }}</h3>
        <p class="text-sm font-mono text-slate-600 mt-0.5">{{ $bank->account_number ?? 'Kas Tunai' }}</p>
        <p class="text-xs text-slate-400 mt-1">a/n {{ $bank->account_holder_name ?? '-' }}</p>

        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Akun GL:</span>
            <span class="font-medium text-slate-700 font-mono">{{ $bank->account->code ?? '-' }} ({{ $bank->account->name ?? '-' }})</span>
        </div>
    </div>
    @empty
    <div class="col-span-3 py-12 text-center text-slate-400 bg-white rounded-xl border border-slate-200">
        Belum ada rekening kas atau bank terdaftar.
    </div>
    @endforelse
</div>
@endsection
