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
