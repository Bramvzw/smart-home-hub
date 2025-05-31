<div id="now-playing" class="flex flex-col items-center">
    <div id="album-art" class="w-5/6 bg-gray-800 rounded-lg shadow-xl mb-6  flex-shrink-0">
        @if (isset($playbackState['item']['album']['images'][0]['url']))
            <img id="track-image" src="{{ $playbackState['item']['album']['images'][0]['url'] }}" alt="Album Art"
                 class="w-full h-full object-cover rounded-lg">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <i class="fas fa-music text-5xl"></i>
            </div>
        @endif
    </div>
    <div class="text-center">
        <h3 id="track-name" class="text-2xl font-semibold text-white mb-2">
            {{ isset($playbackState['item']['name']) ? $playbackState['item']['name'] : 'No track playing' }}
        </h3>
        <p id="artist-name" class="text-gray-300 text-lg">
            @if (isset($playbackState['item']['artists']))
                {{ collect($playbackState['item']['artists'])->pluck('name')->join(', ') }}
            @else
                Unknown artist
            @endif
        </p>
        <p id="album-name" class="text-gray-400 text-sm">
            {{ isset($playbackState['item']['album']['name']) ? $playbackState['item']['album']['name'] : 'Unknown album' }}
        </p>
    </div>
</div>
