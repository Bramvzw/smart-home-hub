<?php

namespace Modules\Calendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarPlan extends Model
{
    // The database table keeps its historical name (production data on the NAS).
    protected $table = 'planner_plans';

    protected $fillable = ['week_key', 'summary', 'status', 'is_fallback', 'generated_at'];

    protected $casts = [
        'is_fallback' => 'boolean',
        'generated_at' => 'immutable_datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CalendarPlanItem::class, 'plan_id')->orderByRaw('start_at is null')->orderBy('start_at')->orderBy('id');
    }

    /**
     * The most recently generated plan, with its items eager-loaded.
     */
    public function scopeLatestGenerated(Builder $query): Builder
    {
        return $query->with('items')->latest('generated_at');
    }
}
