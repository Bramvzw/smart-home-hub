<x-dashboard.layout title="Calendar">
    <x-slot:scripts>
        @vite(['Modules/Calendar/resources/assets/js/calendar.js'])
    </x-slot:scripts>

    <div class="flex h-full flex-col" data-calendar>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-[var(--hub-line)] px-6 py-3">
            <p class="text-[13px] text-[var(--hub-dim)]">Komende {{ $windowDays }} dagen · alleen-lezen</p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                @if($configured && count($sources) > 1)
                    @foreach($sources as $source)
                        <span class="inline-flex items-center gap-1.5 text-[13px] {{ in_array($source['label'], $staleFeeds, true) ? 'text-[var(--hub-dim)]' : 'text-[var(--hub-muted)]' }}">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $source['color'] }}"></span>
                            {{ $source['label'] }}
                            @if(in_array($source['label'], $staleFeeds, true))<span class="text-[var(--hub-danger)]">(verouderd)</span>@endif
                        </span>
                    @endforeach
                @endif

                @if($configured && ! empty($events))
                    <div class="inline-flex items-center gap-1 rounded-[10px] bg-[var(--hub-card)] p-1 ring-1 ring-[var(--hub-line)]" role="tablist" aria-label="Weergave">
                        <button type="button" data-view-toggle="list" class="rounded-[7px] px-3.5 py-1.5 text-sm font-semibold text-[var(--hub-dim)] transition-colors hover:text-[var(--hub-text)] aria-selected:bg-[var(--hub-accent-soft)] aria-selected:text-[var(--hub-text)]" aria-selected="true">Lijst</button>
                        <button type="button" data-view-toggle="week" class="rounded-[7px] px-3.5 py-1.5 text-sm font-semibold text-[var(--hub-dim)] transition-colors hover:text-[var(--hub-text)] aria-selected:bg-[var(--hub-accent-soft)] aria-selected:text-[var(--hub-text)]" aria-selected="false">Week</button>
                    </div>
                @endif
            </div>
        </div>

        @if($configured && $failed)
            <div class="px-6 pt-4">
                <div class="rounded-[10px] bg-[var(--hub-danger-soft)] px-4 py-2.5 text-[13px] text-[var(--hub-danger)] ring-1 ring-[var(--hub-danger)]">
                    @if(count($staleFeeds))
                        Kon niet bijwerken: {{ implode(', ', $staleFeeds) }} — laatst bekende afspraken worden getoond.
                    @else
                        De agenda kon niet worden opgehaald — laatst bekende afspraken worden getoond.
                    @endif
                </div>
            </div>
        @endif

        <div class="min-h-0 flex-1">
            @unless($configured)
                <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <div class="mb-4 grid h-12 w-12 place-items-center rounded-[12px] bg-[var(--hub-card)] text-[var(--hub-dim)] ring-1 ring-[var(--hub-line)]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="17" rx="2"/><path stroke-linecap="round" d="M3 9h18M8 2v4M16 2v4"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-[var(--hub-muted)]">Geen agenda gekoppeld</h3>
                    <p class="mt-1 max-w-sm text-sm text-[var(--hub-dim)]">
                        Stel <code class="rounded bg-[var(--hub-card)] px-1.5 py-0.5 text-[var(--hub-muted)]">CALENDAR_ICS_FEEDS</code> in met je geheime iCal-adres(sen) van Google Agenda om afspraken te tonen.
                    </p>
                </div>
            @else
                @if(empty($events))
                    <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                        <div class="mb-4 grid h-12 w-12 place-items-center rounded-[12px] bg-[var(--hub-card)] text-[var(--hub-dim)] ring-1 ring-[var(--hub-line)]">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="17" rx="2"/><path stroke-linecap="round" d="M3 9h18M8 2v4M16 2v4"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-[var(--hub-muted)]">Geen afspraken</h3>
                        <p class="mt-1 text-sm text-[var(--hub-dim)]">Er staan geen afspraken in de komende {{ $windowDays }} dagen.</p>
                    </div>
                @else
                    @include('calendar::components.calendar-view')
                @endif
            @endunless
        </div>
    </div>
</x-dashboard.layout>
