@extends('layouts.app')

@section('title', 'Kotak Masuk Persetujuan (Work Queue) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kotak Masuk Persetujuan (Work Queue)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Daftar transaksi dan dokumen keuangan yang membutuhkan otorisasi bertingkat sebelum diposting</p>
    </div>
</div>
@endsection

@section('content')
<!-- Anti-Self Approval Policy Banner -->
<div class="p-4 rounded-xl border border-blue-200 bg-blue-50/60 mb-6 flex items-center justify-between text-xs text-blue-800">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span><strong>Prinsip Anti-Self Approval Aktif:</strong> Pembuat dokumen tidak dapat menyetujui pengajuannya sendiri guna mencegah konflik kepentingan (Segregation of Duties).</span>
    </div>
    <span class="font-mono text-[11px] bg-blue-200/60 text-blue-900 px-2 py-0.5 rounded font-semibold">SOD ENFORCED</span>
</div>

<!-- Pending Approvals Section -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-8" x-data="{ showRejectModal: false, activeRejectUrl: '' }">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-slate-800 text-sm">Menunggu Persetujuan Anda (Pending Tasks)</h2>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">{{ $pendingRequests->count() }}</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-36">Tipe Dokumen</th>
                    <th class="py-3 px-6 w-36">ID / Referensi</th>
                    <th class="py-3 px-6">Diajukan Oleh</th>
                    <th class="py-3 px-6 w-36">Waktu Pengajuan</th>
                    <th class="py-3 px-6">Catatan Pengaju</th>
                    <th class="py-3 px-6 w-52 text-center">Tindakan Otorisasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($pendingRequests as $req)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-medium text-slate-900 capitalize">
                        {{ str_replace('_', ' ', $req->document_type) }}
                    </td>
                    <td class="py-3.5 px-6 font-mono text-xs font-bold text-blue-600">
                        #{{ $req->document_id }}
                    </td>
                    <td class="py-3.5 px-6">
                        <div class="font-medium text-slate-900">{{ $req->requester->name ?? '-' }}</div>
                        <div class="text-xs text-slate-400">{{ $req->requester->email ?? '' }}</div>
                    </td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                        {{ $req->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600">
                        {{ $req->notes ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        @if($req->requester_id === auth()->id())
                            <span class="text-xs text-amber-600 italic bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                                Menunggu Reviewer Lain
                            </span>
                        @else
                            <div class="flex items-center justify-center gap-2">
                                <form method="POST" action="{{ route('workflow.approve', $req) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs">
                                        Setujui
                                    </button>
                                </form>
                                <button type="button" @click="activeRejectUrl = '{{ route('workflow.reject', $req) }}'; showRejectModal = true" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold">
                                    Tolak
                                </button>
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Tidak ada antrean tugas persetujuan saat ini. Semua dokumen telah diproses.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRejectModal" @click="showRejectModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showRejectModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <form method="POST" :action="activeRejectUrl">
                    @csrf
                    <div class="bg-rose-50 px-6 py-4 border-b border-rose-100 flex items-center justify-between">
                        <h3 class="text-base font-bold text-rose-900">Tolak Permintaan Persetujuan</h3>
                        <button type="button" @click="showRejectModal = false" class="text-rose-400 hover:text-rose-600">✕</button>
                    </div>
                    <div class="p-6 space-y-3">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Alasan Penolakan (Wajib)</label>
                        <textarea name="notes" rows="3" required placeholder="Tuliskan alasan mengapa dokumen ini ditolak..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
                    </div>
                    <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-sm font-semibold shadow-sm">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approval History Section -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 text-sm">Riwayat Keputusan Otorisasi Terkini</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-32">Waktu Selesai</th>
                    <th class="py-3 px-6 w-36">Tipe Dokumen</th>
                    <th class="py-3 px-6 w-32">ID Ref</th>
                    <th class="py-3 px-6">Diajukan Oleh</th>
                    <th class="py-3 px-6">Reviewer / Otorisator</th>
                    <th class="py-3 px-6 w-28 text-center">Keputusan</th>
                    <th class="py-3 px-6">Catatan Otorisator</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($historyRequests as $hist)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                        {{ $hist->updated_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="py-3.5 px-6 font-medium text-slate-900 capitalize">
                        {{ str_replace('_', ' ', $hist->document_type) }}
                    </td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                        #{{ $hist->document_id }}
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-800">
                        {{ $hist->requester->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs font-medium text-slate-900">
                        {{ $hist->approver->name ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        @if($hist->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Disetujui</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ditolak</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 text-xs text-slate-600">
                        {{ $hist->notes ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        Belum ada riwayat persetujuan dokumen sebelumnya.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
