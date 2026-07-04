<?php

namespace Modules\Calendar\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarToken extends Model
{
    protected $fillable = ['access_token', 'refresh_token', 'expires_at'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
