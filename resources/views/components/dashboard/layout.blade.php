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
    <style>
        :root {
            --accent: #8b5cf6;
            --accent-glow: rgba(139, 92, 246, 0.15);
        }
        body {
            background: #13141a;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(99, 52, 202, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(56, 100, 220, 0.04) 0%, transparent 50%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-strong {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-hover:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .nav-active {
            background: var(--accent-glow);
            border-color: rgba(139, 92, 246, 0.3);
            color: #ddd6fe;
        }
        .glow-text { color: #c4b5fd; }
        .glow-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 8px var(--accent);
        }
        /* Sidebar collapse */
        #sidebar { transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        #sidebar.collapsed { width: 4.5rem; }
        #sidebar.collapsed .nav-label,
        #sidebar.collapsed .brand-text,
        #sidebar.collapsed .sidebar-section-title,
        #sidebar.collapsed .toggle-label { display: none; }
        #sidebar.collapsed .brand-area { justify-content: center; }
        #sidebar.collapsed .brand-link { display: none; }
        #sidebar.collapsed .toggle-btn svg { transform: rotate(180deg); }
        #sidebar.collapsed .nav-link { justify-content: center; padding: 0.75rem; min-height: 48px; }
        #sidebar.collapsed .nav-icon { margin-right: 0; }
        #sidebar.collapsed .nav-area { padding-left: 0.5rem; padding-right: 0.5rem; }
        #sidebar.collapsed .bottom-area { justify-content: center; }
        #sidebar.collapsed .glow-dot { margin-right: 0; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.2); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(139, 92, 246, 0.4); }
        * { scrollbar-width: thin; scrollbar-color: rgba(139, 92, 246, 0.2) transparent; }
    </style>
</head>
<body class="text-gray-100 h-screen flex overflow-hidden">
    {{-- Sidebar --}}
    <aside id="sidebar" class="w-64 glass-strong flex flex-col h-screen shrink-0 z-10">
        {{-- Brand --}}
        <div class="brand-area p-3 flex items-center justify-between border-b border-white/5">
            <a href="/" class="brand-link flex items-center space-x-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-violet-600/20 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="brand-text font-semibold text-white/90 truncate">Smart Home</span>
            </a>
            <button onclick="document.getElementById('sidebar').classList.toggle('collapsed')" class="toggle-btn text-gray-500 hover:text-gray-300 transition-colors p-2.5 rounded-xl hover:bg-white/5 min-w-[44px] min-h-[44px] flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="nav-area flex-1 px-3 py-3 space-y-1.5">
            <a href="/"
               class="nav-link flex items-center px-3 py-3 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->is('/') ? 'nav-active' : 'text-gray-300 hover:text-white glass-hover' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon h-5 w-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="nav-label">Dashboard</span>
            </a>

            @foreach($navigation as $item)
                <a href="{{ route($item['route']) }}"
                   class="nav-link flex items-center px-3 py-3 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs($item['route'] . '*') ? 'nav-active' : 'text-gray-300 hover:text-white glass-hover' }}">
                    @if(isset($item['icon']))
                        <x-dynamic-component :component="'dashboard.icons.' . $item['icon']" class="nav-icon h-5 w-5 mr-3 shrink-0" />
                    @endif
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Bottom --}}
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        {{-- Header --}}
        @if (!$hideHeader)
        <header class="px-6 py-3.5 flex items-center justify-between shrink-0 border-b border-white/5">
            <div class="flex items-center space-x-3">
                <h1 class="text-base font-medium text-white">{{ $title }}</h1>
            </div>
            {{ $actions ?? '' }}
        </header>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
