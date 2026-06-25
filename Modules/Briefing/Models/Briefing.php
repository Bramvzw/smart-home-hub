<?php

namespace Modules\Briefing\Models;

use Illuminate\Database\Eloquent\Model;

class Briefing extends Model
{
    protected $fillable = [
        'date',
        'body',
        'sections',
        'generated_at',
        'model',
        'is_fallback',
    ];

    protected $casts = [
        'date' => 'immutable_date',
        'sections' => 'array',
        'generated_at' => 'immutable_datetime',
        'is_fallback' => 'boolean',
    ];
}
