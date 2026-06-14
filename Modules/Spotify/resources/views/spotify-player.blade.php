<x-dashboard.layout title="Spotify" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Spotify/resources/assets/css/player.css'])
    </x-slot:head>

    <x-slot:scripts>
        @if($isConnected)
            <script>window.SPOTIFY_STATE = @json($playbackState ?? []);</script>
        @endif
        @vite(['Modules/Spotify/resources/assets/js/core/player.js'])
    </x-slot:scripts>

    <div class="spotify-ui">
        <div class="spotify-shell">
            @if (! $isConnected)
                <div class="flex-1 grid place-items-center p-6">
                    <x-spotify::connect-account :auth-url="$authUrl"/>
                </div>
            @else
                <template id="message-template">
                    <div class="text-center text-[var(--spotify-dim)] text-sm py-3"></div>
                </template>

                <header class="spotify-top">
                    <nav class="spotify-tabs" id="tab-bar" aria-label="Spotify onderdelen">
                        <button type="button"
                                data-tab="panel-playing"
                                class="spotify-tab {{ $hasCurrentTrack ? 'tab-active is-active' : 'tab-inactive' }}"
                                aria-selected="{{ $hasCurrentTrack ? 'true' : 'false' }}">
                            <span>Nu aan het afspelen</span>
                        </button>
                        <button type="button"
                                data-tab="panel-search"
                                class="spotify-tab {{ $hasCurrentTrack ? 'tab-inactive' : 'tab-active is-active' }}"
                                aria-selected="{{ $hasCurrentTrack ? 'false' : 'true' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-5.15a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z"/></svg>
                            <span>Zoeken</span>
                        </button>
                        <button type="button" data-tab="panel-playlists" class="spotify-tab tab-inactive" aria-selected="false">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                            <span>Afspeellijsten</span>
                        </button>
                        <button type="button" data-tab="panel-queue" class="spotify-tab tab-inactive" aria-selected="false">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h11M4 12h11M4 17h7"/><circle cx="18.5" cy="16" r="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 16V9l-2 .6"/></svg>
                            <span>Wachtrij</span>
                        </button>
                        <button type="button" data-tab="panel-recent" class="spotify-tab tab-inactive" aria-selected="false">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2"/></svg>
                            <span>Recent</span>
                        </button>
                    </nav>
                </header>

                <div id="panel-playing" class="spotify-panel spotify-now {{ $hasCurrentTrack ? '' : 'hidden' }}">
                    <x-spotify::track-details :playback-state="$playbackState" :upcoming-track="$upcomingTrack" />
                    <section class="spotify-main" aria-label="Bediening">
                        <div class="spotify-eyebrow">
                            <span class="spotify-eq {{ ($playbackState['is_playing'] ?? false) ? 'is-on' : '' }}" data-playing-indicator aria-hidden="true">
                                <span style="--eq-i: 0"></span>
                                <span style="--eq-i: 1"></span>
                                <span style="--eq-i: 2"></span>
                                <span style="--eq-i: 3"></span>
                            </span>
                            <span>Nu aan het afspelen</span>
                        </div>
                        <h2 id="track-name" data-track-name class="spotify-title">{{ $playbackState['item']['name'] ?? 'Onbekend nummer' }}</h2>
                        <p id="artist-name" data-track-artists class="spotify-artist">
                            {{ collect($playbackState['item']['artists'] ?? [])->pluck('name')->join(', ') }}
                        </p>
                        <p id="album-name" data-track-album class="spotify-album">{{ $playbackState['item']['album']['name'] ?? '' }}</p>

                        <div class="mt-8">
                            <x-spotify::track-progress :playback-state="$playbackState" />
                        </div>

                        <x-spotify::track-controls :playback-state="$playbackState" />
                        <x-spotify::volume-slider :playback-state="$playbackState" />
                    </section>
                </div>

                <div id="panel-playlists" class="spotify-panel spotify-tabview hidden">
                    <div class="spotify-section-head">
                        <div>
                            <p class="spotify-section-kicker">Bibliotheek</p>
                            <h2 class="spotify-section-title">Jouw afspeellijsten</h2>
                        </div>
                    </div>
                    <div class="spotify-scroll-area">
                        @if ($hasPlaylists)
                            <x-spotify::playlists/>
                        @else
                            <div class="text-center text-[var(--spotify-dim)] text-sm py-8">Geen afspeellijsten gevonden</div>
                        @endif
                    </div>
                    <x-spotify::mini-player :playback-state="$playbackState" />
                </div>

                <div id="panel-queue" class="spotify-panel spotify-tabview hidden">
                    <div class="spotify-section-head">
                        <div>
                            <p class="spotify-section-kicker">Hierna</p>
                            <h2 class="spotify-section-title">Wachtrij</h2>
                        </div>
                    </div>
                    <div class="spotify-current-row">
                        <span class="spotify-eyebrow">
                            <span class="spotify-eq {{ ($playbackState['is_playing'] ?? false) ? 'is-on' : '' }}" data-playing-indicator aria-hidden="true">
                                <span style="--eq-i: 0"></span>
                                <span style="--eq-i: 1"></span>
                                <span style="--eq-i: 2"></span>
                                <span style="--eq-i: 3"></span>
                            </span>
                            <span>Nu</span>
                        </span>
                        <span class="spotify-row-thumb">
                            <img data-track-image src="{{ data_get($playbackState, 'item.album.images.0.url', asset('images/no-track.webp')) }}" alt="">
                        </span>
                        <span class="spotify-row-meta">
                            <span data-track-name class="spotify-row-title">{{ $playbackState['item']['name'] ?? 'Onbekend nummer' }}</span>
                            <span data-track-artists class="spotify-row-subtitle">{{ collect($playbackState['item']['artists'] ?? [])->pluck('name')->join(', ') }}</span>
                        </span>
                        <span class="spotify-row-time">{{ isset($playbackState['item']['duration_ms']) ? gmdate("i:s", $playbackState['item']['duration_ms'] / 1000) : '0:00' }}</span>
                    </div>
                    <div id="queue-tracks-list" class="spotify-panel-list">
                        <div class="text-center text-[var(--spotify-dim)] text-sm py-8">Wachtrij laden...</div>
                    </div>
                    <x-spotify::mini-player :playback-state="$playbackState" />
                </div>

                <div id="panel-recent" class="spotify-panel spotify-tabview hidden">
                    <div class="spotify-section-head">
                        <div>
                            <p class="spotify-section-kicker">Geschiedenis</p>
                            <h2 class="spotify-section-title">Recent afgespeeld</h2>
                        </div>
                    </div>
                    <div id="recent-tracks-list" class="spotify-panel-list">
                        <div class="text-center text-[var(--spotify-dim)] text-sm py-8">Recent afgespeeld laden...</div>
                    </div>
                    <x-spotify::mini-player :playback-state="$playbackState" />
                </div>

                <div id="panel-search" class="spotify-panel spotify-tabview {{ $hasCurrentTrack ? 'hidden' : '' }}">
                    <div class="spotify-search-bar">
                        <svg class="w-5 h-5 text-[var(--spotify-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-5.15a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z"/></svg>
                        <input id="search-input" type="text" placeholder="Artiesten, nummers, podcasts" autocomplete="off" spellcheck="false">
                    </div>
                    <div class="spotify-search-chips" aria-label="Snelle zoekopdrachten">
                        <button type="button" class="spotify-chip" data-search-chip="KATNUF">KATNUF</button>
                        <button type="button" class="spotify-chip" data-search-chip="Ronnie Flex">Ronnie Flex</button>
                        <button type="button" class="spotify-chip" data-search-chip="Zomerhits">Zomerhits</button>
                        <button type="button" class="spotify-chip" data-search-chip="Frenna">Frenna</button>
                    </div>
                    <div class="spotify-section-head">
                        <div>
                            <p class="spotify-section-kicker">Zoeken</p>
                            <h2 class="spotify-section-title">Vind iets voor nu</h2>
                        </div>
                    </div>
                    <div id="search-results" class="spotify-search-results">
                        <div class="text-center text-[var(--spotify-dim)] text-sm py-8">Zoek naar nummers, albums of afspeellijsten</div>
                    </div>
                    <x-spotify::mini-player :playback-state="$playbackState" />
                </div>
            @endif
        </div>
    </div>
</x-dashboard.layout>
