@extends('layouts.app')

@section('title', 'Anggaran Biaya (Budgeting) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-data>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Anggaran Biaya (Budgeting)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Penetapan target anggaran biaya operasional dan analisis deviasi terhadap realisasi transaksi aktual</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="$dispatch('open-budget-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Anggaran Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ budgetModal: false }" @open-budget-modal.window="budgetModal = true">

    @if($activeBudget)
    <!-- Active Budget Comparison Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">
                    Tahun Anggaran: {{ $activeBudget->fiscal_year }}
                </span>
                <h2 class="text-base font-bold text-slate-900 mt-1">{{ $activeBudget->name }}</h2>
            </div>
            <div class="text-xs text-slate-500">
                Status: <span class="font-semibold text-emerald-600 uppercase">Aktif</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6">Akun Beban / Pengeluaran</th>
                        <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Pagu Anggaran</th>
                        <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Realisasi Aktual</th>
                        <th class="py-3 px-6 w-36 text-right whitespace-nowrap">Sisa / Selisih</th>
                        <th class="py-3 px-6 w-48 text-center whitespace-nowrap">Penyerapan (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($comparison as $row)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3.5 px-6 font-medium text-slate-900">
                            <span class="font-mono text-xs text-blue-600 mr-1.5">{{ $row['account']->code }}</span>
                            {{ $row['account']->name }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-medium whitespace-nowrap">
                            Rp {{ number_format($row['budget'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-bold whitespace-nowrap text-slate-900">
                            Rp {{ number_format($row['actual'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-right font-mono font-semibold whitespace-nowrap {{ $row['difference'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            Rp {{ number_format($row['difference'], 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6 text-center whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                                    <div class="h-2 rounded-full {{ $row['usage_percent'] > 100 ? 'bg-rose-500' : ($row['usage_percent'] > 80 ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ min($row['usage_percent'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-mono font-bold {{ $row['usage_percent'] > 100 ? 'text-rose-600' : 'text-slate-700' }} w-12 text-right">
                                    {{ $row['usage_percent'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            Belum ada baris alokasi akun pada anggaran ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
        Belum ada anggaran yang dibuat. Silakan klik tombol "Buat Anggaran Baru" di atas.
    </div>
    @endif

    <!-- Modal Buat Anggaran -->
    <div x-show="budgetModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="budgetModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Tetapkan Pagu Anggaran Baru</h3>
                <form method="POST" action="{{ route('budgets.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Anggaran</label>
                            <input type="text" name="name" required placeholder="Contoh: Budget Operasional 2026" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Tahun Anggaran</label>
                            <input type="number" name="fiscal_year" value="{{ date('Y') }}" min="2020" max="2035" required class="w-full text-sm rounded-lg border-slate-300 font-mono">
                        </div>
                    </div>
                    <div class="border-t border-slate-100 pt-3">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-2">Alokasi Akun Beban</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <select name="items[0][account_id]" required class="w-full text-sm rounded-lg border-slate-300">
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="number" name="items[0][annual_amount]" value="50000000" min="1" step="100000" required placeholder="Pagu 1 Tahun" class="w-full text-sm rounded-lg border-slate-300 font-mono">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Catatan</label>
                        <textarea name="notes" rows="2" placeholder="Kebijakan pengetatan efisiensi..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="budgetModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Tetapkan Anggaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
