<?php

namespace Modules\Tasks\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Actions\Recurrences\MaterializeDueMaintenance;
use Modules\Tasks\Briefing\TasksBriefingSource;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskRecurrence;
use Tests\TestCase;

class TaskRecurrenceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-25 10:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_complete_habit_is_idempotent_and_can_be_undone(): void
    {
        $habit = $this->recurrence('habit', 'times_per_week', ['times' => 3]);

        $this->postJson(route('tasks.habits.complete', $habit), ['date' => '2026-06-25'])
            ->assertOk()
            ->assertJsonPath('habit.completed_today', true)
            ->assertJsonPath('habit.progress.completed', 1);
        $this->postJson(route('tasks.habits.complete', $habit), ['date' => '2026-06-25'])
            ->assertOk()
            ->assertJsonPath('habit.progress.completed', 1);

        $this->assertDatabaseCount('task_recurrence_completions', 1);

        $this->deleteJson(route('tasks.habits.complete.destroy', $habit), ['date' => '2026-06-25'])
            ->assertOk()
            ->assertJsonPath('removed', true)
            ->assertJsonPath('habit.completed_today', false)
            ->assertJsonPath('habit.progress.completed', 0);

        $this->assertDatabaseCount('task_recurrence_completions', 0);
    }

    public function test_habits_endpoint_returns_contract_with_progress_and_streaks(): void
    {
        $habit = $this->recurrence('habit', 'weekly', title: 'Plan week');
        $habit->completions()->create([
            'completed_on' => '2026-06-25',
            'period_key' => '2026-W26',
        ]);

        $this->getJson(route('tasks.habits.index', ['date' => '2026-06-25']))
            ->assertOk()
            ->assertJsonPath('date', '2026-06-25')
            ->assertJsonPath('habits.0.id', $habit->id)
            ->assertJsonPath('habits.0.title', 'Plan week')
            ->assertJsonPath('habits.0.completed_today', true)
            ->assertJsonPath('habits.0.progress.period_key', '2026-W26')
            ->assertJsonStructure([
                'habits' => [
                    [
                        'id',
                        'type',
                        'title',
                        'cadence_type',
                        'cadence_config',
                        'progress' => ['period_key', 'completed', 'target', 'is_complete', 'percentage'],
                        'current_streak',
                        'best_streak',
                        'completed_today',
                    ],
                ],
            ]);
    }

    public function test_due_maintenance_is_materialized_once_and_notifies(): void
    {
        $notifier = new FakeTaskRecurrenceNotifier;
        $this->app->instance(HubNotifier::class, $notifier);
        $maintenance = $this->recurrence('maintenance', 'interval', [
            'interval' => 1,
            'unit' => 'months',
        ], 'Replace filter', [
            'next_due_on' => '2026-06-25',
            'notify' => true,
        ]);

        $first = app(MaterializeDueMaintenance::class)(CarbonImmutable::parse('2026-06-25'));
        $second = app(MaterializeDueMaintenance::class)(CarbonImmutable::parse('2026-06-25'));

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertCount(1, $notifier->sent);
        $this->assertDatabaseCount('kanban_tasks', 1);
        $this->assertDatabaseHas('kanban_tasks', [
            'recurrence_id' => $maintenance->id,
            'title' => 'Replace filter',
            'due_date' => '2026-06-25 00:00:00',
        ]);
        $this->assertSame('2026-06-25', $maintenance->fresh()->last_materialized_on->toDateString());
    }

    public function test_completing_maintenance_card_reschedules_interval_through_move(): void
    {
        $board = $this->board();
        $todo = $board->columns()->where('name', 'Todo')->firstOrFail();
        $done = $board->columns()->where('name', 'Done')->firstOrFail();
        $maintenance = $this->recurrence('maintenance', 'interval', [
            'interval' => 2,
            'unit' => 'weeks',
        ], 'Clean fan', [
            'board_id' => $board->id,
            'next_due_on' => '2026-06-25',
            'last_materialized_on' => '2026-06-25',
        ]);
        $task = $board->tasks()->create([
            'column_id' => $todo->id,
            'recurrence_id' => $maintenance->id,
            'title' => 'Clean fan',
            'priority' => 'normal',
            'position' => 0,
        ]);

        $this->putJson(route('tasks.tasks.move', $task), [
            'column_id' => $done->id,
            'task_ids' => [$task->id],
        ])->assertOk();

        $maintenance = $maintenance->fresh();

        $this->assertSame('2026-07-09', $maintenance->next_due_on->toDateString());
        $this->assertNull($maintenance->last_materialized_on);
        $this->assertTrue($task->fresh()->completed);
        $this->assertDatabaseHas('task_recurrence_completions', [
            'recurrence_id' => $maintenance->id,
            'period_key' => '2026-06-25',
        ]);
    }

    public function test_tasks_briefing_source_includes_top_tasks_and_today_habits(): void
    {
        $board = $this->board();
        $task = $this->task($board, 'Pay invoice');
        $habit = $this->recurrence('habit', 'weekly', title: 'Review planning');
        $habit->completions()->create([
            'completed_on' => '2026-06-25',
            'period_key' => '2026-W26',
        ]);

        $section = app(TasksBriefingSource::class)->contribute(CarbonImmutable::parse('2026-06-25'));

        $this->assertNotNull($section);
        $this->assertStringContainsString('Pay invoice', $section->summary);
        $this->assertStringContainsString('Review planning 1/1', $section->summary);
        $this->assertSame($task->title, $section->data['tasks'][0]['title']);
        $this->assertSame('Review planning', $section->data['habits'][0]['title']);
        $this->assertTrue($section->data['habits'][0]['completed_today']);
    }

    public function test_habits_page_renders_html_with_habits_and_maintenance(): void
    {
        $this->recurrence('habit', 'times_per_week', ['times' => 3], 'Sporten');
        $this->recurrence('maintenance', 'interval', ['interval' => 6, 'unit' => 'months'], 'Rookmelders testen', [
            'next_due_on' => '2026-09-25',
        ]);

        $this->get(route('tasks.habits.index'))
            ->assertOk()
            ->assertSee('Habits')
            ->assertSee('Sporten')
            ->assertSee('3× per week')
            ->assertSee('Rookmelders testen')
            ->assertSee('every 6 months');
    }

    public function test_complete_maintenance_endpoint_reschedules_and_clears_board_card(): void
    {
        $board = $this->board();
        $todo = $board->columns()->where('name', 'Todo')->firstOrFail();
        $maintenance = $this->recurrence('maintenance', 'interval', [
            'interval' => 6,
            'unit' => 'months',
        ], 'Rookmelders testen', [
            'board_id' => $board->id,
            'next_due_on' => '2026-06-20',
            'last_materialized_on' => '2026-06-20',
        ]);
        $task = $board->tasks()->create([
            'column_id' => $todo->id,
            'recurrence_id' => $maintenance->id,
            'title' => 'Rookmelders testen',
            'priority' => 'normal',
            'position' => 0,
        ]);

        $this->postJson(route('tasks.maintenance.complete', $maintenance), ['date' => '2026-06-25'])
            ->assertOk()
            ->assertJsonPath('recurrence.id', $maintenance->id);

        $maintenance = $maintenance->fresh();

        $this->assertSame('2026-12-25', $maintenance->next_due_on->toDateString());
        $this->assertNull($maintenance->last_materialized_on);
        $this->assertDatabaseHas('task_recurrence_completions', [
            'recurrence_id' => $maintenance->id,
            'period_key' => '2026-06-25',
        ]);
        $this->assertNotNull($task->fresh()->archived_at);
    }

    public function test_complete_maintenance_rejects_a_habit(): void
    {
        $habit = $this->recurrence('habit', 'times_per_week', ['times' => 3]);

        $this->postJson(route('tasks.maintenance.complete', $habit), ['date' => '2026-06-25'])
            ->assertStatus(422);
    }

    private function recurrence(
        string $type,
        string $cadenceType,
        array $cadenceConfig = [],
        string $title = 'Recurring task',
        array $overrides = [],
    ): TaskRecurrence {
        return TaskRecurrence::query()->create(array_merge([
            'type' => $type,
            'title' => $title,
            'description' => null,
            'cadence_type' => $cadenceType,
            'cadence_config' => $cadenceConfig,
            'notify' => false,
            'active' => true,
        ], $overrides));
    }

    private function board(string $name = 'Tasks'): TaskBoard
    {
        $board = TaskBoard::query()->create(['name' => $name]);

        foreach (['Todo', 'Doing', 'Done'] as $position => $column) {
            $board->columns()->create(['name' => $column, 'position' => $position]);
        }

        return $board;
    }

    private function task(TaskBoard $board, string $title): KanbanTask
    {
        $column = $board->columns()->where('name', 'Todo')->firstOrFail();

        return $board->tasks()->create([
            'column_id' => $column->id,
            'title' => $title,
            'priority' => 'normal',
            'position' => 0,
        ]);
    }
}

class FakeTaskRecurrenceNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '', 10);
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}
