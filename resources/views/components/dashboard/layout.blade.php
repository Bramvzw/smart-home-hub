@props(['title', 'navigation' => [], 'hideHeader' => false])
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Smart Home Hub</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="hub-shell">
    <aside id="sidebar" class="hub-sidebar flex flex-col h-screen z-10">
        <div class="hub-brand">
            <a href="/" class="brand-link flex items-center gap-3 min-w-0 flex-1">
                <div class="hub-brand-mark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="hub-brand-text">
                    <strong>Smart Home Hub</strong>
                    <span>Local dashboard</span>
                </span>
            </a>
            <button type="button" id="sidebar-toggle" class="hub-icon-button toggle-btn" title="Toggle navigation">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        <nav class="hub-nav">
            <a href="/"
               class="hub-nav-link {{ request()->is('/') ? 'is-active' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="nav-label">Dashboard</span>
            </a>

            @foreach($navigation as $item)
                <a href="{{ route($item['route']) }}"
                   class="hub-nav-link {{ request()->routeIs($item['route'] . '*') ? 'is-active' : '' }}">
                    @if(isset($item['icon']))
                        <x-dynamic-component :component="'dashboard.icons.' . $item['icon']" class="nav-icon h-5 w-5 shrink-0" />
                    @endif
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="hub-main">
        @if (!$hideHeader)
            <header class="hub-header">
                <div>
                    <h1 class="hub-title">{{ $title }}</h1>
                    <p class="hub-subtle">Smart Home Hub</p>
                </div>
                {{ $actions ?? '' }}
            </header>
        @endif

        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
