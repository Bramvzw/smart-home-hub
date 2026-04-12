<div class="grid grid-cols-2 gap-2">
    @foreach($playlists as $playlist)
        <div class="playlist-item flex items-center space-x-3 p-2.5 rounded-xl cursor-pointer transition-all duration-150 hover:bg-white/5 active:bg-white/10 group min-h-[52px]"
             @if($playlist->id === 'liked-songs')
                 data-id="liked-songs"
             @else
                 data-uri="{{ $playlist->externalUrl }}"
             @endif
        >
            <div class="w-11 h-11 rounded-lg overflow-hidden bg-white/5 shrink-0 ring-1 ring-white/5">
                <img src="{{ $playlist->imageUrl }}"
                     alt="{{ $playlist->name }}"
                     class="w-full h-full object-cover"
                     loading="lazy">
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-sm text-gray-300 group-hover:text-white truncate block transition-colors font-medium">{{ $playlist->name }}</span>
            </div>
        </div>
    @endforeach
</div>
