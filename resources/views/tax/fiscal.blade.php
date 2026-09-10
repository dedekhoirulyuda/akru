@extends('layouts.app')

@section('title', 'Rekonsiliasi Fiskal (Koreksi Fiskal Positif & Negatif) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rekonsiliasi Fiskal (SPT 1771-I PPh Badan)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penyesuaian laba komersial ke laba fiskal (PKP) melalui koreksi positif & negatif sesuai UU PPh HPP</p>
    </div>
    <div class="flex items-center gap-2" x-data="{ showModal: false }">
        <button @click="$dispatch('open-correction-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Koreksi Fiskal
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showModal: false }" @open-correction-modal.window="showModal = true">
    <!-- Filter Year -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
        <form method="GET" action="{{ route('tax.fiscal') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tahun Pajak</label>
                <input type="number" name="tax_year" min="2020" max="2035" value="{{ $taxYear }}" class="text-sm rounded-lg border-slate-300 w-32">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
                Tampilkan Tahun Pajak
            </button>
        </form>
    </div>

    <!-- Summary Overview Card -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Laba Bersih Komersial</div>
            <div class="text-xl font-bold font-mono text-slate-900 mt-1">
                Rp {{ number_format($commercialProfit, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Berdasarkan pembukuan SAK</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Koreksi Fiskal Positif (+)</div>
            <div class="text-xl font-bold font-mono text-rose-600 mt-1">
                + Rp {{ number_format($totalPositive, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Beban tidak dapat dikurangkan</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-400">Koreksi Fiskal Negatif (-)</div>
            <div class="text-xl font-bold font-mono text-emerald-600 mt-1">
                - Rp {{ number_format($totalNegative, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Penghasilan bukan objek / final</div>
        </div>
        <div class="bg-emerald-50 p-5 rounded-xl border border-emerald-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-emerald-800">Penghasilan Kena Pajak (PKP)</div>
            <div class="text-xl font-bold font-mono text-emerald-900 mt-1">
                Rp {{ number_format($fiscalProfit, 0, ',', '.') }}
            </div>
            <div class="text-xs font-semibold text-emerald-700 mt-1">
                PPh Terutang (22%): Rp {{ number_format($corporateTaxPayable, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Reconciliation Statement Form 1771-I -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h2 class="font-bold text-slate-900 text-base mb-4 pb-3 border-b border-slate-200">
            Rincian Koreksi Fiskal Tahun Pajak {{ $taxYear }} (Formulir 1771-I)
        </h2>

        <div class="space-y-6 text-sm">
            <!-- 1. Laba Komersial -->
            <div class="flex justify-between py-2 border-b border-slate-100 font-semibold text-slate-900">
                <span>1. Penghasilan Neto Komersial Sebelum Pajak</span>
                <span class="font-mono">Rp {{ number_format($commercialProfit, 0, ',', '.') }}</span>
            </div>

            <!-- 2. Koreksi Positif -->
            <div>
                <div class="font-bold text-rose-800 text-xs uppercase tracking-wider mb-2">2. Penyesuaian Fiskal Positif (+)</div>
                <div class="space-y-1.5 pl-4">
                    @forelse($positiveCorrections as $pc)
                    <div class="flex justify-between text-slate-700 py-1">
                        <div>
                            <span class="font-medium text-slate-900">{{ $pc->category }}:</span>
                            <span>{{ $pc->description }}</span>
                            <span class="text-xs text-slate-400 font-mono">({{ $pc->account->code ?? '' }} - {{ $pc->account->name ?? '' }})</span>
                        </div>
                        <span class="font-mono text-rose-700">+ Rp {{ number_format($pc->amount, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="text-slate-400 italic text-xs py-1">Tidak ada koreksi fiskal positif yang dicatat.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 pl-4 border-t border-slate-100 mt-2 font-semibold text-rose-800">
                    <span>Total Penyesuaian Fiskal Positif</span>
                    <span class="font-mono">+ Rp {{ number_format($totalPositive, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 3. Koreksi Negatif -->
            <div>
                <div class="font-bold text-emerald-800 text-xs uppercase tracking-wider mb-2">3. Penyesuaian Fiskal Negatif (-)</div>
                <div class="space-y-1.5 pl-4">
                    @forelse($negativeCorrections as $nc)
                    <div class="flex justify-between text-slate-700 py-1">
                        <div>
                            <span class="font-medium text-slate-900">{{ $nc->category }}:</span>
                            <span>{{ $nc->description }}</span>
                            <span class="text-xs text-slate-400 font-mono">({{ $nc->account->code ?? '' }} - {{ $nc->account->name ?? '' }})</span>
                        </div>
                        <span class="font-mono text-emerald-700">- Rp {{ number_format($nc->amount, 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div class="text-slate-400 italic text-xs py-1">Tidak ada koreksi fiskal negatif yang dicatat.</div>
                    @endforelse
                </div>
                <div class="flex justify-between py-2 pl-4 border-t border-slate-100 mt-2 font-semibold text-emerald-800">
                    <span>Total Penyesuaian Fiskal Negatif</span>
                    <span class="font-mono">- Rp {{ number_format($totalNegative, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- 4. Laba Fiskal PKP -->
            <div class="flex justify-between py-4 px-4 bg-emerald-50 border border-emerald-300 rounded-xl font-bold text-base text-emerald-950">
                <span>4. Penghasilan Neto Fiskal / PKP (1 + 2 - 3)</span>
                <span class="font-mono text-lg">Rp {{ number_format($fiscalProfit, 0, ',', '.') }}</span>
            </div>

            <!-- 5. Pajak Terutang -->
            <div class="flex justify-between py-2 px-4 text-slate-700 font-medium">
                <span>5. Pajak Penghasilan Badan Terutang (Tarif Pasal 17 ayat (1) huruf b UU HPP: 22%)</span>
                <span class="font-mono font-bold text-slate-900">Rp {{ number_format($corporateTaxPayable, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Koreksi Fiskal -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('tax.fiscal.correction') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Tambah Koreksi Fiskal (Pasal 1771-I)</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tahun Pajak</label>
                                <input type="number" name="tax_year" value="{{ $taxYear }}" required class="w-full text-sm rounded-lg border-slate-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Jenis Koreksi</label>
                                <select name="correction_type" required class="w-full text-sm rounded-lg border-slate-300">
                                    <option value="positive">Koreksi Positif (+ Menambah PKP)</option>
                                    <option value="negative">Koreksi Negatif (- Mengurangi PKP)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Akun Terkait (COA)</label>
                            <select name="account_id" required class="w-full text-sm rounded-lg border-slate-300">
                                <option value="">-- Pilih Akun Biaya / Pendapatan --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ $acc->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Kategori Koreksi Fiskal</label>
                            <select name="category" required class="w-full text-sm rounded-lg border-slate-300">
                                <option value="Biaya Entertainment & Representasi Tanpa Nominatif">Biaya Entertainment Tanpa Daftar Nominatif (PMK 02/2010)</option>
                                <option value="Natura & Kenikmatan Non-Deductible">Natura & Kenikmatan Tertentu (PMK 66/2023)</option>
                                <option value="Sanksi & Denda Administrasi Pajak">Sanksi & Denda Administrasi Perpajakan</option>
                                <option value="Sumbangan Non-UU Perpajakan">Sumbangan yang Tidak Memenuhi Ketentuan UU</option>
                                <option value="Beda Waktu Penyusutan Fiskal vs Komersial">Beda Waktu Penyusutan Aset Tetap</option>
                                <option value="Penghasilan Dikenakan PPh Final">Penghasilan Dikenakan PPh Bersifat Final (Bunga Bank)</option>
                                <option value="Penghasilan Bukan Objek Pajak">Penghasilan Bukan Objek Pajak (Dividen Dalam Negeri)</option>
                                <option value="Koreksi Lain-Lain">Koreksi Fiskal Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nominal Koreksi (Rp)</label>
                            <input type="number" step="1000" min="1" name="amount" required placeholder="0" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Keterangan / Rincian Alasan Koreksi</label>
                            <textarea name="description" rows="2" required placeholder="Contoh: Jamuan makan malam klien tanpa melampirkan daftar nominatif" class="w-full text-sm rounded-lg border-slate-300"></textarea>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">Simpan Koreksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
