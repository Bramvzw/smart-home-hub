<?php

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Modules\News\Models\Builders\NewsItemBuilder;

class NewsItem extends Model
{
    protected $fillable = [
        'feed_key',
        'topic',
        'guid',
        'title',
        'url',
        'summary',
        'author',
        'image_url',
        'published_at',
        'is_read',
        'read_at',
        'notified',
        'matched_keywords',
    ];

    protected $casts = [
        'published_at' => 'immutable_datetime',
        'is_read' => 'boolean',
        'read_at' => 'immutable_datetime',
        'notified' => 'boolean',
        'matched_keywords' => 'array',
    ];

    public function newEloquentBuilder($query): NewsItemBuilder
    {
        /** @var BaseBuilder $query */
        return new NewsItemBuilder($query);
    }
}
