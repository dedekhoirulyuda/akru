@extends('layouts.app')

@section('title', 'Asisten & Otomasi Cerdas (AKRU AI) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold text-slate-900">Asisten & Otomasi Cerdas (AKRU AI)</h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-pulse"></span>
                Add-on Aktif
            </span>
            @if(isset($aiQuota) && !$aiQuota['is_unlimited'])
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($aiQuota['remaining'] ?? 0) > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }} flex items-center gap-1.5 shadow-xs"
                      title="Kuota chat AI harian untuk paket {{ $aiQuota['plan_name'] }}">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Trial Kuota: <strong>{{ $aiQuota['used'] }}/{{ $aiQuota['limit'] }}</strong> chat hari ini</span>
                </span>
            @elseif(isset($aiQuota) && $aiQuota['is_unlimited'])
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Unlimited AI Copilot</span>
                </span>
            @endif
        </div>
        <p class="text-sm text-slate-500 mt-0.5">Asisten keuangan interaktif, deteksi anomali real-time, dan rekomendasi akun COA otomatis</p>
    </div>
    @if(isset($aiQuota) && !$aiQuota['is_unlimited'])
    <div class="flex items-center gap-2">
        <a href="{{ route('subscription.index') }}" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center gap-1.5 transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Upgrade Unlimited</span>
        </a>
    </div>
    @endif
