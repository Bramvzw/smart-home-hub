<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-white">Coming Up</h3>
    </div>
    <div class="p-4 card-dark shrink-0 w-full">
        <div id="next-track" class="flex items-center justify-center w-full flex-col">
            <div class="bg-gray-800 rounded mb-3 flex-shrink-0 next">
                <img id="next-track-image" src="{{ $upcomingTrack->trackImage }}" alt="Next Track"
                     class="w-full h-full object-cover rounded">
            </div>
            <div class="flex-grow">
                <div id="next-track-name" class="text-white text-sm font-medium">{{ $upcomingTrack->trackName }}</div>
                <div id="next-track-artists" class="text-gray-400 text-xs">{{ $upcomingTrack->artistNames }}</div>
            </div>
        </div>
    </div>
</div>
