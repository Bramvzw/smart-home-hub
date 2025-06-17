<?php

namespace Modules\Tasks\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'lane_id',
        'title',
        'description',
        'label',
        'priority',
        'due_date',
        'notify_before_expiry',
    ];

    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class);
    }
}
