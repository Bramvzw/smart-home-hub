<section class="spotify-art-panel" aria-label="Albumhoes">
    <div class="spotify-art-wrap">
        <div class="spotify-cover-frame">
            <img id="track-image"
                 data-track-image
                 src="{{ data_get($playbackState, 'item.album.images.0.url', asset('images/no-track.webp')) }}"
                 alt="Albumhoes"
                 loading="eager">
        </div>
    </div>

    <x-spotify::upcoming-track :upcoming-track="$upcomingTrack" />
</section>
