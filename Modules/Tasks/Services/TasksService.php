<?php

namespace Modules\Tasks\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Tasks\Events\LaneCreated;
use Modules\Tasks\Events\LaneDeleted;
use Modules\Tasks\Events\LaneUpdated;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Events\TaskDeleted;
use Modules\Tasks\Events\TaskMoved;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Models\Lane;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskAttachment;

class TasksService
{
    public function createLane(array $data): Lane
    {
        $data['position'] = (Lane::max('position') ?? -1) + 1;
        $lane = Lane::create($data);
        event(new LaneCreated($lane));
        return $lane;
    }

    public function updateLane(Lane $lane, array $data): Lane
    {
        $lane->update($data);
        event(new LaneUpdated($lane));
        return $lane;
    }

    public function deleteLane(Lane $lane): void
    {
        $lane->delete();
        event(new LaneDeleted($lane));
    }

    public function createTask(array $data): Task
    {
        $data['order'] = (Task::where('lane_id', $data['lane_id'])->max('order') ?? -1) + 1;
        $task = Task::create($data);
        event(new TaskCreated($task));
        return $task->load('attachments');
    }

    public function updateTask(Task $task, array $data): Task
    {
        $task->update($data);
        event(new TaskUpdated($task));
        return $task->fresh()->load('attachments');
    }

    public function moveTask(Task $task, int $laneId, int $order): Task
    {
        $task->update(['lane_id' => $laneId, 'order' => $order]);
        event(new TaskMoved($task));
        return $task->fresh();
    }

    public function deleteTask(Task $task): void
    {
        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }
        $task->delete();
        event(new TaskDeleted($task));
    }

    public function searchTasks(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = Task::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['label'])) {
            $query->where('label', $filters['label']);
        }

        if (!empty($filters['lane_id'])) {
            $query->where('lane_id', $filters['lane_id']);
        }

        return $query->orderBy('order')->get();
    }

    public function addAttachment(Task $task, UploadedFile $file): TaskAttachment
    {
        $path = $file->store('task-attachments/' . $task->id, 'public');

        return TaskAttachment::create([
            'task_id' => $task->id,
            'filename' => basename($path),
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'path' => $path,
            'size' => $file->getSize(),
        ]);
    }

    public function removeAttachment(TaskAttachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();
    }
}
