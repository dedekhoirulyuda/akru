@extends('platform.layouts.app')

@section('title', 'Kelola Langganan & Pembayaran — AKRU SaaS')

@section('content')
<div class="space-y-8" x-data="{ invoiceModalOpen: false, extendModalOpen: false, payModalOpen: false, changePlanModalOpen: false, selectedSub: null, selectedInvoice: null }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Manajemen Langganan & Penagihan (Billing)</h1>
            <p class="text-xs text-slate-400">Pantau masa aktif tenant, perpanjang langganan, konfirmasi transfer bank, dan terbitkan invoice tagihan.</p>
        </div>
        <button @click="invoiceModalOpen = true" type="button" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Terbitkan Invoice Baru</span>
        </button>
    </div>

    {{-- Section 1: Active Subscriptions --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>Status Langganan Perusahaan Klien</span>
            </h2>
        </div>

        <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Perusahaan Klien</th>
                            <th class="px-4 py-3.5">Paket Aktif</th>
                            <th class="px-4 py-3.5">Siklus Tagihan</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Masa Berlaku</th>
                            <th class="px-6 py-3.5 text-right">Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($subscriptions as $sub)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-100">{{ $sub->company?->name ?? 'Perusahaan Dihapus' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $sub->company?->email ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $sub->plan?->name ?? 'Kustom' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-300 font-mono text-[11px]">
                                {{ $sub->billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }}
                            </td>
                            <td class="px-4 py-4">
                                @if($sub->status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        ● Aktif
                                    </span>
                                @elseif($sub->status === 'trial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        ● Trial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        ● {{ ucfirst($sub->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-300 font-mono text-[11px]">
                                {{ $sub->ends_at?->format('d M Y') ?? '-' }}
                                @if($sub->ends_at && $sub->ends_at->isPast())
                                    <span class="text-rose-400 font-bold block text-[10px]">Kedaluwarsa</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="selectedSub = {{ json_encode($sub) }}; changePlanModalOpen = true" 
                                            type="button" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-[11px] font-semibold cursor-pointer">
                                        Ubah Paket
                                    </button>
                                    <button @click="selectedSub = {{ json_encode($sub) }}; extendModalOpen = true" 
                                            type="button" class="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-[11px] font-bold transition-all cursor-pointer">
                                        Perpanjang
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada data langganan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Section 2: Invoices & Payment History --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>Daftar Invoice & Riwayat Tagihan</span>
            </h2>
        </div>

        <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">No. Invoice</th>
                            <th class="px-4 py-3.5">Perusahaan</th>
                            <th class="px-4 py-3.5">Paket</th>
                            <th class="px-4 py-3.5">Nominal (IDR)</th>
                            <th class="px-4 py-3.5">Jatuh Tempo</th>
                            <th class="px-4 py-3.5">Status Pembayaran</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-indigo-400">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="px-4 py-4 text-slate-200 font-bold">
                                {{ $inv->company?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-slate-300">
                                {{ $inv->plan?->name ?? '-' }} ({{ $inv->billing_cycle }})
                            </td>
                            <td class="px-4 py-4 font-mono font-bold text-slate-100">
                                Rp {{ number_format($inv->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-slate-400 font-mono text-[11px]">
                                {{ $inv->due_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-4">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        ● Lunas ({{ $inv->payment_method }})
                                    </span>
                                @elseif($inv->status === 'unpaid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        ● Menunggu Bayar
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-400">
                                        ● {{ ucfirst($inv->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($inv->status !== 'paid')
                                <button @click="selectedInvoice = {{ json_encode($inv) }}; payModalOpen = true" 
                                        type="button" class="px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-[11px] font-bold transition-all cursor-pointer">
                                    Konfirmasi Bayar
                                </button>
                                @else
                                <span class="text-slate-500 text-[11px] font-mono">Telah lunas</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">Belum ada invoice tagihan yang diterbitkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Create Invoice --}}
    <div x-show="invoiceModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="invoiceModalOpen = false" class="w-full max-w-md rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Terbitkan Tagihan Langganan Baru</h3>
                <button @click="invoiceModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('platform.invoices.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Pilih Perusahaan Klien *</label>
                    <select name="company_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        @foreach($companies as $comp)
                        <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->city ?? 'Indonesia' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Paket Langganan *</label>
                        <select name="plan_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Siklus Tagihan *</label>
                        <select name="billing_cycle" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Nominal Tagihan (IDR) *</label>
                        <input type="number" name="amount" required placeholder="Contoh: 299000" 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-300">Tanggal Jatuh Tempo *</label>
                        <input type="date" name="due_date" value="{{ now()->addDays(7)->format('Y-m-d') }}" required 
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Catatan / Keterangan Tagihan</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Tagihan perpanjangan periode Oktober 2026..." 
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="invoiceModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer shadow-lg shadow-indigo-600/30">
                        Terbitkan Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Extend Subscription --}}
    <div x-show="extendModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="extendModalOpen = false" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Perpanjang Masa Aktif</h3>
                <button @click="extendModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/subscriptions/' + selectedSub?.id + '/extend'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="font-bold text-slate-200" x-text="selectedSub?.company?.name"></div>
                    <div class="text-[11px] text-slate-500">Paket: <span class="text-indigo-400 font-bold" x-text="selectedSub?.plan?.name"></span></div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Tambah Durasi Masa Aktif</label>
                    <select name="months" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12" selected>12 Bulan (1 Tahun)</option>
                        <option value="24">24 Bulan (2 Tahun)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Set Status Akun</label>
                    <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="active">Aktif Normal</option>
                        <option value="grace">Grace Period (Tenggat)</option>
                    </select>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="extendModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Perpanjang Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Pay Invoice --}}
    <div x-show="payModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="payModalOpen = false" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Konfirmasi Pelunasan Tagihan</h3>
                <button @click="payModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/invoices/' + selectedInvoice?.id + '/pay'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="font-bold text-slate-200" x-text="selectedInvoice?.company?.name"></div>
                    <div class="font-mono text-indigo-400" x-text="selectedInvoice?.invoice_number"></div>
                    <div class="mt-1 font-bold text-white text-sm" x-text="'Rp ' + (selectedInvoice?.amount ? Number(selectedInvoice.amount).toLocaleString('id-ID') : 0)"></div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Metode Pembayaran</label>
                    <select name="payment_method" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="manual_transfer">Transfer Manual (BCA/Mandiri)</option>
                        <option value="midtrans">Midtrans Gateway</option>
                        <option value="xendit">Xendit Gateway</option>
                        <option value="other">Lainnya / B2B Invoice</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">No. Referensi / Bukti Transfer</label>
                    <input type="text" name="payment_reference" placeholder="Contoh: TRF-BCA-20260910-0987" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="payModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold cursor-pointer">
                        Konfirmasi Lunas & Perpanjang
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Change Plan --}}
    <div x-show="changePlanModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="changePlanModalOpen = false" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Ubah Paket Langganan Perusahaan</h3>
                <button @click="changePlanModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form :action="'/platform/subscriptions/' + selectedSub?.id + '/plan'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                    <div class="font-bold text-slate-200" x-text="selectedSub?.company?.name"></div>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Pilih Paket Baru</label>
                    <select name="plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} (Rp {{ number_format($plan->price_per_month, 0, ',', '.') }}/bln)</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="changePlanModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Ubah Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
