<?php

namespace Modules\Calendar\Tests\Unit;

use App\Data\SchedulableGoal;
use Carbon\CarbonImmutable;
use Modules\Calendar\Contracts\PlanComposer;
use Modules\Calendar\Data\BusyTime;
use Modules\Calendar\Data\ComposedPlan;
use Modules\Calendar\Services\SlotFinder;
use Modules\Calendar\Services\WeeklyPlanner;
use Tests\TestCase;

class WeeklyPlannerTest extends TestCase
{
    private function goal(int $min, int $max, array $windows = []): SchedulableGoal
    {
        return new SchedulableGoal(
            id: 1,
            title: 'Sporten',
            category: 'sport',
            frequencyType: 'times_per_week',
            durationMinutes: 90,
            targetMin: $min,
            targetMax: $max,
            preferredWindows: $windows,
            active: true,
        );
    }

    public function test_places_up_to_target_max_when_slots_allow(): void
    {
        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), [], [$this->goal(3, 4)]);

        $proposed = collect($composed->items)->where('status', 'proposed');
        $this->assertCount(4, $proposed);
        $this->assertCount(0, collect($composed->items)->where('status', 'unplaceable'));
    }

    public function test_tight_week_falls_back_toward_target_min_and_marks_extras_unplaceable(): void
    {
        // Narrow window: only one weekday evening slot fits per day, and we block most of the week.
        $goal = $this->goal(3, 4, [['days' => 'weekday', 'after' => '17:00', 'before' => '18:30']]);

        // Week 2026-06-29 (Mon) .. 07-05. Weekdays Mon-Fri have the 17:00-18:30 window.
        // Block Mon/Tue/Wed so only Thu + Fri remain -> 2 placeable, below target_min (3).
        $busy = [
            new BusyTime(CarbonImmutable::parse('2026-06-29 17:00'), CarbonImmutable::parse('2026-06-29 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-06-30 17:00'), CarbonImmutable::parse('2026-06-30 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-01 17:00'), CarbonImmutable::parse('2026-07-01 18:30')),
        ];

        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), $busy, [$goal]);

        $proposed = collect($composed->items)->where('status', 'proposed');
        $unplaceable = collect($composed->items)->where('status', 'unplaceable');

        // 2 fit (Thu/Fri), the remaining attempts up to target_max are reported, not dropped.
        $this->assertCount(2, $proposed);
        $this->assertCount(2, $unplaceable);
        $this->assertNotEmpty($unplaceable->first()->unplaceableReason);
    }

    public function test_extras_above_target_min_that_do_not_fit_are_reported(): void
    {
        $goal = $this->goal(1, 4, [['days' => 'weekday', 'after' => '17:00', 'before' => '18:30']]);

        // Leave only Monday free; block Tue-Fri evening windows -> 1 placeable (== target_min), 3 extras unplaceable.
        $busy = [
            new BusyTime(CarbonImmutable::parse('2026-06-30 17:00'), CarbonImmutable::parse('2026-06-30 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-01 17:00'), CarbonImmutable::parse('2026-07-01 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-02 17:00'), CarbonImmutable::parse('2026-07-02 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-03 17:00'), CarbonImmutable::parse('2026-07-03 18:30')),
        ];

        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), $busy, [$goal]);

        $this->assertCount(1, collect($composed->items)->where('status', 'proposed'));
        $extras = collect($composed->items)->where('status', 'unplaceable');
        $this->assertCount(3, $extras);
        $this->assertSame('No room for an extra block this week', $extras->first()->unplaceableReason);
    }

    private function planner(): WeeklyPlanner
    {
        return new WeeklyPlanner(app(SlotFinder::class), new PassthroughComposer);
    }
}

class PassthroughComposer implements PlanComposer
{
    public function compose(array $items, array $busy): ComposedPlan
    {
        return new ComposedPlan('test', $items, false);
    }
}
