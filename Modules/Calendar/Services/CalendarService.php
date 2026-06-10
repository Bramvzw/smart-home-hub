<?php

namespace Modules\Calendar\Services;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Data\CalendarEvent;
use Modules\Calendar\Data\CalendarFeed;
use RuntimeException;
use Sabre\VObject\Reader;
use Throwable;

class CalendarService
{
    private const CACHE_PREFIX = 'calendar:feed';

    /**
     * Build the event feed for the next $days, starting from $now.
     *
     * A successful fetch is cached for the configured TTL. When the cache has
     * expired and a refresh fails, the last known-good result is returned with
     * a stale flag instead of bubbling the error to the page.
     */
    public function feed(?int $days = null, ?CarbonImmutable $now = null): CalendarFeed
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $days = $days ?? (int) config('calendar.window_days', 7);
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        $rangeStart = $now->startOfDay();
        $rangeEnd = $now->addDays($days)->endOfDay();

        $cacheKey = self::CACHE_PREFIX.":{$days}";
        $lastGoodKey = $cacheKey.':last-good';

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return new CalendarFeed($cached, stale: false, failed: false);
        }

        try {
            $events = $this->fetchAndParse($rangeStart, $rangeEnd, $timezone);

            Cache::put($cacheKey, $events, (int) config('calendar.cache_ttl', 900));
            Cache::forever($lastGoodKey, $events);

            return new CalendarFeed($events, stale: false, failed: false);
        } catch (Throwable $e) {
            $lastGood = Cache::get($lastGoodKey);

            return new CalendarFeed(
                is_array($lastGood) ? $lastGood : [],
                stale: true,
                failed: true,
            );
        }
    }

    /**
     * @return list<CalendarEvent>
     */
    private function fetchAndParse(CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, string $timezone): array
    {
        $urls = (array) config('calendar.ics_urls', []);
        $timeout = (int) config('calendar.request_timeout', 10);

        $events = [];

        foreach ($urls as $url) {
            $response = Http::timeout($timeout)->get($url);

            // Never include the (secret) URL in the message — only the status.
            if (! $response->successful()) {
                throw new RuntimeException("Calendar feed responded with HTTP {$response->status()}.");
            }

            foreach ($this->parse($response->body(), $rangeStart, $rangeEnd, $timezone) as $event) {
                $events[] = $event;
            }
        }

        usort($events, static fn (CalendarEvent $a, CalendarEvent $b) => $a->start <=> $b->start);

        return $events;
    }

    /**
     * @return list<CalendarEvent>
     */
    private function parse(string $ics, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, string $timezone): array
    {
        $tz = new DateTimeZone($timezone);

        $calendar = Reader::read($ics, Reader::OPTION_FORGIVING);

        // Expand recurring events into concrete instances within the window.
        $calendar = $calendar->expand(
            new DateTime($rangeStart->toDateTimeString(), $tz),
            new DateTime($rangeEnd->toDateTimeString(), $tz),
            $tz,
        );

        $events = [];

        foreach ($calendar->select('VEVENT') as $vevent) {
            $dtStart = $vevent->DTSTART;
            $allDay = ! $dtStart->hasTime();

            $start = CarbonImmutable::instance($dtStart->getDateTime($tz))->setTimezone($timezone);
            $end = isset($vevent->DTEND)
                ? CarbonImmutable::instance($vevent->DTEND->getDateTime($tz))->setTimezone($timezone)
                : $start->addHour();

            $events[] = new CalendarEvent(
                uid: (string) ($vevent->UID ?? ''),
                summary: trim((string) ($vevent->SUMMARY ?? '')) ?: '(geen titel)',
                start: $start,
                end: $end,
                allDay: $allDay,
                location: isset($vevent->LOCATION) ? ((string) $vevent->LOCATION ?: null) : null,
            );
        }

        return $events;
    }
}
