<?php

namespace Modules\News\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsItemResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $feeds = collect((array) config('news.feeds', []))->keyBy('key');
        $feed = $feeds->get($this->feed_key, []);
        $timezone = (string) config('news.timezone', 'Europe/Amsterdam');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'summary' => $this->summary,
            'source' => (string) ($feed['label'] ?? $this->feed_key),
            'topic' => $this->topic,
            'published_at' => $this->published_at->setTimezone($timezone)->toIso8601String(),
            'is_read' => $this->is_read,
            'image_url' => $this->image_url,
            'matched_keywords' => array_values($this->matched_keywords ?? []),
        ];
    }
}
