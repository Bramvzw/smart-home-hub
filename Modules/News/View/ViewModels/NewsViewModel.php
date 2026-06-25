<?php

namespace Modules\News\View\ViewModels;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\News\Http\Resources\NewsItemResource;
use Modules\News\Models\NewsItem;

class NewsViewModel
{
    public function state(): array
    {
        $limit = max(1, (int) config('news.items_per_topic', 6));
        $itemsByTopic = NewsItem::query()->latestPerTopic($limit)->groupBy('topic');
        $topics = [];

        foreach ((array) config('news.topics', []) as $key => $label) {
            $items = $itemsByTopic->get((string) $key, new Collection)->values();

            $topics[] = [
                'key' => (string) $key,
                'label' => (string) $label,
                'unread' => NewsItem::query()->forTopic((string) $key)->unread()->count(),
                'items' => NewsItemResource::collection($items)->resolve(),
            ];
        }

        return [
            'topics' => $topics,
            'total_unread' => NewsItem::query()->unread()->count(),
            'last_refreshed_at' => $this->lastRefreshedAt(),
        ];
    }

    private function lastRefreshedAt(): ?string
    {
        $last = NewsItem::query()->max('updated_at');

        if ($last === null) {
            return null;
        }

        return CarbonImmutable::parse($last, 'UTC')
            ->setTimezone((string) config('news.timezone', 'Europe/Amsterdam'))
            ->toIso8601String();
    }
}
