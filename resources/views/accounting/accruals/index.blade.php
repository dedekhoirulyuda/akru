@extends('layouts.app')

@section('title', 'Jadwal Amortisasi & Akrual (Accruals) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" x-data>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Jadwal Amortisasi & Beban Akrual</h1>
        <p class="text-sm text-slate-500 mt-0.5">Alokasi otomatis beban dibayar di muka (prepaid expenses) menjadi beban periodik bulanan secara akurat</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="$dispatch('open-accrual-modal')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500 shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Jadwal Baru
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ accrualModal: false }" @open-accrual-modal.window="accrualModal = true">

    <div class="space-y-4">
        @forelse($schedules as $s)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-200">
                            {{ $s->schedule_number }}
                        </span>
                        <h2 class="text-base font-bold text-slate-900">{{ $s->name }}</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Dari: <strong>{{ $s->prepaidAccount->name ?? '-' }}</strong> → Ke Beban: <strong>{{ $s->targetAccount->name ?? '-' }}</strong>
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500">Total Nilai Kontrak:</span>
                    <p class="text-sm font-bold font-mono text-slate-900">Rp {{ number_format($s->total_amount, 0, ',', '.') }}</p>
                    <span class="text-[11px] text-slate-400 font-mono">{{ $s->periods_count }} Periode (Rp {{ number_format($s->amount_per_period, 0, ',', '.') }}/bulan)</span>
                </div>
            </div>

            <!-- Schedule lines table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-6 w-20">Periode</th>
                            <th class="py-2.5 px-6 w-32">Tanggal Jadwal</th>
                            <th class="py-2.5 px-6 text-right w-36">Nominal Amortisasi</th>
                            <th class="py-2.5 px-6 w-28 text-center">Status</th>
                            <th class="py-2.5 px-6 w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($s->lines as $line)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-2.5 px-6 font-mono font-medium">Bulan #{{ $line->period_number }}</td>
                            <td class="py-2.5 px-6 font-mono text-slate-600">{{ $line->schedule_date->format('d/m/Y') }}</td>
                            <td class="py-2.5 px-6 text-right font-mono font-semibold text-slate-900">Rp {{ number_format($line->amount, 0, ',', '.') }}</td>
                            <td class="py-2.5 px-6 text-center">
                                @if($line->status === 'posted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Sudah Diposting
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        Menunggu
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-6 text-center">
                                @if($line->status !== 'posted')
                                <form method="POST" action="{{ route('accruals.process', [$s->id, $line->id]) }}" onsubmit="return confirm('Posting jurnal amortisasi bulan ke-{{ $line->period_number }} ke Buku Besar?')">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium text-[11px] border border-blue-200 transition-colors">
                                        Posting Jurnal →
                                    </button>
                                </form>
                                @else
                                <span class="text-slate-400 font-mono text-[10px]">Tercatat</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
            Belum ada jadwal amortisasi beban dibayar di muka yang aktif. Klik "Buat Jadwal Baru" di atas.
        </div>
        @endforelse
    </div>

    <!-- Modal Buat Jadwal Amortisasi -->
    <div x-show="accrualModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="accrualModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <div class="relative z-10 inline-block bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Buat Jadwal Amortisasi Beban Baru</h3>
                <form method="POST" action="{{ route('accruals.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Kontrak / Uraian</label>
                        <input type="text" name="name" required placeholder="Contoh: Sewa Kantor Wisma Sudirman 2026-2027" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <input type="hidden" name="type" value="prepaid_expense">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Akun Prepaid (Aset)</label>
                            <select name="prepaid_account_id" required class="w-full text-sm rounded-lg border-slate-300">
                                @foreach($assetAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Akun Beban Target</label>
                            <select name="target_account_id" required class="w-full text-sm rounded-lg border-slate-300">
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Total Nilai Kontrak (Rp)</label>
                            <input type="number" name="total_amount" value="120000000" min="1000" step="1000" required class="w-full text-sm rounded-lg border-slate-300 font-mono font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Jml Bulan</label>
                            <input type="number" name="periods_count" value="12" min="1" max="60" required class="w-full text-sm rounded-lg border-slate-300 font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Tanggal Mulai Amortisasi</label>
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="accrualModal = false" class="px-4 py-2 border rounded-lg text-sm text-slate-600">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-500">Buat Skedul</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
