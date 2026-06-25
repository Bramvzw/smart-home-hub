<?php

namespace Modules\News\Actions;

use Carbon\CarbonImmutable;
use Modules\News\Models\NewsItem;

class MarkAllRead
{
    public function __invoke(?string $topic = null): int
    {
        $query = NewsItem::query()->unread();

        if ($topic !== null) {
            $query->forTopic($topic);
        }

        return $query->update([
            'is_read' => true,
            'read_at' => CarbonImmutable::now('UTC'),
        ]);
    }
}
