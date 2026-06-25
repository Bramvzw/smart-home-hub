<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class TasteProfile extends Model
{
    protected $fillable = ['favorite_titles', 'genres', 'notes'];

    protected $casts = [
        'favorite_titles' => 'array',
        'genres' => 'array',
    ];
}
