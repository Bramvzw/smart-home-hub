<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $fillable = ['source', 'external_id', 'artist', 'title', 'venue', 'city', 'date', 'url', 'relevance', 'notified'];

    protected $casts = [
        'date' => 'immutable_datetime',
        'notified' => 'boolean',
    ];
}
