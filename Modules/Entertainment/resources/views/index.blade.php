<x-dashboard.layout title="Entertainment & muziek" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Entertainment/resources/assets/css/entertainment.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Entertainment/resources/assets/js/entertainment.js'])
    </x-slot:scripts>

    @php
        // ---- icon closure: ports the EIc SVGs from ent-core.jsx ----
        $eIc = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $fill = in_array($name, ['StarF', 'Play'], true);
            $inner = match ($name) {
                'Film' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M8 4v16M16 4v16M3 9h5M16 9h5M3 15h5M16 15h5"/>',
                'Clapper' => '<path d="M3 9.5h18V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 9.5 4.4 5l16.2 0L21 9.5M8 9.5 9.4 5M13 9.5 14.4 5"/>',
                'Ticket' => '<path d="M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5V10a2 2 0 0 0 0 4v2.5A1.5 1.5 0 0 1 18.5 18h-13A1.5 1.5 0 0 1 4 16.5V14a2 2 0 0 0 0-4z"/><path d="M14 6v12"/>',
                'Disc' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="2.4"/>',
                'Note' => '<path d="M9 18V6l11-2v12"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
                'Spotify' => '<circle cx="12" cy="12" r="9"/><path d="M7.5 9.5c3-1 6.5-.7 9 1M8 12.6c2.4-.7 5.2-.5 7.2 .9M8.7 15.4c1.8-.5 3.9-.4 5.4 .7"/>',
                'ThumbUp' => '<path d="M7 10v10H4a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1z"/><path d="M7 10l4-7a2 2 0 0 1 2.6 2.4L12.5 9h5.4a2 2 0 0 1 2 2.4l-1.3 6.4A2 2 0 0 1 16.6 20H7"/>',
                'ThumbDown' => '<path d="M7 14V4H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1z"/><path d="M7 14l4 7a2 2 0 0 0 2.6-2.4L12.5 15h5.4a2 2 0 0 0 2-2.4l-1.3-6.4A2 2 0 0 0 16.6 4H7"/>',
                'Star' => '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 17l-5.2 2.7 1-5.9L3.5 9.7l5.9-.8z"/>',
                'StarF' => '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 17l-5.2 2.7 1-5.9L3.5 9.7l5.9-.8z"/>',
                'Sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
                'Refresh' => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 4v5h-5"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'X' => '<path d="M6 6l12 12M18 6 6 18"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'ArrowR' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'Calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
                'Pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 0 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
                'Home' => '<path d="M4 11.5 12 5l8 6.5"/><path d="M6 10.5V20h12v-9.5"/>',
                'Play' => '<path d="M7 5l12 7-12 7z"/>',
                'Link' => '<path d="M9.5 14.5 14.5 9.5"/><path d="M8 11 5.8 13.2a3.5 3.5 0 0 0 5 5L13 16M16 13l2.2-2.2a3.5 3.5 0 0 0-5-5L11 8"/>',
                'Lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
                'Info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                'Filter' => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
                default => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M8 4v16M16 4v16M3 9h5M16 9h5M3 15h5M16 15h5"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="'.($fill ? 'currentColor' : 'none').'" stroke="'.($fill ? 'none' : 'currentColor').'" stroke-width="'.($fill ? 0 : $stroke).'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        // ---- availability platform meta (beschikbaarheid) ----
        $platMeta = [
            'cinema' => ['label' => 'Bios', 'cls' => 'bios'],
            'netflix' => ['label' => 'Netflix', 'cls' => 'netflix'],
            'prime' => ['label' => 'Prime', 'cls' => 'prime'],
        ];

        // ---- relevance meta (concerten) ----
        $relMeta = [
            'followed' => ['label' => 'Gevolgd', 'cls' => 'gevolgd', 'icon' => 'Spotify'],
            'hedon' => ['label' => 'Hedon', 'cls' => 'hedon', 'icon' => 'Home'],
            'might_like' => ['label' => 'Misschien leuk', 'cls' => 'misschien', 'icon' => 'Sparkle'],
        ];

        // ---- release type meta (muziek) ----
        $typeMeta = ['album' => 'Album', 'single' => 'Single', 'ep' => 'EP'];

        // ---- derive a poster glyph from the film title ----
        $filmGlyph = static function (string $title): string {
            $t = mb_strtolower($title);

            return match (true) {
                str_contains($t, 'documentaire') || str_contains($t, 'docu') => 'Clapper',
                default => 'Film',
            };
        };

        // ---- normalize state ----
        $films = collect($films);
        $concerts = collect($concerts);
        $music = collect($music);

        $filmCount = $films->count();
        $concertCount = $concerts->count();
        $musicCount = $music->count();

        $hasFilms = $filmCount > 0;
        $hasConcerts = $concertCount > 0;
        $hasMusic = $musicCount > 0;

        $monthsNl = [1 => 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
        $daysNl = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

        $initialTab = 'films';
    @endphp

    <div class="ent"
         data-ent
         data-initial-tab="{{ $initialTab }}"
         data-refresh-url="{{ route('entertainment.refresh') }}"
         data-taste-url="{{ route('entertainment.taste.update') }}">
        <div class="ent-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="ent-head">
                <div class="ent-head-l">
                    <span class="ent-eyebrow">{!! $eIc('Note', 14, 1.7, 'ic') !!} Aanbevolen voor jou</span>
                    <h1 class="ent-title disp">Entertainment &amp; muziek</h1>
                    <div class="ent-sub" data-ent-sub="films">
                        <b class="tnum">{{ $filmCount }}</b> films voor jou<span class="dot">·</span>gecureerd op je <b>smaakprofiel</b>
                    </div>
                    <div class="ent-sub" data-ent-sub="concerten" hidden>
                        <b class="tnum">{{ $concertCount }}</b> concerten<span class="dot">·</span><b>heel NL</b> + Hedon Zwolle
                    </div>
                    <div class="ent-sub" data-ent-sub="muziek" hidden>
                        <b class="tnum">{{ $musicCount }}</b> nieuwe releases<span class="dot">·</span>van je <b>gevolgde artiesten</b>
                    </div>
                </div>
                <div class="ent-head-r">
                    <span class="ent-conn {{ ($spotifyConnected ?? false) ? 'on' : 'off' }}">
                        <span class="dot-led"></span>
                        {{ ($spotifyConnected ?? false) ? 'Spotify gekoppeld' : 'Spotify niet gekoppeld' }}
                    </span>
                    <button class="ent-btn ent-btn-primary" data-ent-refresh>
                        {!! $eIc('Refresh', 15, 1.7, 'ic') !!} Vernieuwen
                    </button>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="ent-tabs">
                <button class="ent-tab on" data-ent-tab="films">
                    {!! $eIc('Film', 15, 1.7, 'ic') !!} Films
                    <span class="ent-tab-count tnum">{{ $filmCount }}</span>
                </button>
                <button class="ent-tab" data-ent-tab="concerten">
                    {!! $eIc('Ticket', 15, 1.7, 'ic') !!} Concerten
                    <span class="ent-tab-count tnum">{{ $concertCount }}</span>
                </button>
                <button class="ent-tab" data-ent-tab="muziek">
                    {!! $eIc('Disc', 15, 1.7, 'ic') !!} Nieuwe muziek
                    <span class="ent-tab-count tnum">{{ $musicCount }}</span>
                </button>
            </div>

            {{-- ============ FILMS PANEL ============ --}}
            <div data-ent-panel="films">
                @if($hasFilms)
                    <div class="ent-strip">
                        {!! $eIc('Sparkle', 15, 1.7, 'ic') !!}
                        <span>Aanbevolen op basis van je <b>smaakprofiel</b> — gesorteerd op match.</span>
                    </div>

                    <div class="ent-films">
                        <div class="ent-film-grid">
                            @foreach($films as $film)
                                @php $glyph = $filmGlyph($film['title']); @endphp
                                <div class="ent-film" data-ent-film="{{ $film['id'] }}"
                                     data-ent-feedback-url="{{ route('entertainment.films.feedback', $film['id']) }}"
                                     data-ent-dismiss-url="{{ route('entertainment.films.dismiss', $film['id']) }}">
                                    <div class="ent-poster">
                                        @if(($film['score'] ?? null) !== null)
                                            <span class="ent-match">{!! $eIc('Sparkle', 11, 1.7, 'ic') !!} {{ (int) $film['score'] }}%</span>
                                        @endif
                                        @if(! empty($film['poster_url']))
                                            <img src="{{ $film['poster_url'] }}" alt="{{ $film['title'] }}"
                                                 style="position:relative;z-index:1;width:100%;height:100%;object-fit:cover;">
                                        @else
                                            {!! $eIc($glyph, 34, 1.4, 'ic') !!}
                                            <span class="ent-poster-cap">poster</span>
                                        @endif
                                    </div>
                                    <div class="ent-film-body">
                                        <div>
                                            <div class="ent-film-top">
                                                <span class="ent-film-name">{{ $film['title'] }}</span>
                                            </div>
                                        </div>

                                        @if(! empty($film['why']))
                                            <div class="ent-pitch">
                                                <span class="why">Waarom jij dit leuk vindt:</span> {{ $film['why'] }}
                                            </div>
                                        @elseif(! empty($film['overview']))
                                            <div class="ent-pitch">{{ $film['overview'] }}</div>
                                        @endif

                                        @if(! empty($film['availability']))
                                            <div class="ent-avail">
                                                @foreach($film['availability'] as $plat)
                                                    @php $meta = $platMeta[mb_strtolower((string) $plat)] ?? null; @endphp
                                                    @if($meta)
                                                        <span class="ent-plat {{ $meta['cls'] }}">
                                                            <span class="dot-led"></span>{{ $meta['label'] }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="ent-film-foot">
                                            <span class="ent-film-foot-lab">Past dit?</span>
                                            <div class="ent-rate">
                                                <button class="ent-thumb up" data-ent-thumb="up" aria-label="Duim omhoog" aria-pressed="false">
                                                    {!! $eIc('ThumbUp', 16, 1.7) !!}
                                                </button>
                                                <button class="ent-thumb down" data-ent-thumb="down" aria-label="Duim omlaag" aria-pressed="false">
                                                    {!! $eIc('ThumbDown', 16, 1.7) !!}
                                                </button>
                                                <button class="ent-thumb" data-ent-dismiss aria-label="Verbergen">
                                                    {!! $eIc('X', 16, 1.7) !!}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @include('entertainment::partials.taste', ['eIc' => $eIc])
                    </div>
                @else
                    <div class="ent-state">
                        <span class="ent-state-ico">{!! $eIc('Star', 26, 1.7) !!}</span>
                        <div class="ent-state-title">Nog geen aanbevelingen</div>
                        <div class="ent-state-sub">
                            Je smaakprofiel is nog leeg. Kies een paar favoriete genres en films die je goed vond,
                            dan stelt de hub elke ochtend een lijst met passende films samen.
                        </div>
                        <div class="ent-state-actions">
                            <button class="ent-btn ent-btn-primary" data-ent-refresh>
                                {!! $eIc('Refresh', 15, 1.7, 'ic') !!} Aanbevelingen ophalen
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============ CONCERTEN PANEL ============ --}}
            <div data-ent-panel="concerten" hidden>
                @if($hasConcerts)
                    <div class="ent-strip">
                        {!! $eIc('Pin', 15, 1.7, 'ic') !!}
                        <span>Gescand in <b>heel NL + Hedon Zwolle</b> — gesorteerd op datum.</span>
                    </div>

                    <div class="ent-concerts">
                        <div class="ent-clist">
                            @foreach($concerts as $concert)
                                @php
                                    $date = ! empty($concert['date']) ? \Carbon\CarbonImmutable::parse($concert['date']) : null;
                                    $rel = $relMeta[$concert['relevance'] ?? ''] ?? null;
                                @endphp
                                <div class="ent-crow">
                                    <div class="ent-cdate">
                                        <div class="day tnum">{{ $date ? $date->day : '—' }}</div>
                                        <div class="mon">{{ $date ? ($monthsNl[$date->month] ?? '') : '' }}</div>
                                        <div class="dow">{{ $date ? ($daysNl[$date->dayOfWeekIso - 1] ?? '') : '' }}</div>
                                    </div>
                                    <div class="ent-cbody">
                                        <div class="ent-cartist">{{ $concert['artist'] }}</div>
                                        <div class="ent-cvenue">
                                            {!! $eIc('Pin', 14, 1.7, 'ic') !!}
                                            {{ $concert['venue'] }}<span class="sep">·</span><span class="city">{{ $concert['city'] }}</span>
                                        </div>
                                    </div>
                                    <div class="ent-crow-r">
                                        @if($rel)
                                            <span class="ent-rel {{ $rel['cls'] }}">
                                                {!! $eIc($rel['icon'], 12, 1.7, 'ic') !!} {{ $rel['label'] }}
                                            </span>
                                        @endif
                                        @if(! empty($concert['url']))
                                            <a class="ent-cgo" href="{{ $concert['url'] }}" target="_blank" rel="noopener" aria-label="Naar tickets">
                                                {!! $eIc('ArrowR', 16, 1.7) !!}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    @include('entertainment::partials.spotify-prompt', ['eIc' => $eIc, 'context' => 'concerten'])
                @endif
            </div>

            {{-- ============ NIEUWE MUZIEK PANEL ============ --}}
            <div data-ent-panel="muziek" hidden>
                @if($hasMusic)
                    <div class="ent-strip">
                        {!! $eIc('Spotify', 15, 1.7, 'ic') !!}
                        <span>Releases van de artiesten die je op <b>Spotify</b> volgt.</span>
                    </div>

                    <div class="ent-music">
                        @foreach($music as $release)
                            @php
                                $type = mb_strtolower((string) ($release['type'] ?? ''));
                                $typeLabel = $typeMeta[$type] ?? mb_strtoupper((string) ($release['type'] ?? ''));
                                $relDate = ! empty($release['release_date']) ? \Carbon\CarbonImmutable::parse($release['release_date']) : null;
                                $isToday = $relDate && $relDate->isToday();
                            @endphp
                            <div class="ent-release">
                                <div class="ent-cover">
                                    @if(! empty($release['image_url']))
                                        <img src="{{ $release['image_url'] }}" alt="{{ $release['title'] }}"
                                             style="position:relative;z-index:1;width:100%;height:100%;object-fit:cover;">
                                    @else
                                        {!! $eIc('Disc', 34, 1.4, 'ic') !!}
                                        <span class="ent-cover-cap">cover</span>
                                    @endif
                                    @if(! empty($release['url']))
                                        <a class="ent-cover-play" href="{{ $release['url'] }}" target="_blank" rel="noopener" aria-label="Afspelen">
                                            {!! $eIc('Play', 14, 1.7) !!}
                                        </a>
                                    @endif
                                </div>
                                <div>
                                    <div class="ent-rel-meta">
                                        <span class="ent-type {{ $type }}">{{ $typeLabel }}</span>
                                        <span class="ent-date {{ $isToday ? 'today' : '' }}">
                                            {{ $relDate ? ($isToday ? 'vandaag' : $relDate->locale('nl')->isoFormat('D MMM')) : '' }}
                                        </span>
                                    </div>
                                    <div class="ent-rel-title" style="margin-top:8px;">{{ $release['title'] }}</div>
                                    <div class="ent-rel-artist"><span class="dot-led"></span>{{ $release['artist'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('entertainment::partials.spotify-prompt', ['eIc' => $eIc, 'context' => 'muziek'])
                @endif
            </div>
        </div>
    </div>
</x-dashboard.layout>
