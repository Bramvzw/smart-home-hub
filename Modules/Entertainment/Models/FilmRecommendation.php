<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class FilmRecommendation extends Model
{
    protected $fillable = ['tmdb_id', 'title', 'overview', 'availability', 'poster_url', 'why', 'score', 'dismissed', 'refreshed_at'];

    protected $casts = [
        'availability' => 'array',
        'score' => 'integer',
        'dismissed' => 'boolean',
        'refreshed_at' => 'immutable_datetime',
    ];
}
