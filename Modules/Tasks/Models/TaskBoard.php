<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskBoard extends Model
{
    protected $fillable = [
        'name',
        'position',
    ];

    /** @return HasMany<TaskColumn, $this> */
    public function columns(): HasMany
    {
        return $this->hasMany(TaskColumn::class, 'board_id')->orderBy('position');
    }

    /** @return HasMany<TaskLabel, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(TaskLabel::class, 'board_id')->orderBy('name');
    }

    /** @return HasMany<KanbanTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(KanbanTask::class, 'board_id')->orderBy('position');
    }
}
