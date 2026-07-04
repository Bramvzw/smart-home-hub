<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Modules\Tasks\Models\Builders\TaskRecurrenceBuilder;

class TaskRecurrence extends Model
{
    protected $fillable = [
        'board_id',
        'type',
        'title',
        'description',
        'cadence_type',
        'cadence_config',
        'notify',
        'active',
        'plannable',
        'duration_minutes',
        'next_due_on',
        'last_materialized_on',
    ];

    protected $casts = [
        'cadence_config' => 'array',
        'notify' => 'boolean',
        'active' => 'boolean',
        'plannable' => 'boolean',
        'duration_minutes' => 'integer',
        'next_due_on' => 'immutable_date',
        'last_materialized_on' => 'immutable_date',
    ];

    public function newEloquentBuilder($query): TaskRecurrenceBuilder
    {
        /** @var BaseBuilder $query */
        return new TaskRecurrenceBuilder($query);
    }

    /** @return BelongsTo<TaskBoard, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    /** @return HasMany<TaskRecurrenceCompletion, $this> */
    public function completions(): HasMany
    {
        return $this->hasMany(TaskRecurrenceCompletion::class, 'recurrence_id')->orderByDesc('completed_on');
    }

    /** @return HasMany<KanbanTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(KanbanTask::class, 'recurrence_id');
    }

    public function isHabit(): bool
    {
        return $this->type === 'habit';
    }

    public function isMaintenance(): bool
    {
        return $this->type === 'maintenance';
    }
}
