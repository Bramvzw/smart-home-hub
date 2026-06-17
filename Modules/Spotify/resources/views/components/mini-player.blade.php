@php
    $durationMs = data_get($playbackState, 'item.duration_ms', 0);
    $progressMs = $playbackState['progress_ms'] ?? 0;
    $progressPercent = $durationMs > 0 ? ($progressMs / $durationMs) * 100 : 0;
    $isPlaying = $playbackState['is_playing'] ?? false;
@endphp

<footer class="spotify-mini" aria-label="Mini player">
    <div class="spotify-progress-row">
        <span data-current-time class="spotify-time">{{ $progressMs ? gmdate("i:s", $progressMs / 1000) : '0:00' }}</span>
        <div class="spotify-seek" aria-hidden="true">
            <div data-progress-fill class="spotify-seek-fill spotify-progress-fill" style="width: {{ $progressPercent }}%">
                <span class="spotify-seek-knob"></span>
            </div>
        </div>
        <span data-track-duration class="spotify-time">{{ $durationMs ? gmdate("i:s", $durationMs / 1000) : '0:00' }}</span>
    </div>

    <div class="spotify-mini-row">
        <button type="button" class="spotify-mini-now" data-tab-jump="panel-playing">
            <span class="spotify-mini-cover">
                <img data-track-image src="{{ data_get($playbackState, 'item.album.images.0.url', asset('images/no-track.webp')) }}" alt="">
            </span>
            <span class="spotify-mini-meta">
                <span data-track-name class="spotify-mini-title">{{ $playbackState['item']['name'] ?? 'Unknown track' }}</span>
                <span data-track-artists class="spotify-mini-artists">{{ collect($playbackState['item']['artists'] ?? [])->pluck('name')->join(', ') }}</span>
            </span>
        </button>

        <div class="spotify-mini-transport">
            <button type="button" class="spotify-mini-btn" data-spotify-control="shuffle" aria-label="Shuffle">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10.59 9.17 5.41 4 4 5.41l5.17 5.17 1.42-1.41ZM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5Zm.33 9.41-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13Z"/></svg>
            </button>
            <button type="button" class="spotify-mini-btn" data-spotify-control="previous" aria-label="Previous">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
            </button>
            <button type="button" class="spotify-mini-play" data-spotify-control="toggle-play" aria-label="{{ $isPlaying ? 'Pause' : 'Play' }}">
                @if($isPlaying)
                    <svg data-play-icon class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" data-playing="true"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
                @else
                    <svg data-play-icon class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24" data-playing="false"><path d="M8 5v14l11-7z"/></svg>
                @endif
            </button>
            <button type="button" class="spotify-mini-btn" data-spotify-control="next" aria-label="Next">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="m6 18 8.5-6L6 6v12ZM16 6v12h2V6h-2Z"/></svg>
            </button>
            <button type="button" class="spotify-mini-btn" data-spotify-control="repeat" aria-label="Repeat">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7Zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4Z"/></svg>
            </button>
        </div>

        <div class="spotify-mini-right">
            <button type="button" class="spotify-mini-btn" data-spotify-control="like" aria-label="Save">
                <svg data-like-icon class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 0 0 0 6.364L12 20.364l7.682-7.682a4.5 4.5 0 0 0-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 0 0-6.364 0Z"/></svg>
            </button>
            @if ($playbackState['device']['supports_volume'] ?? false)
                <input type="range"
                       class="spotify-volume-range spotify-mini-volume"
                       min="0"
                       max="100"
                       value="{{ $playbackState['device']['volume_percent'] ?? 50 }}"
                       data-volume-proxy
                       aria-label="Volume">
            @endif
        </div>
    </div>
</footer>
