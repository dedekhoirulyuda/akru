<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <title>@yield('title', 'AKRU — Accounting, Finance & Tax Control')</title>
    <meta name="description" content="@yield('meta_description', 'AKRU — Data nyata. Kendali penuh. SaaS Accounting, Finance & Tax Control untuk bisnis Indonesia.')">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e293b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: true, mobileMenuOpen: false }" x-cloak>

    {{-- Offline Status Banner --}}
    <div id="offline-banner" class="hidden fixed top-0 inset-x-0 z-50 bg-amber-500 text-white text-center text-sm py-1.5 font-medium">
        ⚠ Anda sedang offline. Beberapa fitur terbatas.
    </div>

    {{-- Impersonation Alert Banner --}}
    @if(session('impersonated_by'))
    <div class="bg-amber-400 text-slate-950 px-4 py-2 font-bold text-xs flex items-center justify-between z-50 sticky top-0 shadow-md">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Mode Bantuan / Impersonasi: Anda sedang mengakses sistem sebagai <strong>{{ auth()->user()->name }}</strong> (Dijalankan oleh Super Admin {{ session('impersonator_name') }}).</span>
        </div>
        <form method="POST" action="{{ route('platform.impersonate.stop') }}">
            @csrf
            <button type="submit" class="px-3 py-1 bg-slate-950 text-white rounded-lg text-[11px] font-bold hover:bg-slate-900 transition-colors cursor-pointer">
                ⬅️ Kembali ke Platform Admin
            </button>
        </form>
    </div>
    @endif

    <div class="flex h-full">
        {{-- Sidebar --}}
        @include('components.sidebar')

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            @include('components.topbar')

            {{-- Page Content with bottom clearance for floating AI button --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 pb-24 lg:pb-28">
                {{-- Content Header --}}
                @hasSection('header')
                <div class="mb-6">
                    @yield('header')
                </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
                @endif

                {{-- Main Content --}}
                @yield('content')
            </main>
        </div>

        {{-- Right Drawer (detail, activity, AI suggestion) --}}
        @stack('drawer')
    </div>

    {{-- Sync Status Indicator --}}
    <div id="sync-status" class="fixed bottom-4 left-4 z-40">
        {{-- Populated by JS --}}
    </div>

    {{-- Floating AKRU AI Chat Dialog Modal --}}
    @include('components.ai-chat-modal')

    @stack('scripts')

    {{-- Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('AKRU SW registered:', reg.scope))
                .catch(err => console.warn('AKRU SW registration failed:', err));
        }
    </script>
</body>
</html>
