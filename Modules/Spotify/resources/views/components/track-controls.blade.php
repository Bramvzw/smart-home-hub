<div class="spotify-controls">
    <button type="button"
            id="shuffle-btn"
            class="spotify-icon-btn"
            data-shuffle-state="{{ ($playbackState['shuffle_state'] ?? false) ? 'true' : 'false' }}"
            aria-label="Shuffle">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10.59 9.17 5.41 4 4 5.41l5.17 5.17 1.42-1.41ZM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5Zm.33 9.41-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13Z"/></svg>
    </button>
    <button type="button" id="previous-btn" class="spotify-icon-btn" aria-label="Previous">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
    </button>
    <button type="button"
            id="play-pause-btn"
            class="spotify-play-btn"
            aria-label="{{ ($playbackState['is_playing'] ?? false) ? 'Pause' : 'Play' }}">
        @if($playbackState['is_playing'] ?? false)
            <svg id="play-pause-icon" data-play-icon class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" data-playing="true"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
        @else
            <svg id="play-pause-icon" data-play-icon class="w-8 h-8 ml-0.5" fill="currentColor" viewBox="0 0 24 24" data-playing="false"><path d="M8 5v14l11-7z"/></svg>
        @endif
    </button>
    <button type="button" id="next-btn" class="spotify-icon-btn" aria-label="Next">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="m6 18 8.5-6L6 6v12ZM16 6v12h2V6h-2Z"/></svg>
    </button>
    <button type="button" id="repeat-btn" class="spotify-icon-btn" data-repeat-state="{{ $playbackState['repeat_state'] ?? 'off' }}" aria-label="Repeat">
        <svg id="repeat-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7Zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4Z"/></svg>
        <span id="repeat-dot" class="hidden"></span>
    </button>
    <button type="button" id="like-btn" class="spotify-icon-btn" aria-label="Save">
        <svg id="like-icon" data-like-icon class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 0 0 0 6.364L12 20.364l7.682-7.682a4.5 4.5 0 0 0-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 0 0-6.364 0Z"/></svg>
    </button>
</div>
