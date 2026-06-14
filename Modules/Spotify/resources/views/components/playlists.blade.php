<div class="spotify-playlists">
    @foreach($playlists as $playlist)
        <button type="button"
                class="playlist-item spotify-playlist-card"
                @if($playlist->id === 'liked-songs')
                    data-id="liked-songs"
                @else
                    data-uri="{{ $playlist->externalUrl }}"
                @endif
        >
            <span class="spotify-playlist-cover">
                <img src="{{ $playlist->imageUrl }}"
                     alt=""
                     loading="lazy">
                <span class="spotify-playlist-overlay" aria-hidden="true">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
            <span class="spotify-playlist-title">{{ $playlist->name }}</span>
        </button>
    @endforeach
</div>
