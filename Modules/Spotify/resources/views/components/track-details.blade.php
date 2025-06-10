<div>
    <div id="album-art" class="w-full h-3/4 rounded-lg shadow-xl mb-12 flex-shrink-0">
        <img
                id="track-image"
                src="{{ $trackDetails->trackImage }}"
                alt="Album Art"
                class="w-full h-full object-cover rounded-lg"
        />
    </div>
    <div class="text-center">
        <h3 id="track-name" class="text-2xl font-semibold text-white mb-2">
            {{ $trackDetails->trackName }}
        </h3>
        <p id="artist-name" class="text-gray-300 text-lg">
            {{ $trackDetails->artistNames }}
        </p>
        <p id="album-name" class="text-gray-400 text-sm">
            {{ $trackDetails->albumName }}
        </p>
    </div>
</div>
