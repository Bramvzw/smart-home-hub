<?php

namespace Modules\Planner\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Services\SlotFinder;
use Tests\TestCase;

class SlotFinderTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_never_overlap_work_hours_or_busy_events(): void
    {
        $intention = PlannerIntention::query()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 1,
            'target_max' => 1,
            'preferred_windows' => [['days' => 'weekday', 'after' => '16:00', 'before' => '20:00']],
            'duration_minutes' => 90,
        ]);
        $busy = [new BusyTime(CarbonImmutable::parse('2026-06-29 18:00'), CarbonImmutable::parse('2026-06-29 20:00'))];

        $slots = app(SlotFinder::class)->slots($intention, CarbonImmutable::parse('2026-06-29'), $busy);

        $this->assertNotEmpty($slots);
        foreach ($slots as $slot) {
            $this->assertFalse($slot['start']->betweenIncluded('2026-06-29 09:00', '2026-06-29 16:59'));
            $this->assertFalse($slot['start']->lt($busy[0]->end) && $slot['end']->gt($busy[0]->start));
        }
    }

    public function test_default_windows_respect_categories(): void
    {
        $date = PlannerIntention::query()->create([
            'title' => 'Date night',
            'category' => 'date',
            'frequency_type' => 'weekly',
            'target_min' => 1,
            'target_max' => 1,
            'duration_minutes' => 180,
        ]);

        $slots = app(SlotFinder::class)->slots($date, CarbonImmutable::parse('2026-06-29'), []);

        $this->assertNotEmpty($slots);
        $this->assertTrue(collect($slots)->every(fn ($slot): bool => $slot['start']->isWeekend() && $slot['start']->hour >= 18));
    }
}
