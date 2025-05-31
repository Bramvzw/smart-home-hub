<div class="flex justify-center items-center space-x-8 mb-8">
    <button id="previous-btn" class="text-gray-400 hover:text-white focus:outline-none transition duration-200">
        <i class="fas fa-step-backward text-2xl"></i>
    </button>
    <button id="play-pause-btn"
            class="spotify-btn rounded-full w-14 h-14 flex items-center justify-center focus:outline-none">
        @if (isset($playbackState['is_playing']) && $playbackState['is_playing'])
            <i class="fas fa-pause text-xl"></i>
        @else
            <i class="fas fa-play text-xl"></i>
        @endif
    </button>
    <button id="next-btn" class="text-gray-400 hover:text-white focus:outline-none transition duration-200">
        <i class="fas fa-step-forward text-2xl"></i>
    </button>
    <button id="like-btn" class="text-gray-400 hover:text-white focus:outline-none transition duration-200 ml-4">
        <i class="far fa-heart text-xl"></i>
    </button>
</div>
