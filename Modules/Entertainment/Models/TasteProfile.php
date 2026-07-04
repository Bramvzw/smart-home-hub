<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class TasteProfile extends Model
{
    protected $fillable = ['favorite_titles', 'genres', 'notes'];

    protected function casts(): array
    {
        return [
            'favorite_titles' => 'array',
            'genres' => 'array',
        ];
    }
}
