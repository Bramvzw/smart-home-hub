<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskChecklist extends Model
{
    protected $fillable = [
        'task_id',
        'title',
        'order',
    ];

    /**
     * Get the task that owns the checklist.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the items for the checklist.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'task_checklist_id')->orderBy('order');
    }

    /**
     * Get the completion percentage of the checklist.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return 0;
        }

        $completedCount = $items->where('is_completed', true)->count();
        return (int) round(($completedCount / $items->count()) * 100);
    }
}
