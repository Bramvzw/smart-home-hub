<div class="mt-4 pt-3 border-t border-[var(--hub-line)]">
    <div id="next-track" class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-[7px] overflow-hidden bg-[var(--hub-card)] shrink-0 ring-1 ring-[var(--hub-line)]">
            <img id="next-track-image" src="{{ $upcomingTrack->trackImage ?? asset('images/no-track.webp') }}" alt="Next" class="w-full h-full object-cover">
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-[11px] text-[var(--hub-dim)] uppercase tracking-wider">Next up</div>
            <div id="next-track-name" class="text-sm text-[var(--hub-muted)] font-semibold truncate">{{ $upcomingTrack->trackName ?? '' }}</div>
            <div id="next-track-artists" class="text-xs text-[var(--hub-dim)] truncate">{{ $upcomingTrack->artistNames ?? '' }}</div>
        </div>
    </div>
</div>
