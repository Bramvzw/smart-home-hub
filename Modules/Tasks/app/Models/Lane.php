<?php

namespace Modules\Tasks\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lane extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position'];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
