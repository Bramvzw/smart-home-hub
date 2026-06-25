<?php

namespace Modules\Tasks\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\StreakCalculator;
use Tests\TestCase;

class StreakCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_times_per_week_progress_and_current_streak(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 10:00:00', 'UTC'));
        $calculator = app(StreakCalculator::class);
        $habit = $this->habit('times_per_week', ['times' => 3]);

        foreach (['2026-06-08', '2026-06-09', '2026-06-10', '2026-06-15', '2026-06-16', '2026-06-17', '2026-06-23', '2026-06-24'] as $date) {
            $this->complete($habit, $date);
        }

        $progress = $calculator->progress($habit, CarbonImmutable::parse('2026-06-25'));

        $this->assertSame(2, $progress->completed);
        $this->assertSame(3, $progress->target);
        $this->assertSame(2, $calculator->currentStreak($habit));
        $this->assertSame(2, $calculator->bestStreak($habit));

        $this->complete($habit, '2026-06-25');

        $this->assertSame(3, $calculator->currentStreak($habit));
        $this->assertSame(3, $calculator->bestStreak($habit));
    }

    public function test_weekdays_streak_uses_scheduled_days_only(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-26 10:00:00', 'UTC'));
        $calculator = app(StreakCalculator::class);
        $habit = $this->habit('weekdays', ['weekdays' => [1, 3, 5]]);

        $this->complete($habit, '2026-06-22');
        $this->complete($habit, '2026-06-24');

        $progress = $calculator->progress($habit, CarbonImmutable::parse('2026-06-26'));

        $this->assertSame(2, $progress->completed);
        $this->assertSame(3, $progress->target);
        $this->assertSame(2, $calculator->currentStreak($habit));
        $this->assertSame(2, $calculator->bestStreak($habit));

        $this->complete($habit, '2026-06-26');

        $this->assertSame(3, $calculator->currentStreak($habit));
        $this->assertSame(3, $calculator->bestStreak($habit));
    }

    public function test_weekly_and_monthly_progress_and_best_streaks(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 10:00:00', 'UTC'));
        $calculator = app(StreakCalculator::class);
        $weekly = $this->habit('weekly');
        $monthly = $this->habit('monthly');

        foreach (['2026-06-01', '2026-06-08', '2026-06-25'] as $date) {
            $this->complete($weekly, $date);
        }

        $weeklyProgress = $calculator->progress($weekly, CarbonImmutable::parse('2026-06-25'));

        $this->assertSame('2026-W26', $weeklyProgress->periodKey);
        $this->assertTrue($weeklyProgress->isComplete());
        $this->assertSame(2, $calculator->bestStreak($weekly));

        $this->complete($monthly, '2026-04-10');
        $this->complete($monthly, '2026-05-10');

        $this->assertSame(2, $calculator->currentStreak($monthly));
        $this->assertSame(2, $calculator->bestStreak($monthly));

        $this->complete($monthly, '2026-06-10');

        $this->assertSame(3, $calculator->currentStreak($monthly));
        $this->assertSame(3, $calculator->bestStreak($monthly));
    }

    private function habit(string $cadenceType, array $cadenceConfig = []): TaskRecurrence
    {
        return TaskRecurrence::query()->create([
            'type' => 'habit',
            'title' => 'Habit '.$cadenceType,
            'cadence_type' => $cadenceType,
            'cadence_config' => $cadenceConfig,
            'notify' => false,
            'active' => true,
        ]);
    }

    private function complete(TaskRecurrence $habit, string $date): void
    {
        $date = CarbonImmutable::parse($date);
        $calculator = app(StreakCalculator::class);

        $habit->completions()->firstOrCreate(
            ['period_key' => $calculator->completionPeriodKey($habit, $date)],
            ['completed_on' => $date->toDateString()]
        );
    }
}
