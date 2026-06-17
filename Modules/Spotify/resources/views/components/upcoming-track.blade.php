@php
    $hasNextTrack = $upcomingTrack?->hasTrack ?? false;
    $hasNextImage = $hasNextTrack && $upcomingTrack->trackImage !== '';
@endphp

<div class="spotify-next-dock {{ $hasNextTrack ? '' : 'is-empty' }}" aria-label="Upcoming track">
    <div id="next-track" class="spotify-next-content">
        <div class="spotify-next-thumb {{ $hasNextImage ? '' : 'is-empty' }}">
            <img id="next-track-image"
                 src="{{ $hasNextImage ? $upcomingTrack->trackImage : asset('images/no-track.webp') }}"
                 alt=""
                 @if (! $hasNextImage) hidden @endif
                 loading="lazy">
        </div>
        <div class="spotify-next-copy">
            <div class="spotify-next-label">Next</div>
            <div id="next-track-name" class="spotify-next-title">{{ $upcomingTrack?->trackName ?? 'No upcoming track' }}</div>
            <div id="next-track-artists" class="spotify-next-artists">{{ $upcomingTrack?->artistNames ?? '' }}</div>
        </div>
        <button type="button" class="spotify-next-play play-next-track-btn" aria-label="Play next" @disabled(! $hasNextTrack)>
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="m6 18 8.5-6L6 6v12ZM16 6v12h2V6h-2Z"/></svg>
        </button>
    </div>
</div>
