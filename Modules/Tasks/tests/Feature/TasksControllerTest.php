<?php

namespace Modules\Tasks\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;
use Tests\TestCase;

class TasksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_creates_the_default_board(): void
    {
        $response = $this->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks::index');
        $this->assertDatabaseHas('task_boards', ['name' => 'Tasks']);
        $this->assertDatabaseHas('task_columns', ['name' => 'Todo']);
        $this->assertDatabaseHas('task_columns', ['name' => 'Doing']);
        $this->assertDatabaseHas('task_columns', ['name' => 'Done']);
    }

    public function test_can_create_a_board_with_default_columns(): void
    {
        $response = $this->postJson(route('tasks.boards.store'), ['name' => 'Home']);

        $response->assertOk()->assertJson(['success' => true]);
        $board = TaskBoard::query()->where('name', 'Home')->firstOrFail();

        $this->assertEquals(['Todo', 'Doing', 'Done'], $board->columns()->pluck('name')->all());
    }

    public function test_can_create_column_and_task(): void
    {
        $board = $this->board();

        $columnResponse = $this->postJson(route('tasks.columns.store', $board), ['name' => 'Later']);
        $columnResponse->assertOk();

        $column = $board->columns()->where('name', 'Later')->firstOrFail();
        $taskResponse = $this->postJson(route('tasks.tasks.store', $board), [
            'column_id' => $column->id,
            'title' => 'Clean dashboard tasks',
        ]);

        $taskResponse->assertOk();
        $this->assertDatabaseHas('kanban_tasks', [
            'board_id' => $board->id,
            'column_id' => $column->id,
            'title' => 'Clean dashboard tasks',
            'priority' => 'normal',
        ]);
    }

    public function test_labels_are_scoped_per_board(): void
    {
        $firstBoard = $this->board('Personal');
        $secondBoard = $this->board('House');
        $task = $this->task($firstBoard);

        $this->putJson(route('tasks.tasks.update', $task), [
            'title' => $task->title,
            'labels' => [
                ['name' => 'urgent', 'color' => 'red'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('task_labels', [
            'board_id' => $firstBoard->id,
            'name' => 'urgent',
        ]);
        $this->assertDatabaseMissing('task_labels', [
            'board_id' => $secondBoard->id,
            'name' => 'urgent',
        ]);
    }

    public function test_moving_to_done_marks_task_completed(): void
    {
        $board = $this->board();
        $task = $this->task($board);
        $done = $board->columns()->where('name', 'Done')->firstOrFail();

        $this->putJson(route('tasks.tasks.move', $task), [
            'column_id' => $done->id,
            'task_ids' => [$task->id],
        ])->assertOk();

        $this->assertTrue($task->fresh()->completed);
    }

    public function test_toggling_task_open_moves_it_out_of_done(): void
    {
        $board = $this->board();
        $done = $board->columns()->where('name', 'Done')->firstOrFail();
        $todo = $board->columns()->where('name', 'Todo')->firstOrFail();
        $task = $board->tasks()->create([
            'column_id' => $done->id,
            'title' => 'Done task',
            'priority' => 'normal',
            'completed' => true,
        ]);

        $this->putJson(route('tasks.tasks.update', $task), [
            'title' => $task->title,
            'completed' => false,
        ])->assertOk();

        $this->assertFalse($task->fresh()->completed);
        $this->assertSame($todo->id, $task->fresh()->column_id);
    }

    public function test_archive_and_delete_task(): void
    {
        $board = $this->board();
        $task = $this->task($board);

        $this->postJson(route('tasks.tasks.archive', $task))->assertOk();
        $this->assertNotNull($task->fresh()->archived_at);

        $this->deleteJson(route('tasks.tasks.destroy', $task))->assertOk();
        $this->assertDatabaseMissing('kanban_tasks', ['id' => $task->id]);
    }

    private function board(string $name = 'Tasks'): TaskBoard
    {
        $board = TaskBoard::query()->create(['name' => $name]);

        foreach (['Todo', 'Doing', 'Done'] as $position => $column) {
            $board->columns()->create(['name' => $column, 'position' => $position]);
        }

        return $board;
    }

    private function task(TaskBoard $board): KanbanTask
    {
        $column = $board->columns()->where('name', 'Todo')->firstOrFail();

        return $board->tasks()->create([
            'column_id' => $column->id,
            'title' => 'Test task',
            'priority' => 'normal',
            'position' => 0,
        ]);
    }
}
