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
     * Full events in the window — powers the agenda view. Events are pulled from
     * every calendar the user has selected in Google (not just the primary one),
     * each carrying its own label and colour.
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
        $timezone = (string) config('calendar.timezone', 'Europe/Amsterdam');
        $timeout = (int) config('calendar.request_timeout', 10);

        $events = [];
        foreach ($this->calendars($token) as $calendar) {
            foreach ($this->fetchEvents($token, $calendar, $start, $end, $timezone, $timeout) as $event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Fetch and map the events of a single calendar in the window.
     *
     * @param  array{id: string, label: string, color: string}  $calendar
     * @return list<CalendarEvent>
     */
    private function fetchEvents(string $token, array $calendar, CarbonImmutable $start, CarbonImmutable $end, string $timezone, int $timeout): array
    {
        $response = Http::withToken($token)->acceptJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            ->retry(2, 200)
            ->get('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($calendar['id']).'/events', [
                'timeMin' => $start->toIso8601String(),
                'timeMax' => $end->toIso8601String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 250,
            ])->throw()->json();

        return array_values(collect(data_get($response, 'items', []))
            ->reject(fn (array $event): bool => ($event['status'] ?? '') === 'cancelled')
            ->map(function (array $event) use ($timezone, $calendar): CalendarEvent {
                $allDay = isset($event['start']['date']);
                $startRaw = $event['start']['dateTime'] ?? $event['start']['date'];
                $endRaw = $event['end']['dateTime'] ?? $event['end']['date'] ?? $startRaw;

                return new CalendarEvent(
                    uid: (string) ($event['id'] ?? ''),
                    summary: trim((string) ($event['summary'] ?? '')) ?: '(no title)',
                    start: CarbonImmutable::parse($startRaw)->setTimezone($timezone),
                    end: CarbonImmutable::parse($endRaw)->setTimezone($timezone),
                    allDay: $allDay,
                    calendarLabel: $calendar['label'],
                    calendarColor: $calendar['color'],
                    location: isset($event['location']) ? ((string) $event['location'] ?: null) : null,
                );
            })
            ->all());
    }

    /**
     * The calendars to read from: every calendar the user has ticked in Google,
     * each with its own label and colour. Falls back to the single configured
     * calendar when the list cannot be fetched, so a blip never blanks the agenda.
     *
     * @return list<array{id: string, label: string, color: string}>
     */
    private function calendars(string $token): array
    {
        $timeout = (int) config('calendar.request_timeout', 10);

        try {
            $response = Http::withToken($token)->acceptJson()
                ->timeout($timeout)
                ->connectTimeout($timeout)
                ->retry(2, 200)
                ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList')
                ->throw()->json();

            $calendars = array_values(collect(data_get($response, 'items', []))
                ->filter(fn (array $c): bool => ($c['selected'] ?? false) === true)
                ->map(fn (array $c): array => [
                    'id' => (string) ($c['id'] ?? ''),
                    'label' => trim((string) ($c['summaryOverride'] ?? $c['summary'] ?? '')) ?: 'Google Calendar',
                    'color' => (string) ($c['backgroundColor'] ?? self::EVENT_COLOUR),
                ])
                ->filter(fn (array $c): bool => $c['id'] !== '')
                ->all());

            if ($calendars !== []) {
                return $calendars;
            }
        } catch (\Throwable) {
            // Fall through to the single configured calendar below.
        }

        return [[
            'id' => (string) config('calendar.google.calendar_id', 'primary'),
            'label' => 'Google Calendar',
            'color' => self::EVENT_COLOUR,
        ]];
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
        $timeout = (int) config('calendar.request_timeout', 10);

        // Consider every selected calendar so the planner does not place goals on
        // top of commitments living outside the primary calendar.
        $items = array_map(
            static fn (array $calendar): array => ['id' => $calendar['id']],
            $this->calendars($token),
        );

        $response = Http::withToken($token)->acceptJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            ->post('https://www.googleapis.com/calendar/v3/freeBusy', [
                'timeMin' => $start->toIso8601String(),
                'timeMax' => $end->toIso8601String(),
                'items' => $items,
            ])->throw()->json();

        // Keys are raw calendar ids (with dots), so index directly instead of
        // via data_get() dot-notation.
        $busy = [];
        foreach ((array) ($response['calendars'] ?? []) as $calendar) {
            foreach ((array) ($calendar['busy'] ?? []) as $slot) {
                $busy[] = new BusyTime(
                    CarbonImmutable::parse($slot['start']),
                    CarbonImmutable::parse($slot['end']),
                );
            }
        }

        return $busy;
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
