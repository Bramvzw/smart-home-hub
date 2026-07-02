<x-dashboard.layout title="Maintenance" :hideHeader="true">
    <x-slot:head>
        @vite(['Modules/Tasks/resources/assets/css/maintenance.css'])
    </x-slot:head>
    <x-slot:scripts>
        @vite(['Modules/Tasks/resources/assets/js/maintenance.js'])
    </x-slot:scripts>

    @php
        $hbIcon = static function (string $name, int $size = 16, float $stroke = 1.7, string $class = ''): string {
            $paths = match ($name) {
                'Plus' => '<path d="M12 5v14M5 12h14"/>',
                'Repeat' => '<path d="M4 8a6 6 0 0 1 6-6h7"/><path d="M20 16a6 6 0 0 1-6 6H7"/>',
                'Wrench' => '<path d="M15 4a4.5 4.5 0 0 0-5.7 5.7L3 16v5h5l6.3-6.3A4.5 4.5 0 0 0 20 9l-3 3-2.5-.5L14 9z"/>',
                'Drop' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
                'Bell' => '<path d="M18 9a6 6 0 1 0-12 0c0 6-3 7-3 7h18s-3-1-3-7z"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/>',
                'Leaf' => '<path d="M4 20c0-9 6-15 16-15 0 10-6 15-13 15-2 0-3-1-3-3z"/><path d="M9 15c2-3 5-5 8-6"/>',
                'Grid' => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.4"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.4"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.4"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.4"/>',
                'Alert' => '<path d="M12 4 2.8 20a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 18h.01"/>',
                'CheckSm' => '<path d="M4 12l5 5L20 6"/>',
                'X' => '<path d="M6 6l12 12M18 6L6 18"/>',
                'Clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                default => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
            };

            return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
                .' stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
        };
    @endphp

    <div class="hb" data-maintenance data-date="{{ $date }}" data-store-url="{{ route('tasks.recurrences.store') }}">
        <div class="hb-wrap">
            <div class="hb-head">
                <div class="hb-head-l">
                    <span class="hb-eyebrow">{!! $hbIcon('Wrench', 13, 1.7, 'ic') !!} Tasks · Maintenance</span>
                    <h1 class="hb-title disp">Maintenance</h1>
                    <div class="hb-sub">
                        <b>{{ $today_label }}</b>
                        @if($overdue_count > 0)
                            · <span class="acc">{{ $overdue_count }} {{ $overdue_count === 1 ? 'task' : 'tasks' }} overdue</span>
                        @endif
                        @if($soon_count > 0)
                            , {{ $soon_count }} soon
                        @endif
                    </div>
                </div>
                <div class="hb-head-r">
                    <button class="hb-btn hb-btn-primary" data-hb-create>{!! $hbIcon('Plus', 15, 2.2) !!} New maintenance task</button>
                </div>
            </div>

            <div class="hb-tabs">
                <a class="hb-tab" href="{{ route('tasks.index') }}" wire:navigate title="The kanban board">
                    {!! $hbIcon('Grid', 15, 1.7, 'ic') !!} Board
                </a>
                <button class="hb-tab on">{!! $hbIcon('Wrench', 15, 1.7, 'ic') !!} Maintenance <span class="hb-tab-count tnum">{{ $maintenance_count }}</span></button>
                <span class="hb-tabs-spacer"></span>
            </div>

            @if($overdue_count > 0)
                <div class="hb-note">
                    {!! $hbIcon('Alert', 17, 1.7, 'ic') !!}
                    <div class="hb-note-tx">
                        <b>{{ $overdue_count }} {{ $overdue_count === 1 ? 'maintenance task is' : 'maintenance tasks are' }} overdue</b>
                        and now also {{ $overdue_count === 1 ? 'appears' : 'appear' }} as a card on your kanban board, with a recurring marker.
                    </div>
                </div>
            @endif

            @if($maintenance_count === 0)
                <div class="hb-state">
                    <span class="hb-state-ico">{!! $hbIcon('Wrench', 26) !!}</span>
                    <div class="hb-state-title">No maintenance tasks yet</div>
                    <div class="hb-state-sub">
                        Schedule recurring maintenance — smoke detectors, filters, the garden. Due tasks appear
                        automatically on your board with a recurring marker.
                    </div>
                    <div class="hb-state-actions">
                        <button class="hb-btn hb-btn-primary" data-hb-create>{!! $hbIcon('Plus', 15, 2.2) !!} New maintenance task</button>
                    </div>
                </div>
            @else
                <span class="hb-section-label">Recurring maintenance tasks</span>
                <div class="hb-mlist">
                    @foreach($maintenance as $item)
                        <div class="hb-mrow {{ $item['status'] }}" data-hb-mrow data-hb-id="{{ $item['id'] }}">
                            <span class="hb-mico">{!! $hbIcon($item['icon'], 20) !!}</span>
                            <div class="hb-mbody">
                                <div class="hb-mtitle">{{ $item['title'] }}</div>
                                <div class="hb-mmeta">
                                    <span class="hb-mcad">{!! $hbIcon('Repeat', 13, 1.7, 'ic') !!} {{ $item['cadence_label'] }}</span>
                                    @if($item['last_label'])
                                        <span class="hb-mlast">last: {{ $item['last_label'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="hb-mright">
                                @if($item['on_board'])
                                    <span class="hb-onboard" data-hb-onboard>{!! $hbIcon('Grid', 11) !!} On the board</span>
                                @endif
                                <span class="hb-due">
                                    <span class="hb-due-rel" data-hb-due-rel>{{ $item['due_rel'] }}</span>
                                    <span class="hb-due-abs" data-hb-due-abs>{{ $item['due_abs'] }}</span>
                                </span>
                                <button class="hb-maction" data-hb-maction data-hb-complete-url="{{ $item['complete_url'] }}">
                                    {!! $hbIcon('CheckSm', 14, 2.2) !!} <span data-hb-maction-label>Mark done</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- create modal (maintenance only) --}}
        <div class="hb-modal-backdrop" data-hb-modal hidden>
            <div class="hb-modal" role="dialog" aria-modal="true">
                <div class="hb-modal-head">
                    <span class="hb-modal-ico">{!! $hbIcon('Plus', 18, 2.2) !!}</span>
                    <span class="hb-modal-title">New maintenance task</span>
                    <button class="hb-modal-close" data-hb-modal-close aria-label="Close">{!! $hbIcon('X', 18, 2) !!}</button>
                </div>
                <form data-hb-form>
                    <div class="hb-field">
                        <label class="hb-label" for="hb-title">Title</label>
                        <input class="hb-input" id="hb-title" name="title" required maxlength="160" placeholder="e.g. Replace smoke detector battery" data-hb-form-title>
                    </div>
                    <div class="hb-cadence-row">
                        <div class="hb-field">
                            <label class="hb-label" for="hb-interval">Every</label>
                            <input class="hb-input" id="hb-interval" type="number" min="1" value="3" data-hb-interval>
                        </div>
                        <div class="hb-field">
                            <label class="hb-label" for="hb-unit">Unit</label>
                            <select class="hb-select" id="hb-unit" data-hb-unit>
                                <option value="days">days</option>
                                <option value="weeks">weeks</option>
                                <option value="months" selected>months</option>
                                <option value="years">years</option>
                            </select>
                        </div>
                    </div>
                    <div class="hb-field">
                        <label class="hb-label" for="hb-due">Next due</label>
                        <input class="hb-input" id="hb-due" type="date" data-hb-due>
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
