<x-dashboard.layout title="Dagelijkse briefing" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Briefing/resources/assets/css/briefing.css'])
    </x-slot:head>

    @php
        $brIcon = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $inner = match ($name) {
                'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 14.5l.7 2 .8.7-.8.6-.7 2-.6-2-2-.6 2-.7z"/>',
                'sun' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>',
                'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
                'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.4"/>',
                'rss' => '<path d="M5 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/><path d="M4 11a8 8 0 0 1 8 8M4 5a14 14 0 0 1 14 14"/>',
                'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v5h-5"/>',
                'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'coffee' => '<path d="M5 8h11v5a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4z"/><path d="M16 9h2.2a2.3 2.3 0 0 1 0 4.6H16"/><path d="M8 3.5c-.5.7-.5 1.3 0 2M11.5 3.5c-.5.7-.5 1.3 0 2"/>',
                'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                'arrow-r' => '<path d="M5 12h13M13 6l6 6-6 6"/>',
                default => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        $sectionIcon = static function (string $key): string {
            return match ($key) {
                'weather' => 'sun',
                'calendar' => 'calendar',
                'tasks' => 'target',
                'news' => 'rss',
                default => 'info',
            };
        };

        $timezone = (string) config('briefing.timezone', 'Europe/Amsterdam');
        $dateLabel = \Carbon\CarbonImmutable::parse($date)->locale('nl')->isoFormat('dddd D MMMM YYYY');
        $generatedAt = $hasBriefing
            ? $briefing->generated_at->setTimezone($timezone)->format('H:i')
            : null;
        $isFallback = $hasBriefing && $briefing->is_fallback;
    @endphp

    <div class="br">
        <div class="br-wrap">
            <div class="br-head">
                <div class="br-head-l">
                    <span class="br-eyebrow">{!! $brIcon('coffee', 14, 1.7, 'ic') !!} Smart Home Hub · Ochtend</span>
                    <h1 class="br-title disp">Dagelijkse briefing</h1>
                    <div class="br-date">{{ $dateLabel }}</div>
                </div>
                <div class="br-head-r">
                    <span class="br-stamp">
                        {!! $brIcon('clock', 14, 1.7, 'ic') !!}
                        @if($hasBriefing)
                            Gegenereerd om <span class="tnum">{{ $generatedAt }}</span>
                        @else
                            Nog niet gegenereerd vandaag
                        @endif
                    </span>
                    <div class="br-actions">
                        @if($hasBriefing)
                            <span class="br-mode {{ $isFallback ? 'fallback' : 'ai' }}">
                                @if($isFallback)
                                    {!! $brIcon('info', 13) !!} Fallback · zonder AI
                                @else
                                    {!! $brIcon('sparkle', 13) !!} Gemaakt met AI
                                @endif
                            </span>
                        @endif
                        <form method="POST" action="{{ route('briefing.regenerate') }}">
                            @csrf
                            <button type="submit" class="br-btn br-btn-primary">
                                {!! $brIcon('refresh', 15, 1.7, 'ic') !!}
                                {{ $hasBriefing ? 'Opnieuw genereren' : 'Genereer nu' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <hr class="br-divide" />

            @if($hasBriefing)
                @if($isFallback)
                    <div class="br-note">
                        {!! $brIcon('info', 17, 1.7, 'ic') !!}
                        <div class="br-note-tx">
                            <b>De AI-dienst was niet bereikbaar</b> bij het genereren. Dit is een fallback-briefing,
                            automatisch samengesteld uit je modulegegevens — feitelijk correct, maar zonder de
                            vrije samenvattingstekst.
                        </div>
                    </div>
                @endif

                <div class="br-summary {{ $isFallback ? 'fb' : '' }}">
                    <p class="br-summary-tx">{{ $briefing->body }}</p>
                </div>

                @if(! empty($briefing->sections))
                    <span class="br-sections-label">Per onderdeel</span>
                    <div class="br-sections">
                        @foreach($briefing->sections as $section)
                            <section class="br-sec">
                                <div class="br-sec-head">
                                    <span class="br-sec-ico">{!! $brIcon($sectionIcon($section['key'] ?? ''), 17) !!}</span>
                                    <span class="br-sec-name">{{ $section['label'] ?? $section['key'] ?? '' }}</span>
                                    <span class="br-sec-spacer"></span>
                                </div>
                                <div class="br-lines">
                                    <div class="br-line">
                                        <span class="br-line-v">{{ $section['summary'] ?? '' }}</span>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="br-state">
                    <span class="br-state-ico">{!! $brIcon('sparkle', 26) !!}</span>
                    <div class="br-state-title">Briefing van vandaag staat klaar</div>
                    <div class="br-state-sub">
                        Je ochtendsamenvatting is nog niet gemaakt. Genereer hem nu en de hub stelt in een paar
                        tellen een overzicht samen uit je weer, agenda, taken en nieuws.
                    </div>
                    <div class="br-state-actions">
                        <form method="POST" action="{{ route('briefing.regenerate') }}">
                            @csrf
                            <button type="submit" class="br-btn br-btn-primary">
                                {!! $brIcon('sparkle', 15) !!} Genereer nu
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-dashboard.layout>
