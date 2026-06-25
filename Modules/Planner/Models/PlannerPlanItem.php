<?php

namespace Modules\Planner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerPlanItem extends Model
{
    protected $fillable = ['plan_id', 'intention_id', 'title', 'start_at', 'end_at', 'status', 'unplaceable_reason', 'google_event_id'];

    protected $casts = [
        'start_at' => 'immutable_datetime',
        'end_at' => 'immutable_datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlannerPlan::class, 'plan_id');
    }

    public function intention(): BelongsTo
    {
        return $this->belongsTo(PlannerIntention::class, 'intention_id');
    }
}
