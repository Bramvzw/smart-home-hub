<?php

namespace Modules\Calendar\View\ViewModels;

use App\Contracts\SchedulableGoals;
use Carbon\CarbonImmutable;
use Modules\Calendar\Http\Resources\CalendarPlanResource;
use Modules\Calendar\Models\CalendarPlan;
use Modules\Calendar\Services\CalendarService;

class CalendarViewModel
{
    public function __construct(
        private readonly CalendarService $service,
        private readonly SchedulableGoals $goals,
    ) {}

    /**
     * Combined read model for the Calendar page: the agenda view (Google events)
     * plus the weekly planner state (plan + intentions).
     */
    public function page(): array
    {
        $connected = $this->service->connected();
        $days = (int) config('calendar.window_days', 7);
        $feed = $this->service->feed($days);
        $plan = CalendarPlan::latestGenerated()->first();
        $today = CarbonImmutable::now((string) config('calendar.timezone', 'Europe/Amsterdam'))->startOfDay();

        return [
            'connected' => $connected,
            'events' => $feed->events,
            'eventsByDay' => $this->groupByDay($feed->events),
            'days' => $this->dayBuckets($days),
            'windowDays' => $days,
            'stale' => $feed->stale,
            'failed' => $feed->failed,
            'staleFeeds' => $feed->staleFeeds,
            'plan' => $plan ? CalendarPlanResource::make($plan)->resolve() : null,
            'habits' => array_map(fn (\App\Data\HabitCard $card): array => $card->toArray(), $this->goals->cards($today)),
            'today' => $today->toDateString(),
        ];
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
        $today = CarbonImmutable::now((string) config('calendar.timezone', 'Europe/Amsterdam'))->startOfDay();
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
