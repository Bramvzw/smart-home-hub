@php $isMusic = ($context ?? 'concerten') === 'muziek'; @endphp

<div class="ent-spotify">
    <span class="ent-spotify-ico">{!! $eIc('Spotify', 30, 1.7) !!}</span>
    <div class="ent-spotify-title">Connect your Spotify</div>
    <div class="ent-spotify-sub">
        @if($isMusic)
            New releases are compiled from the artists you follow on <b>Spotify</b>.
            Connect your account to see your release feed.
        @else
            To sort concerts by <b>relevance</b> — <b>Followed</b>, in Hedon Zwolle, or might like —
            the hub uses the artists you follow on <b>Spotify</b>.
        @endif
    </div>
    <div class="ent-spotify-actions">
        <button class="ent-btn ent-btn-spotify" data-ent-refresh>
            {!! $eIc('Link', 15, 1.7) !!} Connect Spotify
        </button>
        @unless($isMusic)
            <a class="ent-btn ent-btn-ghost" href="{{ route('entertainment.concerts.index') }}" target="_blank" rel="noopener">
                {!! $eIc('Ticket', 15, 1.7) !!} Show everything anyway
            </a>
        @endunless
    </div>
    <div class="ent-spotify-note">
        {!! $eIc('Lock', 13, 1.7, 'ic') !!} The connection stays local on the hub — nothing goes outside
    </div>
</div>
