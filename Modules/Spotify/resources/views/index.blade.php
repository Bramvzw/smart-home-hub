<x-spotify::spotify-player
        :playback-state="$playbackState"
        :is-connected="$isConnected"
        :auth-url="$authUrl"
        :upcoming-track="$upcomingTrack"
        :playlists="$playlists"
/>
