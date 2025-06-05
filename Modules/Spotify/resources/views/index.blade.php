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
                <div class="w-1/6 p-4 overflow-hidden mb-4 flex flex-col h-full">
                    <h3 class="text-xl font-semibold mb-3 text-white">Your Playlists</h3>
                    <div id="recently-played-container" class="grid grid-cols-2 gap-2 flex-grow overflow-hidden">
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

    {{-- Templates for dynamic elements used in spotify.js --}}
    <template id="playlist-item-template">
        <div class="playlist-item flex flex-col items-center rounded cursor-pointer transition-transform transform hover:scale-105 hover:shadow-lg active:brightness-90">
            <div class="w-full aspect-square bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-transform duration-300">
                <img src="" alt="" class="playlist-image w-full h-full object-cover hover:scale-105">
            </div>
        </div>
    </template>

    <template id="next-track-template">
        <div class="flex items-center justify-center w-full flex-col">
            <div class="h-14 w-20 bg-gray-800 rounded mb-3 flex-shrink-0 next-track-container">
                <img src="" alt="" class="next-track-image w-full h-full object-cover rounded">
                <div class="next-track-play-button">
                    <button class="play-next-track-btn text-white rounded-full w-8 h-8 flex items-center justify-center bg-green-500 hover:bg-green-600 focus:outline-none">
                        <i class="fas fa-play text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="flex-grow">
                <div class="next-track-name text-white text-sm font-medium"></div>
                <div class="next-track-artists text-gray-400 text-xs"></div>
            </div>
        </div>
    </template>

    <template id="message-template">
        <div class="text-center text-gray-400 py-2"></div>
    </template>

    <template id="alert-template">
        <div class="alert fixed top-4 right-4 p-4 rounded shadow-md">
            <p class="message"></p>
            <button class="absolute top-2 right-2 close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </template>
@endsection
