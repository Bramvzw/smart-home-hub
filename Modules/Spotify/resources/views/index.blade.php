@extends('spotify::layouts.app')
@if ($isConnected)
    @vite(['Modules/Spotify/resources/assets/js/spotify.js'])
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
                    <x-spotify::track-details :playback-state="$playbackState"/>

                    <x-spotify::track-progress :playback-state="$playbackState"/>

                   <x-spotify::track-controls :playback-state="$playbackState"/>

                    <x-spotify::volume-slider :playback-state="$playbackState"/>
                </div>
            @endif
        </div>
    </div>
@endsection
