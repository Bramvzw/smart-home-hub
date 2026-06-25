<?php

namespace Modules\News\Actions;

use Carbon\CarbonImmutable;
use Modules\News\Models\NewsItem;

class MarkItemsRead
{
    /**
     * @param  list<int>  $ids
     */
    public function __invoke(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        return NewsItem::query()
            ->whereKey($ids)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => CarbonImmutable::now('UTC'),
            ]);
    }
}
