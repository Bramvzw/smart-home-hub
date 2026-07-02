<?php

namespace Modules\Calendar\Services\Google;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Data\BusyTime;
use Modules\Calendar\Data\CalendarEvent;
use Modules\Calendar\Models\CalendarPlanItem;
use RuntimeException;

class GoogleCalendarClient
{
    private const EVENT_COLOUR = '#6aa6ff';

    public function __construct(private readonly GoogleCalendarTokenService $tokens) {}

    /**
     * Full events in the window — powers the agenda view.
     *
     * @return list<CalendarEvent>
     */
    public function events(CarbonPeriod $period): array
    {
        $token = $this->tokens->accessToken();

        if (! $token) {
            return [];
        }

        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate());
        $calendarId = (string) config('calendar.google.calendar_id', 'primary');
        $timezone = (string) config('app.timezone', 'UTC');

        $timeout = (int) config('calendar.request_timeout', 10);

        $response = Http::withToken($token)->acceptJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            ->retry(2, 200)
            ->get('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($calendarId).'/events', [
                'timeMin' => $start->toIso8601String(),
                'timeMax' => $end->toIso8601String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 250,
            ])->throw()->json();

        return collect(data_get($response, 'items', []))
            ->reject(fn (array $event): bool => ($event['status'] ?? '') === 'cancelled')
            ->map(function (array $event) use ($timezone): CalendarEvent {
                $allDay = isset($event['start']['date']);
                $startRaw = $event['start']['dateTime'] ?? $event['start']['date'];
                $endRaw = $event['end']['dateTime'] ?? $event['end']['date'] ?? $startRaw;

                return new CalendarEvent(
                    uid: (string) ($event['id'] ?? ''),
                    summary: trim((string) ($event['summary'] ?? '')) ?: '(no title)',
                    start: CarbonImmutable::parse($startRaw)->setTimezone($timezone),
                    end: CarbonImmutable::parse($endRaw)->setTimezone($timezone),
                    allDay: $allDay,
                    calendarLabel: 'Google Calendar',
                    calendarColor: self::EVENT_COLOUR,
                    location: isset($event['location']) ? ((string) $event['location'] ?: null) : null,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<BusyTime>
     */
    public function busyTimes(CarbonPeriod $period): array
    {
        $token = $this->tokens->accessToken();

        if (! $token) {
            return [];
        }

        $start = CarbonImmutable::instance($period->getStartDate());
        $end = CarbonImmutable::instance($period->getEndDate());
        $calendarId = (string) config('calendar.google.calendar_id', 'primary');
        $timeout = (int) config('calendar.request_timeout', 10);
        $response = Http::withToken($token)->acceptJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            ->post('https://www.googleapis.com/calendar/v3/freeBusy', [
                'timeMin' => $start->toIso8601String(),
                'timeMax' => $end->toIso8601String(),
                'items' => [['id' => $calendarId]],
            ])->throw()->json();

        return collect(data_get($response, "calendars.{$calendarId}.busy", []))
            ->map(fn (array $busy): BusyTime => new BusyTime(CarbonImmutable::parse($busy['start']), CarbonImmutable::parse($busy['end'])))
            ->values()
            ->all();
    }

    public function insertEvent(CalendarPlanItem $item): string
    {
        if (! $item->start_at || ! $item->end_at) {
            throw new RuntimeException('Cannot insert an unplaced planner item.');
        }

        $token = $this->tokens->accessToken();

        if (! $token) {
            throw new RuntimeException('Google Calendar is not connected.');
        }

        $calendarId = (string) config('calendar.google.calendar_id', 'primary');
        $timeout = (int) config('calendar.request_timeout', 10);
        // POST write: timeouts guard against hangs, but no retry — inserting twice would duplicate the event.
        $response = Http::withToken($token)->acceptJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", [
                'summary' => $item->title,
                'start' => ['dateTime' => $item->start_at->toIso8601String()],
                'end' => ['dateTime' => $item->end_at->toIso8601String()],
            ])->throw()->json();

        return (string) ($response['id'] ?? '');
    }
}
