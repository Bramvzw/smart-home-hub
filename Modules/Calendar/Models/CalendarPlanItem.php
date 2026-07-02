<?php

namespace Modules\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarPlanItem extends Model
{
    // The database table keeps its historical name (production data on the NAS).
    protected $table = 'planner_plan_items';

    protected $fillable = ['plan_id', 'recurrence_id', 'title', 'category', 'start_at', 'end_at', 'status', 'unplaceable_reason', 'google_event_id'];

    protected $casts = [
        'start_at' => 'immutable_datetime',
        'end_at' => 'immutable_datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CalendarPlan::class, 'plan_id');
    }
}
