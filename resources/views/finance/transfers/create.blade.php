@extends('layouts.app')

@section('title', 'Transfer Antar Bank — AKRU')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="{{ route('transfers.index') }}" class="hover:text-blue-600">Transfer Antar Bank</a>
            <span>&rsaquo;</span>
            <span class="text-slate-900 font-medium">Transfer Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Transfer Dana Internal Antar-Bank</h1>
    </div>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('transfers.store') }}" class="max-w-xl mx-auto">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold text-slate-900 text-base border-b border-slate-100 pb-3">Informasi Pemindahan Dana</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Dari Rekening Asal *</label>
                <select name="from_bank_account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    <option value="">-- Pilih Rekening Sumber --</option>
                    @foreach($bankAccounts as $b)
                        <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number ?? 'Kas' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ke Rekening Tujuan *</label>
                <select name="to_bank_account_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
                    <option value="">-- Pilih Rekening Tujuan --</option>
                    @foreach($bankAccounts as $b)
                        <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number ?? 'Kas' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">No. Bukti Transfer *</label>
                <input type="text" name="transfer_number" required value="{{ $defaultNumber }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transfer *</label>
                <input type="date" name="transfer_date" required value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Transfer (Rp) *</label>
                <input type="number" name="amount" required min="1" placeholder="10000000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-base font-bold font-mono text-slate-900 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Biaya Admin Transfer (Rp)</label>
                <input type="number" name="fee_amount" min="0" value="0" placeholder="2500" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Referensi</label>
            <textarea name="notes" rows="2" placeholder="Pemindahan saldo untuk likuiditas operasional" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('transfers.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-500 shadow-sm transition-all">
                Simpan & Post ke Buku Besar
            </button>
        </div>
    </div>
</form>
@endsection
