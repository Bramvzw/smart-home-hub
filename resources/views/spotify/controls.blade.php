@extends('layouts.app')
@if ($isConnected)
    @vite(['resources/js/spotify.js'])
@endif

@section('title', 'Spotify Control')

@section('content')
    <div>
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Spotify Control</h1>
            <a href="{{ url('/') }}" class="text-blue-500 hover:text-blue-700">
                <i class="fas fa-home mr-2"></i>Home
            </a>
        </div>

        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            @if (!$isConnected)
                <div class="p-8 text-center">
                    <h2 class="text-2xl font-semibold mb-4">Connect to Spotify</h2>
                    <p class="text-gray-600 mb-6">To control your music, you need to connect to your Spotify
                        account.</p>
                    <a href="{{ $authUrl }}"
                       class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-full transition duration-300">
                        <i class="fab fa-spotify mr-2"></i>Connect to Spotify
                    </a>
                </div>
            @else
                <div id="player-container" class="p-8">
                    <div id="now-playing" class="flex items-center mb-8">
                        <div id="album-art" class="w-24 h-24 bg-gray-200 rounded-md mr-6 flex-shrink-0">
                            @if (isset($playbackState['item']['album']['images'][0]['url']))
                                <img id="track-image" src="{{ $playbackState['item']['album']['images'][0]['url'] }}" alt="Album Art"
                                     class="w-full h-full object-cover rounded-md">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-music text-3xl"></i>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h3 id="track-name" class="text-xl font-semibold">
                                {{ isset($playbackState['item']['name']) ? $playbackState['item']['name'] : 'No track playing' }}
                            </h3>
                            <p id="artist-name" class="text-gray-600">
                                @if (isset($playbackState['item']['artists']))
                                    {{ collect($playbackState['item']['artists'])->pluck('name')->join(', ') }}
                                @else
                                    Unknown artist
                                @endif
                            </p>
                            <p id="album-name" class="text-gray-500 text-sm">
                                {{ isset($playbackState['item']['album']['name']) ? $playbackState['item']['album']['name'] : 'Unknown album' }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span id="current-time">
                                @if (isset($playbackState['progress_ms']))
                                    {{ gmdate("i:s", $playbackState['progress_ms'] / 1000) }}
                                @else
                                    0:00
                                @endif
                            </span>
                            <span id="duration">
                                @if (isset($playbackState['item']['duration_ms']))
                                    {{ gmdate("i:s", $playbackState['item']['duration_ms'] / 1000) }}
                                @else
                                    0:00
                                @endif
                            </span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full">
                            <div id="progress-bar" class="h-full bg-green-500 rounded-full" style="width:
                                @if (isset($playbackState['progress_ms']) && isset($playbackState['item']['duration_ms']))
                                    {{ ($playbackState['progress_ms'] / $playbackState['item']['duration_ms']) * 100 }}%
                                @else
                                    0%
                                @endif
                            "></div>
                        </div>
                    </div>

                    <div class="flex justify-center items-center space-x-8 mb-8">
                        <button id="previous-btn" class="text-gray-700 hover:text-gray-900 focus:outline-none">
                            <i class="fas fa-step-backward text-2xl"></i>
                        </button>
                        <button id="play-pause-btn"
                                class="bg-green-500 hover:bg-green-600 text-white rounded-full w-14 h-14 flex items-center justify-center focus:outline-none">
                            @if (isset($playbackState['is_playing']) && $playbackState['is_playing'])
                                <i class="fas fa-pause text-xl"></i>
                            @else
                                <i class="fas fa-play text-xl"></i>
                            @endif
                        </button>
                        <button id="next-btn" class="text-gray-700 hover:text-gray-900 focus:outline-none">
                            <i class="fas fa-step-forward text-2xl"></i>
                        </button>
                    </div>
                    @if ($playbackState['device']['supports_volume'] ?? false)
                        <div class="flex items-center">
                            <i class="fas fa-volume-down text-gray-600 mr-2"></i>
                            <input type="range" id="volume-slider"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer opacity-100 transition"
                                   min="0" max="100">
                            <i class="fas fa-volume-up text-gray-600 ml-2"></i>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
