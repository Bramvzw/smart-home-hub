<?php

namespace Modules\Calendar\Services;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Data\CalendarEvent;
use Modules\Calendar\Data\CalendarFeed;
use Modules\Calendar\Data\CalendarSource;
use RuntimeException;
use Sabre\VObject\Reader;
use Throwable;

class CalendarService
{
    private const CACHE_PREFIX = 'calendar:feed';

    /**
     * Build the merged event feed for the next $days, starting from $now.
     *
     * Each configured feed is fetched and cached independently. A successful
     * fetch is cached for the configured TTL. When one feed's refresh fails its
     * last known-good events are served and that feed is flagged stale — the
     * other feeds are unaffected (per-feed degradation).
     */
    public function feed(?int $days = null, ?CarbonImmutable $now = null): CalendarFeed
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $days = $days ?? (int) config('calendar.window_days', 7);
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        $rangeStart = $now->startOfDay();
        $rangeEnd = $now->addDays($days)->endOfDay();
        $ttl = (int) config('calendar.cache_ttl', 900);

        $events = [];
        $staleFeeds = [];

        foreach ($this->sources() as $source) {
            $cacheKey = self::CACHE_PREFIX.':'.md5($source->url).":{$days}";
            $lastGoodKey = $cacheKey.':last-good';

            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $events = array_merge($events, $cached);

                continue;
            }

            try {
                $fetched = $this->fetchAndParse($source, $rangeStart, $rangeEnd, $timezone);

                Cache::put($cacheKey, $fetched, $ttl);
                Cache::forever($lastGoodKey, $fetched);

                $events = array_merge($events, $fetched);
            } catch (Throwable $e) {
                $staleFeeds[] = $source->label;

                $lastGood = Cache::get($lastGoodKey);
                $events = array_merge($events, is_array($lastGood) ? $lastGood : []);
            }
        }

        usort($events, static fn (CalendarEvent $a, CalendarEvent $b) => $a->start <=> $b->start);

        return new CalendarFeed(
            $events,
            stale: $staleFeeds !== [],
            failed: $staleFeeds !== [],
            staleFeeds: array_values(array_unique($staleFeeds)),
        );
    }

    /**
     * @return list<CalendarSource>
     */
    private function sources(): array
    {
        $sources = [];

        foreach ((array) config('calendar.feeds', []) as $feed) {
            $url = (string) ($feed['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $sources[] = new CalendarSource(
                label: (string) ($feed['label'] ?? 'Agenda'),
                color: (string) ($feed['color'] ?? '#f2ad66'),
                url: $url,
            );
        }

        return $sources;
    }

    /**
     * @return list<CalendarEvent>
     */
    private function fetchAndParse(CalendarSource $source, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, string $timezone): array
    {
        $response = Http::timeout((int) config('calendar.request_timeout', 10))->get($source->url);

        // Never include the (secret) URL in the message — only the status.
        if (! $response->successful()) {
            throw new RuntimeException("Calendar feed responded with HTTP {$response->status()}.");
        }

        return $this->parse($response->body(), $source, $rangeStart, $rangeEnd, $timezone);
    }

    /**
     * @return list<CalendarEvent>
     */
    private function parse(string $ics, CalendarSource $source, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, string $timezone): array
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
                summary: trim((string) ($vevent->SUMMARY ?? '')) ?: '(no title)',
                start: $start,
                end: $end,
                allDay: $allDay,
                calendarLabel: $source->label,
                calendarColor: $source->color,
                location: isset($vevent->LOCATION) ? ((string) $vevent->LOCATION ?: null) : null,
            );
        }

        return $events;
    }
}
