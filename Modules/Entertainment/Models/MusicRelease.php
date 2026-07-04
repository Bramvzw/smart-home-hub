<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class MusicRelease extends Model
{
    protected $fillable = ['spotify_id', 'artist', 'title', 'type', 'release_date', 'url', 'image_url', 'notified', 'saved_on_spotify'];

    protected function casts(): array
    {
        return [
            'release_date' => 'immutable_date',
            'notified' => 'boolean',
            'saved_on_spotify' => 'boolean',
        ];
    }
}
