<?php

namespace Modules\Calendar\Tests\Unit;

use App\Data\SchedulableGoal;
use Carbon\CarbonImmutable;
use Modules\Calendar\Data\BusyTime;
use Modules\Calendar\Services\SlotFinder;
use Tests\TestCase;

class SlotFinderTest extends TestCase
{
    private function goal(string $category, int $duration, array $windows = [], string $frequency = 'times_per_week'): SchedulableGoal
    {
        return new SchedulableGoal(
            id: 1,
            title: 'Test',
            category: $category,
            frequencyType: $frequency,
            durationMinutes: $duration,
            targetMin: 1,
            targetMax: 1,
            preferredWindows: $windows,
            active: true,
        );
    }

    public function test_slots_never_overlap_work_hours_or_busy_events(): void
    {
        $goal = $this->goal('sport', 90, [['days' => 'weekday', 'after' => '16:00', 'before' => '20:00']]);
        $busy = [new BusyTime(CarbonImmutable::parse('2026-06-29 18:00'), CarbonImmutable::parse('2026-06-29 20:00'))];

        $slots = app(SlotFinder::class)->slots($goal, CarbonImmutable::parse('2026-06-29'), $busy);

        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertFalse($slot['start']->betweenIncluded('2026-06-29 09:00', '2026-06-29 16:59'));
            $this->assertFalse($slot['start']->lt($busy[0]->end) && $slot['end']->gt($busy[0]->start));
        }
    }

    public function test_default_windows_respect_categories(): void
    {
        $goal = $this->goal('date', 180, [], 'weekly');

        $slots = app(SlotFinder::class)->slots($goal, CarbonImmutable::parse('2026-06-29'), []);

        $this->assertNotEmpty($slots);
        $this->assertTrue(collect($slots)->every(fn ($slot): bool => $slot['start']->isWeekend() && $slot['start']->hour >= 18));
    }
}
