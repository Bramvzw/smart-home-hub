@if ($playbackState['device']['supports_volume'] ?? false)
    <div class="flex justify-center">
        <div class="flex items-center w-1/3 max-w-xl">
            <i class="fas fa-volume-down text-gray-400 mr-2"></i>
            <input type="range" id="volume-slider"
                   class="flex-grow h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer opacity-100 transition"
                   min="0" max="100" value="{{ $playbackState['device']['volume_percent'] ?? 50 }}">
            <i class="fas fa-volume-up text-gray-400 ml-2"></i>
        </div>
    </div>
@endif
