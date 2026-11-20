@php
    $seo = app(\App\Services\SeoService::class);
    $pageTitle = $seo->getTitle() ?: 'beacon-sift — feed.raupulus.dev';
    $pageDescription = $seo->getDescription() ?: 'Agregador cronológico de noticias y alertas en tiempo real.';
    $pageUrl = $seo->getUrl() ?: url()->current();
    $pageImage = $seo->getImage() ?: asset('images/redes-sociales/og-image-1200x630.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-broadcast-channel="{{ config('beacon.broadcast_channel', 'beacon.news') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#090E17">
    <link rel="canonical" href="{{ $pageUrl }}">

    {{-- Favicons y manifest --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="alternate" type="application/rss+xml" title="beacon-sift RSS" href="{{ route('feed.rss') }}">

    {{-- Open Graph / Twitter Card --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="beacon-sift">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    {{-- Tipografía (Bunny Fonts, sin rastreo) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|jetbrains-mono:400,500,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-text font-sans antialiased flex flex-col selection:bg-primary/30 selection:text-text">
    {{-- Header sticky --}}
    <header class="sticky top-0 z-30 h-16 bg-bg/90 backdrop-blur border-b border-border">
        <div class="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-bright rounded-lg">
                <img src="{{ asset('images/logos/logo.svg') }}" alt="beacon-sift" class="h-9 w-9 rounded-lg ring-1 ring-primary/30 group-hover:ring-primary-bright transition">
                <div class="flex flex-col leading-none">
                    <span class="text-lg font-bold tracking-tight text-text">
                        beacon<span class="text-primary">-sift</span>
                    </span>
                    <span class="font-mono text-[11px] text-text-muted">feed.raupulus.dev</span>
                </div>
            </a>

            <nav class="flex items-center gap-2 sm:gap-4" aria-label="Navegación principal">
                <a href="{{ route('home') }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('home') ? 'bg-surface-elevated text-primary' : 'text-text-secondary hover:text-text hover:bg-surface' }}">
                    Noticias
                </a>
                <a href="{{ route('sources.index') }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('sources.index') ? 'bg-surface-elevated text-primary' : 'text-text-secondary hover:text-text hover:bg-surface' }}">
                    Fuentes
                </a>
                <a href="{{ $rssUrl ?? route('feed.rss') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-mono font-medium border border-beacon/40 bg-beacon/10 text-beacon-bright hover:bg-beacon/20 transition"
                   title="Suscribirse al feed RSS">
                    <span>RSS</span>
                </a>

                {{-- Indicador de conexión WebSocket en vivo --}}
                <div x-data="{ state: 'offline' }"
                     @beacon:ws-status.window="state = $event.detail.state || ($event.detail.connected ? 'connected' : 'offline')"
                     class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-mono"
                     :class="{
                        'border-live/40 bg-live/10 text-live': state === 'connected',
                        'border-beacon/40 bg-beacon/10 text-beacon-bright': state === 'connecting',
                        'border-border bg-surface text-text-muted': state === 'offline'
                     }">
                    <span class="h-2 w-2 rounded-full"
                          :class="{
                             'bg-live animate-pulse': state === 'connected',
                             'bg-beacon animate-pulse': state === 'connecting',
                             'bg-text-muted': state === 'offline'
                          }"></span>
                    <span x-text="state === 'connected' ? 'LIVE' : (state === 'connecting' ? 'CONECTANDO' : 'OFFLINE')">FEED</span>
                </div>
            </nav>
        </div>
    </header>

    {{-- Contenido principal --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Pie de página --}}
    <footer class="border-t border-border bg-bg mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
            <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-center sm:text-left">
                <span class="font-mono text-text-secondary">beacon-sift · feed.raupulus.dev</span>
                <span>Agregador responsable de enlaces y extractos breves con atribución a sus fuentes originales.</span>
            </div>
            <div class="flex items-center gap-4 font-mono">
                <a href="{{ route('about') }}" class="hover:text-primary transition">Acerca de</a>
                <a href="{{ route('privacy') }}" class="hover:text-primary transition">Privacidad</a>
                <a href="{{ route('cookies') }}" class="hover:text-primary transition">Cookies</a>
                <a href="mailto:{{ config('beacon.contact_email', 'public@raupulus.dev') }}" class="hover:text-primary transition">
                    {{ config('beacon.contact_email', 'public@raupulus.dev') }}
                </a>
            </div>
        </div>
    </footer>
</body>
</html>
