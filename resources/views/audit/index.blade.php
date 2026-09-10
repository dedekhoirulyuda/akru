@extends('layouts.app')

@section('title', 'Rekam Jejak Audit (Audit Trail Explorer) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rekam Jejak Audit (Audit Trail Explorer)</h1>
        <p class="text-sm text-slate-500 mt-0.5">Catatan forensik append-only atas seluruh aktivitas pengguna, perubahan data, dan eksekusi sistem</p>
    </div>
</div>
@endsection

@section('content')
<!-- Filter Box -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Pengguna</label>
            <select name="user_id" class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Semua Pengguna --</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Aksi</label>
            <select name="action" class="w-full text-sm rounded-lg border-slate-300">
                <option value="">-- Semua Aksi --</option>
                <option value="created" {{ $action === 'created' ? 'selected' : '' }}>Created (Dibuat)</option>
                <option value="posted" {{ $action === 'posted' ? 'selected' : '' }}>Posted (Dibukukan)</option>
                <option value="reversed" {{ $action === 'reversed' ? 'selected' : '' }}>Reversed (Dibalik)</option>
                <option value="updated" {{ $action === 'updated' ? 'selected' : '' }}>Updated (Diubah)</option>
                <option value="deleted" {{ $action === 'deleted' ? 'selected' : '' }}>Deleted (Dihapus)</option>
                <option value="approved" {{ $action === 'approved' ? 'selected' : '' }}>Approved (Disetujui)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full text-sm rounded-lg border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full text-sm rounded-lg border-slate-300">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">Filter</button>
            <a href="{{ route('audit.index') }}" class="px-3 py-2 border border-slate-300 hover:bg-slate-50 text-slate-600 rounded-lg text-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Immutability Security Banner -->
<div class="p-4 rounded-xl border border-slate-200 bg-slate-50 mb-6 flex items-center justify-between text-xs text-slate-600">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <span><strong>Append-Only Integrity:</strong> Seluruh baris log dilindungi di level arsitektur dan basis data. Tidak dapat dimodifikasi atau dihapus oleh siapapun termasuk administrator.</span>
    </div>
    <span class="font-mono text-[11px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded">WORM ARCHITECTURE</span>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ selectedLog: null, showModal: false }">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                    <th class="py-3 px-6 w-36">Waktu (WIB)</th>
                    <th class="py-3 px-6 w-44">Pengguna</th>
                    <th class="py-3 px-6 w-28 text-center">Aksi</th>
                    <th class="py-3 px-6 w-44">Entitas Terkait</th>
                    <th class="py-3 px-6 w-36 font-mono text-xs">IP Address</th>
                    <th class="py-3 px-6">Perubahan (Diff)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-600">
                        {{ $log->created_at?->format('d/m/Y H:i:s') ?? '-' }}
                    </td>
                    <td class="py-3.5 px-6">
                        <div class="font-medium text-slate-900">{{ $log->user->name ?? 'Sistem' }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ $log->user->email ?? '-' }}</div>
                    </td>
                    <td class="py-3.5 px-6 text-center">
                        @if($log->action === 'posted')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">POSTED</span>
                        @elseif($log->action === 'created')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">CREATED</span>
                        @elseif($log->action === 'reversed')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">REVERSED</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">{{ strtoupper($log->action) }}</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-6 font-mono text-xs">
                        <span class="font-semibold text-slate-800">{{ class_basename($log->entity_type) }}</span>
                        <span class="text-slate-400">#{{ $log->entity_id ?? '-' }}</span>
                    </td>
                    <td class="py-3.5 px-6 font-mono text-xs text-slate-500">
                        {{ $log->ip_address ?? '127.0.0.1' }}
                    </td>
                    <td class="py-3.5 px-6 text-xs">
                        <button type="button" @click="selectedLog = {{ json_encode($log) }}; showModal = true" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[11px] transition-colors">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Lihat Payload
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        Tidak ada catatan log audit yang cocok dengan filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $logs->links() }}
    </div>
    @endif

    <!-- Diff Payload Modal -->
    <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" @click="showModal = false" class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showModal" class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Rincian Nilai Payload Audit</h3>
                    <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-semibold uppercase">Waktu:</span>
                            <span class="font-mono text-slate-800 ml-1" x-text="selectedLog?.created_at"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold uppercase">Correlation ID:</span>
                            <span class="font-mono text-slate-800 ml-1 truncate block" x-text="selectedLog?.correlation_id"></span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Nilai Baru / Parameter Ditambahkan (New Values)</div>
                        <pre class="bg-slate-900 text-emerald-400 p-3 rounded-lg text-xs font-mono overflow-x-auto max-h-48" x-text="JSON.stringify(selectedLog?.new_values, null, 2) || 'null'"></pre>
                    </div>
                    <div x-show="selectedLog?.old_values">
                        <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Nilai Lama Sebelum Perubahan (Old Values)</div>
                        <pre class="bg-slate-900 text-rose-400 p-3 rounded-lg text-xs font-mono overflow-x-auto max-h-48" x-text="JSON.stringify(selectedLog?.old_values, null, 2) || 'null'"></pre>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="showModal = false" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-700">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
