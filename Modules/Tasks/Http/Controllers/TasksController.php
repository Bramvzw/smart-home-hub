<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tasks\Models\Lane;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TasksService;

class TasksController extends Controller
{
    protected TasksService $tasksService;

    public function __construct(TasksService $tasksService)
    {
        $this->tasksService = $tasksService;
    }

    /**
     * Display the Tasks interface
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $lanes = Lane::with('tasks')->orderBy('order')->get();

        return view('tasks::index', [
            'lanes' => $lanes,
        ]);
    }

    /**
     * Display tasks notifications overview.
     */
    public function notifications()
    {
        $tasksAboutToExpire = $this->tasksService->getTasksAboutToExpire();
        $overdueTasks = Task::whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        return view('tasks::notifications', [
            'tasksAboutToExpire' => $tasksAboutToExpire,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    /**
     * Get all unique task labels.
     */
    public function labels()
    {
        $labels = Task::query()
            ->whereNotNull('label')
            ->distinct()
            ->orderBy('label')
            ->pluck('label');

        return response()->json(['success' => true, 'labels' => $labels]);
    }

    /**
     * Create a new lane
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createLane(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $lane = $this->tasksService->createLane($validated['name']);

        return response()->json(['success' => true, 'lane' => $lane]);
    }

    /**
     * Update a lane
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLane(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $lane = $this->tasksService->updateLane($id, $validated['name']);

        return response()->json(['success' => true, 'lane' => $lane]);
    }

    /**
     * Delete a lane
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLane($id)
    {
        $this->tasksService->deleteLane($id);

        return response()->json(['success' => true]);
    }

    /**
     * Create a new task
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'lane_id' => 'required|exists:lanes,id',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|string|in:low,medium,high',
            'urls' => 'nullable|array',
            'urls.*' => 'url',
            'notify_before_expiry' => 'nullable|boolean',
        ]);

        $task = $this->tasksService->createTask(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['label'] ?? null,
            $validated['lane_id'],
            $validated['due_date'] ?? null,
            $validated['priority'] ?? null,
            $validated['urls'] ?? null,
            $validated['notify_before_expiry'] ?? false
        );

        return response()->json(['success' => true, 'task' => $task]);
    }

    /**
     * Update a task
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|string|in:low,medium,high',
            'urls' => 'nullable|array',
            'urls.*' => 'url',
            'notify_before_expiry' => 'nullable|boolean',
        ]);

        $task = $this->tasksService->updateTask(
            $id,
            $validated['title'],
            $validated['description'] ?? null,
            $validated['label'] ?? null,
            $validated['due_date'] ?? null,
            $validated['priority'] ?? null,
            $validated['urls'] ?? null,
            $validated['notify_before_expiry'] ?? false
        );

        return response()->json(['success' => true, 'task' => $task]);
    }

    /**
     * Move a task to a different lane
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveTask(Request $request, $id)
    {
        $validated = $request->validate([
            'lane_id' => 'required|exists:lanes,id',
            'order' => 'required|integer|min:0',
        ]);

        $task = $this->tasksService->moveTask(
            $id,
            $validated['lane_id'],
            $validated['order']
        );

        return response()->json(['success' => true, 'task' => $task]);
    }

    /**
     * Delete a task
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTask($id)
    {
        $this->tasksService->deleteTask($id);

        return response()->json(['success' => true]);
    }

    /**
     * Upload an attachment to a task
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTaskAttachment(Request $request, $id)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $attachment = $this->tasksService->addTaskAttachment($id, $request->file('file'));

        return response()->json(['success' => true, 'attachment' => $attachment]);
    }

    /**
     * Delete a task attachment
     *
     * @param int $taskId
     * @param int $attachmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTaskAttachment($taskId, $attachmentId)
    {
        $this->tasksService->deleteTaskAttachment($attachmentId);

        return response()->json(['success' => true]);
    }

    /**
     * Add a checklist to a task
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskChecklist(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist = $this->tasksService->addTaskChecklist($id, $validated['title']);

        return response()->json(['success' => true, 'checklist' => $checklist]);
    }

    /**
     * Update a task checklist
     *
     * @param Request $request
     * @param int $taskId
     * @param int $checklistId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTaskChecklist(Request $request, $taskId, $checklistId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist = $this->tasksService->updateTaskChecklist($checklistId, $validated['title']);

        return response()->json(['success' => true, 'checklist' => $checklist]);
    }

    /**
     * Delete a task checklist
     *
     * @param int $taskId
     * @param int $checklistId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTaskChecklist($taskId, $checklistId)
    {
        $this->tasksService->deleteTaskChecklist($checklistId);

        return response()->json(['success' => true]);
    }

    /**
     * Add an item to a checklist
     *
     * @param Request $request
     * @param int $taskId
     * @param int $checklistId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addChecklistItem(Request $request, $taskId, $checklistId)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $item = $this->tasksService->addChecklistItem($checklistId, $validated['description']);

        return response()->json(['success' => true, 'item' => $item]);
    }

    /**
     * Update a checklist item
     *
     * @param Request $request
     * @param int $taskId
     * @param int $checklistId
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateChecklistItem(Request $request, $taskId, $checklistId, $itemId)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'is_completed' => 'nullable|boolean',
        ]);

        $item = $this->tasksService->updateChecklistItem(
            $itemId,
            $validated['description'],
            $validated['is_completed'] ?? null
        );

        return response()->json(['success' => true, 'item' => $item]);
    }

    /**
     * Toggle the completion status of a checklist item
     *
     * @param int $taskId
     * @param int $checklistId
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleChecklistItemCompletion($taskId, $checklistId, $itemId)
    {
        $item = $this->tasksService->toggleChecklistItemCompletion($itemId);

        return response()->json(['success' => true, 'item' => $item]);
    }

    /**
     * Delete a checklist item
     *
     * @param int $taskId
     * @param int $checklistId
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteChecklistItem($taskId, $checklistId, $itemId)
    {
        $this->tasksService->deleteChecklistItem($itemId);

        return response()->json(['success' => true]);
    }

    /**
     * Add a dependency between tasks
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskDependency(Request $request, $id)
    {
        $validated = $request->validate([
            'depends_on_task_id' => 'required|exists:tasks,id|different:' . $id,
        ]);

        $this->tasksService->addTaskDependency($id, $validated['depends_on_task_id']);

        return response()->json(['success' => true]);
    }

    /**
     * Remove a dependency between tasks
     *
     * @param int $id
     * @param int $dependsOnTaskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeTaskDependency($id, $dependsOnTaskId)
    {
        $this->tasksService->removeTaskDependency($id, $dependsOnTaskId);

        return response()->json(['success' => true]);
    }

    /**
     * Search and filter tasks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchTasks(Request $request)
    {
        $query = Task::query();

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Filter by due date
        if ($request->has('due_date_from')) {
            $query->where('due_date', '>=', $request->input('due_date_from'));
        }

        if ($request->has('due_date_to')) {
            $query->where('due_date', '<=', $request->input('due_date_to'));
        }

        // Filter by label
        if ($request->has('label')) {
            $query->where('label', $request->input('label'));
        }

        // Filter by lane
        if ($request->has('lane_id')) {
            $query->where('lane_id', $request->input('lane_id'));
        }

        $tasks = $query->get();

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }
}
