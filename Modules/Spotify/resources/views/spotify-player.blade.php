<x-dashboard.layout title="Spotify" :hideHeader="true">
    <x-slot:scripts>
        @if($isConnected && $isPlaying)
            <script>window.SPOTIFY_STATE = @json($playbackState);</script>
        @endif
        @vite(['Modules/Spotify/resources/assets/js/core/player.js'])
    </x-slot:scripts>
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
        <style>
            .spotify-ui { font-family: 'DM Sans', system-ui, sans-serif; }
            #progress-bar { transition: width 0.3s linear; }
            #track-image, #next-track-image { transition: opacity 0.5s ease, transform 0.5s ease; }

            .ambient-bg {
                position: absolute; inset: 0; z-index: 0;
                background: radial-gradient(ellipse at 30% 50%, rgba(29, 185, 84, 0.06) 0%, transparent 60%),
                            radial-gradient(ellipse at 70% 30%, rgba(139, 92, 246, 0.04) 0%, transparent 50%);
            }

            .album-glow {
                filter: blur(80px) saturate(1.5);
                opacity: 0.25;
                position: absolute;
                inset: -40px;
                z-index: 0;
            }

            .tab-active { color: white; border-bottom: 2px solid #1DB954; }
            .tab-inactive { color: #6b7280; border-bottom: 2px solid transparent; }

            .track-row { transition: background 0.15s; }
            .track-row:hover { background: rgba(255,255,255,0.05); }

            input[type="range"] { -webkit-appearance: none; appearance: none; background: transparent; }
            input[type="range"]::-webkit-slider-runnable-track {
                height: 6px; border-radius: 3px; background: rgba(255,255,255,0.1);
            }
            input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 20px; height: 20px; border-radius: 50%;
                background: #1DB954; margin-top: -7px; cursor: pointer;
                box-shadow: 0 0 10px rgba(29,185,84,0.4);
            }
        </style>
    </x-slot:head>

    <div class="spotify-ui flex flex-col h-full overflow-hidden relative">
        <div class="ambient-bg"></div>

        @if (! $isConnected)
            <x-spotify::connect-account :auth-url="$authUrl"/>
        @elseif (! $isPlaying)
            <div class="flex-1 flex items-center justify-center relative z-10">
                <x-spotify::empty-player/>
            </div>
        @else
            {{-- Tab Bar --}}
            <div class="flex px-3 items-end space-x-6 border-b border-white/5 shrink-0 relative z-10" style="height: 68px;" id="tab-bar">
                <button data-tab="panel-playing" class="spotify-tab tab-active text-sm font-medium pb-2 px-1 min-h-[36px]">Now Playing</button>
                <button data-tab="panel-search" class="spotify-tab tab-inactive text-sm font-medium pb-2 px-1 min-h-[36px]">Search</button>
                <button data-tab="panel-playlists" class="spotify-tab tab-inactive text-sm font-medium pb-2 px-1 min-h-[36px]">Playlists</button>
                <button data-tab="panel-queue" class="spotify-tab tab-inactive text-sm font-medium pb-2 px-1 min-h-[36px]">Queue</button>
                <button data-tab="panel-recent" class="spotify-tab tab-inactive text-sm font-medium pb-2 px-1 min-h-[36px]">Recent</button>
            </div>

            {{-- Panel: Now Playing --}}
            <div id="panel-playing" class="spotify-panel flex-1 flex overflow-hidden relative z-10">

                {{-- LEFT: Album Art + Track Info --}}
                <x-spotify::track-details :playback-state="$playbackState" />

                {{-- RIGHT: Controls --}}
                <div class="flex-1 flex flex-col justify-center px-6 py-4 min-w-0">

                    {{-- Progress --}}
                    <x-spotify::track-progress :playback-state="$playbackState" />

                    {{-- Controls --}}
                    <x-spotify::track-controls :playback-state="$playbackState" />

                    {{-- Volume + Device --}}
                    <x-spotify::volume-slider :playback-state="$playbackState" />

                    {{-- Next Up --}}
                    <x-spotify::upcoming-track :upcoming-track="$upcomingTrack" />
                </div>
            </div>

            {{-- Panel: Playlists (hidden by default) --}}
            <div id="panel-playlists" class="spotify-panel flex-1 overflow-y-auto px-4 py-3 hidden relative z-10">
                @if ($hasPlaylists)
                    <x-spotify::playlists/>
                @endif
            </div>

            {{-- Panel: Queue --}}
            <div id="panel-queue" class="spotify-panel flex-1 overflow-y-auto px-4 py-3 hidden relative z-10">
                <div id="queue-tracks-list" class="space-y-1">
                    <div class="text-center text-gray-500 text-sm py-8">Loading queue...</div>
                </div>
            </div>

            {{-- Panel: Recently Played --}}
            <div id="panel-recent" class="spotify-panel flex-1 overflow-y-auto px-4 py-3 hidden relative z-10">
                <div id="recent-tracks-list" class="space-y-1">
                    <div class="text-center text-gray-500 text-sm py-8">Loading recently played...</div>
                </div>
            </div>

            {{-- Panel: Search --}}
            <div id="panel-search" class="spotify-panel flex-1 flex flex-col overflow-hidden hidden relative z-10 px-4 py-3">
                <div class="shrink-0 mb-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input id="search-input" type="text" placeholder="Search for songs..." autocomplete="off"
                               class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-green-500/50 focus:ring-1 focus:ring-green-500/30">
                    </div>
                </div>
                <div id="search-results" class="flex-1 overflow-y-auto space-y-1">
                    <div class="text-center text-gray-600 text-sm py-8">Search for tracks to play</div>
                </div>
            </div>
        @endif
    </div>
</x-dashboard.layout>
