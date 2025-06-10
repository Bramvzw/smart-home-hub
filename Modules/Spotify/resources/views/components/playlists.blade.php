<div class="h-full">
    <h3 class="text-xl font-semibold mb-3 text-white">Your Playlists</h3>
    <div class="grid grid-cols-2 gap-2 overflow-hidden">
        @foreach($playlists as $playlist)
            <div
                    class="playlist-item flex flex-col p-1 items-center rounded cursor-pointer"
                    @if($playlist->id === 'liked-songs')
                        data-id="liked-songs"
                    @else
                        data-uri="{{ $playlist->externalUrl }}"
                    @endif
            >
                <div
                        class="w-full
                 aspect-square
                 bg-gray-800
                 rounded-lg
                 shadow-lg
                 overflow-hidden
                 min-w-[100px]
                 max-w-[150px]"
                >
                    <img
                            src="{{ $playlist->imageUrl }}"
                            alt="{{ $playlist->name }}"
                            class="w-full h-full object-cover"
                    >
                </div>
            </div>
        @endforeach
    </div>
</div>
