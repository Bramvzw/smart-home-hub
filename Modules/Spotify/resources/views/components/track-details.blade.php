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
