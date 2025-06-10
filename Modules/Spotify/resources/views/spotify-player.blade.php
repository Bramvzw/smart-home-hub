@extends('spotify::layouts.app')
@vite(['Modules/Spotify/resources/assets/js/core/player.js'])

@section('title', 'Spotify Control')

@section('content')
    <div class="flex flex-col h-screen overflow-hidden bg-opacity-30 bg-black">
        @if (! $isConnected)
            <x-spotify::connect-account :auth-url="$authUrl"/>
        @else
            <div class="flex flex-grow overflow-hidden">
                <div id="sidebar"
                    class="flex-shrink-0 w-[18rem] min-w-[16rem] max-w-[30rem] p-4 flex flex-col h-full"
                >
                    @if ($isPlaying)
                    <div class="bg-gray-800 p-4 rounded-lg shadow-lg mb-4">
                        <x-spotify::upcoming-track/>
                    </div>
                    @endif

                    {{-- Playlists --}}
                    <div class="overflow-y-auto flex-grow">
                        @if ($hasPlaylists)
                            <x-spotify::playlists/>
                        @endif
                    </div>
                </div>

                @if ($isPlaying)
                    <div class="w-full p-4 flex flex-col overflow-hidden">
                        <div id="player-container" class="p-4 flex flex-col flex-grow overflow-hidden">
                            {{-- track details --}}
                            <div class="flex-grow flex items-center justify-center overflow-hidden">
                                <x-spotify::track-details :playback-state="$playbackState"/>
                            </div>

                            {{-- progress + controls --}}
                            <div class="mt-4 shrink-0">
                                <x-spotify::track-progress :playback-state="$playbackState"/>
                                <x-spotify::track-controls :playback-state="$playbackState"/>
                                <x-spotify::volume-slider :playback-state="$playbackState"/>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="w-full p-4 flex flex-col overflow-hidden">
                        <div id="player-container" class="p-4 flex-grow flex items-center justify-center">
                            <x-spotify::empty-player/>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
