<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrenceCompletion extends Model
{
    protected $fillable = [
        'recurrence_id',
        'completed_on',
        'period_key',
    ];

    protected $casts = [
        'completed_on' => 'immutable_date',
    ];

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(TaskRecurrence::class, 'recurrence_id');
    }
}
