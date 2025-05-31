@if ($playbackState['device']['supports_volume'] ?? false)
    <div class="flex items-center">
        <i class="fas fa-volume-down text-gray-600 mr-2"></i>
        <input type="range" id="volume-slider"
               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer opacity-100 transition"
               min="0" max="100">
        <i class="fas fa-volume-up text-gray-600 ml-2"></i>
    </div>
@endif
