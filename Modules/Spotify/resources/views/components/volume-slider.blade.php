<div class="flex items-center justify-between mt-4">
    @if ($playbackState['device']['supports_volume'] ?? false)
    <div class="flex items-center space-x-2.5 flex-1">
        <svg class="w-4 h-4 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
        <input type="range" id="volume-slider" class="w-28 touch-none" min="0" max="100" value="{{ $playbackState['device']['volume_percent'] ?? 50 }}">
    </div>
    @else
    <div></div>
    @endif
    <div class="relative">
        <button id="device-btn" class="flex items-center space-x-1.5 text-[11px] text-gray-600 hover:text-gray-400 transition-colors">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 18H7V4h10v16z"/></svg>
            <span id="device-name">{{ $playbackState['device']['name'] ?? 'Unknown' }}</span>
            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
        </button>
        <div id="device-list" class="hidden absolute bottom-full right-0 mb-2 w-56 bg-[#1a1a2e] border border-white/10 rounded-xl shadow-xl overflow-hidden z-50">
            <div class="px-3 py-2 text-[10px] text-gray-500 uppercase tracking-wider border-b border-white/5">Select device</div>
            <div id="device-list-items" class="py-1 max-h-48 overflow-y-auto">
                <div class="text-center text-gray-600 text-xs py-3">Loading...</div>
            </div>
        </div>
    </div>
</div>
