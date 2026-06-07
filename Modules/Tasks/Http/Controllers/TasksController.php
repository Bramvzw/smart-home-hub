<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tasks\Actions\Boards\CreateBoard;
use Modules\Tasks\Actions\Boards\DeleteBoard;
use Modules\Tasks\Actions\Boards\EnsureDefaultBoard;
use Modules\Tasks\Actions\Columns\CreateColumn;
use Modules\Tasks\Actions\Columns\DeleteColumn;
use Modules\Tasks\Actions\Columns\ResequenceColumns;
use Modules\Tasks\Actions\Columns\UpdateColumn;
use Modules\Tasks\Actions\Tasks\ArchiveTask;
use Modules\Tasks\Actions\Tasks\CreateTask;
use Modules\Tasks\Actions\Tasks\DeleteTask;
use Modules\Tasks\Actions\Tasks\MoveTask;
use Modules\Tasks\Actions\Tasks\UpdateTask;
use Modules\Tasks\Http\Requests\MoveTaskRequest;
use Modules\Tasks\Http\Requests\ReorderColumnsRequest;
use Modules\Tasks\Http\Requests\StoreBoardRequest;
use Modules\Tasks\Http\Requests\StoreColumnRequest;
use Modules\Tasks\Http\Requests\StoreTaskRequest;
use Modules\Tasks\Http\Requests\UpdateTaskRequest;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskColumn;
use Modules\Tasks\View\ViewModels\TasksBoardViewModel;

class TasksController
{
    public function __construct(
        private readonly TasksBoardViewModel $viewModel,
    ) {
    }

    public function index(Request $request, EnsureDefaultBoard $ensureDefaultBoard): View
    {
        $board = TaskBoard::query()->find($request->integer('board')) ?? $ensureDefaultBoard();

        return view('tasks::index', [
            'state' => $this->viewModel->state($board),
        ]);
    }

    public function storeBoard(StoreBoardRequest $request, CreateBoard $createBoard): JsonResponse
    {
        return $this->stateResponse($createBoard($request->validated('name')));
    }

    public function updateBoard(StoreBoardRequest $request, TaskBoard $board): JsonResponse
    {
        $board->update($request->validated());

        return $this->stateResponse($board);
    }

    public function destroyBoard(TaskBoard $board, DeleteBoard $deleteBoard): JsonResponse
    {
        return $this->stateResponse($deleteBoard($board));
    }

    public function storeColumn(StoreColumnRequest $request, TaskBoard $board, CreateColumn $createColumn): JsonResponse
    {
        $column = $createColumn($board, $request->validated('name'));

        return $this->stateResponse($column->board);
    }

    public function updateColumn(StoreColumnRequest $request, TaskColumn $column, UpdateColumn $updateColumn): JsonResponse
    {
        $column = $updateColumn($column, $request->validated('name'));

        return $this->stateResponse($column->board);
    }

    public function destroyColumn(TaskColumn $column, DeleteColumn $deleteColumn): JsonResponse
    {
        return $this->stateResponse($deleteColumn($column));
    }

    public function reorderColumns(ReorderColumnsRequest $request, TaskBoard $board, ResequenceColumns $resequenceColumns): JsonResponse
    {
        $resequenceColumns($board, $request->validated('column_ids'));

        return $this->stateResponse($board);
    }

    public function storeTask(StoreTaskRequest $request, TaskBoard $board, CreateTask $createTask): JsonResponse
    {
        $task = $createTask($board, $request->integer('column_id'), $request->validated('title'));

        return $this->stateResponse($board, $task);
    }

    public function updateTask(UpdateTaskRequest $request, KanbanTask $task, UpdateTask $updateTask): JsonResponse
    {
        $task = $updateTask($task, $request->validated());

        return $this->stateResponse($task->board, $task);
    }

    public function moveTask(MoveTaskRequest $request, KanbanTask $task, MoveTask $moveTask): JsonResponse
    {
        $task = $moveTask($task, $request->integer('column_id'), $request->validated('task_ids'));

        return $this->stateResponse($task->board, $task);
    }

    public function archiveTask(KanbanTask $task, ArchiveTask $archiveTask): JsonResponse
    {
        $task = $archiveTask($task);

        return $this->stateResponse($task->board, $task);
    }

    public function destroyTask(KanbanTask $task, DeleteTask $deleteTask): JsonResponse
    {
        return $this->stateResponse($deleteTask($task));
    }

    private function stateResponse(TaskBoard $board, ?KanbanTask $task = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'state' => $this->viewModel->state($board),
            'selected_task_id' => $task?->id,
        ]);
    }
}
