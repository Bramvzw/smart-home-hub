<?php

namespace Modules\Calendar\View\ViewModels;

use Carbon\CarbonImmutable;
use Modules\Calendar\Services\CalendarService;

class CalendarViewModel
{
    public function __construct(
        private readonly CalendarService $service,
    ) {}

    /**
     * Read model for the Calendar module page.
     */
    public function page(): array
    {
        $days = (int) config('calendar.window_days', 7);
        $feed = $this->service->feed($days);

        return [
            'events' => $feed->events,
            'eventsByDay' => $this->groupByDay($feed->events),
            'days' => $this->dayBuckets($days),
            'windowDays' => $days,
            'stale' => $feed->stale,
            'failed' => $feed->failed,
            'staleFeeds' => $feed->staleFeeds,
            'sources' => $this->sources(),
            'configured' => $this->sources() !== [],
        ];
    }

    /**
     * Feed identities (label + colour) for the legend — never the secret URL.
     *
     * @return list<array{label: string, color: string}>
     */
    private function sources(): array
    {
        return array_values(array_map(
            static fn ($feed) => [
                'label' => (string) ($feed['label'] ?? 'Agenda'),
                'color' => (string) ($feed['color'] ?? '#f2ad66'),
            ],
            array_filter((array) config('calendar.feeds', []), static fn ($feed) => ! empty($feed['url'])),
        ));
    }

    /**
     * @param  list<\Modules\Calendar\Data\CalendarEvent>  $events
     * @return array<string, list<\Modules\Calendar\Data\CalendarEvent>>
     */
    private function groupByDay(array $events): array
    {
        $byDay = [];

        foreach ($events as $event) {
            $byDay[$event->start->toDateString()][] = $event;
        }

        return $byDay;
    }

    /**
     * The ordered list of day labels covered by the window, for the week view.
     *
     * @return list<array{date: string, label: string, isToday: bool}>
     */
    private function dayBuckets(int $days): array
    {
        $today = CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
        $buckets = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $today->addDays($offset);
            $buckets[] = [
                'date' => $day->toDateString(),
                'label' => $day->isoFormat('ddd D MMM'),
                'isToday' => $offset === 0,
            ];
        }

        return $buckets;
    }
}
