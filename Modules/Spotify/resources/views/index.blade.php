@extends('spotify::layouts.app')

@if ($isConnected)
    @vite(['Modules/Spotify/resources/assets/js/spotify.js'])
@endif

@section('title', 'Spotify Control')

@section('content')
    <div class="flex flex-col h-screen overflow-hidden bg-opacity-30 bg-black">
        @if (!$isConnected)
            <div class="flex-grow flex items-center justify-center overflow-hidden">
                <div class="dark-card p-8 text-center max-w-md mx-auto">
                    <h2 class="text-2xl font-semibold mb-4 text-white">Connect to Spotify</h2>
                    <p class="text-gray-300 mb-6">To control your music, you need to connect to your Spotify
                        account.</p>
                    <a href="{{ $authUrl }}"
                       class="spotify-btn inline-block font-bold py-3 px-6 rounded-full transition duration-300">
                        <i class="fab fa-spotify mr-2"></i>Connect to Spotify
                    </a>
                </div>
            </div>
        @else
            <div class="flex flex-grow overflow-hidden">
                <div class="w-1/8 p-4 overflow-hidden flex flex-col h-full">
                    <h3 class="text-xl font-semibold mb-3 text-white">Your Playlists</h3>
                    <div id="recently-played-container" class="grid grid-cols-2 gap-4 flex-grow overflow-auto">
                        <div class="text-center text-gray-400 py-4">
                            Loading your playlists...
                        </div>
                    </div>

                    <div class="p-4 dark-card shrink-0 w-full">
                        <h3 class="text-md font-semibold mb-2 text-white text-center">Next Up</h3>
                        <div id="next-track" class="flex items-center">
                            <div class="text-center text-gray-400 py-2">
                                Loading next track...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - Player and controls -->
                <div class="w-full p-4 flex flex-col overflow-hidden">
                    <div id="player-container" class="p-4 flex flex-col flex-grow overflow-hidden">
                        <div class="flex-grow flex items-center justify-center overflow-hidden">
                            <x-spotify::track-details :playback-state="$playbackState"/>
                        </div>

                        <div class="mt-4 shrink-0">
                            <x-spotify::track-progress :playback-state="$playbackState"/>
                            <x-spotify::track-controls :playback-state="$playbackState"/>
                            <x-spotify::volume-slider :playback-state="$playbackState"/>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
