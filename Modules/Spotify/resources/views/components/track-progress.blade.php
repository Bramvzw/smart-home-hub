<div class="w-full">
    <div id="progress-container" class="h-2 cursor-pointer bg-[var(--hub-line-strong)] rounded-full relative touch-none">
        <div id="progress-bar" class="h-full bg-[#34b8a0] rounded-full relative" style="width:
            @if (isset($playbackState['progress_ms']) && isset($playbackState['item']['duration_ms']) && $playbackState['item']['duration_ms'] > 0)
                {{ ($playbackState['progress_ms'] / $playbackState['item']['duration_ms']) * 100 }}%
            @else
                0%
            @endif
        ">
            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 bg-[#d8fff7] rounded-full shadow-lg -mr-1"></div>
        </div>
    </div>
    <div class="flex justify-between text-[11px] text-[var(--hub-dim)] mt-1 tabular-nums">
        <span id="current-time">{{ isset($playbackState['progress_ms']) ? gmdate("i:s", $playbackState['progress_ms'] / 1000) : '0:00' }}</span>
        <span id="duration">{{ isset($playbackState['item']['duration_ms']) ? gmdate("i:s", $playbackState['item']['duration_ms'] / 1000) : '0:00' }}</span>
    </div>
</div>
