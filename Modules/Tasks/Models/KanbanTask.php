<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanTask extends Model
{
    protected $table = 'kanban_tasks';

    protected $fillable = [
        'board_id',
        'column_id',
        'recurrence_id',
        'title',
        'description',
        'priority',
        'due_date',
        'completed',
        'archived_at',
        'position',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'due_date' => 'date',
        'archived_at' => 'datetime',
    ];

    /** @return BelongsTo<TaskBoard, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    /** @return BelongsTo<TaskColumn, $this> */
    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    /** @return BelongsTo<TaskRecurrence, $this> */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(TaskRecurrence::class, 'recurrence_id');
    }

    /** @return BelongsToMany<TaskLabel, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'kanban_task_label', 'task_id', 'label_id')->orderBy('name');
    }

    /** @return HasMany<TaskChecklistItem, $this> */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class, 'task_id')->orderBy('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
