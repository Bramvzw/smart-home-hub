<x-dashboard.layout title="Spotify" :hideHeader="true">
    <x-slot:scripts>
        @if($isConnected && $isPlaying)
            <script>window.SPOTIFY_STATE = @json($playbackState);</script>
        @endif
        @vite(['Modules/Spotify/resources/assets/js/core/player.js'])
    </x-slot:scripts>
    <x-slot:head>
        <style>
            .spotify-ui { --spotify-accent: #34b8a0; font-family: inherit; }
            #progress-bar { transition: width 0.3s linear; }
            #track-image, #next-track-image { transition: opacity 0.5s ease, transform 0.5s ease; }

            .album-glow {
                filter: blur(80px) saturate(1.5);
                opacity: 0.18;
                position: absolute;
                inset: -40px;
                z-index: 0;
            }

            .spotify-tab {
                border: 1px solid var(--hub-line);
                border-radius: 7px;
                background: var(--hub-input);
                color: var(--hub-muted);
                transition: border-color 120ms ease, background 120ms ease, color 120ms ease;
            }

            .spotify-tab:hover {
                border-color: var(--hub-line-strong);
                color: var(--hub-text);
            }

            .tab-active {
                border-color: rgba(52, 184, 160, 0.4);
                background: rgba(52, 184, 160, 0.14);
                color: #95e2d3;
            }

            .tab-inactive { color: var(--hub-muted); }

            .track-row { transition: background 0.15s; }
            .track-row:hover { background: var(--hub-line); }

            input[type="range"] { -webkit-appearance: none; appearance: none; background: transparent; }
            input[type="range"]::-webkit-slider-runnable-track {
                height: 6px; border-radius: 999px; background: var(--hub-line-strong);
            }
            input[type="range"]::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 16px; height: 16px; border-radius: 50%;
                background: var(--spotify-accent); margin-top: -5px; cursor: pointer;
                box-shadow: 0 0 0 4px rgba(52, 184, 160, 0.14);
            }
        </style>
    </x-slot:head>

    <div class="spotify-ui flex flex-col h-full overflow-hidden relative bg-[var(--hub-surface)]">
        @if (! $isConnected)
            <x-spotify::connect-account :auth-url="$authUrl"/>
        @elseif (! $isPlaying)
            <div class="flex-1 flex items-center justify-center relative z-10">
                <x-spotify::empty-player/>
            </div>
        @else
            <div class="flex shrink-0 items-center justify-between border-b border-[var(--hub-line)] px-5 py-3">
                <div>
                    <h1 class="text-[19px] font-bold text-[var(--hub-text)] leading-tight">Spotify</h1>
                    <p class="text-xs font-semibold text-[var(--hub-dim)]">Playback control</p>
                </div>
                <div class="flex items-center gap-2" id="tab-bar">
                    <button data-tab="panel-playing" class="spotify-tab tab-active text-sm font-semibold px-3 min-h-[34px]">Now Playing</button>
                    <button data-tab="panel-search" class="spotify-tab tab-inactive text-sm font-semibold px-3 min-h-[34px]">Search</button>
                    <button data-tab="panel-playlists" class="spotify-tab tab-inactive text-sm font-semibold px-3 min-h-[34px]">Playlists</button>
                    <button data-tab="panel-queue" class="spotify-tab tab-inactive text-sm font-semibold px-3 min-h-[34px]">Queue</button>
                    <button data-tab="panel-recent" class="spotify-tab tab-inactive text-sm font-semibold px-3 min-h-[34px]">Recent</button>
                </div>
            </div>

            {{-- Panel: Now Playing --}}
            <div id="panel-playing" class="spotify-panel flex-1 flex overflow-hidden relative z-10">

                {{-- LEFT: Album Art + Track Info --}}
                <x-spotify::track-details :playback-state="$playbackState" />

                {{-- RIGHT: Controls --}}
                <div class="flex-1 flex flex-col justify-center px-6 py-5 min-w-0">

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
            <div id="panel-playlists" class="spotify-panel flex-1 overflow-y-auto px-5 py-4 hidden relative z-10">
                @if ($hasPlaylists)
                    <x-spotify::playlists/>
                @endif
            </div>

            {{-- Panel: Queue --}}
            <div id="panel-queue" class="spotify-panel flex-1 overflow-y-auto px-5 py-4 hidden relative z-10">
                <div id="queue-tracks-list" class="space-y-1">
                    <div class="text-center text-[var(--hub-dim)] text-sm py-8">Loading queue...</div>
                </div>
            </div>

            {{-- Panel: Recently Played --}}
            <div id="panel-recent" class="spotify-panel flex-1 overflow-y-auto px-5 py-4 hidden relative z-10">
                <div id="recent-tracks-list" class="space-y-1">
                    <div class="text-center text-[var(--hub-dim)] text-sm py-8">Loading recently played...</div>
                </div>
            </div>

            {{-- Panel: Search --}}
            <div id="panel-search" class="spotify-panel flex-1 flex flex-col overflow-hidden hidden relative z-10 px-5 py-4">
                <div class="shrink-0 mb-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--hub-dim)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input id="search-input" type="text" placeholder="Search for songs..." autocomplete="off"
                               class="hub-input w-full pl-10 pr-4 py-2.5 text-sm placeholder-[var(--hub-dim)]">
                    </div>
                </div>
                <div id="search-results" class="flex-1 overflow-y-auto space-y-1">
                    <div class="text-center text-[var(--hub-dim)] text-sm py-8">Search for tracks to play</div>
                </div>
            </div>
        @endif
    </div>
</x-dashboard.layout>
