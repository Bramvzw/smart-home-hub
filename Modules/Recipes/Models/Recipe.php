<?php

namespace Modules\Recipes\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'week_key',
        'title',
        'description',
        'servings',
        'time_minutes',
        'estimated_cost',
        'ingredients',
        'steps',
        'shopping_list',
        'model',
        'is_fallback',
    ];

    protected $casts = [
        'servings' => 'integer',
        'time_minutes' => 'integer',
        'estimated_cost' => 'decimal:2',
        'ingredients' => 'array',
        'steps' => 'array',
        'shopping_list' => 'array',
        'is_fallback' => 'boolean',
    ];
}
