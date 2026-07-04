<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskColumn extends Model
{
    protected $fillable = [
        'board_id',
        'name',
        'position',
    ];

    /** @return BelongsTo<TaskBoard, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class, 'board_id');
    }

    /** @return HasMany<KanbanTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(KanbanTask::class, 'column_id')->orderBy('position');
    }

    public function isDoneColumn(): bool
    {
        return mb_strtolower(trim($this->name)) === 'done';
    }
}
