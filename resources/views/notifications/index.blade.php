@extends('layouts.app')

@section('title', 'Pusat Notifikasi & Peringatan — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pusat Notifikasi & Peringatan</h1>
        <p class="text-sm text-slate-500 mt-0.5">Pemberitahuan real-time untuk antrean persetujuan, faktur jatuh tempo, dan peringatan kepatuhan</p>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl space-y-4">
    @forelse($notifications as $notif)
    <div class="bg-white rounded-xl shadow-sm border {{ $notif->is_read ? 'border-slate-200' : 'border-blue-200 bg-blue-50/20' }} p-5 flex items-start justify-between gap-4 transition-all">
        <div class="flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $notif->type === 'approval' ? 'bg-amber-100 text-amber-600' : ($notif->type === 'due_date' ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600') }}">
                @if($notif->type === 'approval')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif($notif->type === 'due_date')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-900">{{ $notif->title }}</h3>
                    @if(!$notif->is_read)
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $notif->message }}</p>
                <div class="flex items-center gap-4 mt-3">
                    <span class="text-[11px] font-mono text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                    @if($notif->action_url)
                    <a href="{{ $notif->action_url }}" class="text-xs text-blue-600 font-semibold hover:underline">Buka Tautan Tindakan →</a>
                    @endif
                </div>
            </div>
        </div>
        @if(!$notif->is_read)
        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
            @csrf
            <button type="submit" class="text-xs text-slate-400 hover:text-slate-700 whitespace-nowrap p-1 rounded hover:bg-slate-100">
                Tandai Dibaca
            </button>
        </form>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
        Semua notifikasi dan peringatan telah diselesaikan.
    </div>
    @endforelse

    @if($notifications->hasPages())
    <div class="pt-2">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
