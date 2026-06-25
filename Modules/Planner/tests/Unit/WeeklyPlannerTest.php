<?php

namespace Modules\Planner\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Planner\Contracts\PlanComposer;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Data\ComposedPlan;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Services\SlotFinder;
use Modules\Planner\Services\WeeklyPlanner;
use Tests\TestCase;

class WeeklyPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_up_to_target_max_when_slots_allow(): void
    {
        PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 3,
            'target_max' => 4,
            'duration_minutes' => 90,
            'active' => true,
        ]);

        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), []);

        $proposed = collect($composed->items)->where('status', 'proposed');
        $this->assertCount(4, $proposed);
        $this->assertCount(0, collect($composed->items)->where('status', 'unplaceable'));
    }

    public function test_tight_week_falls_back_toward_target_min_and_marks_extras_unplaceable(): void
    {
        PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 3,
            'target_max' => 4,
            // Narrow window: only one weekday evening slot fits per day, and we block most of the week.
            'preferred_windows' => [['days' => 'weekday', 'after' => '17:00', 'before' => '18:30']],
            'duration_minutes' => 90,
            'active' => true,
        ]);

        // Week 2026-06-29 (Mon) .. 07-05. Weekdays Mon-Fri have the 17:00-18:30 window.
        // Block Mon/Tue/Wed so only Thu + Fri remain -> 2 placeable, below target_min (3).
        $busy = [
            new BusyTime(CarbonImmutable::parse('2026-06-29 17:00'), CarbonImmutable::parse('2026-06-29 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-06-30 17:00'), CarbonImmutable::parse('2026-06-30 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-01 17:00'), CarbonImmutable::parse('2026-07-01 18:30')),
        ];

        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), $busy);

        $proposed = collect($composed->items)->where('status', 'proposed');
        $unplaceable = collect($composed->items)->where('status', 'unplaceable');

        // 2 fit (Thu/Fri), the remaining attempts up to target_max are reported, not dropped.
        $this->assertCount(2, $proposed);
        $this->assertCount(2, $unplaceable);
        $this->assertNotEmpty($unplaceable->first()->unplaceableReason);
    }

    public function test_extras_above_target_min_that_do_not_fit_are_reported(): void
    {
        PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 1,
            'target_max' => 4,
            'preferred_windows' => [['days' => 'weekday', 'after' => '17:00', 'before' => '18:30']],
            'duration_minutes' => 90,
            'active' => true,
        ]);

        // Leave only Monday free; block Tue-Fri evening windows -> 1 placeable (== target_min), 3 extras unplaceable.
        $busy = [
            new BusyTime(CarbonImmutable::parse('2026-06-30 17:00'), CarbonImmutable::parse('2026-06-30 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-01 17:00'), CarbonImmutable::parse('2026-07-01 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-02 17:00'), CarbonImmutable::parse('2026-07-02 18:30')),
            new BusyTime(CarbonImmutable::parse('2026-07-03 17:00'), CarbonImmutable::parse('2026-07-03 18:30')),
        ];

        $composed = $this->planner()->plan(CarbonImmutable::parse('2026-06-29'), $busy);

        $this->assertCount(1, collect($composed->items)->where('status', 'proposed'));
        $extras = collect($composed->items)->where('status', 'unplaceable');
        $this->assertCount(3, $extras);
        $this->assertSame('Geen ruimte meer voor extra blok deze week', $extras->first()->unplaceableReason);
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
