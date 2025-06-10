<?php

namespace Modules\Tasks\Services;

use Modules\Tasks\Models\Lane;
use Modules\Tasks\Models\Task;

class TasksService
{
    /**
     * Create a new lane
     *
     * @param string $name
     * @return Lane
     */
    public function createLane(string $name): Lane
    {
        $maxOrder = Lane::max('order') ?? -1;

        return Lane::create([
            'name' => $name,
            'order' => $maxOrder + 1,
        ]);
    }

    /**
     * Update a lane
     *
     * @param int $id
     * @param string $name
     * @return Lane
     */
    public function updateLane(int $id, string $name): Lane
    {
        $lane = Lane::findOrFail($id);
        $lane->update(['name' => $name]);

        return $lane;
    }

    /**
     * Delete a lane
     *
     * @param int $id
     * @return void
     */
    public function deleteLane(int $id): void
    {
        $lane = Lane::findOrFail($id);
        $lane->delete();
    }

    /**
     * Create a new task
     *
     * @param string $title
     * @param string|null $description
     * @param string|null $label
     * @param int $laneId
     * @param string|null $due_date
     * @param string|null $priority
     * @param array|null $urls
     * @param bool $notify_before_expiry
     * @return Task
     */
    public function createTask(
        string $title,
        ?string $description = null,
        ?string $label = null,
        int $laneId,
        ?string $due_date = null,
        ?string $priority = null,
        ?array $urls = null,
        bool $notify_before_expiry = false
    ): Task
    {
        $lane = Lane::findOrFail($laneId);
        $maxOrder = $lane->tasks()->max('order') ?? -1;

        return Task::create([
            'title' => $title,
            'description' => $description,
            'label' => $label,
            'lane_id' => $laneId,
            'order' => $maxOrder + 1,
            'due_date' => $due_date,
            'priority' => $priority,
            'urls' => $urls,
            'notify_before_expiry' => $notify_before_expiry,
        ]);
    }

    /**
     * Update a task
     *
     * @param int $id
     * @param string $title
     * @param string|null $description
     * @param string|null $label
     * @param string|null $due_date
     * @param string|null $priority
     * @param array|null $urls
     * @param bool $notify_before_expiry
     * @return Task
     */
    public function updateTask(
        int $id,
        string $title,
        ?string $description = null,
        ?string $label = null,
        ?string $due_date = null,
        ?string $priority = null,
        ?array $urls = null,
        bool $notify_before_expiry = false
    ): Task
    {
        $task = Task::findOrFail($id);
        $task->update([
            'title' => $title,
            'description' => $description,
            'label' => $label,
            'due_date' => $due_date,
            'priority' => $priority,
            'urls' => $urls,
            'notify_before_expiry' => $notify_before_expiry,
        ]);

        return $task;
    }

    /**
     * Move a task to a different lane
     *
     * @param int $id
     * @param int $laneId
     * @param int $order
     * @return Task
     */
    public function moveTask(int $id, int $laneId, int $order): Task
    {
        $task = Task::findOrFail($id);
        $lane = Lane::findOrFail($laneId);

        // Update the order of tasks in the target lane
        if ($task->lane_id == $laneId) {
            // Moving within the same lane
            if ($task->order < $order) {
                // Moving down
                Task::where('lane_id', $laneId)
                    ->where('order', '>', $task->order)
                    ->where('order', '<=', $order)
                    ->decrement('order');
            } else {
                // Moving up
                Task::where('lane_id', $laneId)
                    ->where('order', '<', $task->order)
                    ->where('order', '>=', $order)
                    ->increment('order');
            }
        } else {
            // Moving to a different lane
            // Decrement order of tasks in the source lane
            Task::where('lane_id', $task->lane_id)
                ->where('order', '>', $task->order)
                ->decrement('order');

            // Increment order of tasks in the target lane
            Task::where('lane_id', $laneId)
                ->where('order', '>=', $order)
                ->increment('order');
        }

        // Update the task
        $task->update([
            'lane_id' => $laneId,
            'order' => $order,
        ]);

        return $task;
    }

    /**
     * Delete a task
     *
     * @param int $id
     * @return void
     */
    public function deleteTask(int $id): void
    {
        $task = Task::findOrFail($id);

        // Update the order of tasks in the lane
        Task::where('lane_id', $task->lane_id)
            ->where('order', '>', $task->order)
            ->decrement('order');

        $task->delete();
    }

