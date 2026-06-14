@php
    $durationMs = data_get($playbackState, 'item.duration_ms', 0);
    $progressMs = $playbackState['progress_ms'] ?? 0;
    $progressPercent = $durationMs > 0 ? ($progressMs / $durationMs) * 100 : 0;
@endphp

<div class="spotify-progress">
    <div class="spotify-progress-row">
        <span id="current-time" data-current-time class="spotify-time">
            {{ $progressMs ? gmdate("i:s", $progressMs / 1000) : '0:00' }}
        </span>
        <div id="progress-container" class="spotify-seek" role="slider" aria-label="Voortgang" aria-valuemin="0" aria-valuemax="{{ $durationMs }}" aria-valuenow="{{ $progressMs }}" tabindex="0">
            <div id="progress-bar" data-progress-fill class="spotify-seek-fill spotify-progress-fill" style="width: {{ $progressPercent }}%">
                <span class="spotify-seek-knob"></span>
            </div>
        </div>
        <span id="duration" data-track-duration class="spotify-time">
            {{ $durationMs ? gmdate("i:s", $durationMs / 1000) : '0:00' }}
        </span>
    </div>
</div>
