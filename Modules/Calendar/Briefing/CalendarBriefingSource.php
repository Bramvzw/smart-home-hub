<?php

namespace Modules\Calendar\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Calendar\Data\CalendarEvent;
use Modules\Calendar\Services\CalendarService;

class CalendarBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly CalendarService $service,
    ) {}

    public function key(): string
    {
        return 'calendar';
    }

    public function label(): string
    {
        return 'Agenda';
    }

    public function priority(): int
    {
        return 20;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        if ($this->configuredFeeds() === 0) {
            return null;
        }

        $tz = (string) config('briefing.timezone', 'Europe/Amsterdam');

        $feed = $this->service->feed(1, $date->startOfDay());
        $events = array_values(array_filter(
            $feed->events,
            static fn (CalendarEvent $event): bool => $event->start->toDateString() === $date->toDateString(),
        ));

        if ($events === []) {
            return null;
        }

        $eventSummaries = array_map(fn (CalendarEvent $event): string => $this->eventLabel($event, $tz), $events);

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: count($events).' afspraak'.(count($events) === 1 ? '' : 'en').': '.implode(', ', $eventSummaries),
            data: [
                'events' => array_map(fn (CalendarEvent $event): array => [
                    'title' => $event->summary,
                    'starts_at' => $event->start->setTimezone($tz)->toIso8601String(),
                    'ends_at' => $event->end->setTimezone($tz)->toIso8601String(),
                    'all_day' => $event->allDay,
                    'calendar' => $event->calendarLabel,
                    'location' => $event->location,
                ], $events),
                'stale' => $feed->stale,
                'stale_feeds' => $feed->staleFeeds,
            ],
        );
    }

    private function configuredFeeds(): int
    {
        return count(array_filter((array) config('calendar.feeds', []), static fn ($feed) => ! empty($feed['url'])));
    }

    private function eventLabel(CalendarEvent $event, string $tz): string
    {
        if ($event->allDay) {
            return 'hele dag '.$event->summary;
        }

        return $event->start->setTimezone($tz)->format('H:i').' '.$event->summary;
    }
}
