<div class="mb-6">
    <div class="flex justify-between text-xs text-gray-400 mb-1">
        <span id="current-time">
            @if (isset($playbackState['progress_ms']))
                {{ gmdate("i:s", $playbackState['progress_ms'] / 1000) }}
            @else
                0:00
            @endif
        </span>
        <span id="duration">
            @if (isset($playbackState['item']['duration_ms']))
                {{ gmdate("i:s", $playbackState['item']['duration_ms'] / 1000) }}
            @else
                0:00
            @endif
        </span>
    </div>
    <div id="progress-container" class="h-2 bg-gray-700 rounded-full progress-container">
        <div id="progress-bar" class="h-full spotify-accent-bg rounded-full" style="width:
            @if (isset($playbackState['progress_ms']) && isset($playbackState['item']['duration_ms']))
                {{ ($playbackState['progress_ms'] / $playbackState['item']['duration_ms']) * 100 }}%
            @else
                0%
            @endif
        "></div>
    </div>
</div>
