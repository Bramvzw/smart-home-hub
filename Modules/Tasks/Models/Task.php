<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'label',
        'due_date',
        'priority',
        'urls',
        'notify_before_expiry',
        'order',
        'lane_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'urls' => 'array',
        'notify_before_expiry' => 'boolean',
    ];

    /**
     * Get the lane that owns the task.
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class);
    }

    /**
     * Get the attachments for the task.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Get the checklists for the task.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class);
    }

    /**
     * Get the tasks that this task depends on.
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    /**
     * Get the tasks that depend on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    /**
     * Check if the task is about to expire.
     *
     * @param int $days Days before due date to consider as "about to expire"
     * @return bool
     */
    public function isAboutToExpire(int $days = 2): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->startOfDay()->diffInDays(now()->startOfDay()) <= $days
            && $this->due_date->startOfDay() >= now()->startOfDay();
    }

    /**
     * Check if the task is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->startOfDay() < now()->startOfDay();
    }
}
