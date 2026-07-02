<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value store for UI-editable configuration. Values are stored as JSON so a
 * setting can hold any scalar or structured payload. Reads happen through
 * {@see \App\Services\Settings\SettingsStore}, which overlays these values on
 * top of config()/env defaults.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
