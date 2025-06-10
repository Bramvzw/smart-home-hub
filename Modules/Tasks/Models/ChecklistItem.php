<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    protected $fillable = [
        'task_checklist_id',
        'description',
        'is_completed',
        'order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Get the checklist that owns the item.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TaskChecklist::class, 'task_checklist_id');
    }

    /**
     * Toggle the completion status of the item.
     */
    public function toggleCompletion(): self
    {
        $this->is_completed = !$this->is_completed;
        $this->save();

        return $this;
    }
}
