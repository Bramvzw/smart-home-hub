<?php

namespace Modules\Planner\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarToken extends Model
{
    protected $fillable = ['access_token', 'refresh_token', 'expires_at'];

    protected $casts = ['expires_at' => 'immutable_datetime'];
}
