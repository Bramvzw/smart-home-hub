<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
    @foreach($playlists as $playlist)
        <div class="playlist-item hub-card flex items-center space-x-3 p-2.5 cursor-pointer transition-all duration-150 active:scale-[0.99] group min-h-[56px]"
             @if($playlist->id === 'liked-songs')
                 data-id="liked-songs"
             @else
                 data-uri="{{ $playlist->externalUrl }}"
             @endif
        >
            <div class="relative w-11 h-11 rounded-[7px] overflow-hidden bg-[var(--hub-elevated)] shrink-0 ring-1 ring-[var(--hub-line)]">
                <img src="{{ $playlist->imageUrl }}"
                     alt="{{ $playlist->name }}"
                     class="w-full h-full object-cover"
                     loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center bg-[#0d0e12]/70 opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-5 h-5 text-[var(--hub-text)]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-sm text-[var(--hub-muted)] group-hover:text-[var(--hub-text)] truncate block transition-colors font-semibold">{{ $playlist->name }}</span>
            </div>
        </div>
    @endforeach
</div>
