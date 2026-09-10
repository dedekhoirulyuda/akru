<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Platform Owner Console — AKRU SaaS')</title>
    <meta name="description" content="Portal Administrasi Terpusat Pemilik Platform SaaS AKRU">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-900 text-slate-100 antialiased flex flex-col" x-data="{ sidebarOpen: true }">

    <div class="flex h-full overflow-hidden">
        {{-- Platform Sidebar --}}
        <aside class="w-64 bg-slate-950 border-r border-slate-800/80 flex flex-col shrink-0 z-30 transition-all duration-300">
            {{-- Logo Header --}}
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 font-black text-white text-base tracking-wider">
                        AK
                    </div>
                    <div>
                        <div class="font-extrabold text-sm tracking-tight text-white flex items-center gap-1.5">
                            <span>AKRU</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase tracking-widest">HQ</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-medium">Platform Owner Console</div>
                    </div>
                </div>
            </div>

            {{-- Navigation Menu --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-xs font-semibold">
                <div class="px-3 pb-2 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    SaaS Operations
                </div>

                <a href="{{ route('platform.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard Eksekutif</span>
                </a>

                <a href="{{ route('platform.companies.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.companies.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Entitas Perusahaan</span>
                </a>

                <a href="{{ route('platform.users.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.users.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Seluruh Pengguna</span>
                </a>

                <div class="pt-4 px-3 pb-2 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    Monetisasi & Billing
                </div>

                <a href="{{ route('platform.plans.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.plans.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Paket Langganan</span>
                </a>

                <a href="{{ route('platform.subscriptions.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.subscriptions.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Langganan & Tagihan</span>
                </a>

                <div class="pt-4 px-3 pb-2 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    Sistem & Integrasi
                </div>

                <a href="{{ route('platform.modules.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.modules.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Modul & Fitur</span>
                </a>

                <a href="{{ route('platform.api.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('platform.api.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-900' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Koneksi API & Token</span>
                </a>
            </nav>

            {{-- Sidebar Footer: Back to Tenant App --}}
            <div class="p-3 border-t border-slate-800/80 bg-slate-950/60">
                <a href="{{ route('dashboard.index') }}" class="flex items-center justify-between w-full px-3 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white transition-all text-xs font-semibold group border border-slate-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        <span>Aplikasi Bisnis</span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-mono">Tenant</span>
                </a>
            </div>
        </aside>

        {{-- Main Container --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-900">
            {{-- Top Navbar --}}
            <header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-slate-800/80 px-6 flex items-center justify-between shrink-0 z-20">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                            Production Engine Live
                        </span>
                    </div>
                </div>

                {{-- Right Profile & Quick Actions --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard.index') }}" class="hidden sm:flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 border border-indigo-500/20 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Buka Workspace Perusahaan</span>
                    </a>

                    <div class="h-6 w-px bg-slate-800"></div>

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs ring-2 ring-indigo-500/30">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="text-xs font-bold text-white">{{ auth()->user()->name ?? 'Super Admin' }}</div>
                            <div class="text-[10px] text-amber-400 font-semibold uppercase tracking-wider">Platform Owner</div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Body Content --}}
            <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">
                {{-- Flash Notifications --}}
                @if(session('success'))
                <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-emerald-300 text-sm flex items-center gap-3 animate-fade-in shadow-lg shadow-emerald-950/20">
                    <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-rose-300 text-sm flex items-center gap-3 animate-fade-in shadow-lg shadow-rose-950/20">
                    <svg class="w-5 h-5 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if(session('info'))
                <div class="rounded-xl bg-blue-500/10 border border-blue-500/30 p-4 text-blue-300 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('info') }}</span>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
