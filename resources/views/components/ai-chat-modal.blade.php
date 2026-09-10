{{-- Floating AKRU AI Chat Dialog Modal --}}
<div x-data="akruAiWidget()" class="relative" x-cloak>
    {{-- Floating Action Button (Bottom-Right) --}}
    <div class="fixed bottom-6 right-6 z-50 flex items-center gap-1.5" x-data="{ isCompact: localStorage.getItem('akru_ai_compact') === 'true' }">
        <button @click="toggleModal()" 
                type="button"
                id="btn-open-ai-modal"
                class="group relative flex items-center gap-2 px-3.5 py-2.5 bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 hover:from-purple-500 hover:to-indigo-500 text-white rounded-full shadow-lg hover:shadow-purple-500/30 hover:scale-105 active:scale-95 transition-all duration-200 border border-purple-400/40 cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 focus:ring-offset-slate-900"
                :title="isCompact ? 'Buka Tanya AKRU AI' : 'Asisten Keuangan & Pajak Pintar'"
                aria-label="Buka Asisten AKRU AI">
            {{-- Pulsing Live Online Indicator --}}
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
            </span>

            {{-- Icon (Switches between Sparkle and Close) --}}
            <template x-if="!isOpen">
                <svg class="w-4.5 h-4.5 text-purple-200 group-hover:rotate-12 transition-transform duration-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </template>
            <template x-if="isOpen">
                <svg class="w-4.5 h-4.5 text-purple-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>

            <span x-show="!isCompact || isOpen" class="text-xs font-semibold tracking-wide whitespace-nowrap" x-text="isOpen ? 'Tutup Asisten' : 'Tanya AKRU AI'"></span>
        </button>

        {{-- Toggle compact mode (Shrink to mini icon button) --}}
        <template x-if="!isOpen">
            <button @click="isCompact = !isCompact; localStorage.setItem('akru_ai_compact', isCompact)"
                    type="button"
                    :title="isCompact ? 'Perluas tombol teks' : 'Kecilkan tombol jadi ikon bulat'"
                    class="p-1.5 rounded-full bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/80 shadow-md transition-all text-xs cursor-pointer opacity-70 hover:opacity-100">
                <svg x-show="!isCompact" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Kecilkan"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                <svg x-show="isCompact" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Perluas"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
        </template>
    </div>

    {{-- Chat Dialog Modal Window --}}
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         id="akru-ai-modal-window"
         class="fixed bottom-20 right-6 z-50 w-[430px] max-w-[calc(100vw-2rem)] h-[600px] max-h-[calc(100vh-6.5rem)] flex flex-col bg-slate-900/95 backdrop-blur-xl border border-slate-700/80 rounded-2xl shadow-2xl shadow-purple-950/50 overflow-hidden">
        
        {{-- Modal Header --}}
        <div class="px-4 py-3.5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border-b border-slate-700/70 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-purple-900/40 border border-purple-400/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-white tracking-wide">AKRU AI Assistant</h3>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-500/20 text-purple-300 border border-purple-500/30">v1.2</span>
                    </div>
                    <p class="text-[11px] text-emerald-400 flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Terhubung ke Buku Besar & Perpajakan
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                {{-- Reset Chat Button --}}
                <button @click="clearMessages()" 
                        type="button"
                        title="Bersihkan Percakapan"
                        class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800/80 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                {{-- Close Button --}}
                <button @click="isOpen = false" 
                        type="button"
                        title="Tutup Modal"
                        class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800/80 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Chat Messages Scroll Container --}}
        <div x-ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-3.5 text-xs">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div>
                    {{-- User Bubble --}}
                    <template x-if="msg.sender === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] px-3.5 py-2.5 rounded-2xl rounded-tr-sm bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md text-xs leading-relaxed break-words whitespace-pre-wrap" x-text="msg.text"></div>
                        </div>
                    </template>

                    {{-- AI Bubble --}}
                    <template x-if="msg.sender === 'ai'">
                        <div class="flex justify-start items-start gap-2">
                            <div class="w-6 h-6 rounded-lg bg-purple-600/30 border border-purple-500/40 flex items-center justify-center text-purple-300 flex-shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="max-w-[92%] px-3.5 py-2.5 rounded-2xl rounded-tl-sm bg-slate-800/90 border border-slate-700/60 text-slate-200 shadow-sm text-xs leading-relaxed break-words">
                                <template x-if="msg.engine">
                                    <div class="mb-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-purple-950/90 text-purple-300 border border-purple-700/40">
                                            <span class="w-1 h-1 rounded-full bg-emerald-400"></span>
                                            <span x-text="msg.engine"></span>
                                        </span>
                                    </div>
                                </template>
                                <div class="prose prose-invert prose-xs max-w-none space-y-1.5" x-html="renderMarkdown(msg.text)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Thinking / Typing Indicator --}}
            <div x-show="isThinking" class="flex justify-start items-center gap-2 text-slate-400">
                <div class="w-6 h-6 rounded-lg bg-purple-600/30 border border-purple-500/40 flex items-center justify-center text-purple-300 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div class="px-3 py-2 rounded-xl bg-slate-800/80 border border-slate-700/50 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-bounce" style="animation-delay: 0.3s"></span>
                    <span class="text-[11px] text-slate-400 ml-1">Menganalisis data buku besar & regulasi pajak...</span>
                </div>
            </div>
        </div>

        {{-- Quick Prompts Chips --}}
        <div class="px-3 py-2 bg-slate-900/90 border-t border-slate-800/80 overflow-x-auto scrollbar-none flex items-center gap-1.5 flex-nowrap">
            <button @click="sendQuickPrompt('Berapa saldo kas dan bank saat ini?')" 
                    type="button" 
                    class="flex-shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-purple-900/40 text-slate-300 hover:text-purple-200 border border-slate-700/60 hover:border-purple-500/40 transition-colors">
                💰 Saldo Kas
            </button>
            <button @click="sendQuickPrompt('Berapa total omzet penjualan bulan berjalan?')" 
                    type="button" 
                    class="flex-shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-purple-900/40 text-slate-300 hover:text-purple-200 border border-slate-700/60 hover:border-purple-500/40 transition-colors">
                📈 Omzet Bulan Ini
            </button>
            <button @click="sendQuickPrompt('hitung pph 21 gaji Rp 15.000.000 status K/1')" 
                    type="button" 
                    class="flex-shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-purple-900/40 text-slate-300 hover:text-purple-200 border border-slate-700/60 hover:border-purple-500/40 transition-colors">
                🧮 Hitung PPh 21
            </button>
            <button @click="sendQuickPrompt('Berapa tarif pajak PPh 21 dan PPh 23?')" 
                    type="button" 
                    class="flex-shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-purple-900/40 text-slate-300 hover:text-purple-200 border border-slate-700/60 hover:border-purple-500/40 transition-colors">
                ⚖️ Tarif PPh 21/23
            </button>
            <button @click="sendQuickPrompt('saya menyewa ruko setahun dengan pembayaran dimuka seluruhnya, bagaimana perlakuan akuntansi dan pajaknya?')" 
                    type="button" 
                    class="flex-shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-purple-900/40 text-slate-300 hover:text-purple-200 border border-slate-700/60 hover:border-purple-500/40 transition-colors">
                🏢 Sewa Ruko Di Muka
            </button>
        </div>

        {{-- Input Bar --}}
        <div class="p-3 bg-slate-900 border-t border-slate-800 flex items-center gap-2">
            <input type="text" 
                   x-model="inputQuery" 
                   @keydown.enter.prevent="sendMessage()" 
                   x-ref="chatInput"
                   placeholder="Tanya perlakuan akuntansi, keuangan, atau pajak..."
                   class="flex-1 bg-slate-800/90 text-white placeholder-slate-400 text-xs px-3.5 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
            
            <button @click="sendMessage()" 
                    :disabled="isThinking || !inputQuery.trim()"
                    type="button" 
                    class="p-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:hover:bg-purple-600 text-white font-medium transition-all shadow-md cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function akruAiWidget() {
    return {
        isOpen: false,
        isThinking: false,
        inputQuery: '',
        messages: [
            {
                sender: 'ai',
                text: "Halo! Saya **AKRU AI**, asisten khusus Akuntansi, Keuangan, dan Perpajakan bisnis Indonesia.\n\nAnda dapat menanyakan posisi kas riil, omzet, piutang tempo, rekomendasi jurnal akun COA, simulasi hitung pajak (PPh 21 TER, PPh 23), hingga perlakuan sewa ruko dibayar di muka.\n\nAda yang bisa saya bantu analisa hari ini?",
                engine: 'AKRU FinLogic Core v1.2'
            }
        ],

        init() {
            window.addEventListener('open-akru-ai', () => {
                this.isOpen = true;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.chatInput) {
                        this.$refs.chatInput.focus();
                    }
                });
            });
        },

        toggleModal() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.chatInput) {
                        this.$refs.chatInput.focus();
                    }
                });
            }
        },

        sendQuickPrompt(text) {
            this.inputQuery = text;
            this.sendMessage();
        },

        async sendMessage() {
            const query = this.inputQuery.trim();
            if (!query || this.isThinking) return;

            // Push user message
            this.messages.push({
                sender: 'user',
                text: query
            });
            this.inputQuery = '';
            this.isThinking = true;
            this.scrollToBottom();

            try {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                // Include multi-turn conversation history (last 6 turns)
                const history = this.messages.slice(-6).map(m => ({
                    sender: m.sender,
                    text: m.text
                }));

                const response = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        message: query,
                        history: history
                    })
                });

                const data = await response.json();
                if (data.reply) {
                    this.messages.push({
                        sender: 'ai',
                        text: data.reply,
                        engine: data.engine || 'AKRU FinLogic Core'
                    });
                } else {
                    this.messages.push({
                        sender: 'ai',
                        text: "Maaf, sistem tidak dapat memproses tanggapan saat ini. Silakan ulangi pertanyaan Anda.",
                        engine: 'System Notice'
                    });
                }
            } catch (err) {
                console.error('AKRU AI Chat Error:', err);
                this.messages.push({
                    sender: 'ai',
                    text: "⚠️ Terjadi kendala koneksi ke server AKRU AI. Pastikan server lokal Anda aktif.",
                    engine: 'Connection Warning'
                });
            } finally {
                this.isThinking = false;
                this.scrollToBottom();
            }
        },

        clearMessages() {
            this.messages = [
                {
                    sender: 'ai',
                    text: "Percakapan telah dibersihkan. Silakan ajukan pertanyaan seputar keuangan, akuntansi, atau perpajakan entitas Anda.",
                    engine: 'AKRU FinLogic Core v1.2'
                }
            ];
        },

        scrollToBottom() {
            this.$nextTick(() => {
                if (this.$refs.messagesContainer) {
                    this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                }
            });
        },

        renderMarkdown(text) {
            if (!text) return '';

            // Handle Markdown Tables
            let formattedText = text;
            const tableRegex = /((?:\|[^\n]+\|\r?\n)+)/g;
            formattedText = formattedText.replace(tableRegex, (match) => {
                const rows = match.trim().split(/\r?\n/).filter(r => r.trim().startsWith('|'));
                if (rows.length < 2) return match;

                let tableHtml = '<div class="overflow-x-auto my-2 border border-slate-700/80 rounded-lg"><table class="min-w-full divide-y divide-slate-700/80 text-[11px]">';
                let isHeader = true;

                rows.forEach((row, rowIdx) => {
                    if (rowIdx === 1 && row.includes('---')) {
                        // Separator row, ignore
                        isHeader = false;
                        return;
                    }
                    const cols = row.split('|').slice(1, -1).map(c => c.trim());
                    if (isHeader) {
                        tableHtml += '<thead class="bg-slate-900/90 text-purple-200"><tr>';
                        cols.forEach(c => {
                            tableHtml += `<th class="px-2.5 py-1.5 font-semibold text-left border-r border-slate-700/50 last:border-0">${c}</th>`;
                        });
                        tableHtml += '</tr></thead><tbody class="divide-y divide-slate-800/80 bg-slate-800/50">';
                    } else {
                        tableHtml += '<tr class="hover:bg-slate-750 transition-colors">';
                        cols.forEach(c => {
                            tableHtml += `<td class="px-2.5 py-1.5 text-slate-300 border-r border-slate-700/40 last:border-0">${c}</td>`;
                        });
                        tableHtml += '</tr>';
                    }
                });

                tableHtml += '</tbody></table></div>';
                return tableHtml;
            });

            let html = formattedText
                // Headers ### and ####
                .replace(/^#### (.*$)/gim, '<h5 class="text-xs font-bold text-purple-300 mt-2 mb-1">$1</h5>')
                .replace(/^### (.*$)/gim, '<h4 class="text-xs font-bold text-white mt-2.5 mb-1.5 pb-0.5 border-b border-slate-700/50 flex items-center gap-1.5">$1</h4>')
                // Bold text
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-white">$1</strong>')
                // Blockquotes
                .replace(/^> (.*$)/gim, '<blockquote class="border-l-2 border-purple-500 pl-2.5 my-1.5 text-slate-300 italic text-[11px] bg-purple-950/20 py-1 pr-2 rounded-r">$1</blockquote>')
                // Bullet points
                .replace(/^\• (.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-purple-400 mt-0.5 text-[10px]">•</span><span>$1</span></div>')
                // Numbered lists 1. 2. 3.
                .replace(/^([0-9]+)\. (.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-purple-300 font-semibold">$1.</span><span>$2</span></div>')
                // Horizontal divider
                .replace(/^---$/gim, '<hr class="border-slate-700/60 my-2">')
                // Inline code `code`
                .replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 bg-slate-900 border border-slate-700 text-purple-200 rounded font-mono text-[11px]">$1</code>')
                // Newlines (outside HTML tags)
                .replace(/(?<!>)\n/g, '<br>');

            return html;
        }
    };
}
</script>
