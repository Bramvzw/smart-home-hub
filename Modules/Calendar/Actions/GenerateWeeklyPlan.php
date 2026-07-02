<?php

namespace Modules\Calendar\Actions;

use App\Contracts\SchedulableGoals;
use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Modules\Calendar\Models\CalendarPlan;
use Modules\Calendar\Services\Google\GoogleCalendarClient;
use Modules\Calendar\Services\WeeklyPlanner;

class GenerateWeeklyPlan
{
    public function __construct(
        private readonly GoogleCalendarClient $calendar,
        private readonly WeeklyPlanner $planner,
        private readonly HubNotifier $notifier,
        private readonly SchedulableGoals $goals,
    ) {}

    public function __invoke(?CarbonImmutable $weekStart = null, bool $push = true): CalendarPlan
    {
        $this->ensureDefaults();
        $weekStart ??= CarbonImmutable::now()->next(CarbonInterface::MONDAY)->startOfDay();
        $weekStart = $weekStart->startOfWeek();
        $weekKey = $weekStart->format('o-\WW');
        $busy = $this->calendar->busyTimes(CarbonPeriod::create($weekStart, $weekStart->addDays(7)));
        $composed = $this->planner->plan($weekStart, $busy, $this->goals->plannable());
        $plan = CalendarPlan::query()->updateOrCreate(
            ['week_key' => $weekKey],
            [
                'summary' => $composed->summary,
                'status' => 'proposed',
                'is_fallback' => $composed->isFallback,
                'generated_at' => CarbonImmutable::now(),
            ]
        );

        $plan->items()->where('status', '!=', 'accepted')->delete();

        foreach ($composed->items as $item) {
            $plan->items()->create([
                'recurrence_id' => $item->goalId,
                'title' => $item->title,
                'category' => $item->category,
                'start_at' => $item->start,
                'end_at' => $item->end,
                'status' => $item->status,
                'unplaceable_reason' => $item->unplaceableReason,
            ]);
        }

        if ($push) {
            $this->notifier->send('Your week plan is ready', $plan->summary ?? 'Your week plan is ready in the hub.');
        }

        return $plan->fresh('items');
    }

    private function ensureDefaults(): void
    {
        if ($this->goals->all() !== []) {
            return;
        }

        $durations = (array) config('calendar.default_durations', []);

        foreach ([
            ['title' => 'Sporten', 'category' => 'sport', 'frequency_type' => 'times_per_week', 'target_min' => 3, 'target_max' => 4],
            ['title' => 'Moeder bezoeken', 'category' => 'family', 'frequency_type' => 'weekly', 'target_min' => 1, 'target_max' => 1],
            ['title' => 'Date night', 'category' => 'date', 'frequency_type' => 'weekly', 'target_min' => 1, 'target_max' => 1],
        ] as $goal) {
            $this->goals->create(array_merge($goal, [
                'preferred_windows' => [],
                'duration_minutes' => (int) ($durations[$goal['category']] ?? 60),
                'active' => true,
                'plannable' => true,
            ]));
        }
    }
}
