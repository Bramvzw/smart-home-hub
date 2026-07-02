<?php

namespace Modules\Tasks\Tests\Unit;

use App\Contracts\SchedulableGoals;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\TaskRecurrence;
use Tests\TestCase;

class RecurrenceGoalsTest extends TestCase
{
    use RefreshDatabase;

    private function goals(): SchedulableGoals
    {
        return app(SchedulableGoals::class);
    }

    public function test_create_maps_attributes_and_target_range(): void
    {
        $goal = $this->goals()->create([
            'title' => 'Sporten',
            'category' => 'sport',
            'frequency_type' => 'times_per_week',
            'target_min' => 3,
            'target_max' => 4,
            'duration_minutes' => 90,
            'plannable' => true,
        ]);

        $this->assertSame('Sporten', $goal->title);
        $this->assertSame('sport', $goal->category);
        $this->assertSame('times_per_week', $goal->frequencyType);
        $this->assertSame(90, $goal->durationMinutes);
        $this->assertSame(3, $goal->targetMin);
        $this->assertSame(4, $goal->targetMax);
        $this->assertTrue($goal->active);

        // Backed by a habit TaskRecurrence with the streak target at the commitment floor.
        $recurrence = TaskRecurrence::query()->habits()->findOrFail($goal->id);
        $this->assertTrue($recurrence->plannable);
        $this->assertSame(3, $recurrence->cadence_config['times']);
        $this->assertSame('sport', $recurrence->cadence_config['category']);
    }

    public function test_plannable_only_returns_active_plannable_habits(): void
    {
        $this->goals()->create(['title' => 'Sporten', 'category' => 'sport', 'frequency_type' => 'times_per_week', 'target_min' => 2, 'target_max' => 2, 'plannable' => true]);

        // A plain habit (not plannable) must not surface to the planner.
        TaskRecurrence::query()->create([
            'type' => 'habit',
            'title' => 'Water drinken',
            'cadence_type' => 'times_per_week',
            'cadence_config' => ['times' => 7],
            'active' => true,
            'plannable' => false,
        ]);

        $plannable = $this->goals()->plannable();

        $this->assertCount(1, $plannable);
        $this->assertSame('Sporten', $plannable[0]->title);
    }

    public function test_set_active_and_delete(): void
    {
        $goal = $this->goals()->create(['title' => 'Lezen', 'category' => 'custom', 'frequency_type' => 'weekly', 'plannable' => true]);

        $this->assertFalse($this->goals()->setActive($goal->id, false)->active);
        $this->assertCount(0, $this->goals()->plannable());

        $this->goals()->delete($goal->id);
        $this->assertNull($this->goals()->find($goal->id));
    }

    public function test_cards_expose_tracking_and_complete_undo_toggle_streak(): void
    {
        $goal = $this->goals()->create(['title' => 'Hardlopen', 'category' => 'sport', 'frequency_type' => 'times_per_week', 'target_min' => 3, 'target_max' => 3, 'plannable' => true]);
        $date = CarbonImmutable::parse('2026-06-25');

        $cards = $this->goals()->cards($date);
        $this->assertCount(1, $cards);
        $this->assertSame('Hardlopen', $cards[0]->title);
        $this->assertFalse($cards[0]->completedToday);
        $this->assertSame(0, $cards[0]->done);

        $completed = $this->goals()->complete($goal->id, $date);
        $this->assertTrue($completed->completedToday);
        $this->assertSame(1, $completed->done);

        $undone = $this->goals()->undo($goal->id, $date);
        $this->assertFalse($undone->completedToday);
        $this->assertSame(0, $undone->done);
    }
}
