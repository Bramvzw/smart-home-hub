<?php

namespace Modules\Recipes\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeRun extends Model
{
    protected $fillable = [
        'week_key',
        'stores_fetched',
        'stores_failed',
        'ai_unavailable',
        'generated_at',
    ];

    protected $casts = [
        'stores_fetched' => 'array',
        'stores_failed' => 'array',
        'ai_unavailable' => 'boolean',
        'generated_at' => 'immutable_datetime',
    ];
}
