<div class="flex flex-col items-center justify-center p-5 shrink-0" style="width: 280px;">
    <div class="relative w-full max-w-[200px] overflow-hidden">
        <img src="{{ data_get($playbackState, 'item.album.images.0.url', '') }}"
             class="album-glow rounded-3xl" aria-hidden="true" />
        <div class="relative rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/10 aspect-square">
            <img id="track-image"
                 src="{{ data_get($playbackState, 'item.album.images.0.url', asset('images/no-track.webp')) }}"
                 alt="Album Art"
                 class="w-full h-full object-cover" />
        </div>
    </div>
    <div class="text-center w-full min-w-0 mt-4">
        <h2 id="track-name" class="text-lg font-bold text-white truncate leading-tight">
            {{ $playbackState['item']['name'] ?? 'Unknown' }}
        </h2>
        <p id="artist-name" class="text-sm text-gray-400 mt-0.5 truncate">
            {{ collect($playbackState['item']['artists'] ?? [])->pluck('name')->join(', ') }}
        </p>
        <p id="album-name" class="text-xs text-gray-600 mt-0.5 truncate">
            {{ $playbackState['item']['album']['name'] ?? '' }}
        </p>
    </div>
</div>
