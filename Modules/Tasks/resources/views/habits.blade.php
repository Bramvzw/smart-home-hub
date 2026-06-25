<x-dashboard.layout title="Gewoontes" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Tasks/resources/assets/css/habits.css'])
    </x-slot:head>
    <x-slot:scripts>
        @vite(['Modules/Tasks/resources/assets/js/habits.js'])
    </x-slot:scripts>

    @php
        $hbIcon = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $paths = match ($name) {
                'Flame' => '<path d="M12 3c.6 2.6-.9 3.9-2.1 5.2C8.4 9.8 7 11.3 7 14a5 5 0 0 0 10 0c0-1.6-.6-3-1.4-4-.3 1-1 1.7-1.8 1.9.5-2.3-.4-4.6-1.8-8.9z"/>',
                'Check' => '<path d="M5 12.5 10 17l9-10"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'Undo' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
                'Repeat' => '<path d="M4 8a6 6 0 0 1 6-6h7"/><path d="M14 -1l3 3-3 3" transform="translate(0 3)"/><path d="M20 16a6 6 0 0 1-6 6H7"/><path d="M10 25l-3-3 3-3" transform="translate(0 -3)"/>',
                'Activity' => '<path d="M3 12h3.5l2.5 7 5-15 2.5 8H21"/>',
                'Book' => '<path d="M5 4h11a2 2 0 0 1 2 2v14H7a2 2 0 0 0-2 2z"/><path d="M5 4v16M18 18v2"/>',
                'Spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 14.5l.7 2 .8.7-.8.6-.7 2-.6-2-2-.6 2-.7z"/>',
                'Leaf' => '<path d="M4 20c0-9 6-15 16-15 0 10-6 15-13 15-2 0-3-1-3-3z"/><path d="M9 15c2-3 5-5 8-6"/>',
                'Wrench' => '<path d="M15 4a4.5 4.5 0 0 0-5.7 5.7L3 16v5h5l6.3-6.3A4.5 4.5 0 0 0 20 9l-3 3-2.5-.5L14 9z"/>',
                'Drop' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
                'Bell' => '<path d="M18 9a6 6 0 1 0-12 0c0 6-3 7-3 7h18s-3-1-3-7z"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/>',
                'Sun' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>',
                'Calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'Info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
                'Grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.4"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.4"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.4"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.4"/>',
                'Target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.4"/>',
                'ArrowR' => '<path d="M5 12h13M13 6l6 6-6 6"/>',
                'X' => '<path d="M6 6l12 12M18 6L6 18"/>',
                default => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
            };
            $fill = $name === 'Flame';

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24"'
                .' fill="'.($fill ? 'currentColor' : 'none').'" stroke="'.($fill ? 'none' : 'currentColor').'"'
                .' stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
        };
    @endphp

    <div class="hb"
         data-habits
         data-date="{{ $date }}"
         data-store-url="{{ route('tasks.recurrences.store') }}"
         data-board-url="{{ route('tasks.index') }}">
        <div class="hb-wrap">
            {{-- ============ header ============ --}}
            <div class="hb-head">
                <div class="hb-head-l">
                    <span class="hb-eyebrow">{!! $hbIcon('Flame', 13, 1.7, 'ic') !!} Taken · Routines</span>
                    <h1 class="hb-title disp" data-hb-page-title>Gewoontes</h1>
                    <div class="hb-sub">
                        <span data-hb-sub="gewoontes">
                            <b style="text-transform: capitalize">{{ $today_label }}</b> ·
                            <b class="tnum">{{ $done_today }}/{{ $actionable_today }}</b> vandaag afgerond
                        </span>
                        <span data-hb-sub="onderhoud" hidden>
                            <b style="text-transform: capitalize">{{ $today_label }}</b>
                            @if($overdue_count > 0)
                                · <span class="acc">{{ $overdue_count }} {{ $overdue_count === 1 ? 'taak' : 'taken' }} te laat</span>
                            @endif
                            @if($soon_count > 0)
                                , {{ $soon_count }} binnenkort
                            @endif
                        </span>
                    </div>
                </div>
                <div class="hb-head-r">
                    <button class="hb-btn hb-btn-primary" data-hb-create>
                        {!! $hbIcon('Plus', 15, 2.2) !!} <span data-hb-create-label>Nieuwe gewoonte</span>
                    </button>
                </div>
            </div>

            {{-- ============ tabs ============ --}}
            <div class="hb-tabs">
                <a class="hb-tab" href="{{ route('tasks.index') }}" wire:navigate title="Het bestaande kanban-bord">
                    {!! $hbIcon('Grid', 15, 1.7, 'ic') !!} Bord
                </a>
                <button class="hb-tab on" data-hb-tab="gewoontes">
                    {!! $hbIcon('Flame', 15, 1.7, 'ic') !!} Gewoontes
                    <span class="hb-tab-count tnum">{{ $habit_count }}</span>
                </button>
                <button class="hb-tab" data-hb-tab="onderhoud">
                    {!! $hbIcon('Wrench', 15, 1.7, 'ic') !!} Onderhoud
                    <span class="hb-tab-count tnum">{{ $maintenance_count }}</span>
                </button>
                <span class="hb-tabs-spacer"></span>
            </div>

            {{-- ============ GEWOONTES ============ --}}
            <div data-hb-panel="gewoontes">
                @if($habit_count === 0)
                    <div class="hb-state">
                        <span class="hb-state-ico">{!! $hbIcon('Flame', 26) !!}</span>
                        <div class="hb-state-title">Nog geen gewoontes</div>
                        <div class="hb-state-sub">
                            Houd routines bij naast je taken — sporten, lezen, een taal leren. De hub telt je
                            reeks en laat zien wat er vandaag nog open staat.
                        </div>
                        <div class="hb-state-actions">
                            <button class="hb-btn hb-btn-primary" data-hb-create>{!! $hbIcon('Plus', 15, 2.2) !!} Nieuwe gewoonte</button>
                        </div>
                        <div class="hb-suggest">
                            <button class="hb-suggest-chip" data-hb-suggest='{"title":"Sporten","cadence_type":"times_per_week","times":3}'>{!! $hbIcon('Activity', 14, 1.7, 'ic') !!} Sporten · 3× per week</button>
                            <button class="hb-suggest-chip" data-hb-suggest='{"title":"Lezen","cadence_type":"weekdays","weekdays":[1,3,5]}'>{!! $hbIcon('Book', 14, 1.7, 'ic') !!} Lezen · ma/wo/vr</button>
                            <button class="hb-suggest-chip" data-hb-suggest='{"title":"Mediteren","cadence_type":"daily"}'>{!! $hbIcon('Spark', 14, 1.7, 'ic') !!} Mediteren · dagelijks</button>
                            <button class="hb-suggest-chip" data-hb-suggest='{"title":"Water drinken","cadence_type":"daily"}'>{!! $hbIcon('Drop', 14, 1.7, 'ic') !!} Water · dagelijks</button>
                        </div>
                    </div>
                @else
                    <div class="hb-grid">
                        @foreach($habits as $habit)
                            <div class="hb-card {{ $habit['completed_today'] ? 'done' : '' }} {{ $habit['rest_today'] && ! $habit['completed_today'] ? 'rest' : '' }}"
                                 data-hb-card
                                 data-hb-id="{{ $habit['id'] }}"
                                 data-hb-type="{{ $habit['type'] }}"
                                 data-hb-target="{{ $habit['target'] }}"
                                 data-hb-done="{{ $habit['done'] }}">
                                <button class="hb-check {{ $habit['rest_today'] && ! $habit['completed_today'] ? 'disabled' : '' }}"
                                        data-hb-toggle
                                        data-hb-complete-url="{{ $habit['complete_url'] }}"
                                        aria-pressed="{{ $habit['completed_today'] ? 'true' : 'false' }}"
                                        title="{{ $habit['rest_today'] && ! $habit['completed_today'] ? 'Geen geplande dag' : ($habit['completed_today'] ? 'Ongedaan maken' : 'Vandaag afvinken') }}">
                                    <span class="hb-check-rest" data-hb-icon-rest>{!! $hbIcon($habit['icon'], 24, 1.8) !!}</span>
                                    <span class="hb-check-done" data-hb-icon-done>{!! $hbIcon('Check', 26, 2.6) !!}</span>
                                </button>

                                <div class="hb-hbody">
                                    <div class="hb-hrow1">
                                        <span class="hb-htitle">{{ $habit['title'] }}</span>
                                        <span class="hb-cadence">{!! $hbIcon('Repeat', 12, 1.7, 'ic') !!} {{ $habit['cadence_label'] }}</span>
                                        <span class="hb-tag ok" data-hb-tag-done @if(! $habit['completed_today']) hidden @endif>{!! $hbIcon('CheckSm', 12, 2.4) !!} Vandaag gedaan</span>
                                        @if($habit['rest_today'] && ! $habit['completed_today'])
                                            <span class="hb-tag rest">Rustdag vandaag</span>
                                        @endif
                                    </div>
                                    <div class="hb-prog">
                                        @if($habit['type'] === 'count')
                                            <div class="hb-seg" data-hb-seg role="img" aria-label="{{ $habit['done'] }} van {{ $habit['target'] }}">
                                                @for($i = 0; $i < $habit['target']; $i++)
                                                    <i class="{{ $i < $habit['done'] ? ($habit['reached'] ? 'full' : 'fill') : '' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="hb-prog-tx">
                                                <b class="tnum"><span data-hb-prog-done>{{ $habit['done'] }}</span>/{{ $habit['target'] }}</b> deze week
                                                <span class="ok" data-hb-prog-reached @if(! $habit['reached']) hidden @endif> · doel gehaald</span>
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
                                            <span class="hb-prog-tx"><b class="tnum"><span data-hb-prog-done>{{ $habit['week_done'] }}</span>/{{ $habit['week_total'] }}</b> deze week</span>
                                        @endif
                                    </div>
                                    <button class="hb-undo" data-hb-undo @if(! $habit['completed_today']) hidden @endif>
                                        {!! $hbIcon('Undo', 13) !!} Ongedaan maken
                                    </button>
                                </div>

                                <div class="hb-streaks">
                                    <span class="hb-streak {{ $habit['streak'] === 0 ? 'zero' : '' }}" data-hb-streak>
                                        {!! $hbIcon('Flame', 16, 1.7, 'ic') !!}
                                        <span class="n tnum" data-hb-streak-n>{{ $habit['streak'] }}</span>
                                    </span>
                                    <span class="hb-best">beste <b class="tnum">{{ $habit['best'] }}</b></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ============ ONDERHOUD ============ --}}
            <div data-hb-panel="onderhoud" hidden>
                @if($overdue_count > 0)
                    <div class="hb-note">
                        {!! $hbIcon('Alert', 17, 1.7, 'ic') !!}
                        <div class="hb-note-tx">
                            <b>{{ $overdue_count }} {{ $overdue_count === 1 ? 'onderhoudstaak staat' : 'onderhoudstaken staan' }} te laat</b>
                            en {{ $overdue_count === 1 ? 'verschijnt' : 'verschijnen' }} nu ook als kaart op je kanban-bord, met een terugkerend-markering.
                        </div>
                    </div>
                @endif

                @if($maintenance_count === 0)
                    <div class="hb-state">
                        <span class="hb-state-ico">{!! $hbIcon('Wrench', 26) !!}</span>
                        <div class="hb-state-title">Nog geen onderhoudstaken</div>
                        <div class="hb-state-sub">
                            Plan terugkerend onderhoud — rookmelders, filters, de moestuin. Due taken
                            verschijnen automatisch op je bord met een terugkerend-markering.
                        </div>
                        <div class="hb-state-actions">
                            <button class="hb-btn hb-btn-primary" data-hb-create data-hb-create-type="maintenance">{!! $hbIcon('Plus', 15, 2.2) !!} Nieuwe onderhoudstaak</button>
                        </div>
                    </div>
                @else
                    <span class="hb-section-label">Terugkerende onderhoudstaken</span>
                    <div class="hb-mlist">
                        @foreach($maintenance as $item)
                            <div class="hb-mrow {{ $item['status'] }}" data-hb-mrow data-hb-id="{{ $item['id'] }}">
                                <span class="hb-mico">{!! $hbIcon($item['icon'], 20) !!}</span>
                                <div class="hb-mbody">
                                    <div class="hb-mtitle">{{ $item['title'] }}</div>
                                    <div class="hb-mmeta">
                                        <span class="hb-mcad">{!! $hbIcon('Repeat', 13, 1.7, 'ic') !!} {{ $item['cadence_label'] }}</span>
                                        @if($item['last_label'])
                                            <span class="hb-mlast">laatst: {{ $item['last_label'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="hb-mright">
                                    @if($item['on_board'])
                                        <span class="hb-onboard" data-hb-onboard>{!! $hbIcon('Grid', 11) !!} Op het bord</span>
                                    @endif
                                    <span class="hb-due">
                                        <span class="hb-due-rel" data-hb-due-rel>{{ $item['due_rel'] }}</span>
                                        <span class="hb-due-abs" data-hb-due-abs>{{ $item['due_abs'] }}</span>
                                    </span>
                                    <button class="hb-maction" data-hb-maction data-hb-complete-url="{{ $item['complete_url'] }}">
                                        {!! $hbIcon('CheckSm', 14, 2.2) !!} <span data-hb-maction-label>Afvinken</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ create modal ============ --}}
        <div class="hb-modal-backdrop" data-hb-modal hidden>
            <div class="hb-modal" role="dialog" aria-modal="true">
                <div class="hb-modal-head">
                    <span class="hb-modal-ico">{!! $hbIcon('Plus', 18, 2.2) !!}</span>
                    <span class="hb-modal-title" data-hb-modal-title>Nieuwe gewoonte</span>
                    <button class="hb-modal-close" data-hb-modal-close aria-label="Sluiten">{!! $hbIcon('X', 18, 2) !!}</button>
                </div>

                <form data-hb-form>
                    <input type="hidden" name="type" value="habit" data-hb-form-type>

                    <div class="hb-field">
                        <label class="hb-label" for="hb-title">Titel</label>
                        <input class="hb-input" id="hb-title" name="title" required maxlength="160" placeholder="Bijv. Sporten" data-hb-form-title>
                    </div>

                    {{-- habit cadence --}}
                    <div data-hb-mfield="habit">
                        <div class="hb-field">
                            <label class="hb-label" for="hb-cadence">Ritme</label>
                            <select class="hb-select" id="hb-cadence" data-hb-cadence>
                                <option value="times_per_week">Aantal keer per week</option>
                                <option value="weekdays">Bepaalde weekdagen</option>
                                <option value="daily">Dagelijks</option>
                            </select>
                        </div>
                        <div class="hb-field" data-hb-cfield="times_per_week">
                            <label class="hb-label" for="hb-times">Keer per week</label>
                            <input class="hb-input" id="hb-times" type="number" min="1" max="7" value="3" data-hb-times>
                        </div>
                        <div class="hb-field" data-hb-cfield="weekdays" hidden>
                            <label class="hb-label">Weekdagen</label>
                            <div class="hb-weekdays" data-hb-weekdays>
                                @foreach(['ma' => 1, 'di' => 2, 'wo' => 3, 'do' => 4, 'vr' => 5, 'za' => 6, 'zo' => 7] as $label => $iso)
                                    <button type="button" class="hb-wd" data-hb-wd="{{ $iso }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- maintenance cadence --}}
                    <div data-hb-mfield="maintenance" hidden>
                        <div class="hb-cadence-row">
                            <div class="hb-field">
                                <label class="hb-label" for="hb-interval">Elke</label>
                                <input class="hb-input" id="hb-interval" type="number" min="1" value="3" data-hb-interval>
                            </div>
                            <div class="hb-field">
                                <label class="hb-label" for="hb-unit">Eenheid</label>
                                <select class="hb-select" id="hb-unit" data-hb-unit>
                                    <option value="days">dagen</option>
                                    <option value="weeks">weken</option>
                                    <option value="months" selected>maanden</option>
                                    <option value="years">jaar</option>
                                </select>
                            </div>
                        </div>
                        <div class="hb-field">
                            <label class="hb-label" for="hb-due">Eerstvolgende keer</label>
                            <input class="hb-input" id="hb-due" type="date" data-hb-due>
                        </div>
                    </div>

                    <div class="hb-field" data-hb-error hidden>
                        <span class="hb-note-tx" style="color: var(--danger)" data-hb-error-tx></span>
                    </div>

                    <div class="hb-modal-actions">
                        <button type="button" class="hb-btn hb-btn-ghost" data-hb-modal-close>Annuleren</button>
                        <button type="submit" class="hb-btn hb-btn-primary" data-hb-submit>{!! $hbIcon('Plus', 15, 2.2) !!} Aanmaken</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard.layout>
