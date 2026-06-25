@php $isMusic = ($context ?? 'concerten') === 'muziek'; @endphp

<div class="ent-spotify">
    <span class="ent-spotify-ico">{!! $eIc('Spotify', 30, 1.7) !!}</span>
    <div class="ent-spotify-title">Koppel je Spotify</div>
    <div class="ent-spotify-sub">
        @if($isMusic)
            Nieuwe releases worden samengesteld uit de artiesten die je op <b>Spotify</b> volgt.
            Koppel je account om je release-feed te zien.
        @else
            Om concerten op <b>relevantie</b> te sorteren — <b>Gevolgd</b>, in Hedon Zwolle, of misschien
            leuk — gebruikt de hub de artiesten die je op <b>Spotify</b> volgt.
        @endif
    </div>
    <div class="ent-spotify-actions">
        <button class="ent-btn ent-btn-spotify" data-ent-refresh>
            {!! $eIc('Link', 15, 1.7) !!} Spotify koppelen
        </button>
        @unless($isMusic)
            <a class="ent-btn ent-btn-ghost" href="{{ route('entertainment.concerts.index') }}" target="_blank" rel="noopener">
                {!! $eIc('Ticket', 15, 1.7) !!} Toch alles tonen
            </a>
        @endunless
    </div>
    <div class="ent-spotify-note">
        {!! $eIc('Lock', 13, 1.7, 'ic') !!} Koppeling blijft lokaal op de hub — niets gaat naar buiten
    </div>
</div>
