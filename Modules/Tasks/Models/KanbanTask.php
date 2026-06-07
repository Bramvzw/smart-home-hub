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

    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'kanban_task_label', 'task_id', 'label_id')->orderBy('name');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class, 'task_id')->orderBy('position');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
