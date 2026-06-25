<x-dashboard.layout title="Agenda-planner" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Planner/resources/assets/css/planner.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Planner/resources/assets/js/planner.js'])
    </x-slot:scripts>

    @php
        // ---- icon closure: ports the AgIc SVGs from ag-core.jsx ----
        $agIc = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $inner = match ($name) {
                'Calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
                'CalCheck' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4M9 14.5l2 2 4-4"/>',
                'Sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
                'Dumbbell' => '<path d="M6.5 6.5l11 11M4 9l-1.5 1.5a1.5 1.5 0 0 0 0 2.1L4 14M9 4 7.5 5.5M20 15l1.5-1.5a1.5 1.5 0 0 0 0-2.1L20 10M15 20l1.5-1.5"/><path d="M5 10.5 8 7.5l8.5 8.5L13.5 19"/>',
                'Heart' => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/>',
                'Users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M17.5 19a5.5 5.5 0 0 0-3-4.9"/>',
                'Book' => '<path d="M4 5.5A2 2 0 0 1 6 3.5h6V20H6a2 2 0 0 0-2 2z"/><path d="M20 5.5a2 2 0 0 0-2-2h-6V20h6a2 2 0 0 1 2 2z"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'Repeat' => '<path d="M4 9a5 5 0 0 1 5-5h7l-2.5-2.5M20 15a5 5 0 0 1-5 5H8l2.5 2.5"/>',
                'Sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/>',
                'Moon' => '<path d="M20 14a8 8 0 1 1-9-11 6.5 6.5 0 0 0 9 9z"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckCircle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l2.6 2.6L16 9"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'X' => '<path d="M6 6l12 12M18 6 6 18"/>',
                'ArrowR' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'Info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                'Lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
                'Refresh' => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 4v5h-5"/>',
                'Edit' => '<path d="M4 20h4L19 9a2 2 0 0 0-3-3L5 17z"/><path d="M14 7l3 3"/>',
                'Undo' => '<path d="M9 7 4 12l5 5"/><path d="M4 12h11a5 5 0 0 1 0 10h-3"/>',
                'Hand' => '<path d="M9 11V5.5a1.5 1.5 0 0 1 3 0V11M12 11V4.5a1.5 1.5 0 0 1 3 0V11M15 11V6.5a1.5 1.5 0 0 1 3 0V14a6 6 0 0 1-6 6H10a4 4 0 0 1-3-1.4L3.4 14a1.6 1.6 0 0 1 2.4-2.1L9 14.5"/>',
                'Trash' => '<path d="M4 7h16M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2M6 7l1 13a1.5 1.5 0 0 0 1.5 1.4h7A1.5 1.5 0 0 0 17 20L18 7"/>',
                'Google' => '<path d="M21 12.2c0-.7-.06-1.3-.2-2H12v3.8h5.1a4.4 4.4 0 0 1-1.9 2.9v2.4h3.1c1.8-1.7 2.7-4.2 2.7-7.1z"/><path d="M12 21c2.4 0 4.5-.8 6-2.2l-3.1-2.4c-.8.6-1.9.9-2.9.9-2.3 0-4.2-1.5-4.9-3.6H3.9v2.5A9 9 0 0 0 12 21z"/><path d="M7.1 13.7a5.4 5.4 0 0 1 0-3.4V7.8H3.9a9 9 0 0 0 0 8.4z"/><path d="M12 6.6c1.3 0 2.4.4 3.3 1.3l2.5-2.5A9 9 0 0 0 3.9 7.8l3.2 2.5C7.8 8.1 9.7 6.6 12 6.6z"/>',
                default => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
        };

        // ---- category meta: backend category -> CSS class + Dutch label + icon ----
        $catMeta = [
            'sport'  => ['cls' => 'sport',       'label' => 'Sport',   'icon' => 'Dumbbell'],
            'family' => ['cls' => 'familie',     'label' => 'Familie', 'icon' => 'Users'],
            'date'   => ['cls' => 'date',        'label' => 'Date',    'icon' => 'Heart'],
            'custom' => ['cls' => 'persoonlijk', 'label' => 'Overig',  'icon' => 'Book'],
        ];
        $catFor = static fn (?string $c) => $catMeta[$c] ?? $catMeta['custom'];

        // ---- frequency label (Dutch) ----
        $freqLabel = static function (array $intention): string {
            if (($intention['frequency_type'] ?? null) === 'weekly') {
                return 'wekelijks';
            }
            $min = $intention['target_min'] ?? null;
            $max = $intention['target_max'] ?? null;
            if ($min !== null && $max !== null && $min !== $max) {
                return $min.'–'.$max.'× per week';
            }
            return ($min ?? $max ?? 1).'× per week';
        };

        // ---- duration label (Dutch) ----
        $durLabel = static function (?int $minutes): string {
            $m = (int) ($minutes ?? 0);
            if ($m <= 0) {
                return '—';
            }
            $h = intdiv($m, 60);
            $r = $m % 60;
            if ($h && $r) {
                return $h.'u '.$r.'m';
            }
            if ($h) {
                return $h.' uur';
            }
            return $r.' min';
        };

        // ---- normalize state ----
        $plan = $plan ?? null;
        $intentions = collect($intentions ?? []);

        $daysNl = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];
        $monthsNl = [1 => 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

        $items = collect($plan['items'] ?? []);

        // Scheduled items have a start_at; group those for the grid and proposal list.
        $scheduled = $items->filter(fn ($i) => ! empty($i['start_at']) && in_array($i['status'] ?? '', ['proposed', 'accepted'], true));
        $unplaceable = $items->filter(fn ($i) => ($i['status'] ?? '') === 'unplaceable');

        $proposedCount = $items->where('status', 'proposed')->count();
        $acceptedCount = $items->where('status', 'accepted')->count();
        $openItems = $items->whereIn('status', ['proposed'])->values();

        // ---- calendar window + week range ----
        $calStart = 8;
        $calEnd = 23;
        $hourPx = 44;

        // Derive the week's Monday from the plan items (fall back to current week).
        $anchor = null;
        foreach ($items as $i) {
            if (! empty($i['start_at'])) {
                $anchor = \Carbon\CarbonImmutable::parse($i['start_at']);
                break;
            }
        }
        $anchor ??= \Carbon\CarbonImmutable::now();
        $weekStart = $anchor->startOfWeek(\Carbon\CarbonInterface::MONDAY)->startOfDay();

        $weekDays = [];
        for ($d = 0; $d < 7; $d++) {
            $day = $weekStart->addDays($d);
            $weekDays[] = [
                'index' => $d,
                'dow' => $daysNl[$d],
                'dom' => $day->day,
                'weekend' => $d >= 5,
                'today' => $day->isSameDay(\Carbon\CarbonImmutable::now()),
            ];
        }

        $weekKey = $plan['week_key'] ?? null;
        $weekRangeLabel = 'Week '.$weekStart->isoWeek().' · '
            .$weekStart->day.'–'.$weekStart->addDays(6)->day.' '.($monthsNl[$weekStart->month] ?? '');

        // Position helper: minutes-from-calStart for an ISO time on its weekday.
        $posFor = static function (string $iso) use ($weekStart, $calStart) {
            $t = \Carbon\CarbonImmutable::parse($iso);
            $day = (int) $weekStart->startOfDay()->diffInDays($t->startOfDay());
            $minutes = $t->hour * 60 + $t->minute - $calStart * 60;
            return ['day' => $day, 'minutes' => $minutes];
        };

        $hasPlan = $plan !== null;
    @endphp

    <div class="ag"
         data-ag
         data-generate-url="{{ route('planner.generate') }}"
         data-accept-all-url="{{ route('planner.accept-all') }}"
         data-intentions-url="{{ route('planner.intentions.store') }}"
         data-accept-tpl="{{ url('planner/items/__ID__/accept') }}"
         data-reject-tpl="{{ url('planner/items/__ID__/reject') }}"
         data-intention-tpl="{{ url('planner/intentions/__ID__') }}">
        <div class="ag-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="ag-head">
                <div class="ag-head-l">
                    <span class="ag-eyebrow">{!! $agIc('CalCheck', 14, 1.7, 'ic') !!} Weekplanning</span>
                    <h1 class="ag-title disp">Agenda-planner</h1>
                    <div class="ag-sub" data-ag-sub="weekplan">
                        @if(! $connected)
                            <span class="warn">Google Calendar niet gekoppeld</span> <span class="dot">·</span> koppel je agenda om een weekplan te maken
                        @elseif($hasPlan)
                            {{ $weekRangeLabel }} <span class="dot">·</span> <b class="tnum">{{ $proposedCount }}</b> {{ $proposedCount === 1 ? 'voorstel' : 'voorstellen' }}
                        @else
                            {{ $weekRangeLabel }} <span class="dot">·</span> nog geen weekplan
                        @endif
                    </div>
                    <div class="ag-sub" data-ag-sub="voornemens" hidden>
                        Beheer je wekelijkse voornemens <span class="dot">·</span> de planner plant ze rond je <b>vaste afspraken</b>
                    </div>
                </div>
                <div class="ag-head-r">
                    <span class="ag-conn {{ $connected ? 'on' : 'off' }}">
                        <span class="led"></span>
                        {{ $connected ? 'Google Calendar' : 'Niet gekoppeld' }}
                    </span>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="ag-tabs">
                <button class="ag-tab on" data-ag-tab="weekplan">
                    {!! $agIc('Calendar', 15, 1.7, 'ic') !!} Weekplan
                    <span class="ag-tab-count tnum">{{ $proposedCount }}</span>
                </button>
                <button class="ag-tab" data-ag-tab="voornemens">
                    {!! $agIc('Repeat', 15, 1.7, 'ic') !!} Voornemens
                    <span class="ag-tab-count tnum">{{ $intentions->count() }}</span>
                </button>
            </div>

            {{-- ============ WEEKPLAN PANEL ============ --}}
            <div data-ag-panel="weekplan">
                @if(! $connected)
                    {{-- Google connect prompt --}}
                    <div class="ag-connect">
                        <span class="ag-connect-ico">{!! $agIc('Calendar', 28, 1.7) !!}</span>
                        <div class="ag-connect-title">Koppel je Google Calendar</div>
                        <div class="ag-connect-sub">
                            De planner heeft je <b>vaste afspraken</b> nodig om er flexibele voornemens omheen te plannen.
                            Koppel je agenda en de hub stelt elke week een passend voorstel samen — zonder iets te wijzigen
                            zonder jouw akkoord.
                        </div>
                        <div class="ag-connect-actions">
                            <a class="ag-btn ag-btn-google" href="{{ route('planner.google.connect') }}">
                                {!! $agIc('Google', 15, 1.7) !!} Koppel Google Calendar
                            </a>
                        </div>
                        <div class="ag-connect-note">{!! $agIc('Lock', 13, 1.7, 'ic') !!} Alleen-lezen toegang · de hub draait lokaal op je NAS</div>
                    </div>
                @elseif(! $hasPlan)
                    {{-- Connected but no plan yet --}}
                    <div class="ag-state">
                        <span class="ag-state-ico">{!! $agIc('Sparkle', 26, 1.7) !!}</span>
                        <div class="ag-state-title">Nog geen weekplan</div>
                        <div class="ag-state-sub">
                            Je agenda is gekoppeld. Genereer een weekplan en de hub zoekt vrije blokken rond je
                            vaste afspraken om je actieve voornemens in te plannen.
                        </div>
                        <div class="ag-state-actions">
                            <button class="ag-btn ag-btn-primary" data-ag-generate>
                                {!! $agIc('Sparkle', 15, 1.7, 'ic') !!} Weekplan genereren
                            </button>
                        </div>
                    </div>
                @else
                    {{-- ===== AI summary ===== --}}
                    @if(! empty($plan['summary']))
                        <div class="ag-summary">
                            <span class="ag-summary-ic">{!! $agIc('Sparkle', 18, 1.7) !!}</span>
                            <div class="ag-summary-body">
                                <div class="ag-summary-lab">{{ ($plan['is_fallback'] ?? false) ? 'Deze week · automatisch' : 'Deze week' }}</div>
                                <div class="ag-summary-tx">{{ $plan['summary'] }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- ===== plan action bar ===== --}}
                    <div class="ag-planbar">
                        <span class="ag-planbar-info">
                            <b class="tnum">{{ $proposedCount }}</b> {{ $proposedCount === 1 ? 'voorgesteld blok' : 'voorgestelde blokken' }} ·
                            <b class="tnum">{{ $unplaceable->count() }}</b> niet ingepland
                        </span>
                        <div class="ag-planbar-r">
                            <button class="ag-btn ag-btn-ghost" data-ag-generate>
                                {!! $agIc('Refresh', 15, 1.7, 'ic') !!} Opnieuw genereren
                            </button>
                            @if($proposedCount > 0)
                                <button class="ag-btn ag-btn-primary" data-ag-accept-all>
                                    {!! $agIc('CalCheck', 15, 1.7) !!} Alles toevoegen aan agenda
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ===== week grid ===== --}}
                    <div class="ag-cal">
                        <div class="ag-cal-head">
                            <div class="corner"></div>
                            @foreach($weekDays as $day)
                                <div class="ag-dayhead {{ $day['weekend'] ? 'weekend' : '' }} {{ $day['today'] ? 'today' : '' }}">
                                    <div class="dow">{{ $day['dow'] }}</div>
                                    <div class="dom tnum">{{ $day['dom'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="ag-cal-body">
                            @php $bodyH = ($calEnd - $calStart) * $hourPx; @endphp
                            <div class="ag-gutter" style="height: {{ $bodyH }}px;">
                                @for($h = $calStart; $h < $calEnd; $h++)
                                    <div class="hr"><span class="tnum">{{ sprintf('%02d:00', $h) }}</span></div>
                                @endfor
                            </div>
                            @foreach($weekDays as $day)
                                <div class="ag-day {{ $day['weekend'] ? 'weekend' : '' }}" style="height: {{ $bodyH }}px;">
                                    @foreach($scheduled as $item)
                                        @php
                                            $pos = $posFor($item['start_at']);
                                            $end = \Carbon\CarbonImmutable::parse($item['end_at'] ?? $item['start_at']);
                                            $start = \Carbon\CarbonImmutable::parse($item['start_at']);
                                            $durMin = max(15, $start->diffInMinutes($end));
                                        @endphp
                                        @if($pos['day'] === $day['index'])
                                            @php
                                                $meta = $catFor($item['category'] ?? null);
                                                $top = ($pos['minutes'] / 60) * $hourPx;
                                                $height = ($durMin / 60) * $hourPx - 4;
                                                $added = ($item['status'] ?? '') === 'accepted';
                                            @endphp
                                            <div class="ag-ev prop {{ $meta['cls'] }} {{ $added ? 'added' : 'voorstel' }}"
                                                 style="top: {{ $top }}px; height: {{ max(20, $height) }}px;">
                                                <span class="ag-ev-title">{{ $item['title'] }}</span>
                                                <span class="ag-ev-time tnum">{{ $start->format('H:i') }}–{{ $end->format('H:i') }}</span>
                                                <span class="ag-ev-tag">
                                                    @if($added)
                                                        {!! $agIc('Check', 11, 1.7) !!} Toegevoegd
                                                    @else
                                                        {!! $agIc($meta['icon'], 11, 1.7) !!} Voorstel
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== proposal list ===== --}}
                    <div class="ag-section-lab">
                        {!! $agIc('Sparkle', 14, 1.7, 'ic') !!} Voorgestelde blokken <span class="rule"></span>
                        <span class="count">{{ $acceptedCount > 0 ? $acceptedCount.' toegevoegd · ' : '' }}{{ $proposedCount }} open</span>
                    </div>
                    <div class="ag-prop-list" data-ag-prop-list>
                        @foreach($scheduled as $item)
                            @php
                                $meta = $catFor($item['category'] ?? null);
                                $start = \Carbon\CarbonImmutable::parse($item['start_at']);
                                $end = \Carbon\CarbonImmutable::parse($item['end_at'] ?? $item['start_at']);
                                $pos = $posFor($item['start_at']);
                                $dayLabel = ($daysNl[$pos['day']] ?? '').' '.$start->day;
                                $durMin = max(15, $start->diffInMinutes($end));
                                $added = ($item['status'] ?? '') === 'accepted';
                            @endphp
                            <div class="ag-prop {{ $meta['cls'] }} {{ $added ? 'added' : '' }}" data-ag-prop="{{ $item['id'] }}">
                                <div class="ag-prop-tick">{!! $agIc($meta['icon'], 19, 1.7) !!}</div>
                                <div class="ag-prop-main">
                                    <div class="ag-prop-title">{{ $item['title'] }} <span class="ag-cat-pill">{{ $meta['label'] }}</span></div>
                                    <div class="ag-prop-when">
                                        {!! $agIc('Calendar', 14, 1.7, 'ic') !!}
                                        <span><b style="color: var(--tx-1); font-weight: 600;">{{ $dayLabel }}</b> · <span class="tnum">{{ $start->format('H:i') }}–{{ $end->format('H:i') }}</span></span>
                                        <span class="sep">·</span>
                                        <span class="dur">{{ $durLabel($durMin) }}</span>
                                    </div>
                                </div>
                                @if($added)
                                    <div class="ag-prop-resolved ok" data-ag-resolved>
                                        {!! $agIc('CheckCircle', 17, 1.7, 'ic') !!} In agenda
                                    </div>
                                @else
                                    <div class="ag-prop-actions">
                                        <button class="ag-iconbtn reject" data-ag-reject aria-label="Afwijzen">{!! $agIc('X', 16, 1.7) !!}</button>
                                        <button class="ag-iconbtn add" data-ag-accept>{!! $agIc('Plus', 15, 1.7) !!} Toevoegen</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- ===== unplaceable ===== --}}
                    @if($unplaceable->isNotEmpty())
                        <div class="ag-section-lab">
                            {!! $agIc('Alert', 14, 1.7, 'ic') !!} Niet ingepland <span class="rule"></span>
                            <span class="count tnum">{{ $unplaceable->count() }}</span>
                        </div>
                        <div class="ag-unplanned">
                            @foreach($unplaceable as $item)
                                @php $meta = $catFor($item['category'] ?? null); @endphp
                                <div class="ag-unrow {{ $meta['cls'] }}">
                                    <div class="ag-unrow-ic">{!! $agIc($meta['icon'], 18, 1.7) !!}</div>
                                    <div class="ag-unrow-main">
                                        <div class="ag-unrow-title">{{ $item['title'] }}</div>
                                        <div class="ag-unrow-reason">
                                            {!! $agIc('Info', 13, 1.7, 'ic') !!}
                                            {{ $item['unplaceable_reason'] ?? 'Geen vrij blok gevonden dat aan de voorkeuren voldeed.' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- ============ VOORNEMENS PANEL ============ --}}
            <div data-ag-panel="voornemens" hidden>
                @php $activeCount = $intentions->where('active', true)->count(); @endphp
                <div class="ag-manage">
                    <div class="ag-manage-head">
                        <span class="ag-manage-lead">
                            <b class="tnum">{{ $activeCount }}</b> van {{ $intentions->count() }} {{ $intentions->count() === 1 ? 'voornemen' : 'voornemens' }} actief — deze worden elke week rond je vaste afspraken ingepland.
                        </span>
                    </div>
                    <div class="ag-int-list" data-ag-int-list>
                        @foreach($intentions as $intention)
                            @php
                                $meta = $catFor($intention['category'] ?? null);
                                $on = (bool) ($intention['active'] ?? false);
                                $windows = $intention['preferred_windows'] ?? [];
                                $timeLabel = is_array($windows) && count($windows) > 0
                                    ? implode(', ', array_map('strval', $windows))
                                    : 'geen voorkeur';
                                $isDay = str_contains(mb_strtolower($timeLabel), 'weekend') || str_contains(mb_strtolower($timeLabel), 'overdag') || str_contains(mb_strtolower($timeLabel), 'za');
                            @endphp
                            <div class="ag-int {{ $meta['cls'] }} {{ $on ? '' : 'off' }}"
                                 data-ag-int="{{ $intention['id'] }}"
                                 data-ag-int-active="{{ $on ? 'true' : 'false' }}">
                                <div class="ag-int-tick">{!! $agIc($meta['icon'], 20, 1.7) !!}</div>
                                <div class="ag-int-main">
                                    <div class="ag-int-top">
                                        <span class="ag-int-title">{{ $intention['title'] }}</span>
                                        <span class="ag-cat-pill">{{ $meta['label'] }}</span>
                                    </div>
                                    <div class="ag-int-attrs">
                                        <span class="ag-attr">{!! $agIc('Repeat', 14, 1.7, 'ic') !!} <span class="ag-attr-lab">frequentie</span> <b>{{ $freqLabel($intention) }}</b></span>
                                        <span class="ag-attr">{!! $agIc($isDay ? 'Sun' : 'Moon', 14, 1.7, 'ic') !!} <span class="ag-attr-lab">voorkeur</span> <b>{{ $timeLabel }}</b></span>
                                        <span class="ag-attr">{!! $agIc('Clock', 14, 1.7, 'ic') !!} <span class="ag-attr-lab">duur</span> <b>{{ $durLabel($intention['duration_minutes'] ?? null) }}</b></span>
                                    </div>
                                </div>
                                <div class="ag-int-r">
                                    <button class="ag-int-edit" data-ag-int-delete aria-label="Verwijderen">{!! $agIc('Trash', 16, 1.7) !!}</button>
                                    <button class="ag-toggle {{ $on ? 'on' : '' }}" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}"
                                            aria-label="{{ $on ? 'Actief' : 'Gepauzeerd' }}" data-ag-int-toggle></button>
                                </div>
                            </div>
                        @endforeach
                        <button class="ag-int-add" data-ag-int-add>{!! $agIc('Plus', 17, 1.7) !!} Voornemen toevoegen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard.layout>
