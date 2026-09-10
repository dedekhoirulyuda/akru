<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AKRU — Masuk')</title>
    <meta name="description" content="Masuk ke AKRU — Accounting, Finance & Tax Control untuk bisnis Indonesia.">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e293b">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-900 font-sans antialiased">
    <div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            {{-- Brand --}}
            <div class="text-center">
                <h1 class="text-4xl font-bold text-white tracking-tight">AKRU</h1>
                <p class="mt-2 text-sm text-slate-400">Accounting, Finance & Tax Control</p>
                <p class="text-xs text-slate-500 mt-1">Data nyata. Kendali penuh.</p>
            </div>

            {{-- Auth Card --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 p-8 shadow-2xl">
                @yield('content')
            </div>

            {{-- Footer --}}
            <p class="text-center text-xs text-slate-600">
                &copy; {{ date('Y') }} AKRU. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
