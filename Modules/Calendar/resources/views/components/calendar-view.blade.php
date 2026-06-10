@php
    $tz = config('app.timezone', 'UTC');
@endphp

{{-- List view: events grouped per day, each event a distinct card --}}
<div data-calendar-list class="h-full overflow-y-auto px-6 py-5">
    <div class="mx-auto max-w-3xl space-y-7">
        @foreach($days as $bucket)
            @php($dayEvents = $eventsByDay[$bucket['date']] ?? [])
            @if(! empty($dayEvents))
                <section>
                    <div class="mb-2.5 flex items-baseline gap-2">
                        <h3 class="text-xs font-bold uppercase tracking-wide {{ $bucket['isToday'] ? 'text-[var(--hub-accent)]' : 'text-[var(--hub-muted)]' }}">
                            {{ \Carbon\CarbonImmutable::parse($bucket['date'])->locale('nl')->isoFormat('dddd D MMMM') }}
                        </h3>
                        @if($bucket['isToday'])
                            <span class="rounded-full bg-[var(--hub-accent-soft)] px-2 py-0.5 text-[10px] font-bold uppercase text-[var(--hub-accent)]">vandaag</span>
                        @endif
                    </div>
                    <div class="space-y-2">
                        @foreach($dayEvents as $event)
                            <div class="flex items-stretch overflow-hidden rounded-[12px] bg-[var(--hub-card)] ring-1 ring-[var(--hub-line-strong)] transition-colors hover:bg-[var(--hub-card-hover)] hover:ring-[var(--hub-accent-line)]">
                                <span class="w-1 shrink-0 {{ $bucket['isToday'] ? 'bg-[var(--hub-accent)]' : 'bg-[var(--hub-line-strong)]' }}"></span>
                                <div class="flex min-w-0 flex-1 items-center gap-4 py-3 pl-4 pr-4">
                                    <div class="w-16 shrink-0 text-right">
                                        @if($event->allDay)
                                            <p class="text-[13px] font-bold uppercase tracking-wide text-[var(--hub-muted)]">hele<br>dag</p>
                                        @else
                                            <p class="text-[15px] font-bold tabular-nums text-[var(--hub-text)]">{{ $event->start->setTimezone($tz)->format('H:i') }}</p>
                                            <p class="text-xs tabular-nums text-[var(--hub-dim)]">{{ $event->end->setTimezone($tz)->format('H:i') }}</p>
                                        @endif
                                    </div>
                                    <span class="h-9 w-px shrink-0 bg-[var(--hub-line)]"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[15px] font-semibold text-[var(--hub-text)]">{{ $event->summary }}</p>
                                        @if($event->location)
                                            <p class="mt-0.5 truncate text-[13px] text-[var(--hub-muted)]">{{ $event->location }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</div>

{{-- Week view: a full-height column per day --}}
<div data-calendar-week hidden class="h-full overflow-x-auto px-6 py-5">
    <div class="flex h-full min-w-max gap-3">
        @foreach($days as $bucket)
            @php($dayEvents = $eventsByDay[$bucket['date']] ?? [])
            <div class="flex w-64 flex-col overflow-hidden rounded-[14px] bg-[var(--hub-card)] ring-1 {{ $bucket['isToday'] ? 'ring-[var(--hub-accent-line)]' : 'ring-[var(--hub-line)]' }}">
                <div class="flex items-center justify-between border-b border-[var(--hub-line)] px-3 py-2.5">
                    <h4 class="text-xs font-bold {{ $bucket['isToday'] ? 'text-[var(--hub-accent)]' : 'text-[var(--hub-muted)]' }}">
                        {{ \Carbon\CarbonImmutable::parse($bucket['date'])->locale('nl')->isoFormat('ddd D MMM') }}
                    </h4>
                    @if(count($dayEvents))
                        <span class="rounded-full bg-[var(--hub-elevated)] px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-[var(--hub-dim)] ring-1 ring-[var(--hub-line)]">{{ count($dayEvents) }}</span>
                    @endif
                </div>
                <div class="flex-1 space-y-1.5 overflow-y-auto p-2">
                    @forelse($dayEvents as $event)
                        <div class="rounded-[9px] bg-[var(--hub-elevated)] px-2.5 py-2 ring-1 ring-[var(--hub-line-strong)]">
                            <p class="text-[11px] font-bold tabular-nums {{ $bucket['isToday'] ? 'text-[var(--hub-accent)]' : 'text-[var(--hub-muted)]' }}">
                                {{ $event->allDay ? 'hele dag' : $event->start->setTimezone($tz)->format('H:i') }}
                            </p>
                            <p class="mt-0.5 line-clamp-2 text-xs font-medium text-[var(--hub-text)]">{{ $event->summary }}</p>
                        </div>
                    @empty
                        <p class="px-1 pt-1 text-xs text-[var(--hub-dim)]">—</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
