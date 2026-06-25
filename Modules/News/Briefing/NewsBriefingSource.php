<?php

namespace Modules\News\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\News\Models\NewsItem;

class NewsBriefingSource implements BriefingSource
{
    public function key(): string
    {
        return 'news';
    }

    public function label(): string
    {
        return 'Nieuws';
    }

    public function priority(): int
    {
        return 40;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $limit = max(1, (int) config('briefing.news_items_per_topic', 2));
        $feeds = collect((array) config('news.feeds', []))->keyBy('key');
        $groups = [];
        $total = 0;

        foreach ((array) config('news.topics', []) as $topic => $label) {
            $items = NewsItem::query()
                ->forTopic((string) $topic)
                ->unread()
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();

            if ($items->isEmpty()) {
                continue;
            }

            $total += $items->count();
            $groups[] = [
                'topic' => (string) $topic,
                'label' => (string) $label,
                'items' => $items->map(fn (NewsItem $item): array => [
                    'title' => $item->title,
                    'summary' => $item->summary,
                    'source' => (string) (($feeds->get($item->feed_key)['label'] ?? null) ?: $item->feed_key),
                    'url' => $item->url,
                    'published_at' => $item->published_at?->toIso8601String(),
                    'matched_keywords' => $item->matched_keywords ?? [],
                ])->values()->all(),
            ];
        }

        if ($groups === []) {
            return null;
        }

        $topicLabels = array_map(static fn (array $group): string => $group['label'], $groups);

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: "{$total} ongelezen nieuwsitem".($total === 1 ? '' : 's').' uit '.implode(', ', $topicLabels),
            data: [
                'date' => $date->toDateString(),
                'groups' => $groups,
            ],
        );
    }
}
