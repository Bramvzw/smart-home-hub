<?php

namespace Modules\Tasks\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskChecklistItem;
use Modules\Tasks\Models\TaskColumn;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_relationships(): void
    {
        $board = TaskBoard::query()->create(['name' => 'Tasks']);
        $column = $board->columns()->create(['name' => 'Todo']);
        $label = $board->labels()->create(['name' => 'home', 'color' => 'teal']);
        $task = $board->tasks()->create([
            'column_id' => $column->id,
            'title' => 'Replace task module',
            'priority' => 'high',
        ]);
        $task->labels()->attach($label);
        $task->checklistItems()->create(['text' => 'Add tests', 'completed' => true]);

        $this->assertInstanceOf(TaskBoard::class, $task->board);
        $this->assertInstanceOf(TaskColumn::class, $task->column);
        $this->assertEquals('home', $task->labels->first()->name);
        $this->assertInstanceOf(TaskChecklistItem::class, $task->checklistItems->first());
    }

    public function test_active_scope_excludes_archived_tasks(): void
    {
        $board = TaskBoard::query()->create(['name' => 'Tasks']);
        $column = $board->columns()->create(['name' => 'Todo']);

        $board->tasks()->create(['column_id' => $column->id, 'title' => 'Visible']);
        $board->tasks()->create([
            'column_id' => $column->id,
            'title' => 'Archived',
            'archived_at' => now(),
        ]);

        $this->assertEquals(['Visible'], KanbanTask::query()->active()->pluck('title')->all());
    }

    public function test_done_column_detection_is_case_insensitive(): void
    {
        $column = new TaskColumn(['name' => ' done ']);

        $this->assertTrue($column->isDoneColumn());
    }
}
