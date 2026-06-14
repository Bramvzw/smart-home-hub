<div class="spotify-utility">
    @if ($playbackState['device']['supports_volume'] ?? false)
        <div class="spotify-volume">
            <button type="button" class="spotify-volume-icon" aria-label="Geluid dempen" data-spotify-control="mute">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3Zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02Z"/></svg>
            </button>
            <input type="range"
                   id="volume-slider"
                   class="spotify-volume-range"
                   min="0"
                   max="100"
                   value="{{ $playbackState['device']['volume_percent'] ?? 50 }}"
                   aria-label="Volume">
        </div>
    @endif

    <div class="spotify-device-area">
        <button type="button" id="device-btn" class="spotify-device-pill">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2Zm0 18H7V4h10v16Z"/></svg>
            <span id="device-name">{{ $playbackState['device']['name'] ?? 'Onbekend apparaat' }}</span>
            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="m7 10 5 5 5-5H7Z"/></svg>
        </button>
        <div id="device-list" class="spotify-device-menu hidden">
            <div class="spotify-device-menu-title">Kies apparaat</div>
            <div id="device-list-items" class="max-h-56 overflow-y-auto py-1">
                <div class="text-center text-[var(--spotify-dim)] text-xs py-3">Laden...</div>
            </div>
        </div>
    </div>
</div>