</div>
@endsection

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: 'chat',
    queryText: '', 
    suggestedResult: null, 
    suggestLoading: false,
    chatInput: '',
    chatLoading: false,
    quotaInfo: {{ isset($aiQuota) ? Js::from($aiQuota) : 'null' }},
    chatMessages: [
        {
            role: 'assistant',
            time: 'Baru saja',
            content: 'Halo! Saya **AKRU AI**, asisten keuangan dan akuntansi cerdas entitas Anda. Anda dapat menanyakan posisi kas & bank, total omzet penjualan, piutang jatuh tempo, rekomendasi akun COA, atau konsultasi pencatatan jurnal keuangan. Ada yang bisa saya bantu?'
        }
    ],
    formatMessage(text) {
        if (!text) return '';
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/### (.*?)\n/g, '<h4 class=\'font-bold text-sm text-purple-950 mb-2 mt-2\'>$1</h4>')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href=\'$2\' class=\'inline-flex items-center gap-1 text-purple-700 hover:text-purple-900 font-bold underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200 mt-1\'>$1 &rarr;</a>');
    },
    askAI(question) {
        this.chatInput = question;
        this.sendChatMessage();
    },
    sendChatMessage() {
        if (!this.chatInput.trim() || this.chatLoading) return;
        const msg = this.chatInput.trim();
        this.chatMessages.push({
            role: 'user',
            time: 'Baru saja',
            content: msg
        });
        this.chatInput = '';
        this.chatLoading = true;

        fetch('/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: msg })
        })
        .then(res => res.json())
        .then(data => {
            this.chatLoading = false;
            if (data.quota) {
                this.quotaInfo = data.quota;
            }
            this.chatMessages.push({
                role: 'assistant',
                time: 'Baru saja',
                content: data.reply || 'Maaf, terjadi kendala saat memproses jawaban.'
            });
            this.$nextTick(() => {
                const el = document.getElementById('chat-scroll-container');
                if (el) el.scrollTop = el.scrollHeight;
            });
        })
        .catch(err => {
            this.chatLoading = false;
            this.chatMessages.push({
                role: 'assistant',
                time: 'Baru saja',
                content: 'Terjadi gangguan jaringan saat menghubungi asisten AI. Silakan coba kembali.'
            });
        });
    },
    checkCategory(sampleText) {
        if (sampleText) this.queryText = sampleText;
        if (!this.queryText.trim()) return;
        this.suggestLoading = true;
        this.suggestedResult = null;

        fetch('/ai/suggest-category?query=' + encodeURIComponent(this.queryText.trim()))
            .then(res => res.json())
            .then(data => {
                this.suggestLoading = false;
                this.suggestedResult = data;
            })
            .catch(() => {
                this.suggestLoading = false;
            });
    }
}">

    <!-- Privacy & Governance Compliance Banner -->
    <div class="p-4 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900 text-xs flex items-start gap-3 shadow-sm">
        <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <div>
            <strong class="font-bold text-purple-800">Prinsip Kendali Manusia (Blueprint §1.1A & §2.13 — Human-in-the-Loop):</strong>
            <p class="mt-0.5 leading-relaxed">
                AKRU AI bertindak sebagai asisten konsultasi, analisis prediktif, pendeteksi anomali, dan pemberi rekomendasi akun. Seluruh keputusan pembukuan, posting jurnal memorial, dan pelaporan perpajakan final tetap memerlukan tinjauan dan otorisasi manajemen manusia.
            </p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200">
        <button type="button" @click="activeTab = 'chat'" 
                :class="activeTab === 'chat' ? 'border-purple-600 text-purple-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 py-3 px-4 border-b-2 text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Tanya AKRU AI (Chatbot Interaktif)
        </button>
        <button type="button" @click="activeTab = 'categorizer'" 
                :class="activeTab === 'categorizer' ? 'border-purple-600 text-purple-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 py-3 px-4 border-b-2 text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Rekomendasi Akun COA Cerdas
        </button>
        <button type="button" @click="activeTab = 'anomalies'" 
                :class="activeTab === 'anomalies' ? 'border-purple-600 text-purple-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 py-3 px-4 border-b-2 text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Pindai Anomali & Risiko
        </button>
    </div>

    <!-- TAB 1: CHATBOT INTERAKTIF -->
    <div x-show="activeTab === 'chat'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[580px]">
        <!-- Chat Header -->
        <div class="p-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center text-white shadow-sm font-bold text-xs">
                    AI
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 leading-tight">Asisten Keuangan AKRU OS</h2>
                    <p class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Terhubung ke Buku Besar & Basis Data Entitas
                    </p>
                </div>
            <span class="text-xs text-slate-400 font-mono flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full {{ !empty($aiQuota['is_trial']) ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                <span x-text="'Model: ' + (quotaInfo?.engine_name || '{{ $aiQuota['engine_name'] ?? 'AKRU FinLogic Core' }}')">
                    Model: {{ $aiQuota['engine_name'] ?? 'AKRU FinLogic Core' }}
                </span>
            </span>
        </div>

        <!-- Chat Message Area -->
        <div id="chat-scroll-container" class="flex-1 overflow-y-auto p-5 space-y-4 bg-slate-50/30">
            <template x-for="(msg, idx) in chatMessages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user' ? 'bg-purple-600 text-white rounded-2xl rounded-tr-sm max-w-[80%] p-4 shadow-sm' : 'bg-white border border-slate-200 text-slate-800 rounded-2xl rounded-tl-sm max-w-[85%] p-4 shadow-sm'">
                        <div class="text-[11px] font-bold mb-1 opacity-75" x-text="msg.role === 'user' ? 'Anda' : 'AKRU AI'"></div>
                        <div class="text-xs leading-relaxed whitespace-pre-line font-sans" x-html="formatMessage(msg.content)"></div>
                    </div>
                </div>
            </template>
            <template x-if="chatLoading">
                <div class="flex justify-start">
                    <div class="bg-white border border-slate-200 text-slate-600 rounded-2xl p-4 shadow-sm text-xs flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-purple-600 animate-bounce"></div>
                        <div class="w-2 h-2 rounded-full bg-purple-600 animate-bounce [animation-delay:-.3s]"></div>
                        <div class="w-2 h-2 rounded-full bg-purple-600 animate-bounce [animation-delay:-.5s]"></div>
                        <span class="text-slate-400 ml-1">Menganalisis data keuangan entitas...</span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Trial Quota Notification if limit reached -->
        <template x-if="quotaInfo && !quotaInfo.is_unlimited && quotaInfo.remaining <= 0">
            <div class="px-4 py-2.5 bg-gradient-to-r from-amber-50 to-orange-50 border-t border-amber-200 text-amber-900 text-xs flex flex-col sm:flex-row items-center justify-between gap-3 shadow-inner">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Batas kuota chat Free Trial (<strong x-text="quotaInfo.used + '/' + quotaInfo.limit"></strong>) telah tercapai hari ini. Kuota di-reset otomatis besok pukul 00:00 WIB.</span>
                </div>
                <a href="{{ route('subscription.index') }}" class="px-3 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs whitespace-nowrap shadow-xs transition-colors flex items-center gap-1">
                    <span>Upgrade Unlimited</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </template>

        <!-- Quick Prompt Chips -->
        <div class="p-3 bg-white border-t border-slate-100 flex items-center gap-2 overflow-x-auto text-xs">
            <span class="text-slate-400 whitespace-nowrap text-[11px] font-semibold uppercase">Pertanyaan Cepat:</span>
            <button type="button" @click="askAI('Berapa saldo kas dan bank saat ini?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                💰 Saldo Kas & Bank
            </button>
            <button type="button" @click="askAI('Berapa total omzet penjualan bulan ini?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                📈 Total Omzet Bulan Ini
            </button>
            <button type="button" @click="askAI('Apakah ada piutang pelanggan yang jatuh tempo?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                ⏳ Piutang Jatuh Tempo
            </button>
            <button type="button" @click="askAI('Beli bensin dinas masuk akun apa?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                ⛽ Akun Bensin Dinas
            </button>
            <button type="button" @click="askAI('Berapa tarif pajak PPh 21/23?')" class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                ⚖️ Tarif PPh 21 & 23
            </button>
            <button type="button" @click="askAI('Bagaimana status kepatuhan pajak Coretax?') " class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-purple-100 text-slate-700 hover:text-purple-700 whitespace-nowrap transition-colors border border-slate-200">
                📑 Kepatuhan Pajak
            </button>
        </div>

        <!-- Chat Input Form -->
        <div class="p-3 bg-white border-t border-slate-200">
            <form @submit.prevent="sendChatMessage()" class="flex items-center gap-2">
                <input type="text" x-model="chatInput" placeholder="Ketik pertanyaan keuangan Anda (contoh: Berapa omzet minggu ini? / Beli laptop kantor masuk akun mana?)..." 
                       class="flex-1 text-xs sm:text-sm rounded-lg border-slate-300 focus:ring-purple-500 focus:border-purple-500 px-3.5 py-2.5">
                <button type="submit" :disabled="chatLoading || !chatInput.trim()" 
                        class="px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-semibold text-xs sm:text-sm flex items-center gap-1.5 transition-colors shadow-sm">
                    <span>Kirim</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- TAB 2: REKOMENDASI AKUN COA CERDAS -->
    <div x-show="activeTab === 'categorizer'" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="max-w-2xl">
                <h2 class="font-bold text-slate-900 text-base mb-1">Rekomendasi Akun COA & Jurnal Otomatis</h2>
                <p class="text-xs text-slate-500 mb-5 leading-relaxed">
                    Ketik uraian transaksi apapun dalam bahasa Indonesia sehari-hari. Mesin semantik AKRU AI akan mencocokkan kata kunci ke Bagan Akun Standar (COA), memberikan alasan akuntansi (PSAK), serta menyarankan posisi Debit dan Kredit secara instan.
                </p>

                <!-- Input box -->
                <div class="flex items-center gap-2.5">
                    <input type="text" x-model="queryText" @keydown.enter.prevent="checkCategory()"
                           placeholder="Contoh: Tagihan internet kantor, beli bensin dinas, servis AC ruko, beli ATK..." 
                           class="flex-1 text-sm rounded-lg border-slate-300 focus:ring-purple-500 focus:border-purple-500 px-3.5 py-2.5">
                    <button type="button" @click="checkCategory()" :disabled="suggestLoading"
                            class="px-5 py-2.5 rounded-lg bg-purple-600 text-white font-semibold hover:bg-purple-700 shadow-sm text-sm transition-colors flex items-center gap-2">
                        <template x-if="suggestLoading">
                            <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        </template>
                        <span>Cari Akun</span>
                    </button>
                </div>

                <!-- Example Chips -->
                <div class="flex flex-wrap items-center gap-2 mt-3 text-xs text-slate-500">
                    <span class="font-semibold text-slate-400">Contoh Cepat:</span>
                    <button type="button" @click="checkCategory('Tagihan internet kantor')" class="text-purple-600 hover:underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        Tagihan internet kantor
                    </button>
                    <button type="button" @click="checkCategory('Beli bensin dinas operasional')" class="text-purple-600 hover:underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        Beli bensin dinas operasional
                    </button>
                    <button type="button" @click="checkCategory('Beli kertas HVS dan ATK')" class="text-purple-600 hover:underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        Beli kertas HVS dan ATK
                    </button>
                    <button type="button" @click="checkCategory('Servis AC dan perawatan kantor')" class="text-purple-600 hover:underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        Servis AC dan perawatan kantor
                    </button>
                    <button type="button" @click="checkCategory('Beli laptop kerja i7')" class="text-purple-600 hover:underline bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                        Beli laptop kerja i7
                    </button>
                </div>
            </div>

            <!-- Result Card -->
            <template x-if="suggestedResult">
                <div class="mt-6 p-5 bg-gradient-to-r from-purple-50/80 to-indigo-50/50 rounded-xl border border-purple-200 max-w-2xl">
                    <template x-if="suggestedResult.suggested_account">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] font-bold text-purple-700 uppercase tracking-wider">Rekomendasi Akun COA Terpilih:</span>
                                    <h3 class="text-base font-bold text-slate-900 mt-0.5" x-text="suggestedResult.suggested_account.code + ' — ' + suggestedResult.suggested_account.name"></h3>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-purple-200 text-purple-900" x-text="'Confidence: ' + suggestedResult.confidence"></span>
                            </div>

                            <div class="p-3 bg-white/80 rounded-lg border border-purple-100 text-xs text-slate-700 space-y-1.5">
                                <div>
                                    <span class="font-semibold text-slate-900">Alasan Akuntansi (PSAK/SAK):</span>
                                    <p class="mt-0.5" x-text="suggestedResult.reasoning"></p>
                                </div>
                                <div class="pt-1 border-t border-slate-100 text-purple-800 font-medium">
                                    <span class="font-semibold text-slate-900">Saran Jurnal:</span>
                                    <span class="font-mono text-[11px] ml-1" x-text="suggestedResult.journal_hint"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!suggestedResult.suggested_account">
                        <div class="text-xs text-slate-600">
                            Tidak ada akun yang cocok persis. Silakan pilih akun beban operasional secara manual saat pencatatan transaksi.
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- TAB 3: ANOMALY DETECTION ENGINE -->
    <div x-show="activeTab === 'anomalies'" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-sm">Hasil Pemindaian Anomali & Risiko (Anomaly Detection)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Analisis heuristik terhadap potensi salah input, duplikasi transaksi, dan nominal tidak wajar</p>
            </div>
            <span class="px-2.5 py-1 rounded text-[11px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                Pemeriksaan Otomatis Aktif
            </span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($anomalies as $anomaly)
            <div class="p-5 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0 {{ $anomaly['severity'] === 'Tinggi' ? 'bg-rose-500' : ($anomaly['severity'] === 'Sedang' ? 'bg-amber-500' : 'bg-emerald-500') }}"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-900">{{ $anomaly['type'] }}</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $anomaly['severity'] === 'Tinggi' ? 'bg-rose-50 text-rose-700' : ($anomaly['severity'] === 'Sedang' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                                Severity: {{ $anomaly['severity'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-700 mt-1">{{ $anomaly['description'] }}</p>
                        <p class="text-xs text-blue-600 font-medium mt-1.5">Saran Tindakan: {{ $anomaly['recommendation'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
