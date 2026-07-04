<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class FilmFeedback extends Model
{
    public $timestamps = false;

    protected $table = 'film_feedback';

    protected $fillable = ['tmdb_id', 'title', 'sentiment', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
