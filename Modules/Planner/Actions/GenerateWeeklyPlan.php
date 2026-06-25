<?php

namespace Modules\Planner\Actions;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Models\PlannerPlan;
use Modules\Planner\Services\Google\GoogleCalendarClient;
use Modules\Planner\Services\WeeklyPlanner;

class GenerateWeeklyPlan
{
    public function __construct(
        private readonly GoogleCalendarClient $calendar,
        private readonly WeeklyPlanner $planner,
        private readonly HubNotifier $notifier,
    ) {}

    public function __invoke(?CarbonImmutable $weekStart = null, bool $push = true): PlannerPlan
    {
        $this->ensureDefaults();
        $weekStart ??= CarbonImmutable::now()->next(CarbonInterface::MONDAY)->startOfDay();
        $weekStart = $weekStart->startOfWeek();
        $weekKey = $weekStart->format('o-\WW');
        $busy = $this->calendar->busyTimes(CarbonPeriod::create($weekStart, $weekStart->addDays(7)));
        $composed = $this->planner->plan($weekStart, $busy);
        $plan = PlannerPlan::query()->updateOrCreate(
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
                'intention_id' => $item->intentionId,
                'title' => $item->title,
                'start_at' => $item->start,
                'end_at' => $item->end,
                'status' => $item->status,
                'unplaceable_reason' => $item->unplaceableReason,
            ]);
        }

        if ($push) {
            $this->notifier->send('Je weekplan staat klaar', $plan->summary ?? 'Je weekplan staat klaar in de hub.');
        }

        return $plan->fresh('items');
    }

    private function ensureDefaults(): void
    {
        if (PlannerIntention::query()->exists()) {
            return;
        }

        foreach ([
            ['title' => 'Sporten', 'category' => 'sport', 'frequency_type' => 'times_per_week', 'target_min' => 3, 'target_max' => 4],
            ['title' => 'Moeder bezoeken', 'category' => 'family', 'frequency_type' => 'weekly', 'target_min' => 1, 'target_max' => 1],
            ['title' => 'Date night', 'category' => 'date', 'frequency_type' => 'weekly', 'target_min' => 1, 'target_max' => 1],
        ] as $data) {
            PlannerIntention::query()->create(array_merge($data, [
                'preferred_windows' => null,
                'duration_minutes' => (int) config('planner.default_durations.'.$data['category'], 60),
                'active' => true,
            ]));
        }
    }
}
