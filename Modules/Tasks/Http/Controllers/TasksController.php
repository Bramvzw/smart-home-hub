<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tasks\Models\Lane;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;
use Modules\Tasks\Services\TasksService;

class TasksController
{
    public function __construct(protected TasksService $service) {}

    public function index(): View
    {
        $lanes = Lane::with('tasks.attachments')->orderBy('position')->get();
        return view('tasks::index', compact('lanes'));
    }

    public function storeLane(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $lane = $this->service->createLane($data);
        return response()->json(['success' => true, 'lane' => $lane]);
    }

    public function updateLane(Request $request, Lane $lane): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $lane = $this->service->updateLane($lane, $data);
        return response()->json(['success' => true, 'lane' => $lane]);
    }

    public function destroyLane(Lane $lane): JsonResponse
    {
        $this->service->deleteLane($lane);
        return response()->json(['success' => true]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lane_id' => 'required|exists:lanes,id',
            'label' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            'notify_before_expiry' => 'nullable|boolean',
        ]);
        $task = $this->service->createTask($data);
        return response()->json(['success' => true, 'task' => $task]);
    }

    public function updateTask(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            'notify_before_expiry' => 'nullable|boolean',
        ]);
        $task = $this->service->updateTask($task, $data);
        return response()->json(['success' => true, 'task' => $task]);
    }

    public function moveTask(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'lane_id' => 'required|exists:lanes,id',
            'order' => 'required|integer|min:0',
        ]);
        $task = $this->service->moveTask($task, $data['lane_id'], $data['order']);
        return response()->json(['success' => true, 'task' => $task]);
    }

    public function destroyTask(Task $task): JsonResponse
    {
        $this->service->deleteTask($task);
        return response()->json(['success' => true]);
    }

    public function storeAttachment(Request $request, Task $task): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $attachment = $this->service->addAttachment($task, $request->file('file'));
        return response()->json(['success' => true, 'attachment' => $attachment]);
    }

    public function destroyAttachment(Task $task, TaskAttachment $attachment): JsonResponse
    {
        $this->service->removeAttachment($attachment);
        return response()->json(['success' => true]);
    }

    public function search(Request $request): JsonResponse
    {
        $tasks = $this->service->searchTasks($request->only(['search', 'priority', 'label', 'lane_id']));
        return response()->json(['success' => true, 'tasks' => $tasks]);
    }
}
