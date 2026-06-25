<?php

namespace Modules\News\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NewsItemBuilder extends Builder
{
    public function unread(): static
    {
        return $this->where('is_read', false);
    }

    public function forTopic(string $topic): static
    {
        return $this->where('topic', $topic);
    }

    public function latestPerTopic(int $limit): Collection
    {
        return $this->orderByDesc('published_at')
            ->get()
            ->groupBy('topic')
            ->flatMap(fn (Collection $items): Collection => $items->take($limit))
            ->values();
    }
}
