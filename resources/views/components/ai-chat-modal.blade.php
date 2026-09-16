{{-- Bilah Kolom Chat AKRU AI Copilot (Antigravity Split View & Resizable Panel) --}}
<div x-data="akruAiWidget()" 
     class="contents" 
     x-cloak>

    {{-- Floating Action Button (Tampil saat chat panel tertutup di pojok kanan bawah) --}}
    <div x-show="!$store.aiChat.isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-6 right-6 z-40 flex items-center">
        <button @click="$store.aiChat.open()" 
                type="button"
                id="btn-open-ai-chat"
                class="group relative flex items-center gap-2.5 px-4 py-2.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white rounded-full shadow-xl shadow-indigo-950/50 hover:shadow-indigo-500/40 hover:scale-105 active:scale-95 transition-all duration-200 border border-indigo-400/40 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900"
                title="Buka Chat AI Copilot (Ctrl + /)"
                aria-label="Buka Chat AI Copilot">
            {{-- Pulsing Live Online Indicator --}}
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
            </span>

            {{-- Sparkle AI Icon --}}
            <svg class="w-4.5 h-4.5 text-indigo-100 group-hover:rotate-12 transition-transform duration-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>

            <span class="text-xs font-bold tracking-wide whitespace-nowrap">Chat AI</span>

            {{-- Keyboard Hint Badge --}}
            <span class="hidden sm:inline-block px-1.5 py-0.5 rounded text-[10px] font-mono bg-black/25 text-indigo-200 border border-indigo-400/20">Ctrl+/</span>
        </button>
    </div>

    {{-- Backdrop Overlay (khusus layar HP / Tablet saat chat terbuka) --}}
    <div x-show="$store.aiChat.isOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.aiChat.close()"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs lg:hidden"
         style="display: none;"></div>

    {{-- Antigravity Split-View Panel (Docked Samping di Desktop, Slide-Drawer di Mobile) --}}
    <aside x-show="$store.aiChat.isOpen" 
           x-transition:enter="transform transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transform transition ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           id="akru-ai-chat-drawer"
           class="fixed inset-y-0 right-0 z-50 lg:static lg:inset-auto lg:z-30 w-full sm:w-[480px] lg:w-auto h-full shrink-0 flex flex-col bg-white text-slate-800 border-l border-slate-200 dark:bg-slate-950 dark:text-slate-100 dark:border-slate-800/90 shadow-2xl shadow-slate-900/10 dark:shadow-black/80 select-text transition-[width] duration-75 relative"
           :style="window.innerWidth >= 1024 ? { width: $store.aiChat.width + 'px' } : {}"
           style="display: none;">
        
        {{-- Draggable Resize Handle / Splitter (Hanya muncul di Desktop untuk Geser Perlebar / Persempit) --}}
        <div class="hidden lg:flex absolute -left-2 top-0 bottom-0 w-3 cursor-col-resize items-center justify-center z-50 select-none group"
             @mousedown="startResize($event)"
             title="Tahan dan geser untuk memperlebar / mempersempit tampilan bilah AI">
            <div class="h-12 w-1 rounded-full bg-slate-300 group-hover:bg-indigo-500 group-hover:w-1.5 group-active:bg-indigo-600 dark:bg-slate-700 dark:group-hover:bg-indigo-400 dark:group-active:bg-indigo-500 transition-all shadow-xs"></div>
        </div>

        {{-- Antigravity Panel Header --}}
        <div class="px-4 py-3 bg-slate-50/95 border-b border-slate-200 dark:bg-slate-900/95 dark:border-slate-800/90 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 via-purple-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-900/20 dark:shadow-indigo-900/40 border border-indigo-400/30 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-wide">AKRU AI Copilot</h3>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30">Split View</span>
                    </div>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        Terhubung ke Buku Besar & Pajak
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                {{-- Provider Selector Dropdown --}}
                <div class="relative" x-data="{ openProviderMenu: false }">
                    <button @click="openProviderMenu = !openProviderMenu"
                            type="button"
                            class="px-2 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 shadow-2xs dark:bg-slate-800 dark:hover:bg-slate-750 dark:text-slate-300 dark:hover:text-white dark:border-slate-700/80 text-[11px] font-medium flex items-center gap-1.5 transition-colors cursor-pointer"
                            :title="'Provider aktif: ' + selectedProviderLabel">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                        <span x-text="selectedProviderShort"></span>
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="openProviderMenu" 
                         @click.away="openProviderMenu = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-1.5 w-56 rounded-xl bg-white border border-slate-200 shadow-xl py-1 z-50 text-xs dark:bg-slate-900 dark:border-slate-800"
                         style="display: none;">
                        <div class="px-3 py-1.5 text-[10px] uppercase font-bold tracking-wider text-slate-400 border-b border-slate-100 dark:text-slate-500 dark:border-slate-800/80">
                            Pilih Engine AI
                        </div>
                        <button @click="setProvider('akru_native'); openProviderMenu = false" 
                                type="button"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                :class="{ 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300': selectedProvider === 'akru_native' }">
                            <div>
                                <div class="text-xs text-slate-900 dark:text-white">Provider 1 (AKRU Native)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">0 Biaya API • FinLogic Terpadu</div>
                            </div>
                            <span x-show="selectedProvider === 'akru_native'" class="text-indigo-600 dark:text-indigo-400 text-xs">✓</span>
                        </button>
                        <button @click="setProvider('gemini'); openProviderMenu = false" 
                                type="button"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                :class="{ 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300': selectedProvider === 'gemini' }">
                            <div>
                                <div class="text-xs text-slate-900 dark:text-white">Provider 2 (Google Gemini)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Gemini 1.5 Flash / Pro Engine</div>
                            </div>
                            <span x-show="selectedProvider === 'gemini'" class="text-indigo-600 dark:text-indigo-400 text-xs">✓</span>
                        </button>
                        <button @click="setProvider('openai'); openProviderMenu = false" 
                                type="button"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-between transition-colors"
                                :class="{ 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300': selectedProvider === 'openai' }">
                            <div>
                                <div class="text-xs text-slate-900 dark:text-white">Provider 3 (OpenAI ChatGPT)</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">GPT-4o / GPT-4o-mini Engine</div>
                            </div>
                            <span x-show="selectedProvider === 'openai'" class="text-indigo-600 dark:text-indigo-400 text-xs">✓</span>
                        </button>
                    </div>
                </div>

                {{-- Theme Switcher Button (Light / Night Mode) --}}
                <button @click="$store.theme.toggle()" 
                        type="button"
                        title="Ganti Mode Terang / Gelap"
                        class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                    <span x-show="$store.theme.current === 'light'" class="flex items-center">
                        {{-- Moon icon when currently in light mode --}}
                        <svg class="w-4 h-4 text-slate-600 hover:text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </span>
                    <span x-show="$store.theme.current === 'dark'" class="flex items-center" style="display: none;">
                        {{-- Sun icon when currently in dark mode --}}
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                </button>

                {{-- Percakapan Baru / Reset Button --}}
                <button @click="clearMessages()" 
                        type="button"
                        title="Percakapan Baru"
                        class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>

                {{-- Close Button (X) --}}
                <button @click="$store.aiChat.close()" 
                        type="button"
                        title="Tutup Bilah Chat (Esc)"
                        class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Antigravity Message History Stream --}}
        <div x-ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-4 text-xs scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">
            <template x-for="(msg, idx) in messages" :key="idx">
                <div class="space-y-1.5">
                    {{-- User Prompt Card (Antigravity Style) --}}
                    <template x-if="msg.sender === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[90%] px-4 py-2.5 rounded-2xl rounded-tr-xs bg-blue-600 text-white shadow-sm text-xs leading-relaxed break-words whitespace-pre-wrap font-medium dark:bg-slate-800/90 dark:border dark:border-slate-700/70 dark:text-slate-100" x-text="msg.text"></div>
                        </div>
                    </template>

                    {{-- AI Assistant Response Card (Antigravity Style) --}}
                    <template x-if="msg.sender === 'ai'">
                        <div class="flex flex-col gap-1.5 max-w-full">
                            {{-- Engine badge --}}
                            <div class="flex items-center justify-between px-1">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 text-indigo-700 border border-slate-200 shadow-2xs dark:bg-slate-900 dark:text-indigo-300 dark:border-indigo-800/40">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                    <span x-text="msg.engine"></span>
                                </span>
                                {{-- Copy Button --}}
                                <button @click="copyText(msg.text, idx)" 
                                        type="button"
                                        class="text-[10px] text-slate-400 hover:text-slate-700 dark:hover:text-indigo-300 flex items-center gap-1 px-1.5 py-0.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span x-text="copiedIdx === idx ? 'Tersalin!' : 'Salin'"></span>
                                </button>
                            </div>

                            {{-- Formatted Content Card --}}
                            <div class="px-4 py-3 rounded-2xl rounded-tl-xs bg-slate-50 border border-slate-200/90 text-slate-800 shadow-xs text-xs leading-relaxed break-words dark:bg-slate-900/90 dark:border-slate-800/80 dark:text-slate-200">
                                <div class="prose prose-slate dark:prose-invert prose-xs max-w-none space-y-2" x-html="renderMarkdown(msg.text)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Thinking / Streaming Indicator --}}
            <div x-show="isThinking" class="flex flex-col gap-1.5">
                <div class="flex items-center gap-1.5 px-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-slate-900 dark:text-purple-300 dark:border-purple-800/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span>
                        <span x-text="selectedProviderLabel"></span>
                    </span>
                </div>
                <div class="px-4 py-3 rounded-2xl rounded-tl-xs bg-slate-50 border border-slate-200 flex items-center gap-2 text-slate-600 dark:bg-slate-900/90 dark:border-slate-800/80 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce"></span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.3s"></span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 ml-1">Menganalisis transaksi buku besar & ketentuan regulasi...</span>
                </div>
            </div>
        </div>

        {{-- Quick Prompts Suggestion Chips --}}
        <div class="px-3 py-2 bg-slate-100/70 border-t border-slate-200 dark:bg-slate-900/80 dark:border-slate-800/80 overflow-x-auto scrollbar-none flex items-center gap-1.5 shrink-0">
            <button @click="sendQuickPrompt('cara penggunaan akru')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                📘 Cara Penggunaan AKRU
            </button>
            <button @click="sendQuickPrompt('Berapa saldo kas dan bank saat ini?')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                💰 Saldo Kas & Bank
            </button>
            <button @click="sendQuickPrompt('Berapa total omzet penjualan dan laba kotor bulan ini?')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                📈 Omzet & Laba
            </button>
            <button @click="sendQuickPrompt('jika saya pembelian impor, komponen pajak apa saja yang muncul?')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                🚢 Pajak Pembelian Impor
            </button>
            <button @click="sendQuickPrompt('hitung pph 21 gaji Rp 15.000.000 status K/1')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                🧮 Hitung PPh 21 TER
            </button>
            <button @click="sendQuickPrompt('saya menyewa ruko setahun dengan pembayaran dimuka seluruhnya, bagaimana perlakuan akuntansi dan pajaknya?')" 
                    type="button" 
                    class="shrink-0 px-2.5 py-1 text-[11px] rounded-lg bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-colors cursor-pointer dark:bg-slate-800/90 dark:hover:bg-indigo-950/70 dark:text-slate-300 dark:hover:text-indigo-200 dark:border-slate-700/60 dark:hover:border-indigo-500/50">
                🏢 Sewa Ruko Di Muka
            </button>
        </div>

        {{-- Antigravity Bottom Input Area --}}
        <div class="p-3 bg-slate-50 border-t border-slate-200 dark:bg-slate-900 dark:border-slate-800/90 shrink-0 space-y-2">
            <div class="relative flex items-center gap-2">
                <textarea x-model="inputQuery" 
                          @keydown.enter.exact.prevent="sendMessage()" 
                          x-ref="chatInput"
                          rows="2"
                          placeholder="Ketik pesan atau konsultasi untuk AKRU AI... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                          class="flex-1 bg-white text-slate-900 placeholder-slate-400 text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none transition-colors shadow-2xs dark:bg-slate-800/90 dark:text-white dark:placeholder-slate-400 dark:border-slate-700/80"></textarea>
                
                {{-- Circular Send Button (Antigravity Style) --}}
                <button @click="sendMessage()" 
                        :disabled="isThinking || !inputQuery.trim()"
                        type="button" 
                        title="Kirim Pesan (Enter)"
                        class="p-2.5 rounded-full bg-blue-600 hover:bg-blue-500 disabled:opacity-40 disabled:hover:bg-blue-600 text-white font-medium transition-all shadow-md hover:scale-105 active:scale-95 cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Footer Note & Status --}}
            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 px-1">
                <div class="flex items-center gap-1.5">
                    <span class="w-1 h-1 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                    <span x-text="selectedProviderLabel"></span>
                </div>
                <span>Prinsip Human-in-the-Loop • Verifikasi data</span>
            </div>
        </div>
    </aside>
