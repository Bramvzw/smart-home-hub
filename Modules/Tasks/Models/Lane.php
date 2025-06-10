<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lane extends Model
{
    protected $fillable = [
        'name',
        'order',
    ];

    /**
     * Get the tasks for the lane.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }
}
