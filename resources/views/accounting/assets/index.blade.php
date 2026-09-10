@extends('layouts.app')

@section('title', 'Buku Register Aset Tetap & Penyusutan — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Buku Register Aset Tetap</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pencatatan aset kapital, amortisasi fiskal garis lurus, dan pembukuan jurnal penyusutan berkala</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="$dispatch('open-asset-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-500 shadow-sm transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Aset Tetap Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ showModal: false }" @open-asset-modal.window="showModal = true" class="space-y-6">

    <!-- KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Nilai Perolehan (Cost)</div>
            <div class="text-xl font-extrabold text-slate-900 font-mono">
                Rp {{ number_format($totalAcquisition, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Biaya historis kapitalisasi aset</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Akumulasi Penyusutan</div>
            <div class="text-xl font-extrabold text-rose-600 font-mono">
                Rp {{ number_format($totalAccumulated, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Total depresiasi telah dibukukan</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Nilai Buku Bersih (NBV)</div>
            <div class="text-xl font-extrabold text-emerald-600 font-mono">
                Rp {{ number_format($totalBookValue, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Net Book Value di Neraca</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Jumlah Unit Aset</div>
            <div class="text-xl font-extrabold text-blue-600">
                {{ $assets->count() }} <span class="text-xs font-normal text-slate-500">Unit Aktif</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Metode Garis Lurus (Straight-line)</div>
        </div>
    </div>

    <!-- Assets Register Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h2 class="font-semibold text-slate-800 text-sm">Register Daftar Aset Tetap Entitas ({{ $assets->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-5">Kode & Nama Aset</th>
                        <th class="py-3 px-4">Tgl Perolehan</th>
                        <th class="py-3 px-4 text-center">Masa (Thn)</th>
                        <th class="py-3 px-5 text-right">Nilai Perolehan</th>
                        <th class="py-3 px-5 text-right">Akum. Penyusutan</th>
                        <th class="py-3 px-5 text-right">Nilai Buku (NBV)</th>
                        <th class="py-3 px-5 text-right">Beban / Bln</th>
                        <th class="py-3 px-5 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($assets as $asset)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-5">
                            <div class="font-mono text-xs font-bold text-blue-600">{{ $asset->asset_code }}</div>
                            <div class="font-semibold text-slate-900 text-sm">{{ $asset->name }}</div>
                            <div class="text-[11px] text-slate-400">
                                {{ $asset->branch?->name ?? 'Kantor Pusat' }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-600">
                            {{ $asset->acquisition_date?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                {{ $asset->useful_life_years }} thn
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono text-xs font-bold text-slate-900">
                            Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono text-xs text-rose-600">
                            Rp {{ number_format($asset->accumulated_depreciation_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono text-xs font-bold text-emerald-600">
                            Rp {{ number_format($asset->book_value, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono text-xs text-slate-700">
                            Rp {{ number_format($asset->monthly_depreciation, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            @if($asset->book_value > ($asset->salvage_value ?? 0))
                                <form method="POST" action="{{ route('assets.depreciate', $asset->id) }}" onsubmit="return confirm('Susutkan aset {{ $asset->name }} sebesar Rp {{ number_format($asset->monthly_depreciation, 0, ',', '.') }} untuk periode bulan ini?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Susutkan Bln Ini
                                    </button>
                                </form>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-400">
                                    Habis Disusutkan
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            Belum ada aset tetap yang didaftarkan pada entitas ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Aset Tetap -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                <form method="POST" action="{{ route('assets.store') }}">
                    @csrf
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Registrasi Aset Tetap Baru</h3>
                            <p class="text-xs text-slate-500">Pencatatan aset kapital dan pemetaan akun COA pembukuan</p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Kode Aset</label>
                                <input type="text" name="asset_code" required placeholder="Contoh: AST-001, VEH-002" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Cabang Penempatan</label>
                                <select name="branch_id" class="w-full text-sm rounded-lg border-slate-300">
                                    <option value="">Kantor Pusat</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Deskripsi Aset</label>
                            <input type="text" name="name" required placeholder="Contoh: Mesin Produksi Offset, Toyota Innova Zenix" class="w-full text-sm rounded-lg border-slate-300">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tanggal Perolehan</label>
                                <input type="date" name="acquisition_date" required value="{{ date('Y-m-d') }}" class="w-full text-sm rounded-lg border-slate-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Harga Perolehan (Rp)</label>
                                <input type="number" step="0.01" name="acquisition_cost" required placeholder="0" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nilai Residu / Sisa (Rp)</label>
                                <input type="number" step="0.01" name="salvage_value" value="0" placeholder="0" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Masa Manfaat (Tahun)</label>
                                <select name="useful_life_years" required class="w-full text-sm rounded-lg border-slate-300">
                                    <option value="4">4 Tahun (Fiskal Kelompok 1 - 25%/thn)</option>
                                    <option value="8">8 Tahun (Fiskal Kelompok 2 - 12.5%/thn)</option>
                                    <option value="16">16 Tahun (Fiskal Kelompok 3 - 6.25%/thn)</option>
                                    <option value="20">20 Tahun (Bangunan Permanen - 5%/thn)</option>
                                    <option value="5">5 Tahun (Komersial Umum)</option>
                                    <option value="10">10 Tahun (Komersial Umum)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Metode Penyusutan</label>
                                <input type="text" readonly value="Garis Lurus (Straight-line)" class="w-full text-sm rounded-lg border-slate-200 bg-slate-100 text-slate-600">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200">
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Pemetaan Akun Pembukuan (COA)</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Akun Aset</label>
                                    <select name="asset_account_id" required class="w-full text-xs rounded-lg border-slate-300">
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Akun Akumulasi Penyusutan</label>
                                    <select name="accumulated_depreciation_account_id" required class="w-full text-xs rounded-lg border-slate-300">
                                        @foreach($assetAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Akun Beban Penyusutan</label>
                                    <select name="depreciation_expense_account_id" required class="w-full text-xs rounded-lg border-slate-300">
                                        @foreach($expenseAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-semibold shadow-sm">Daftarkan Aset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