</div>

<script>
function akruAiWidget() {
    return {
        isThinking: false,
        isResizing: false,
        inputQuery: '',
        copiedIdx: null,
        selectedProvider: localStorage.getItem('akru_ai_provider') || 'akru_native',
        messages: [
            {
                sender: 'ai',
                text: "### 🏛️ Selamat Datang di AKRU AI Copilot\n\n"
                    + "Saya adalah asisten cerdas terintegrasi untuk akuntansi, keuangan, dan kepatuhan pajak entitas Anda.\n\n"
                    + "Beberapa hal yang dapat Anda tanyakan secara langsung:\n"
                    + "• **Alur Penggunaan:** Ketik *\"cara penggunaan akru\"* untuk panduan SOP 5 langkah.\n"
                    + "• **Likuiditas & Kas:** Ketik *\"Berapa saldo kas dan bank saat ini?\"*.\n"
                    + "• **Omzet & Laba:** Ketik *\"Berapa total penjualan dan laba kotor bulan ini?\"*.\n"
                    + "• **Pajak & Regulasi:** Ketik *\"Pajak pembelian impor\"* atau *\"Hitung PPh 21 TER\"*.\n"
                    + "• **Rekomendasi Akun COA:** Tanyakan perlakuan jurnal transaksi spesifik entitas Anda.\n\n"
                    + "> Seluruh analisis disajikan sebagai rekomendasi pendukung keputusan (*Human-in-the-Loop*).",
                engine: 'Provider 1 (AKRU Native)'
            }
        ],

        get selectedProviderLabel() {
            if (this.selectedProvider === 'gemini') return 'Provider 2 (Google Gemini)';
            if (this.selectedProvider === 'openai') return 'Provider 3 (OpenAI ChatGPT)';
            return 'Provider 1 (AKRU Native)';
        },

        get selectedProviderShort() {
            if (this.selectedProvider === 'gemini') return 'Provider 2';
            if (this.selectedProvider === 'openai') return 'Provider 3';
            return 'Provider 1';
        },

        init() {
            window.addEventListener('akru-ai-focus-input', () => {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.chatInput) {
                        this.$refs.chatInput.focus();
                    }
                });
            });
        },

        startResize(e) {
            e.preventDefault();
            this.isResizing = true;
            document.body.classList.add('select-none', 'cursor-col-resize');

            const onMouseMove = (moveEvent) => {
                if (!this.isResizing) return;
                const newWidth = window.innerWidth - moveEvent.clientX;
                if (this.$store.aiChat) {
                    this.$store.aiChat.setWidth(newWidth);
                }
            };

            const onMouseUp = () => {
                this.isResizing = false;
                document.body.classList.remove('select-none', 'cursor-col-resize');
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        setProvider(code) {
            this.selectedProvider = code;
            localStorage.setItem('akru_ai_provider', code);
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
                        history: history,
                        provider: this.selectedProvider
                    })
                });

                const data = await response.json();
                if (data.reply) {
                    this.messages.push({
                        sender: 'ai',
                        text: data.reply,
                        engine: data.provider?.provider === 'openai' ? 'Provider 3 (OpenAI ChatGPT)' : (data.provider?.provider === 'gemini' ? 'Provider 2 (Google Gemini)' : 'Provider 1 (AKRU Native)')
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
                    text: "Percakapan baru telah dimulai. Silakan ajukan pertanyaan seputar keuangan, akuntansi, atau kepatuhan pajak entitas Anda.",
                    engine: this.selectedProviderLabel
                }
            ];
        },

        copyText(text, idx) {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(text).then(() => {
                this.copiedIdx = idx;
                setTimeout(() => {
                    if (this.copiedIdx === idx) this.copiedIdx = null;
                }, 2000);
            });
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

            let formattedText = text;

            // Handle Markdown Tables
            const tableRegex = /((?:\|[^\n]+\|\r?\n)+)/g;
            formattedText = formattedText.replace(tableRegex, (match) => {
                const rows = match.trim().split(/\r?\n/).filter(r => r.trim().startsWith('|'));
                if (rows.length < 2) return match;

                let tableHtml = '<div class="overflow-x-auto my-2 border border-slate-200 dark:border-slate-700/80 rounded-lg shadow-2xs"><table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/80 text-[11px]">';
                let isHeader = true;

                rows.forEach((row, rowIdx) => {
                    if (rowIdx === 1 && row.includes('---')) {
                        isHeader = false;
                        return;
                    }
                    const cols = row.split('|').slice(1, -1).map(c => c.trim());
                    if (isHeader) {
                        tableHtml += '<thead class="bg-slate-100 text-slate-800 dark:bg-slate-900/90 dark:text-indigo-200"><tr>';
                        cols.forEach(c => {
                            tableHtml += `<th class="px-2.5 py-1.5 font-semibold text-left border-r border-slate-200 dark:border-slate-700/50 last:border-0">${c}</th>`;
                        });
                        tableHtml += '</tr></thead><tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800/80 dark:bg-slate-800/50">';
                    } else {
                        tableHtml += '<tr class="hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors">';
                        cols.forEach(c => {
                            tableHtml += `<td class="px-2.5 py-1.5 text-slate-700 dark:text-slate-300 border-r border-slate-100 dark:border-slate-700/40 last:border-0">${c}</td>`;
                        });
                        tableHtml += '</tr>';
                    }
                });

                tableHtml += '</tbody></table></div>';
                return tableHtml;
            });

            let html = formattedText
                // Headers ### and ####
                .replace(/^#### (.*$)/gim, '<h5 class="text-xs font-bold text-indigo-600 dark:text-indigo-300 mt-2 mb-1">$1</h5>')
                .replace(/^### (.*$)/gim, '<h4 class="text-xs font-bold text-slate-900 dark:text-white mt-2.5 mb-1.5 pb-0.5 border-b border-slate-200 dark:border-slate-700/50 flex items-center gap-1.5">$1</h4>')
                // Links [text](url)
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline underline-offset-2">$1</a>')
                // Bold text
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>')
                // Blockquotes
                .replace(/^> (.*$)/gim, '<blockquote class="border-l-2 border-indigo-500 pl-2.5 my-1.5 text-slate-700 dark:text-slate-300 italic text-[11px] bg-indigo-50/70 dark:bg-indigo-950/20 py-1 pr-2 rounded-r">$1</blockquote>')
                // Bullet points
                .replace(/^\• (.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-indigo-600 dark:text-indigo-400 mt-0.5 text-[10px]">•</span><span>$1</span></div>')
                // Numbered lists 1. 2. 3.
                .replace(/^([0-9]+)\. (.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-indigo-600 dark:text-indigo-300 font-semibold">$1.</span><span>$2</span></div>')
                // Horizontal divider
                .replace(/^---$/gim, '<hr class="border-slate-200 dark:border-slate-800 my-2.5">')
                // Inline code `code`
                .replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-indigo-700 dark:bg-slate-900 dark:border-slate-700 dark:text-indigo-200 rounded font-mono text-[11px]">$1</code>')
                // Newlines
                .replace(/(?<!>)\n/g, '<br>');

            return html;
        }
    };
}
</script>
