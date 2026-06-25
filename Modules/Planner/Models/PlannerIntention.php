<?php

namespace Modules\Planner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlannerIntention extends Model
{
    protected $fillable = ['title', 'category', 'frequency_type', 'target_min', 'target_max', 'preferred_windows', 'duration_minutes', 'location', 'active'];

    protected $casts = [
        'preferred_windows' => 'array',
        'target_min' => 'integer',
        'target_max' => 'integer',
        'duration_minutes' => 'integer',
        'active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PlannerPlanItem::class, 'intention_id');
    }
}
