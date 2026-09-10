@extends('platform.layouts.app')

@section('title', 'Koneksi API & Integrasi Platform — AKRU SaaS')

@section('content')
<div class="space-y-8" x-data="{ activeTab: 'ai', tokenModalOpen: false }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Koneksi API & Integrasi Platform</h1>
            <p class="text-xs text-slate-400">Pusat kendali API keys eksternal (AI, Payment Gateway, Coretax DJP, WhatsApp) dan pengelolaan API Token Developer.</p>
        </div>
    </div>

    {{-- Banner for Newly Created Token --}}
    @if(session('new_token_plain'))
    <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-500/20 via-indigo-500/20 to-amber-500/20 border border-amber-500/40 shadow-2xl space-y-3">
        <div class="flex items-center gap-2 text-amber-400 font-extrabold text-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Simpan Token Baru Ini Sekarang!</span>
        </div>
        <p class="text-xs text-slate-300">Token <strong>{{ session('new_token_name') }}</strong> hanya ditampilkan satu kali ini saja dan di-hash aman di server kami.</p>
        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-400 select-all">
            <span class="flex-1 break-all">{{ session('new_token_plain') }}</span>
        </div>
    </div>
    @endif

    {{-- Category Tabs --}}
    <div class="flex items-center gap-2 border-b border-slate-800 pb-2 overflow-x-auto text-xs font-bold">
        <button @click="activeTab = 'ai'" type="button" 
                :class="activeTab === 'ai' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-900'"
                class="px-4 py-2 rounded-xl border border-slate-800 transition-all flex items-center gap-2 cursor-pointer shrink-0">
            <span>✨ AI Provider (Gemini / OpenAI)</span>
        </button>

        <button @click="activeTab = 'payment'" type="button" 
                :class="activeTab === 'payment' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-900'"
                class="px-4 py-2 rounded-xl border border-slate-800 transition-all flex items-center gap-2 cursor-pointer shrink-0">
            <span>💳 Payment Gateway (Midtrans / Xendit)</span>
        </button>

        <button @click="activeTab = 'coretax'" type="button" 
                :class="activeTab === 'coretax' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-900'"
                class="px-4 py-2 rounded-xl border border-slate-800 transition-all flex items-center gap-2 cursor-pointer shrink-0">
            <span>🏛️ Coretax DJP Integration</span>
        </button>

        <button @click="activeTab = 'notification'" type="button" 
                :class="activeTab === 'notification' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-900'"
                class="px-4 py-2 rounded-xl border border-slate-800 transition-all flex items-center gap-2 cursor-pointer shrink-0">
            <span>💬 Notifikasi (WhatsApp & Email)</span>
        </button>

        <button @click="activeTab = 'tokens'" type="button" 
                :class="activeTab === 'tokens' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-900'"
                class="px-4 py-2 rounded-xl border border-slate-800 transition-all flex items-center gap-2 cursor-pointer shrink-0">
            <span>🔑 API Tokens Developer</span>
        </button>
    </div>

    {{-- TAB 1: AI Provider --}}
    <div x-show="activeTab === 'ai'" class="space-y-6">
        <form method="POST" action="{{ route('platform.api.settings') }}" class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-6">
            @csrf
            <input type="hidden" name="group" value="ai">

            <div class="border-b border-slate-800 pb-4">
                <h3 class="text-sm font-bold text-white">Konfigurasi Mesin Artificial Intelligence</h3>
                <p class="text-xs text-slate-400">Pusat API key untuk fitur Asisten AI, Rekomendasi COA, dan Audit Keuangan Otomatis.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">AI Provider Utama</label>
                    <select name="ai_provider" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200">
                        <option value="gemini" {{ ($settings['ai_provider']->value ?? 'gemini') === 'gemini' ? 'selected' : '' }}>Google Gemini (Direkomendasikan)</option>
                        <option value="openai" {{ ($settings['ai_provider']->value ?? '') === 'openai' ? 'selected' : '' }}>OpenAI (ChatGPT / GPT-4o)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Model Default</label>
                    <input type="text" name="ai_default_model" value="{{ $settings['ai_default_model']->value ?? 'gemini-flash-latest' }}" placeholder="gemini-flash-latest atau gpt-4o" 
                        class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="space-y-1 md:col-span-2">
                    <label class="font-semibold text-slate-300">Google Gemini API Key</label>
                    <input type="password" name="ai_gemini_api_key" value="{{ !empty($settings['ai_gemini_api_key']->value) ? '••••••••••••••••••••••••' : '' }}" placeholder="AIzaSy..." 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                    <span class="text-[10px] text-slate-500">Biarkan titik-titik jika tidak ingin mengubah kunci tersimpan.</span>
                </div>

                <div class="space-y-1 md:col-span-2">
                    <label class="font-semibold text-slate-300">OpenAI API Key (Opsional / Fallback)</label>
                    <input type="password" name="ai_openai_api_key" value="{{ !empty($settings['ai_openai_api_key']->value) ? '••••••••••••••••••••••••' : '' }}" placeholder="sk-..." 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                    Simpan Konfigurasi AI
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 2: Payment Gateway --}}
    <div x-show="activeTab === 'payment'" class="space-y-6">
        <form method="POST" action="{{ route('platform.api.settings') }}" class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-6">
            @csrf
            <input type="hidden" name="group" value="payment">

            <div class="border-b border-slate-800 pb-4">
                <h3 class="text-sm font-bold text-white">Payment Gateway Terpusat</h3>
                <p class="text-xs text-slate-400">Integrasi otomatis penagihan invoice langganan tenant via Virtual Account, QRIS, & Kartu Kredit.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Gateway Utama</label>
                    <select name="payment_gateway" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200">
                        <option value="midtrans" {{ ($settings['payment_gateway']->value ?? 'midtrans') === 'midtrans' ? 'selected' : '' }}>Midtrans Snap</option>
                        <option value="xendit" {{ ($settings['payment_gateway']->value ?? '') === 'xendit' ? 'selected' : '' }}>Xendit Invoice</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Lingkungan (Environment)</label>
                    <select name="midtrans_environment" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200">
                        <option value="sandbox" {{ ($settings['midtrans_environment']->value ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Pengujian)</option>
                        <option value="production" {{ ($settings['midtrans_environment']->value ?? '') === 'production' ? 'selected' : '' }}>Production (Live Transaksi Riil)</option>
                    </select>
                </div>

                <div class="space-y-1 md:col-span-2">
                    <label class="font-semibold text-slate-300">Midtrans Server Key</label>
                    <input type="password" name="midtrans_server_key" value="{{ !empty($settings['midtrans_server_key']->value) ? '••••••••••••••••••••••••' : '' }}" placeholder="SB-Mid-server-..." 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>

                <div class="space-y-1 md:col-span-2">
                    <label class="font-semibold text-slate-300">Midtrans Client Key</label>
                    <input type="text" name="midtrans_client_key" value="{{ $settings['midtrans_client_key']->value ?? '' }}" placeholder="SB-Mid-client-..." 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                    Simpan Payment Gateway
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 3: Coretax DJP --}}
    <div x-show="activeTab === 'coretax'" class="space-y-6">
        <form method="POST" action="{{ route('platform.api.settings') }}" class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-6">
            @csrf
            <input type="hidden" name="group" value="coretax">

            <div class="border-b border-slate-800 pb-4">
                <h3 class="text-sm font-bold text-white">Integrasi Coretax DJP & e-Faktur</h3>
                <p class="text-xs text-slate-400">Koneksi gateway e-Faktur Coretax Host-to-Host (H2H) dengan Direktorat Jenderal Pajak RI.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Environment</label>
                    <select name="coretax_environment" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200">
                        <option value="sandbox">Uji Coba (DJP Sandbox Simulator)</option>
                        <option value="production">Production Live (Host-to-Host)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">API Endpoint Host URL</label>
                    <input type="text" name="coretax_api_endpoint" value="{{ $settings['coretax_api_endpoint']->value ?? 'https://api-coretax.pajak.go.id/v1' }}" 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">PJAP / Platform Client ID</label>
                    <input type="text" name="coretax_client_id" value="{{ $settings['coretax_client_id']->value ?? '' }}" placeholder="AKRU-DJP-CLIENT-01" 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Client Secret</label>
                    <input type="password" name="coretax_client_secret" value="{{ !empty($settings['coretax_client_secret']->value) ? '••••••••••••••••••••••••' : '' }}" 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                    Simpan Coretax Config
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 4: WhatsApp & Email --}}
    <div x-show="activeTab === 'notification'" class="space-y-6">
        <form method="POST" action="{{ route('platform.api.settings') }}" class="rounded-2xl bg-slate-950 border border-slate-800 p-6 shadow-xl space-y-6">
            @csrf
            <input type="hidden" name="group" value="notification">

            <div class="border-b border-slate-800 pb-4">
                <h3 class="text-sm font-bold text-white">Gateway Notifikasi WhatsApp & Email Transaksional</h3>
                <p class="text-xs text-slate-400">Kirim pengingat invoice, notifikasi persetujuan transaksi, dan kode OTP otomatis ke nomor pengguna.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Penyedia WhatsApp Gateway</label>
                    <select name="wa_gateway_provider" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200">
                        <option value="fonnte">Fonnte (Indonesia)</option>
                        <option value="wablas">Wablas Gateway</option>
                        <option value="twilio">Twilio WhatsApp API</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Nomor Pengirim Terdaftar</label>
                    <input type="text" name="wa_sender_number" value="{{ $settings['wa_sender_number']->value ?? '0812XXXXXXXX' }}" 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>

                <div class="space-y-1 md:col-span-2">
                    <label class="font-semibold text-slate-300">WhatsApp API Device Token</label>
                    <input type="password" name="wa_api_token" value="{{ !empty($settings['wa_api_token']->value) ? '••••••••••••••••••••••••' : '' }}" placeholder="Token otorisasi WhatsApp API" 
                           class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-slate-200 font-mono">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                    Simpan Gateway Notifikasi
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 5: Developer API Tokens --}}
    <div x-show="activeTab === 'tokens'" class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-white">Platform API Tokens (Partner & Developer)</h3>
                <p class="text-xs text-slate-400">Token otentikasi Bearer untuk integrasi pihak ketiga, mobile app, atau koneksi API eksternal.</p>
            </div>
            <button @click="tokenModalOpen = true" type="button" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Buat Token Baru</span>
            </button>
        </div>

        <div class="rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Nama Token / Partner</th>
                            <th class="px-4 py-3.5">Hak Izin (Abilities)</th>
                            <th class="px-4 py-3.5">Dibuat Oleh</th>
                            <th class="px-4 py-3.5">Terakhir Digunakan</th>
                            <th class="px-4 py-3.5">Kedaluwarsa</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse($tokens as $t)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-100 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>{{ $t->name }}</span>
                            </td>
                            <td class="px-4 py-4 text-slate-400 font-mono text-[11px]">
                                {{ implode(', ', $t->abilities ?? ['read', 'write']) }}
                            </td>
                            <td class="px-4 py-4 text-slate-300">
                                {{ $t->creator?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-4 text-slate-400 font-mono text-[11px]">
                                {{ $t->last_used_at?->diffForHumans() ?? 'Belum pernah' }}
                            </td>
                            <td class="px-4 py-4 text-slate-400 font-mono text-[11px]">
                                {{ $t->expires_at?->format('d M Y') ?? 'Tanpa Batas' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="{{ route('platform.api.tokens.destroy', $t->id) }}" onsubmit="return confirm('Cabut izin token ini sekarang?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 text-[11px] font-bold transition-all cursor-pointer">
                                        Cabut Token
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">Belum ada token API pihak ketiga yang aktif.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Generate Token --}}
    <div x-show="tokenModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.outside="tokenModalOpen = false" class="w-full max-w-sm rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Buat API Token Baru</h3>
                <button @click="tokenModalOpen = false" class="text-slate-400 hover:text-white cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('platform.api.tokens.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Nama Token / Klien Integrasi *</label>
                    <input type="text" name="name" required placeholder="Contoh: Mobile App Android / POS Integration" 
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="font-semibold text-slate-300">Masa Berlaku Token</label>
                    <select name="expires_days" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 focus:outline-none focus:border-indigo-500">
                        <option value="30">30 Hari</option>
                        <option value="90">90 Hari</option>
                        <option value="365">1 Tahun</option>
                        <option value="">Tanpa Batas (Selamanya)</option>
                    </select>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button @click="tokenModalOpen = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer">
                        Generate Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
