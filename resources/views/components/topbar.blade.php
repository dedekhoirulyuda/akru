{{-- Top Bar --}}
{{-- Blueprint §3.1: company switcher, branch, periode, global search, create shortcut, notification, sync, profil --}}
<header class="sticky top-0 z-30 flex items-center h-16 bg-white border-b border-slate-200 px-4 lg:px-6 gap-4">
    {{-- Mobile menu toggle --}}
    <button class="lg:hidden text-slate-500 hover:text-slate-700 cursor-pointer" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Buka Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Company Switcher --}}
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" type="button" class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-sm font-semibold text-slate-800 transition-colors cursor-pointer">
            <div class="w-5 h-5 rounded bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <span id="company-name" class="max-w-[180px] truncate">{{ $currentCompany?->name ?? session('active_company_name', 'Pilih Perusahaan') }}</span>
            <svg class="w-4 h-4 text-slate-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        {{-- Company Dropdown Menu --}}
        <div x-show="open" @click.outside="open = false" style="display: none;" 
             class="absolute left-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
            <div class="px-3 py-2 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Pilih Entitas Perusahaan
            </div>
            <div class="max-h-60 overflow-y-auto divide-y divide-slate-50">
                @if(isset($userCompanies) && $userCompanies->count() > 0)
                    @foreach($userCompanies as $company)
                        <form method="POST" action="{{ route('companies.switch', $company->id) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3.5 py-2.5 hover:bg-blue-50/70 transition-colors flex items-center justify-between group cursor-pointer">
                                <div class="min-w-0 pr-2">
                                    <div class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600">{{ $company->name }}</div>
                                    <div class="text-[11px] text-slate-400 truncate">{{ $company->city ?? 'Indonesia' }} &bull; NPWP: {{ $company->npwp ?? '-' }}</div>
                                </div>
                                @if(($currentCompany?->id ?? session('active_company_id')) == $company->id)
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </button>
                        </form>
                    @endforeach
                @else
                    <div class="px-4 py-3 text-xs text-slate-400 text-center">
                        {{ $currentCompany?->name ?? 'PT Akru Maju Bersama' }} (Aktif)
                    </div>
                @endif
            </div>
            <div class="p-2 border-t border-slate-100 bg-slate-50/50">
                <a href="{{ route('companies.index') }}" class="flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold text-blue-600 hover:bg-blue-100/50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Kelola Semua Entitas
                </a>
            </div>
        </div>
    </div>

    {{-- Branch Indicator --}}
    <div class="hidden md:flex items-center text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-md">
        <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Semua Cabang</span>
    </div>

    {{-- Period Indicator --}}
    <div class="hidden lg:flex items-center text-xs text-slate-400 font-mono">
        <span>{{ now()->translatedFormat('F Y') }}</span>
    </div>

    {{-- Spacer --}}
    <div class="flex-1"></div>

    {{-- Global Search --}}
    <div class="hidden md:flex items-center">
        <div class="relative">
            <input
                type="text"
                id="global-search"
                placeholder="Cari transaksi, master data..."
                class="w-56 lg:w-64 pl-9 pr-4 py-1.5 text-xs rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                onkeydown="if(event.key === 'Enter') { window.location.href = '{{ route('journals.index') }}'; }"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    {{-- AKRU AI Quick Access in Topbar --}}
    <button @click="window.dispatchEvent(new CustomEvent('open-akru-ai'))" 
            type="button" 
            id="topbar-ai-btn"
            title="Buka Asisten AKRU AI"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-purple-50 via-indigo-50 to-purple-50 hover:from-purple-100 hover:to-indigo-100 border border-purple-200/90 text-purple-700 hover:text-purple-900 text-xs font-bold transition-all shadow-2xs cursor-pointer">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-600"></span>
        </span>
        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span>AKRU AI</span>
    </button>

    {{-- Quick Create Shortcut Dropdown (+ Buat) --}}
    <div class="relative" x-data="{ createOpen: false }">
        <button @click="createOpen = !createOpen" 
                type="button" 
                id="quick-create-btn"
                class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-500 shadow-xs transition-colors cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Buat</span>
            <svg class="w-3 h-3 text-blue-200" :class="createOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        {{-- Quick Create Dropdown Menu --}}
        <div x-show="createOpen" @click.outside="createOpen = false" style="display: none;"
             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 py-3 z-50 divide-y divide-slate-100">
            
            <div class="px-4 pb-2">
                <div class="text-xs font-bold text-slate-900">Pintas Buat Cepat (Quick Action)</div>
                <div class="text-[11px] text-slate-400">Pilih jenis transaksi atau data yang ingin Anda input</div>
            </div>

            <!-- Penjualan & Piutang -->
            <div class="py-2 px-2">
                <div class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-600">Penjualan & Piutang</div>
                <a href="{{ route('sales.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-blue-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <span>Faktur Penjualan Baru</span>
                </a>
                <a href="{{ route('receipts.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-emerald-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span>Penerimaan Pembayaran Piutang</span>
                </a>
            </div>

            <!-- Pembelian & Hutang -->
            <div class="py-2 px-2">
                <div class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-600">Pembelian & Hutang</div>
                <a href="{{ route('purchases.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-amber-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <span>Faktur Pembelian Baru</span>
                </a>
                <a href="{{ route('payments.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-amber-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span>Pembayaran Hutang Pemasok</span>
                </a>
            </div>

            <!-- Kas & Akuntansi -->
            <div class="py-2 px-2">
                <div class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-purple-600">Kas & Akuntansi</div>
                <a href="{{ route('transfers.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-purple-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <span>Transfer Antar Rekening Kas</span>
                </a>
                <a href="{{ route('manual-journals.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-cyan-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <span>Jurnal Penyesuaian / Memorial</span>
                </a>
                <a href="{{ route('assets.index') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-xs font-medium text-slate-700 hover:text-blue-600 transition-colors">
                    <div class="w-6 h-6 rounded bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <span>Registrasi Aset Tetap</span>
                </a>
            </div>

        </div>
    </div>

    {{-- Sync Status Indicator --}}
    <div id="sync-indicator" class="flex items-center" title="Sistem terhubung & tersinkronisasi">
        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></div>
    </div>

    {{-- Notifications --}}
    <a href="{{ route('workflow.index') }}" class="relative p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors" title="Antrean Persetujuan">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    </a>

    {{-- Platform Owner Quick Link (If superadmin) --}}
    @if(auth()->check() && auth()->user()->isSuperAdmin())
    <a href="{{ route('platform.dashboard') }}" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-amber-500 to-indigo-600 hover:from-amber-600 hover:to-indigo-700 text-white text-xs font-extrabold shadow-sm transition-all cursor-pointer">
        <span>🛡️ Konsol Platform</span>
    </a>
    @endif

    {{-- User Profile Dropdown --}}
    <div x-data="{ profileOpen: false }" class="relative">
        <button @click="profileOpen = !profileOpen" type="button" class="flex items-center gap-2 p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xs uppercase shadow-xs">
                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
            </div>
            <svg class="w-3.5 h-3.5 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        {{-- Profile Popup --}}
        <div x-show="profileOpen" @click.outside="profileOpen = false" style="display: none;"
             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50">
            <div class="px-4 py-3 border-b border-slate-100">
                <div class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name ?? 'User' }}</div>
                <div class="text-[11px] text-slate-400 font-mono truncate">{{ auth()->user()->email ?? '' }}</div>
                <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                    {{ $currentCompany?->name ?? 'Entitas Aktif' }}
                </div>
            </div>

            <div class="py-1">
                @if(auth()->check() && auth()->user()->isSuperAdmin())
                <a href="{{ route('platform.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 transition-colors border-b border-slate-100">
                    <span class="text-sm">🛡️</span>
                    <span>Konsol Pemilik Platform</span>
                </a>
                @endif
                <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan Entitas
                </a>
                <a href="{{ route('subscription.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    Paket Langganan & Kuota
                </a>
            </div>

            <div class="pt-1 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar dari Sesi (Logout)
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