    /**
     * Add an attachment to a task
     *
     * @param int $taskId
     * @param \Illuminate\Http\UploadedFile $file
     * @return TaskAttachment
     */
    public function addTaskAttachment(int $taskId, $file): TaskAttachment
    {
        $task = Task::findOrFail($taskId);

        $path = $file->store('task-attachments/' . $taskId, 'public');

        return TaskAttachment::create([
            'task_id' => $taskId,
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Delete a task attachment
     *
     * @param int $attachmentId
     * @return void
     */
    public function deleteTaskAttachment(int $attachmentId): void
    {
        $attachment = TaskAttachment::findOrFail($attachmentId);

        // Delete the file from storage
        \Storage::disk('public')->delete($attachment->path);

        $attachment->delete();
    }

    /**
     * Add a checklist to a task
     *
     * @param int $taskId
     * @param string $title
     * @return TaskChecklist
     */
    public function addTaskChecklist(int $taskId, string $title): TaskChecklist
    {
        $task = Task::findOrFail($taskId);
        $maxOrder = $task->checklists()->max('order') ?? -1;

        return TaskChecklist::create([
            'task_id' => $taskId,
            'title' => $title,
            'order' => $maxOrder + 1,
        ]);
    }

    /**
     * Update a task checklist
     *
     * @param int $checklistId
     * @param string $title
     * @return TaskChecklist
     */
    public function updateTaskChecklist(int $checklistId, string $title): TaskChecklist
    {
        $checklist = TaskChecklist::findOrFail($checklistId);
        $checklist->update(['title' => $title]);

        return $checklist;
    }

    /**
     * Delete a task checklist
     *
     * @param int $checklistId
     * @return void
     */
    public function deleteTaskChecklist(int $checklistId): void
    {
        $checklist = TaskChecklist::findOrFail($checklistId);

        // Update the order of checklists in the task
        TaskChecklist::where('task_id', $checklist->task_id)
            ->where('order', '>', $checklist->order)
            ->decrement('order');

        $checklist->delete();
    }

    /**
     * Add an item to a checklist
     *
     * @param int $checklistId
     * @param string $description
     * @return ChecklistItem
     */
    public function addChecklistItem(int $checklistId, string $description): ChecklistItem
    {
        $checklist = TaskChecklist::findOrFail($checklistId);
        $maxOrder = $checklist->items()->max('order') ?? -1;

        return ChecklistItem::create([
            'task_checklist_id' => $checklistId,
            'description' => $description,
            'is_completed' => false,
            'order' => $maxOrder + 1,
        ]);
    }

    /**
     * Update a checklist item
     *
     * @param int $itemId
     * @param string $description
     * @param bool|null $isCompleted
     * @return ChecklistItem
     */
    public function updateChecklistItem(int $itemId, string $description, ?bool $isCompleted = null): ChecklistItem
    {
        $item = ChecklistItem::findOrFail($itemId);

        $data = ['description' => $description];

        if ($isCompleted !== null) {
            $data['is_completed'] = $isCompleted;
        }

        $item->update($data);

        return $item;
    }

    /**
     * Toggle the completion status of a checklist item
     *
     * @param int $itemId
     * @return ChecklistItem
     */
    public function toggleChecklistItemCompletion(int $itemId): ChecklistItem
    {
        $item = ChecklistItem::findOrFail($itemId);
        return $item->toggleCompletion();
    }

    /**
     * Delete a checklist item
     *
     * @param int $itemId
     * @return void
     */
    public function deleteChecklistItem(int $itemId): void
    {
        $item = ChecklistItem::findOrFail($itemId);

        // Update the order of items in the checklist
        ChecklistItem::where('task_checklist_id', $item->task_checklist_id)
            ->where('order', '>', $item->order)
            ->decrement('order');

        $item->delete();
    }

    /**
     * Add a dependency between tasks
     *
     * @param int $taskId
     * @param int $dependsOnTaskId
     * @return void
     */
    public function addTaskDependency(int $taskId, int $dependsOnTaskId): void
    {
        $task = Task::findOrFail($taskId);
        $dependsOnTask = Task::findOrFail($dependsOnTaskId);

        // Prevent a task from depending on itself
        if ($taskId === $dependsOnTaskId) {
            throw new \InvalidArgumentException('A task cannot depend on itself');
        }

        // Check if the dependency already exists
        if ($task->dependencies()->where('depends_on_task_id', $dependsOnTaskId)->exists()) {
            return;
        }

        $task->dependencies()->attach($dependsOnTaskId);
    }

    /**
     * Remove a dependency between tasks
     *
     * @param int $taskId
     * @param int $dependsOnTaskId
     * @return void
     */
    public function removeTaskDependency(int $taskId, int $dependsOnTaskId): void
    {
        $task = Task::findOrFail($taskId);
        $task->dependencies()->detach($dependsOnTaskId);
    }

    /**
     * Get tasks that are about to expire
     *
     * @param int $days Days before due date to consider as "about to expire"
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksAboutToExpire(int $days = 2)
    {
        $date = now()->addDays($days)->startOfDay();

        return Task::whereNotNull('due_date')
            ->where('due_date', '<=', $date)
            ->where('due_date', '>=', now()->startOfDay())
            ->where('notify_before_expiry', true)
            ->get();
    }
}
