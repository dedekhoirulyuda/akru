{{-- Backdrop Overlay untuk Layar HP / Tablet --}}
<div 
    x-show="mobileMenuOpen" 
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;" 
    class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-xs lg:hidden" 
    @click="mobileMenuOpen = false"
></div>

{{-- Sidebar Navigation --}}
{{-- Blueprint §3.1: Sidebar kiri modul dan submenu sesuai role --}}
<aside
    class="fixed inset-y-0 left-0 z-50 flex flex-col w-72 max-w-[85vw] transition-transform duration-300 ease-in-out lg:static lg:z-0 lg:flex-shrink-0 lg:transition-all"
    :class="{
        'translate-x-0 shadow-2xl': mobileMenuOpen,
        '-translate-x-full lg:translate-x-0': !mobileMenuOpen,
        'lg:w-64': sidebarOpen,
        'lg:w-16': !sidebarOpen
    }"
    @resize.window="if (window.innerWidth < 1024) { sidebarOpen = true }"
>
    <div class="flex flex-col w-full h-full bg-slate-900 text-slate-300 border-r border-slate-800">
        {{-- Brand --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white tracking-wider shadow-sm">
                    A
                </div>
                <div x-show="sidebarOpen" class="flex flex-col">
                    <span class="text-base font-bold text-white tracking-tight leading-none">AKRU</span>
                    <span class="text-[10px] text-slate-400 mt-0.5 tracking-wider font-mono">FINANCE & TAX</span>
                </div>
            </div>
            {{-- Tombol Tutup Mobile Drawer --}}
            <button 
                type="button" 
                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none cursor-pointer"
                @click="mobileMenuOpen = false"
                aria-label="Tutup Menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Navigation with persistent scroll position --}}
        <nav id="sidebar-nav" class="flex-1 overflow-y-auto py-4 space-y-1 px-2.5 text-xs">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition-colors {{ request()->routeIs('dashboard.*') ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span x-show="sidebarOpen" class="text-sm">Dashboard</span>
            </a>
            {{-- OPERASIONAL BISNIS --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Penjualan & Piutang</p>
            </div>
            <a href="{{ route('quotations.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('quotations.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="sidebarOpen">Penawaran (Quotation)</span>
            </a>
            <a href="{{ route('sales-orders.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('sales-orders.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span x-show="sidebarOpen">Pesanan Penjualan (SO)</span>
            </a>
            <a href="{{ route('sales.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('sales.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span x-show="sidebarOpen">Faktur Penjualan</span>
            </a>
            <a href="{{ route('deliveries.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('deliveries.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                <span x-show="sidebarOpen">Surat Jalan (DO)</span>
            </a>
            <a href="{{ route('receipts.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('receipts.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="sidebarOpen">Penerimaan Piutang</span>
            </a>

            <!-- Pembelian & Hutang -->
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pengadaan & Hutang</p>
            </div>
            <a href="{{ route('purchase-requests.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('purchase-requests.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span x-show="sidebarOpen">Permintaan Beli (PR)</span>
            </a>
            <a href="{{ route('purchase-orders.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('purchase-orders.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="sidebarOpen">Pesanan Pembelian (PO)</span>
            </a>
            <a href="{{ route('purchases.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('purchases.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span x-show="sidebarOpen">Faktur Pembelian</span>
            </a>
            <a href="{{ route('goods-receipts.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('goods-receipts.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-show="sidebarOpen">Penerimaan Gudang (GR)</span>
            </a>
            <a href="{{ route('payments.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('payments.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span x-show="sidebarOpen">Pembayaran Hutang</span>
            </a>

            <!-- Persediaan & Gudang -->
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Persediaan & Gudang</p>
            </div>
            <a href="{{ route('inventory.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('inventory.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span x-show="sidebarOpen">Persediaan & Stok</span>
            </a>
            <a href="{{ route('stock-transfers.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('stock-transfers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span x-show="sidebarOpen">Transfer Antar-Gudang</span>
            </a>
            <a href="{{ route('stock-opnames.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('stock-opnames.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-teal-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-show="sidebarOpen">Stock Opname Fisik</span>
            </a>

            {{-- FINANCE / KAS & BANK --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keuangan (Treasury)</p>
            </div>
            <a href="{{ route('cash-bank.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('cash-bank.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="sidebarOpen">Transaksi Kas & Bank</span>
            </a>
            <a href="{{ route('petty-cash.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('petty-cash.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="sidebarOpen">Kas Kecil (Petty Cash)</span>
            </a>
            <a href="{{ route('transfers.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('transfers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span x-show="sidebarOpen">Transfer Antar Kas</span>
            </a>
            <a href="{{ route('reconciliation.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reconciliation.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="sidebarOpen">Rekonsiliasi Bank</span>
            </a>
            <a href="{{ route('budgets.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('budgets.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span x-show="sidebarOpen">Anggaran Biaya (Budget)</span>
            </a>
            <a href="{{ route('forecast.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('forecast.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span x-show="sidebarOpen">Proyeksi Arus Kas</span>
            </a>

            {{-- AKUNTANSI & BUKU BESAR --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Akuntansi (Ledger)</p>
            </div>
            <a href="{{ route('journals.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('journals.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span x-show="sidebarOpen">Jurnal Umum</span>
            </a>
            <a href="{{ route('ledger.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('ledger.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="sidebarOpen">Buku Besar</span>
            </a>
            <a href="{{ route('trial-balance.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('trial-balance.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                <span x-show="sidebarOpen">Neraca Saldo</span>
            </a>
            <a href="{{ route('assets.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('assets.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span x-show="sidebarOpen">Register Aset Tetap</span>
            </a>
            <a href="{{ route('accruals.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('accruals.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-show="sidebarOpen">Amortisasi & Akrual</span>
            </a>
            <a href="{{ route('closing.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('closing.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span x-show="sidebarOpen">Penutupan Buku (Closing)</span>
            </a>

            {{-- LAPORAN KEUANGAN --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Laporan Keuangan</p>
            </div>
            <a href="{{ route('reports.profit-loss') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reports.profit-loss') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span x-show="sidebarOpen">Laba Rugi</span>
            </a>
            <a href="{{ route('reports.balance-sheet') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reports.balance-sheet') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="sidebarOpen">Neraca (Posisi Keuangan)</span>
            </a>
            <a href="{{ route('reports.cash-flow') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reports.cash-flow') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span x-show="sidebarOpen">Arus Kas</span>
            </a>
            <a href="{{ route('reports.aging') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reports.aging') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="sidebarOpen">Umur Piutang / Hutang</span>
            </a>
            <a href="{{ route('reports.analytics') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reports.analytics') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-show="sidebarOpen">Analisis Margin & Profit</span>
            </a>

            {{-- KEPATUHAN PAJAK & CORETAX --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pajak (Tax Control)</p>
            </div>
            <a href="{{ route('tax.ppn') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.ppn') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span x-show="sidebarOpen">SPT Masa PPN 1111</span>
            </a>
            <a href="{{ route('tax.pph') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.pph') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-show="sidebarOpen">PPh Withholding</span>
            </a>
            <a href="{{ route('tax.fiscal') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.fiscal') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span x-show="sidebarOpen">Rekonsiliasi Fiskal</span>
            </a>
            <a href="{{ route('tax.coretax') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.coretax') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span x-show="sidebarOpen">Ekspor XML Coretax</span>
            </a>
            <a href="{{ route('tax.calendar') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.calendar') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-show="sidebarOpen">Kalender Pajak & Alert</span>
            </a>
            <a href="{{ route('tax.audit-package') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax.audit-package') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="sidebarOpen">Kertas Kerja Audit Pajak</span>
            </a>

            {{-- MASTER DATA --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Master Data</p>
            </div>
            <a href="{{ route('coa.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('coa.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span x-show="sidebarOpen">Bagan Akun (COA)</span>
            </a>
            <a href="{{ route('dimensions.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dimensions.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                <span x-show="sidebarOpen">Dimensi Analitik</span>
            </a>
            <a href="{{ route('customers.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('customers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="sidebarOpen">Pelanggan</span>
            </a>
            <a href="{{ route('suppliers.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="sidebarOpen">Pemasok</span>
            </a>
            <a href="{{ route('products.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span x-show="sidebarOpen">Produk & Jasa</span>
            </a>
            <a href="{{ route('bank-accounts.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('bank-accounts.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span x-show="sidebarOpen">Rekening Bank</span>
            </a>
            <a href="{{ route('tax-codes.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tax-codes.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-cyan-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span x-show="sidebarOpen">Tarif Pajak</span>
            </a>

            {{-- TATA KELOLA, ALAT & ADD-ON --}}
            <div class="pt-4 pb-1" x-show="sidebarOpen">
                <p class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tata Kelola & Add-On</p>
            </div>
            <a href="{{ route('workflow.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('workflow.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span x-show="sidebarOpen">Antrean Persetujuan</span>
            </a>
            <a href="{{ route('notifications.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('notifications.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span x-show="sidebarOpen">Pusat Notifikasi</span>
            </a>
            <a href="{{ route('converter.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('converter.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                <span x-show="sidebarOpen">Konverter Mutasi Bank</span>
            </a>

            <a href="{{ route('audit.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('audit.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="sidebarOpen">Audit Trail Explorer</span>
            </a>
            <a href="{{ route('companies.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('companies.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="sidebarOpen">Entitas Perusahaan</span>
            </a>
            <a href="{{ route('branches.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('branches.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="sidebarOpen">Cabang & Gudang</span>
            </a>
            <a href="{{ route('settings.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('settings.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="sidebarOpen">Pengaturan Sistem</span>
            </a>
            <a href="{{ route('print-layout.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('print-layout.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"/></svg>
                <span x-show="sidebarOpen">Pengaturan Cetak</span>
            </a>
            <a href="{{ route('users.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('users.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-show="sidebarOpen">Pengguna & Hak Akses</span>
            </a>
            <a href="{{ route('subscription.index') }}" 
               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('subscription.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span x-show="sidebarOpen">Langganan & Kuota SaaS</span>
            </a>
        </nav>

        {{-- User Profile & Sidebar Toggle Footer --}}
        <div class="border-t border-slate-800 p-3 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0" x-show="sidebarOpen">
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-white uppercase shrink-0">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email ?? '' }}</div>
                    </div>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" x-show="sidebarOpen"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" x-show="!sidebarOpen"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</aside>

<style>
    /* Sleek slim dark scrollbar for sidebar */
    #sidebar-nav::-webkit-scrollbar {
        width: 5px;
    }
    #sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }
    #sidebar-nav::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 9999px;
    }
    #sidebar-nav::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
</style>

<script>
    (function() {
        function alignSidebar() {
            const nav = document.getElementById('sidebar-nav');
            if (!nav) return;

            const activeLink = nav.querySelector('a.bg-slate-800, a.bg-blue-600');
            const isDashboard = activeLink && (activeLink.getAttribute('href') || '').endsWith('/dashboard');

            if (activeLink && !isDashboard) {
                // Instantly center the active menu item in the sidebar viewport
                activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
            } else if (isDashboard) {
                nav.scrollTop = 0;
            } else {
                const saved = sessionStorage.getItem('akru_sidebar_scroll');
                if (saved !== null && parseInt(saved, 10) > 0) {
                    nav.scrollTop = parseInt(saved, 10);
                }
            }
        }

        // Run immediately
        alignSidebar();

        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', alignSidebar);
        }

        // Run after browser layout & fonts settle
        window.addEventListener('load', alignSidebar);
        requestAnimationFrame(alignSidebar);
        setTimeout(alignSidebar, 50);
        setTimeout(alignSidebar, 150);

        // Track clicks on links inside sidebar
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('sidebar-nav');
            if (!nav) return;
            const link = e.target.closest('#sidebar-nav a');
            if (link) {
                sessionStorage.setItem('akru_sidebar_scroll', nav.scrollTop);
            }
        });
    })();
</script>
