<div class="mt-4 pt-3 border-t border-white/5">
    <div id="next-track" class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-lg overflow-hidden bg-white/5 shrink-0 ring-1 ring-white/5">
            <img id="next-track-image" src="{{ $upcomingTrack->trackImage ?? asset('images/no-track.webp') }}" alt="Next" class="w-full h-full object-cover">
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-[11px] text-gray-600 uppercase tracking-wider">Next up</div>
            <div id="next-track-name" class="text-sm text-gray-300 font-medium truncate">{{ $upcomingTrack->trackName ?? '' }}</div>
            <div id="next-track-artists" class="text-xs text-gray-600 truncate">{{ $upcomingTrack->artistNames ?? '' }}</div>
        </div>
    </div>
</div>
