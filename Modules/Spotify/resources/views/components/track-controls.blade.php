<div class="flex items-center justify-center space-x-2 mt-4">
    <button id="shuffle-btn" class="text-gray-600 hover:text-green-400 active:text-green-300 transition-colors p-2 min-w-[40px] min-h-[40px] flex items-center justify-center rounded-xl" data-shuffle-state="{{ ($playbackState['shuffle_state'] ?? false) ? 'true' : 'false' }}">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z"/></svg>
    </button>
    <button id="previous-btn" class="text-gray-400 hover:text-white active:scale-95 transition-all p-2 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
    </button>
    <button id="play-pause-btn" class="bg-white hover:bg-gray-100 active:scale-95 text-black rounded-full w-14 h-14 flex items-center justify-center transition-all duration-150 shadow-lg shadow-white/10">
        @if(isset($playbackState['is_playing']) && $playbackState['is_playing'])
            <svg id="play-pause-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" data-playing="true"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>
        @else
            <svg id="play-pause-icon" class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24" data-playing="false"><path d="M8 5v14l11-7z"/></svg>
        @endif
    </button>
    <button id="next-btn" class="text-gray-400 hover:text-white active:scale-95 transition-all p-2 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
    </button>
    <button id="like-btn" class="text-gray-600 hover:text-green-400 active:text-green-300 transition-colors p-2 min-w-[40px] min-h-[40px] flex items-center justify-center rounded-xl">
        <svg id="like-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </button>
    <button id="repeat-btn" class="relative text-gray-600 hover:text-green-400 active:text-green-300 transition-colors p-2 min-w-[40px] min-h-[40px] flex items-center justify-center rounded-xl" data-repeat-state="{{ $playbackState['repeat_state'] ?? 'off' }}">
        <svg id="repeat-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>
        <span id="repeat-dot" class="absolute bottom-0.5 w-1 h-1 rounded-full bg-green-400 {{ ($playbackState['repeat_state'] ?? 'off') === 'off' ? 'hidden' : '' }}"></span>
    </button>
</div>
