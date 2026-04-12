<div class="w-full">
    <div id="progress-container" class="h-2 cursor-pointer bg-white/10 rounded-full relative touch-none">
        <div id="progress-bar" class="h-full bg-green-500 rounded-full relative" style="width:
            @if (isset($playbackState['progress_ms']) && isset($playbackState['item']['duration_ms']) && $playbackState['item']['duration_ms'] > 0)
                {{ ($playbackState['progress_ms'] / $playbackState['item']['duration_ms']) * 100 }}%
            @else
                0%
            @endif
        ">
            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 bg-white rounded-full shadow-lg -mr-1"></div>
        </div>
    </div>
    <div class="flex justify-between text-[11px] text-gray-500 mt-1 tabular-nums">
        <span id="current-time">{{ isset($playbackState['progress_ms']) ? gmdate("i:s", $playbackState['progress_ms'] / 1000) : '0:00' }}</span>
        <span id="duration">{{ isset($playbackState['item']['duration_ms']) ? gmdate("i:s", $playbackState['item']['duration_ms'] / 1000) : '0:00' }}</span>
    </div>
</div>
