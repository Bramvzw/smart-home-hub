<x-dashboard.layout title="Calendar" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Calendar/resources/assets/css/calendar-plan.css', 'Modules/Calendar/resources/assets/css/habits.css'])
    </x-slot:head>

    <x-slot:scripts>
        @vite(['Modules/Calendar/resources/assets/js/calendar-plan.js', 'Modules/Calendar/resources/assets/js/calendar.js'])
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

        // ---- habit tracking icons (ports the hbIcon set from the old habits page) ----
        $hbIcon = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $paths = match ($name) {
                'Flame' => '<path d="M12 3c.6 2.6-.9 3.9-2.1 5.2C8.4 9.8 7 11.3 7 14a5 5 0 0 0 10 0c0-1.6-.6-3-1.4-4-.3 1-1 1.7-1.8 1.9.5-2.3-.4-4.6-1.8-8.9z"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'Undo' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
                'Repeat' => '<path d="M4 8a6 6 0 0 1 6-6h7"/><path d="M20 16a6 6 0 0 1-6 6H7"/>',
                'Activity' => '<path d="M3 12h3.5l2.5 7 5-15 2.5 8H21"/>',
                'Book' => '<path d="M5 4h11a2 2 0 0 1 2 2v14H7a2 2 0 0 0-2 2z"/><path d="M5 4v16M18 18v2"/>',
                'Spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
                'Drop' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
                'Target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.4"/>',
                'Trash' => '<path d="M4 7h16M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2M6 7l1 13a1.5 1.5 0 0 0 1.5 1.4h7A1.5 1.5 0 0 0 17 20L18 7"/>',
                'X' => '<path d="M6 6l12 12M18 6L6 18"/>',
                default => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
            };
            $fill = $name === 'Flame';

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24"'
                .' fill="'.($fill ? 'currentColor' : 'none').'" stroke="'.($fill ? 'none' : 'currentColor').'"'
                .' stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
        };

        // ---- category meta: backend category -> CSS class + label + icon ----
        $catMeta = [
            'sport'  => ['cls' => 'sport',       'label' => 'Sport',  'icon' => 'Dumbbell'],
            'family' => ['cls' => 'familie',     'label' => 'Family', 'icon' => 'Users'],
            'date'   => ['cls' => 'date',        'label' => 'Date',   'icon' => 'Heart'],
            'custom' => ['cls' => 'persoonlijk', 'label' => 'Other',  'icon' => 'Book'],
        ];
        $catFor = static fn (?string $c) => $catMeta[$c] ?? $catMeta['custom'];

        // ---- frequency label ----
        $freqLabel = static function (array $habit): string {
            if (($habit['frequency_type'] ?? null) === 'weekly') {
                return 'weekly';
            }
            $min = $habit['target_min'] ?? null;
            $max = $habit['target_max'] ?? null;
            if ($min !== null && $max !== null && $min !== $max) {
                return $min.'–'.$max.'× per week';
            }
            return ($min ?? $max ?? 1).'× per week';
        };

        // ---- duration label ----
        $durLabel = static function (?int $minutes): string {
            $m = (int) ($minutes ?? 0);
            if ($m <= 0) {
                return '—';
            }
            $h = intdiv($m, 60);
            $r = $m % 60;
            if ($h && $r) {
                return $h.'h '.$r.'m';
            }
            if ($h) {
                return $h === 1 ? '1 hour' : $h.' hours';
            }
            return $r.' min';
        };

        // ---- normalize state ----
        $plan = $plan ?? null;
        $habits = collect($habits ?? []);
        $events = $events ?? [];

        $daysEn = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $monthsEn = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

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
        $weekEnd = $weekStart->addDays(7);

        $weekDays = [];
        for ($d = 0; $d < 7; $d++) {
            $day = $weekStart->addDays($d);
            $weekDays[] = [
                'index' => $d,
                'dow' => $daysEn[$d],
                'dom' => $day->day,
                'weekend' => $d >= 5,
                'today' => $day->isSameDay(\Carbon\CarbonImmutable::now()),
            ];
        }

        $weekKey = $plan['week_key'] ?? null;
        $weekRangeLabel = 'Week '.$weekStart->isoWeek().' · '
            .$weekStart->day.'–'.$weekStart->addDays(6)->day.' '.($monthsEn[$weekStart->month] ?? '');

        // Position helper: minutes-from-calStart for an ISO time on its weekday.
        $posFor = static function (string $iso) use ($weekStart, $calStart) {
            $t = \Carbon\CarbonImmutable::parse($iso);
            $day = (int) $weekStart->startOfDay()->diffInDays($t->startOfDay());
            $minutes = $t->hour * 60 + $t->minute - $calStart * 60;
            return ['day' => $day, 'minutes' => $minutes];
        };

        // Real Google appointments that fall inside the displayed plan week — shown as
        // fixed appointments behind the proposed blocks.
        $weekBusy = collect($events)
            ->reject(fn ($e) => $e->allDay)
            ->filter(fn ($e) => $e->start->gte($weekStart) && $e->start->lt($weekEnd))
            ->values();

        $hasPlan = $plan !== null;
    @endphp

    <div class="ag"
         data-ag
         data-generate-url="{{ route('calendar.generate') }}"
         data-accept-all-url="{{ route('calendar.accept-all') }}"
         data-goals-url="{{ route('calendar.goals.store') }}"
         data-accept-tpl="{{ url('calendar/items/__ID__/accept') }}"
         data-reject-tpl="{{ url('calendar/items/__ID__/reject') }}"
         data-goal-tpl="{{ url('calendar/goals/__ID__') }}">
        <div class="ag-wrap">
            {{-- ============ HEADER ============ --}}
            <div class="ag-head">
                <div class="ag-head-l">
                    <span class="ag-eyebrow">{!! $agIc('CalCheck', 14, 1.7, 'ic') !!} Agenda</span>
                    <h1 class="ag-title disp">Calendar</h1>
                    <div class="ag-sub" data-ag-sub="agenda">
                        @if(! $connected)
                            <span class="warn">Google Calendar not connected</span> <span class="dot">·</span> connect your calendar to see your events
                        @else
                            Next {{ $windowDays }} days <span class="dot">·</span> live from Google Calendar
                        @endif
                    </div>
                    <div class="ag-sub" data-ag-sub="weekplan" hidden>
                        @if(! $connected)
                            <span class="warn">Google Calendar not connected</span> <span class="dot">·</span> connect your calendar to build a week plan
                        @elseif($hasPlan)
                            {{ $weekRangeLabel }} <span class="dot">·</span> <b class="tnum">{{ $proposedCount }}</b> {{ $proposedCount === 1 ? 'proposal' : 'proposals' }}
                        @else
                            {{ $weekRangeLabel }} <span class="dot">·</span> no week plan yet
                        @endif
                    </div>
                    <div class="ag-sub" data-ag-sub="habits" hidden>
                        Manage your weekly habits <span class="dot">·</span> the planner schedules them around your <b>fixed appointments</b>
                    </div>
                </div>
                <div class="ag-head-r">
                    <span class="ag-conn {{ $connected ? 'on' : 'off' }}">
                        <span class="led"></span>
                        {{ $connected ? 'Google Calendar' : 'Not connected' }}
                    </span>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="ag-tabs">
                <button class="ag-tab on" data-ag-tab="agenda">
                    {!! $agIc('Calendar', 15, 1.7, 'ic') !!} Agenda
                    <span class="ag-tab-count tnum">{{ count($events) }}</span>
                </button>
                <button class="ag-tab" data-ag-tab="weekplan">
                    {!! $agIc('Sparkle', 15, 1.7, 'ic') !!} Week plan
                    <span class="ag-tab-count tnum">{{ $proposedCount }}</span>
                </button>
                <button class="ag-tab" data-ag-tab="habits">
                    {!! $agIc('Repeat', 15, 1.7, 'ic') !!} Habits
                    <span class="ag-tab-count tnum">{{ $habits->count() }}</span>
                </button>
            </div>

            {{-- ============ AGENDA PANEL (live Google events) ============ --}}
            <div data-ag-panel="agenda">
                @if(! $connected)
                    <div class="ag-connect">
                        <span class="ag-connect-ico">{!! $agIc('Calendar', 28, 1.7) !!}</span>
                        <div class="ag-connect-title">Connect your Google Calendar</div>
                        <div class="ag-connect-sub">
                            The hub shows your events and schedules your habits around them — all from one connected calendar.
                        </div>
                        <div class="ag-connect-actions">
                            <a class="ag-btn ag-btn-google" href="{{ route('calendar.google.connect') }}">
                                {!! $agIc('Google', 15, 1.7) !!} Connect Google Calendar
                            </a>
                        </div>
                        <div class="ag-connect-note">{!! $agIc('Lock', 13, 1.7, 'ic') !!} Read-only access · the hub runs locally on your NAS</div>
                    </div>
                @elseif(empty($events))
                    <div class="ag-state">
                        <span class="ag-state-ico">{!! $agIc('Calendar', 26, 1.7) !!}</span>
                        <div class="ag-state-title">No events</div>
                        <div class="ag-state-sub">No events in the next {{ $windowDays }} days.</div>
                    </div>
                @else
                    <div class="flex h-full flex-col" data-calendar>
                        <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 pb-3">
                            <div class="inline-flex items-center gap-1 rounded-[10px] bg-[var(--hub-card)] p-1 ring-1 ring-[var(--hub-line)]" role="tablist" aria-label="View">
                                <button type="button" data-view-toggle="list" class="rounded-[7px] px-3.5 py-1.5 text-sm font-semibold text-[var(--hub-dim)] transition-colors hover:text-[var(--hub-text)] aria-selected:bg-[var(--hub-accent-soft)] aria-selected:text-[var(--hub-text)]" aria-selected="true">List</button>
                                <button type="button" data-view-toggle="week" class="rounded-[7px] px-3.5 py-1.5 text-sm font-semibold text-[var(--hub-dim)] transition-colors hover:text-[var(--hub-text)] aria-selected:bg-[var(--hub-accent-soft)] aria-selected:text-[var(--hub-text)]" aria-selected="false">Week</button>
                            </div>
                        </div>

                        @if($failed)
                            <div class="pb-3">
                                <div class="rounded-[10px] bg-[var(--hub-danger-soft)] px-4 py-2.5 text-[13px] text-[var(--hub-danger)] ring-1 ring-[var(--hub-danger)]">
                                    Couldn't refresh Google Calendar — showing last known events.
                                </div>
                            </div>
                        @endif

                        <div class="min-h-0 flex-1">
                            @include('calendar::components.calendar-view')
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============ WEEK PLAN PANEL ============ --}}
            <div data-ag-panel="weekplan" hidden>
                @if(! $connected)
                    {{-- Google connect prompt --}}
                    <div class="ag-connect">
                        <span class="ag-connect-ico">{!! $agIc('Calendar', 28, 1.7) !!}</span>
                        <div class="ag-connect-title">Connect your Google Calendar</div>
                        <div class="ag-connect-sub">
                            The planner needs your <b>fixed appointments</b> to schedule flexible habits around them.
                            Connect your calendar and the hub builds a fitting proposal each week — without changing anything
                            without your approval.
                        </div>
                        <div class="ag-connect-actions">
                            <a class="ag-btn ag-btn-google" href="{{ route('calendar.google.connect') }}">
                                {!! $agIc('Google', 15, 1.7) !!} Connect Google Calendar
                            </a>
                        </div>
                        <div class="ag-connect-note">{!! $agIc('Lock', 13, 1.7, 'ic') !!} Read-only access · the hub runs locally on your NAS</div>
                    </div>
                @elseif(! $hasPlan)
                    {{-- Connected but no plan yet --}}
                    <div class="ag-state">
                        <span class="ag-state-ico">{!! $agIc('Sparkle', 26, 1.7) !!}</span>
                        <div class="ag-state-title">No week plan yet</div>
                        <div class="ag-state-sub">
                            Your calendar is connected. Generate a week plan and the hub finds free blocks around your
                            fixed appointments to schedule your active habits.
                        </div>
                        <div class="ag-state-actions">
                            <button class="ag-btn ag-btn-primary" data-ag-generate>
                                {!! $agIc('Sparkle', 15, 1.7, 'ic') !!} Generate week plan
                            </button>
                        </div>
                    </div>
                @else
                    {{-- ===== AI summary ===== --}}
                    @if(! empty($plan['summary']))
                        <div class="ag-summary">
                            <span class="ag-summary-ic">{!! $agIc('Sparkle', 18, 1.7) !!}</span>
                            <div class="ag-summary-body">
                                <div class="ag-summary-lab">{{ ($plan['is_fallback'] ?? false) ? 'This week · automatic' : 'This week' }}</div>
                                <div class="ag-summary-tx">{{ $plan['summary'] }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- ===== plan action bar ===== --}}
                    <div class="ag-planbar">
                        <span class="ag-planbar-info">
                            <b class="tnum">{{ $proposedCount }}</b> {{ $proposedCount === 1 ? 'proposed block' : 'proposed blocks' }} ·
                            <b class="tnum">{{ $unplaceable->count() }}</b> unscheduled
                        </span>
                        <div class="ag-planbar-r">
                            <button class="ag-btn ag-btn-ghost" data-ag-generate>
                                {!! $agIc('Refresh', 15, 1.7, 'ic') !!} Regenerate
                            </button>
                            @if($proposedCount > 0)
                                <button class="ag-btn ag-btn-primary" data-ag-accept-all>
                                    {!! $agIc('CalCheck', 15, 1.7) !!} Add all to calendar
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
                                    {{-- Fixed appointments from Google (read-only context) --}}
                                    @foreach($weekBusy as $ev)
                                        @php
                                            $pos = $posFor($ev->start->toIso8601String());
                                            $durMin = max(15, $ev->start->diffInMinutes($ev->end));
                                        @endphp
                                        @if($pos['day'] === $day['index'])
                                            @php
                                                $top = ($pos['minutes'] / 60) * $hourPx;
                                                $height = ($durMin / 60) * $hourPx - 4;
                                            @endphp
                                            <div class="ag-ev busy"
                                                 title="{{ $ev->summary }}"
                                                 style="top: {{ $top }}px; height: {{ max(20, $height) }}px; background: var(--hub-elevated, rgba(120,120,140,.14)); border-left: 3px solid {{ $ev->calendarColor }}; color: var(--tx-2, #9aa);">
                                                <span class="ag-ev-title">{{ $ev->summary }}</span>
                                                <span class="ag-ev-time tnum">{{ $ev->start->format('H:i') }}–{{ $ev->end->format('H:i') }}</span>
                                            </div>
                                        @endif
                                    @endforeach

                                    {{-- Proposed / accepted plan blocks --}}
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
                                                        {!! $agIc('Check', 11, 1.7) !!} Added
                                                    @else
                                                        {!! $agIc($meta['icon'], 11, 1.7) !!} Proposed
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
                        {!! $agIc('Sparkle', 14, 1.7, 'ic') !!} Proposed blocks <span class="rule"></span>
                        <span class="count">{{ $acceptedCount > 0 ? $acceptedCount.' added · ' : '' }}{{ $proposedCount }} open</span>
                    </div>
                    <div class="ag-prop-list" data-ag-prop-list>
                        @foreach($scheduled as $item)
                            @php
                                $meta = $catFor($item['category'] ?? null);
                                $start = \Carbon\CarbonImmutable::parse($item['start_at']);
                                $end = \Carbon\CarbonImmutable::parse($item['end_at'] ?? $item['start_at']);
                                $pos = $posFor($item['start_at']);
                                $dayLabel = ($daysEn[$pos['day']] ?? '').' '.$start->day;
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
                                        {!! $agIc('CheckCircle', 17, 1.7, 'ic') !!} In calendar
                                    </div>
                                @else
                                    <div class="ag-prop-actions">
                                        <button class="ag-iconbtn reject" data-ag-reject aria-label="Reject">{!! $agIc('X', 16, 1.7) !!}</button>
                                        <button class="ag-iconbtn add" data-ag-accept>{!! $agIc('Plus', 15, 1.7) !!} Add</button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- ===== unplaceable ===== --}}
                    @if($unplaceable->isNotEmpty())
                        <div class="ag-section-lab">
                            {!! $agIc('Alert', 14, 1.7, 'ic') !!} Unscheduled <span class="rule"></span>
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
                                            {{ $item['unplaceable_reason'] ?? 'No free block found that matched the preferences.' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- ============ HABITS PANEL (streaks + check-off + manage) ============ --}}
            <div data-ag-panel="habits" hidden>
                @php
                    $activeCount = $habits->where('active', true)->count();
                    $doneToday = $habits->where('completed_today', true)->count();
                @endphp
                <div class="hb" data-hb-root data-date="{{ $today ?? '' }}">
                    <div class="hb-manage-head" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.75rem;">
                        <span class="ag-manage-lead">
                            <b class="tnum">{{ $doneToday }}</b>/{{ $habits->count() }} done today · <b class="tnum">{{ $activeCount }}</b> active
                        </span>
                        <button class="ag-btn ag-btn-primary" data-hb-create>{!! $agIc('Plus', 15, 1.7, 'ic') !!} New habit</button>
                    </div>

                    @if($habits->isEmpty())
                        <div class="ag-state">
                            <span class="ag-state-ico">{!! $hbIcon('Flame', 26) !!}</span>
                            <div class="ag-state-title">No habits yet</div>
                            <div class="ag-state-sub">Track routines — exercise, reading, meditation. The hub counts your streak and schedules the ones you mark as plannable around your fixed appointments.</div>
                            <div class="ag-state-actions">
                                <button class="ag-btn ag-btn-primary" data-hb-create>{!! $agIc('Plus', 15, 1.7, 'ic') !!} New habit</button>
                            </div>
                        </div>
                    @else
                        <div class="hb-grid">
                            @foreach($habits as $habit)
                                @php
                                    $meta = $catFor($habit['category'] ?? null);
                                    $on = (bool) ($habit['active'] ?? false);
                                @endphp
                                <div class="hb-card {{ $habit['completed_today'] ? 'done' : '' }} {{ $habit['rest_today'] && ! $habit['completed_today'] ? 'rest' : '' }} {{ $on ? '' : 'off' }}"
                                     data-hb-card
                                     data-ag-int="{{ $habit['id'] }}"
                                     data-ag-int-active="{{ $on ? 'true' : 'false' }}"
                                     data-hb-type="{{ $habit['type'] }}"
                                     data-hb-target="{{ $habit['target'] }}"
                                     data-hb-done="{{ $habit['done'] }}">
                                    <button class="hb-check {{ $habit['rest_today'] && ! $habit['completed_today'] ? 'disabled' : '' }}"
                                            data-hb-toggle
                                            data-hb-complete-url="{{ url('calendar/habits/'.$habit['id'].'/complete') }}"
                                            aria-pressed="{{ $habit['completed_today'] ? 'true' : 'false' }}"
                                            title="{{ $habit['rest_today'] && ! $habit['completed_today'] ? 'Not a scheduled day' : ($habit['completed_today'] ? 'Undo' : 'Mark done for today') }}">
                                        <span class="hb-check-rest" data-hb-icon-rest>{!! $hbIcon($habit['icon'], 24, 1.8) !!}</span>
                                        <span class="hb-check-done" data-hb-icon-done>{!! $hbIcon('Check', 26, 2.6) !!}</span>
                                    </button>

                                    <div class="hb-hbody">
                                        <div class="hb-hrow1">
                                            <span class="hb-htitle">{{ $habit['title'] }}</span>
                                            <span class="hb-cadence">{!! $hbIcon('Repeat', 12, 1.7, 'ic') !!} {{ $habit['cadence_label'] }}</span>
                                            @if($habit['plannable'])
                                                <span class="ag-cat-pill">{!! $agIc('Sparkle', 11, 1.7) !!} planned</span>
                                            @endif
                                            <span class="hb-tag ok" data-hb-tag-done @if(! $habit['completed_today']) hidden @endif>{!! $hbIcon('CheckSm', 12, 2.4) !!} Done today</span>
                                            @if($habit['rest_today'] && ! $habit['completed_today'])
                                                <span class="hb-tag rest">Rest day today</span>
                                            @endif
                                        </div>
                                        <div class="hb-prog">
                                            @if($habit['type'] === 'count')
                                                <div class="hb-seg" data-hb-seg role="img" aria-label="{{ $habit['done'] }} of {{ $habit['target'] }}">
                                                    @for($i = 0; $i < $habit['target']; $i++)
                                                        <i class="{{ $i < $habit['done'] ? ($habit['reached'] ? 'full' : 'fill') : '' }}"></i>
                                                    @endfor
                                                </div>
                                                <span class="hb-prog-tx">
                                                    <b class="tnum"><span data-hb-prog-done>{{ $habit['done'] }}</span>/{{ $habit['target'] }}</b> this week
                                                    <span class="ok" data-hb-prog-reached @if(! $habit['reached']) hidden @endif> · goal reached</span>
                                                </span>
                                            @else
                                                <div class="hb-week">
                                                    @foreach($habit['week'] as $day)
                                                        <div class="hb-day {{ $day['status'] }} {{ $day['today'] ? 'today' : '' }}">
                                                            <span class="hb-day-l">{{ $day['label'] }}</span>
                                                            <span class="hb-day-c">@if($day['status'] === 'done'){!! $hbIcon('CheckSm', 13, 2.4) !!}@endif</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <span class="hb-prog-tx"><b class="tnum"><span data-hb-prog-done>{{ $habit['week_done'] }}</span>/{{ $habit['week_total'] }}</b> this week</span>
                                            @endif
                                        </div>
                                        <button class="hb-undo" data-hb-undo @if(! $habit['completed_today']) hidden @endif>
                                            {!! $hbIcon('Undo', 13) !!} Undo
                                        </button>
                                    </div>

                                    <div class="hb-streaks">
                                        <span class="hb-streak {{ $habit['streak'] === 0 ? 'zero' : '' }}" data-hb-streak>
                                            {!! $hbIcon('Flame', 16, 1.7, 'ic') !!}
                                            <span class="n tnum" data-hb-streak-n>{{ $habit['streak'] }}</span>
                                        </span>
                                        <span class="hb-best">best <b class="tnum">{{ $habit['best'] }}</b></span>
                                        <span style="display:flex; gap:.4rem; margin-top:.35rem;">
                                            <button class="ag-toggle {{ $on ? 'on' : '' }}" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}"
                                                    aria-label="{{ $on ? 'Active' : 'Paused' }}" data-ag-int-toggle></button>
                                            <button class="hb-undo" data-ag-int-delete aria-label="Delete" title="Delete habit">{!! $hbIcon('Trash', 13) !!}</button>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ create habit modal ============ --}}
        <div class="hb-modal-backdrop" data-hb-modal hidden>
            <div class="hb-modal" role="dialog" aria-modal="true">
                <div class="hb-modal-head">
                    <span class="hb-modal-ico">{!! $hbIcon('Plus', 18, 2.2) !!}</span>
                    <span class="hb-modal-title">New habit</span>
                    <button class="hb-modal-close" data-hb-modal-close aria-label="Close">{!! $hbIcon('X', 18, 2) !!}</button>
                </div>
                <form data-hb-form>
                    <div class="hb-field">
                        <label class="hb-label" for="hb-title">Title</label>
                        <input class="hb-input" id="hb-title" name="title" required maxlength="160" placeholder="e.g. Exercise" data-hb-form-title>
                    </div>
                    <div class="hb-field">
                        <label class="hb-label" for="hb-category">Category</label>
                        <select class="hb-select" id="hb-category" data-hb-category>
                            <option value="custom">Other</option>
                            <option value="sport">Sport</option>
                            <option value="family">Family</option>
                            <option value="date">Date</option>
                        </select>
                    </div>
                    <div class="hb-cadence-row">
                        <div class="hb-field">
                            <label class="hb-label" for="hb-freq">Frequency</label>
                            <select class="hb-select" id="hb-freq" data-hb-freq>
                                <option value="times_per_week">Times per week</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                        <div class="hb-field" data-hb-times-field>
                            <label class="hb-label" for="hb-times">Times</label>
                            <input class="hb-input" id="hb-times" type="number" min="1" max="7" value="3" data-hb-times>
                        </div>
                    </div>
                    <div class="hb-field">
                        <label class="hb-label" for="hb-duration">Duration (min)</label>
                        <input class="hb-input" id="hb-duration" type="number" min="15" max="480" step="15" value="60" data-hb-duration>
                    </div>
                    <div class="hb-field">
                        <label class="hb-label" style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                            <input type="checkbox" checked data-hb-plannable> Plan this into my calendar
                        </label>
                    </div>
                    <div class="hb-field" data-hb-error hidden>
                        <span class="hb-note-tx" style="color: var(--danger)" data-hb-error-tx></span>
                    </div>
                    <div class="hb-modal-actions">
                        <button type="button" class="hb-btn hb-btn-ghost" data-hb-modal-close>Cancel</button>
                        <button type="submit" class="hb-btn hb-btn-primary" data-hb-submit>{!! $hbIcon('Plus', 15, 2.2) !!} Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard.layout>
