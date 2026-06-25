<?php

namespace Modules\Planner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlannerPlan extends Model
{
    protected $fillable = ['week_key', 'summary', 'status', 'is_fallback', 'generated_at'];

    protected $casts = [
        'is_fallback' => 'boolean',
        'generated_at' => 'immutable_datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PlannerPlanItem::class, 'plan_id')->orderByRaw('start_at is null')->orderBy('start_at')->orderBy('id');
    }
}
