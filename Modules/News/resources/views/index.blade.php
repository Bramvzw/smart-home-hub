<x-dashboard.layout title="Nieuws" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/News/resources/assets/css/news.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/News/resources/assets/js/news.js'])
    </x-slot:scripts>

    @php
        $nwIcon = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $inner = match ($name) {
                'rss' => '<path d="M5 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/><path d="M4 11a8 8 0 0 1 8 8M4 5a14 14 0 0 1 14 14"/>',
                'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v5h-5"/>',
                'check-all' => '<path d="M2 13l3.5 3.5L13 9"/><path d="M11 16l1.5 1.5L21 9"/>',
                'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.4"/>',
                'ext' => '<path d="M14 5h5v5"/><path d="M19 5l-8 8"/><path d="M18 14v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h4"/>',
                'inbox' => '<path d="M4 13h4l1.5 3h5L16 13h4"/><path d="M4 13 6.5 5h11L20 13v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/>',
                'cube' => '<path d="M12 3 4 7v10l8 4 8-4V7z"/><path d="M4 7l8 4 8-4M12 11v10"/>',
                'code' => '<path d="m8 9-3 3 3 3M16 9l3 3-3 3M13.5 7l-3 10"/>',
                'heart' => '<path d="M12 20s-7-4.6-9.2-9A4.6 4.6 0 0 1 12 6a4.6 4.6 0 0 1 9.2 5c-2.2 4.4-9.2 9-9.2 9z"/>',
                'sprout' => '<path d="M12 21v-7"/><path d="M12 14C9 14 5 12.5 5 8c4.5 0 7 2.5 7 6z"/><path d="M12 12c0-3.5 2-6 6-6 0 4-3 6-6 6z"/>',
                'gamepad' => '<path d="M7 8h10a4 4 0 0 1 3.9 3.1l.9 4.2A2.4 2.4 0 0 1 17 18l-1.6-2H8.6L7 18a2.4 2.4 0 0 1-4.7-2.7l.9-4.2A4 4 0 0 1 7 8z"/><path d="M7 12h2M8 11v2M15.5 11.5h.01M17.5 13.5h.01"/>',
                default => '<path d="M5 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/><path d="M4 11a8 8 0 0 1 8 8M4 5a14 14 0 0 1 14 14"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        $topicIcon = static function (string $key) use ($nwIcon): string {
            $name = match ($key) {
                '3d-printing' => 'cube',
                'dev' => 'code',
                'fitness' => 'heart',
                'gardening' => 'sprout',
                'switch2' => 'gamepad',
                default => 'rss',
            };

            return $nwIcon($name, 17);
        };

        $hasItems = collect($topics)->contains(fn ($topic) => count($topic['items']) > 0);
        $lastRefreshLabel = $last_refreshed_at
            ? \Carbon\CarbonImmutable::parse($last_refreshed_at)->format('H:i')
            : null;
    @endphp

    <div class="nw"
         data-news
         data-news-refresh-url="{{ route('news.refresh') }}"
         data-news-readall-url="{{ route('news.read-all') }}">
        <div class="nw-wrap">
            <div class="nw-head">
                <div class="nw-head-l">
                    <span class="nw-eyebrow">{!! $nwIcon('rss', 14, 1.7, 'ic') !!} Feeds · {{ count($topics) }} onderwerpen</span>
                    <h1 class="nw-title disp">Nieuws</h1>
                    <div class="nw-sub">
                        @if($total_unread > 0)
                            <span class="acc tnum">{{ $total_unread }} ongelezen</span> verspreid over je onderwerpen
                        @else
                            Helemaal bij — <b>geen ongelezen items</b>
                        @endif
                    </div>
                </div>
                <div class="nw-head-r">
                    <span class="nw-stamp" data-news-stamp>
                        {!! $nwIcon('clock', 14, 1.7, 'ic') !!}
                        @if($lastRefreshLabel)
                            Laatst ververst om <span class="tnum">{{ $lastRefreshLabel }}</span>
                        @else
                            Nog niet ververst
                        @endif
                    </span>
                    <div class="nw-actions">
                        <button class="nw-btn nw-btn-ghost" data-news-read-all @disabled($total_unread === 0)>
                            {!! $nwIcon('check-all', 15) !!} Markeer alles gelezen
                        </button>
                        <button class="nw-btn nw-btn-primary" data-news-refresh>
                            {!! $nwIcon('refresh', 15, 1.7, 'ic') !!} Nu verversen
                        </button>
                    </div>
                </div>
            </div>
            <hr class="nw-divide" />

            @if($hasItems)
                <div class="nw-grid">
                    @foreach($topics as $topic)
                        <section class="nw-topic" data-news-topic="{{ $topic['key'] }}">
                            <div class="nw-topic-head">
                                <span class="nw-topic-ico">{!! $topicIcon($topic['key']) !!}</span>
                                <span class="nw-topic-name">{{ $topic['label'] }}</span>
                                <span class="nw-unread-pill tnum {{ $topic['unread'] === 0 ? 'zero' : '' }}" data-news-topic-pill>{{ $topic['unread'] }}</span>
                                <span class="nw-topic-spacer"></span>
                                <button class="nw-topic-clear" data-news-read-topic="{{ $topic['key'] }}" @disabled($topic['unread'] === 0)>
                                    Markeer gelezen
                                </button>
                            </div>
                            <div class="nw-list">
                                @foreach($topic['items'] as $item)
                                    @php $keyword = $item['matched_keywords'][0] ?? null; @endphp
                                    <button class="nw-item {{ $item['is_read'] ? 'read' : 'unread' }}"
                                            data-news-item="{{ $item['id'] }}"
                                            data-news-url="{{ $item['url'] }}"
                                            data-news-read-url="{{ route('news.items.read', $item['id']) }}">
                                        <div class="nw-item-top">
                                            <span class="nw-dot"></span>
                                            <span class="nw-item-title">{{ $item['title'] }}</span>
                                            {!! $nwIcon('ext', 14, 1.7, 'nw-ext') !!}
                                        </div>
                                        @if($item['summary'] !== '')
                                            <div class="nw-item-sum">{{ $item['summary'] }}</div>
                                        @endif
                                        <div class="nw-item-meta">
                                            <span class="nw-src"><span class="fav"></span> {{ $item['source'] }}</span>
                                            <span class="nw-sep"></span>
                                            <span class="nw-time tnum">{{ \Carbon\CarbonImmutable::parse($item['published_at'])->locale('nl')->diffForHumans() }}</span>
                                            @if($keyword)
                                                <span class="nw-kw" title="Trefwoord-alert: &quot;{{ $keyword }}&quot;">
                                                    {!! $nwIcon('target', 12) !!} {{ $keyword }}
                                                </span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div class="nw-state">
                    <span class="nw-state-ico">{!! $nwIcon('inbox', 26) !!}</span>
                    <div class="nw-state-title">Nog niets opgehaald</div>
                    <div class="nw-state-sub">
                        Je feeds zijn ingesteld, maar er is nog geen inhoud opgehaald. Ververs om de
                        nieuwste items per onderwerp binnen te halen.
                    </div>
                    <div class="nw-state-actions">
                        <button class="nw-btn nw-btn-primary" data-news-refresh>
                            {!! $nwIcon('refresh', 15, 1.7, 'ic') !!} Nu verversen
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-dashboard.layout>
