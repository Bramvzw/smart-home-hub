<?php

namespace Modules\News\Actions;

use App\Services\Ntfy\HubNotifier;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsItem;
use Throwable;

class CheckNewsKeywords
{
    public function __construct(
        private readonly HubNotifier $notifier,
    ) {}

    public function __invoke(): int
    {
        if (! $this->notifier->isConfigured()) {
            return 0;
        }

        $sent = 0;

        $items = NewsItem::query()
            ->where('notified', false)
            ->whereNotNull('matched_keywords')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (NewsItem $item): bool => count($item->matched_keywords ?? []) > 0);

        foreach ($items as $item) {
            try {
                $this->notifier->send(
                    title: 'News keyword match',
                    message: $this->message($item),
                );
            } catch (Throwable $exception) {
                Log::warning('News keyword notification failed', [
                    'news_item_id' => $item->id,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            $item->forceFill(['notified' => true])->save();
            $sent++;
        }

        return $sent;
    }

    private function message(NewsItem $item): string
    {
        $feeds = collect((array) config('news.feeds', []))->keyBy('key');
        $topics = (array) config('news.topics', []);
        $feed = $feeds->get($item->feed_key, []);
        $source = (string) ($feed['label'] ?? $item->feed_key);
        $topic = (string) ($topics[$item->topic] ?? $item->topic);
        $keywords = implode(', ', $item->matched_keywords ?? []);

        return implode("\n", [
            $item->title,
            "Source: {$source}",
            "Topic: {$topic}",
            "Matched: {$keywords}",
            $item->url,
        ]);
    }
}
