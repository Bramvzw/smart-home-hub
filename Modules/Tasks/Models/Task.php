<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'notify_before_expiry' => 'boolean',
    ];

    protected static array $allowedTags = [
        'p', 'br', 'b', 'i', 'u', 'strong', 'em', 'ul', 'ol', 'li',
        'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
        'pre', 'code', 'span', 'div', 'sub', 'sup', 'hr', 'table',
        'thead', 'tbody', 'tr', 'th', 'td',
    ];

    public function setDescriptionAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['description'] = null;
            return;
        }

        $allowed = implode('', array_map(fn($tag) => "<{$tag}>", static::$allowedTags));
        $this->attributes['description'] = strip_tags($value, $allowed);
    }

    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isBefore(today());
    }

    public function isAboutToExpire(int $days = 2): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return !$this->isOverdue() && $this->due_date->isBefore(now()->addDays($days));
    }
}
