@extends('layouts.app')

@section('title', 'Closing Workbench (Penutupan Periode) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Closing Workbench (Penutupan Buku)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Validasi pra-penutupan, penguncian jurnal akhir bulan/tahun, dan audit kepatuhan pembukaan kembali</p>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showReopenModal: false }" class="space-y-6">

    <!-- Period Selector Banner -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Periode Pembukuan Aktif</div>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('closing.index') }}" class="flex items-center gap-2">
                    <select name="period_id" onchange="this.form.submit()" class="text-lg font-bold text-slate-900 rounded-lg border-slate-300 pr-10 focus:ring-blue-500">
                        @foreach($periods as $p)
                            <option value="{{ $p->id }}" {{ $activePeriod?->id === $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ \Carbon\Carbon::parse($p->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($activePeriod?->is_closed)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        TERKUNCI (CLOSED)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        TERBUKA (OPEN)
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-2">
                @if($activePeriod?->is_closed)
                    Ditutup pada {{ $activePeriod->closed_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB. Dokumen pada periode ini terkunci dari perubahan.
                @else
                    Periode terbuka untuk pencatatan transaksi operasional, faktur, dan memorial penyesuaian.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if(!$activePeriod?->is_closed)
                <form method="POST" action="{{ route('closing.close', $activePeriod->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menutup dan mengunci periode {{ $activePeriod->name }}? Pastikan seluruh checklist telah tervalidasi.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold shadow-xs transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Kunci & Tutup Periode Ini
                    </button>
                </form>
            @else
                <button type="button" @click="showReopenModal = true" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    Buka Kembali Periode (Reopen)
                </button>
            @endif
        </div>
    </div>

    <!-- Pre-Closing Readiness Checklist -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-base">Checklist Kelayakan Tutup Buku (Pre-Closing Validation)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Integritas kontrol akuntansi sebelum buku besar periode dikunci</p>
            </div>
            <div>
                @if($allChecksPassed)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Siap Ditutup (100% Valid)</span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Perhatian Diperlukan</span>
                @endif
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($checklist as $idx => $item)
                <div class="p-5 flex items-start gap-4 hover:bg-slate-50/60 transition-colors">
                    <div class="mt-0.5">
                        @if($item['passed'])
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @else
                            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900">{{ $item['title'] }}</h3>
                            @if($item['passed'])
                                <span class="text-xs font-semibold text-emerald-600">Lolos Verifikasi</span>
                            @else
                                <span class="text-xs font-semibold text-amber-600">Perlu Penyesuaian</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 mt-1">{{ $item['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Closing Impact Information Banner -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-blue-50/60 border border-blue-200 rounded-xl p-5 text-xs text-blue-900 space-y-2">
            <h4 class="font-bold text-blue-950 text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Dampak Penguncian Periode (Month-End Lock)
            </h4>
            <ul class="list-disc pl-4 space-y-1 text-blue-800/90">
                <li>Seluruh faktur penjualan, faktur pembelian, dan pembayaran dengan tanggal dalam periode terkunci tidak dapat diedit atau dihapus.</li>
                <li>Jurnal manual dan penyesuaian baru tidak dapat dibukukan pada tanggal periode yang telah ditutup.</li>
                <li>Menjamin integritas laporan keuangan bulanan saat dilaporkan ke manajemen dan audit eksternal.</li>
            </ul>
        </div>

        <div class="bg-purple-50/60 border border-purple-200 rounded-xl p-5 text-xs text-purple-900 space-y-2">
            <h4 class="font-bold text-purple-950 text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Tata Kelola Pembukaan Kembali (Audit Trail)
            </h4>
            <ul class="list-disc pl-4 space-y-1 text-purple-800/90">
                <li>Pembukaan kembali periode buku (reopening) mewajibkan pengisian alasan resmi yang sah.</li>
                <li>Setiap aksi pembukaan kembali dicatat permanen dalam sistem Audit Log beserta nama pengguna, waktu, dan IP address.</li>
                <li>Memenuhi standar kepatuhan regulasi perpajakan dan akuntansi audit forensik.</li>
            </ul>
        </div>
    </div>

    <!-- Modal Buka Kembali Periode -->
    <div x-show="showReopenModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showReopenModal" @click="showReopenModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showReopenModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('closing.reopen', $activePeriod?->id ?? 1) }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Otorisasi Pembukaan Kembali Periode Buku</h3>
                        <button type="button" @click="showReopenModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                            <strong>Peringatan Kepatuhan:</strong> Periode yang dibuka kembali akan memungkinkan perubahan jurnal. Tindakan ini dicatat ke dalam Jejak Audit (Audit Trail).
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Periode Yang Dibuka</label>
                            <input type="text" readonly value="{{ $activePeriod?->name }}" class="w-full text-sm rounded-lg border-slate-200 bg-slate-100 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alasan Pembukaan Kembali (Wajib Diisi)</label>
                            <textarea name="reason" required rows="3" placeholder="Contoh: Koreksi penyesuaian biaya listrik dan rekonsiliasi PPh 21 dari kantor konsultan pajak..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Minimal 5 karakter. Alasan ini akan tersimpan permanen di catatan audit entitas.</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showReopenModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-sm font-semibold shadow-sm">Setujui & Buka Periode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
