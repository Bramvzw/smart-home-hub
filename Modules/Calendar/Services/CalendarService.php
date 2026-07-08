<?php

namespace Modules\Calendar\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Modules\Calendar\Data\CalendarEvent;
use Modules\Calendar\Data\CalendarFeed;
use Modules\Calendar\Services\Google\GoogleCalendarClient;
use Modules\Calendar\Services\Google\GoogleCalendarTokenService;
use Throwable;

class CalendarService
{
    private const CACHE_PREFIX = 'calendar:events';

    public function __construct(
        private readonly GoogleCalendarClient $client,
        private readonly GoogleCalendarTokenService $tokens,
    ) {}

    public function connected(): bool
    {
        return $this->tokens->connected();
    }

    /**
     * Build the event feed for the next $days, starting from $now, sourced from
     * Google Calendar. Successful fetches are cached briefly; when a refresh
     * fails the last known-good events are served and the feed is flagged stale.
     */
    public function feed(?int $days = null, ?CarbonImmutable $now = null): CalendarFeed
    {
        if (! $this->connected()) {
            return new CalendarFeed([], stale: false, failed: false);
        }

        $timezone = (string) config('calendar.timezone', 'Europe/Amsterdam');
        $days ??= (int) config('calendar.window_days', 7);
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        $rangeStart = $now->startOfDay();
        $rangeEnd = $now->addDays($days)->endOfDay();
        $ttl = (int) config('calendar.cache_ttl', 300);

        $cacheKey = self::CACHE_PREFIX.":{$rangeStart->toDateString()}:{$days}";
        $lastGoodKey = $cacheKey.':last-good';

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return new CalendarFeed($this->sort($cached));
        }

        try {
            $events = $this->client->events(CarbonPeriod::create($rangeStart, $rangeEnd));

            Cache::put($cacheKey, $events, $ttl);
            Cache::forever($lastGoodKey, $events);

            return new CalendarFeed($this->sort($events));
        } catch (Throwable) {
            $lastGood = Cache::get($lastGoodKey);

            return new CalendarFeed(
                $this->sort(is_array($lastGood) ? $lastGood : []),
                stale: true,
                failed: true,
                staleFeeds: ['Google Calendar'],
            );
        }
    }

    /**
     * @param  list<CalendarEvent>  $events
     * @return list<CalendarEvent>
     */
    private function sort(array $events): array
    {
        usort($events, static fn (CalendarEvent $a, CalendarEvent $b): int => $a->start <=> $b->start);

        return array_values($events);
    }
}
